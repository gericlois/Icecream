<?php

function format_currency($amount) {
    return '₱' . number_format((float)$amount, 2);
}

function generate_order_number($conn) {
    $today = date('Ymd');
    $prefix = "ORD-{$today}-";
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE order_number LIKE ?");
    $like = $prefix . '%';
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
    return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

function get_setting($conn, $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $val = $result->num_rows > 0 ? $result->fetch_assoc()['setting_value'] : null;
    $stmt->close();
    return $val;
}

function flash_message($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $type = htmlspecialchars($flash['type']);
        $msg = htmlspecialchars($flash['message']);
        echo '<div class="alert alert-' . $type . ' alert-dismissible text-white fade show" role="alert" data-flash-type="' . $type . '">';
        echo '<span class="text-sm">' . $msg . '</span>';
        echo '<button type="button" class="btn-close text-lg py-3 opacity-10" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function get_status_badge($status) {
    $badges = [
        'pending' => 'bg-gradient-warning',
        'approved' => 'bg-gradient-info',
        'for_delivery' => 'bg-gradient-primary',
        'delivered' => 'bg-gradient-success',
        'cancelled' => 'bg-gradient-danger',
    ];
    $class = $badges[$status] ?? 'bg-gradient-secondary';
    $label = ucfirst(str_replace('_', ' ', $status));
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

function get_cart_count() {
    return count($_SESSION['cart'] ?? []);
}

function calculate_subsidy($conn, $user_id, $month, $year) {
    // Get total delivered orders for this user/month
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM orders
        WHERE user_id = ? AND status = 'delivered'
          AND MONTH(delivered_at) = ? AND YEAR(delivered_at) = ?
    ");
    $stmt->bind_param("iii", $user_id, $month, $year);
    $stmt->execute();
    $total = (float)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Get per-package subsidy rate and minimum from user's package
    $stmt = $conn->prepare("
        SELECT p.subsidy_rate, p.subsidy_min_orders, p.name as package_name
        FROM users u
        JOIN packages p ON u.package_info = p.slug
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $pkg = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $factor = (float)get_setting($conn, 'subsidy_factor') ?: 0.88;

    if (!$pkg || $pkg['subsidy_rate'] <= 0) {
        // User has no package or package has no subsidy
        return ['eligible' => false, 'total' => $total, 'subsidy' => 0, 'min' => 0, 'rate' => 0, 'factor' => $factor, 'package' => null];
    }

    $rate = (float)$pkg['subsidy_rate'];
    $min = (float)$pkg['subsidy_min_orders'];
    $package_name = $pkg['package_name'];

    if ($total < $min) {
        return ['eligible' => false, 'total' => $total, 'subsidy' => 0, 'min' => $min, 'rate' => $rate, 'factor' => $factor, 'package' => $package_name];
    }
    $subsidy = round($total * $factor * $rate, 2);
    return ['eligible' => true, 'total' => $total, 'subsidy' => $subsidy, 'min' => $min, 'rate' => $rate, 'factor' => $factor, 'package' => $package_name];
}

function calculate_fda($conn, $user_id, $month, $year) {
    // Get user's package freezer display allowance and membership date
    $stmt = $conn->prepare("
        SELECT p.freezer_display_allowance, p.name as package_name, u.created_at as member_since
        FROM users u
        JOIN packages p ON u.package_info = p.slug
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $pkg = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pkg || $pkg['freezer_display_allowance'] <= 0) {
        return ['eligible' => false, 'allowance' => 0, 'package' => $pkg['package_name'] ?? null, 'member_days' => 0, 'min_days' => 20];
    }

    $allowance = (float)$pkg['freezer_display_allowance'];
    $package_name = $pkg['package_name'];

    // Check membership duration (at least 20 days)
    $member_since = new DateTime($pkg['member_since'], new DateTimeZone('Asia/Manila'));
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $member_days = (int)$now->diff($member_since)->days;

    $eligible = $member_days >= 20;

    return [
        'eligible' => $eligible,
        'allowance' => $allowance,
        'package' => $package_name,
        'member_days' => $member_days,
        'min_days' => 20,
    ];
}

function credit_efunds($conn, $user_id, $amount, $type, $ref_type, $ref_id, $description, $processed_by = null) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT efunds_balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $balance = (float)$stmt->get_result()->fetch_assoc()['efunds_balance'];
        $stmt->close();

        $new_balance = $balance + $amount;
        $stmt = $conn->prepare("UPDATE users SET efunds_balance = ? WHERE id = ?");
        $stmt->bind_param("di", $new_balance, $user_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO efunds_transactions (user_id, type, amount, balance_after, reference_type, reference_id, description, processed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isddisis", $user_id, $type, $amount, $new_balance, $ref_type, $ref_id, $description, $processed_by);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function calculate_agent_subsidy($conn, $agent_id, $month, $year) {
    // Get all active retailers tagged to this agent with their package info
    $stmt = $conn->prepare("
        SELECT u.id, u.full_name, u.package_info, p.name as package_name, p.subsidy_rate
        FROM users u
        LEFT JOIN packages p ON u.package_info = p.slug
        WHERE u.agent_id = ? AND u.role = 'retailer' AND u.status = 'active'
        ORDER BY u.full_name
    ");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $retailers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $grand_total = 0;
    $total_subsidy = 0;
    $breakdown = [];

    foreach ($retailers as $r) {
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM orders
            WHERE user_id = ? AND status = 'delivered'
              AND MONTH(delivered_at) = ? AND YEAR(delivered_at) = ?
        ");
        $stmt->bind_param("iii", $r['id'], $month, $year);
        $stmt->execute();
        $retailer_total = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $rate = (float)($r['subsidy_rate'] ?? 0);
        $retailer_subsidy = round($retailer_total * $rate, 2);
        $grand_total += $retailer_total;
        $total_subsidy += $retailer_subsidy;

        $breakdown[] = [
            'id' => $r['id'],
            'name' => $r['full_name'],
            'package' => $r['package_name'] ?? 'No Package',
            'rate' => $rate,
            'orders_total' => $retailer_total,
            'subsidy' => $retailer_subsidy,
        ];
    }

    // Dynamic minimum: P8,000 per active retailer
    $active_count = count($retailers);
    $min = $active_count * 8000;
    $eligible = $grand_total >= $min && $min > 0;

    return [
        'eligible' => $eligible,
        'grand_total' => $grand_total,
        'total_subsidy' => $eligible ? $total_subsidy : 0,
        'min' => $min,
        'active_retailers' => $active_count,
        'breakdown' => $breakdown,
    ];
}

function debit_efunds($conn, $user_id, $amount, $ref_type, $ref_id, $description, $processed_by = null) {
    return credit_efunds($conn, $user_id, -$amount, 'payment', $ref_type, $ref_id, $description, $processed_by);
}

function get_delivery_window($order_time = null) {
    $now = $order_time ? new DateTime($order_time, new DateTimeZone('Asia/Manila')) : new DateTime('now', new DateTimeZone('Asia/Manila'));
    $day = (int)$now->format('N'); // 1=Mon, 2=Tue, ..., 7=Sun
    $hour = (int)$now->format('G'); // 0-23

    // Determine which window we're in:
    // Before Tuesday 5PM → deliver Thursday & Friday (same week)
    // Tuesday 5PM to Friday 5PM → deliver Monday & Tuesday (next week)
    // After Friday 5PM → deliver Thursday & Friday (next week)

    $ref = clone $now;

    if ($day < 2 || ($day === 2 && $hour < 17)) {
        // Sunday(7→already past), Monday, or Tuesday before 5PM
        // Delivery: this week's Thursday & Friday
        // Cutoff: this Tuesday 5PM
        $cutoff = clone $now;
        $cutoff->modify('tuesday this week');
        $cutoff->setTime(17, 0, 0);

        $start = clone $now;
        $start->modify('thursday this week');
        $end = clone $now;
        $end->modify('friday this week');
    } elseif (($day === 2 && $hour >= 17) || ($day >= 3 && $day <= 4) || ($day === 5 && $hour < 17)) {
        // Tuesday after 5PM, Wednesday, Thursday, or Friday before 5PM
        // Delivery: next Monday & Tuesday
        // Cutoff: this Friday 5PM
        $cutoff = clone $now;
        $cutoff->modify('friday this week');
        $cutoff->setTime(17, 0, 0);

        $start = clone $now;
        $start->modify('next monday');
        $end = clone $now;
        $end->modify('next tuesday');
    } else {
        // Friday after 5PM, Saturday, or Sunday
        // Delivery: next week's Thursday & Friday
        // Cutoff: next Tuesday 5PM
        $cutoff = clone $now;
        $cutoff->modify('next tuesday');
        $cutoff->setTime(17, 0, 0);

        $start = clone $now;
        $start->modify('next thursday');
        $end = clone $now;
        $end->modify('next friday');
    }

    $start->setTime(0, 0, 0);
    $end->setTime(0, 0, 0);

    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d'),
        'cutoff' => $cutoff->format('Y-m-d H:i:s'),
        'cutoff_label' => $cutoff->format('l, M d \\a\\t g:i A'),
        'label' => $start->format('D') . '-' . $end->format('D') . ', ' . $start->format('M d') . '-' . $end->format('d'),
        'start_label' => $start->format('M d, Y'),
        'end_label' => $end->format('M d, Y'),
    ];
}

function time_ago($datetime) {
    $tz = new DateTimeZone('Asia/Manila');
    $now = new DateTime('now', $tz);
    $ago = new DateTime($datetime, $tz);
    $diff = $now->diff($ago);

    if ($diff->days > 0) return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min ago';
    return 'Just now';
}
