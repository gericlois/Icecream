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
        echo '<div class="alert alert-' . $type . ' alert-dismissible text-white fade show" role="alert">';
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

    $min = (float)get_setting($conn, 'subsidy_min_orders') ?: 6000;
    $factor = (float)get_setting($conn, 'subsidy_factor') ?: 0.88;
    $rate = (float)get_setting($conn, 'subsidy_rate') ?: 0.05;

    if ($total < $min) {
        return ['eligible' => false, 'total' => $total, 'subsidy' => 0, 'min' => $min];
    }
    $subsidy = round($total * $factor * $rate, 2);
    return ['eligible' => true, 'total' => $total, 'subsidy' => $subsidy, 'min' => $min];
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

function debit_efunds($conn, $user_id, $amount, $ref_type, $ref_id, $description, $processed_by = null) {
    return credit_efunds($conn, $user_id, -$amount, 'payment', $ref_type, $ref_id, $description, $processed_by);
}

function time_ago($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min ago';
    return 'Just now';
}
