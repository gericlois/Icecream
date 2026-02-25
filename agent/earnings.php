<?php
$page_title = 'Earnings Tracker';
$active_page = 'earnings';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['subdealer']);

$uid = current_user_id();
$month = (int)date('m');
$year = (int)date('Y');

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $w_amount = (float)($_POST['withdraw_amount'] ?? 0);
    $w_method = $_POST['withdraw_method'] ?? '';
    $w_gcash_name = trim($_POST['gcash_name'] ?? '');
    $w_gcash_number = trim($_POST['gcash_number'] ?? '');

    if ($w_amount < 500) {
        flash_message('danger', 'Minimum withdrawal amount is ₱500.00.');
    } elseif (!in_array($w_method, ['gcash', 'check'])) {
        flash_message('danger', 'Please select a withdrawal method.');
    } elseif ($w_method === 'gcash' && (empty($w_gcash_name) || empty($w_gcash_number))) {
        flash_message('danger', 'Please provide GCash account name and number.');
    } else {
        $bal = (float)$conn->query("SELECT efunds_balance FROM users WHERE id = $uid")->fetch_assoc()['efunds_balance'];
        if ($w_amount > $bal) {
            flash_message('danger', 'Insufficient e-funds balance. You have ' . format_currency($bal) . '.');
        } else {
            $stmt = $conn->prepare("INSERT INTO withdrawal_requests (user_id, amount, method, gcash_name, gcash_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idsss", $uid, $w_amount, $w_method, $w_gcash_name, $w_gcash_number);
            $stmt->execute();
            $stmt->close();
            flash_message('success', 'Withdrawal request of ' . format_currency($w_amount) . ' submitted! Processing cut-off: Wednesday 5:00 PM. Releasing: Every Monday.');
        }
    }
    redirect(BASE_URL . '/agent/earnings.php');
}

// Get subsidy data (reuse existing function)
$subsidy = calculate_agent_subsidy($conn, $uid, $month, $year);
$min = $subsidy['min'];
$progress_pct = $min > 0 ? min(100, round(($subsidy['grand_total'] / $min) * 100, 1)) : 0;
$remaining = max(0, $min - $subsidy['grand_total']);

// Weekly breakdown for current month
$weeks = [];
$first_day = new DateTime("$year-$month-01");
$last_day = (clone $first_day)->modify('last day of this month');
$week_start = clone $first_day;
$week_num = 1;

while ($week_start <= $last_day) {
    $week_end = (clone $week_start)->modify('+6 days');
    if ($week_end > $last_day) $week_end = clone $last_day;

    $ws = $week_start->format('Y-m-d');
    $we = $week_end->format('Y-m-d') . ' 23:59:59';

    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(o.id) as cnt
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE u.agent_id = ? AND u.role = 'retailer'
          AND o.status = 'delivered'
          AND o.delivered_at BETWEEN ? AND ?
    ");
    $stmt->bind_param("iss", $uid, $ws, $we);
    $stmt->execute();
    $w = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $weeks[] = [
        'label' => 'Week ' . $week_num . ' (' . $week_start->format('M d') . ' - ' . $week_end->format('d') . ')',
        'total' => (float)$w['total'],
        'orders' => (int)$w['cnt'],
    ];

    $week_start->modify('+7 days');
    $week_num++;
}

// Past 6 months earnings history
$history = $conn->query("
    SELECT * FROM electric_subsidy
    WHERE user_id = $uid AND converted = 1
    ORDER BY year DESC, month DESC
    LIMIT 6
");
$total_earned = 0;
$history_rows = [];
while ($h = $history->fetch_assoc()) {
    $total_earned += (float)$h['subsidy_amount'];
    $history_rows[] = $h;
}

// Approved orders (not yet delivered but approved) for this month — exclude pending
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(o.total_amount), 0) as total
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE u.agent_id = ? AND u.role = 'retailer'
      AND o.status IN ('approved','for_delivery')
      AND MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
");
$stmt->bind_param("iii", $uid, $month, $year);
$stmt->execute();
$approved_pending_total = (float)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$projected_total = $subsidy['grand_total'] + $approved_pending_total;
$projected_pct = $min > 0 ? min(100, round(($projected_total / $min) * 100, 1)) : 0;

// Get e-funds balance for withdrawal
$user_balance = (float)$conn->query("SELECT efunds_balance FROM users WHERE id = $uid")->fetch_assoc()['efunds_balance'];

// Recent withdrawal requests
$withdrawal_requests = $conn->query("SELECT * FROM withdrawal_requests WHERE user_id = $uid ORDER BY created_at DESC LIMIT 5");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Gross Retail Over-Ride Progress Card -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h6><i class="material-icons align-middle text-warning">trending_up</i> Gross Retail Over-Ride Progress - <?php echo date('F Y'); ?></h6>
                            </div>
                            <div class="col-4 text-end">
                                <?php if ($subsidy['eligible']): ?>
                                <span class="badge bg-gradient-success px-3 py-2">QUALIFIED</span>
                                <?php else: ?>
                                <span class="badge bg-gradient-warning px-3 py-2"><?php echo $progress_pct; ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Main progress bar -->
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-sm font-weight-bold"><?php echo format_currency($subsidy['grand_total']); ?> delivered</span>
                            <span class="text-sm text-secondary">Minimum Re-Order: <?php echo format_currency($min); ?> (<?php echo $subsidy['active_retailers']; ?> retailers x ₱8,000)</span>
                        </div>
                        <div class="progress mb-3" style="height: 20px; border-radius: 10px;">
                            <div class="progress-bar bg-gradient-<?php echo $subsidy['eligible'] ? 'success' : ($progress_pct >= 50 ? 'info' : 'warning'); ?>"
                                 role="progressbar"
                                 style="width: <?php echo $progress_pct; ?>%; border-radius: 10px;"
                                 aria-valuenow="<?php echo $progress_pct; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo $progress_pct; ?>%
                            </div>
                        </div>

                        <?php if (!$subsidy['eligible']): ?>
                        <!-- Projected with approved orders -->
                        <?php if ($approved_pending_total > 0): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-xs text-muted">Projected (incl. <?php echo format_currency($approved_pending_total); ?> approved/in transit)</span>
                            <span class="text-xs text-muted"><?php echo $projected_pct; ?>%</span>
                        </div>
                        <div class="progress mb-3" style="height: 8px; border-radius: 5px;">
                            <div class="progress-bar bg-gradient-info opacity-5" style="width: <?php echo $projected_pct; ?>%; border-radius: 5px;"></div>
                        </div>
                        <?php endif; ?>

                        <div class="alert alert-light text-sm mb-0 border">
                            <i class="material-icons align-middle text-warning" style="font-size:18px;">info</i>
                            <strong><?php echo format_currency($remaining); ?></strong> more in delivered retailer orders needed to qualify for Gross Retail Over-Ride this month.
                        </div>
                        <?php else: ?>
                        <div class="alert alert-light text-sm mb-0 border">
                            <i class="material-icons align-middle text-success" style="font-size:18px;">check_circle</i>
                            Minimum re-order reached! Your Gross Retail Over-Ride of <strong><?php echo format_currency($subsidy['total_subsidy']); ?></strong> is ready.
                            <a href="<?php echo BASE_URL; ?>/agent/subsidy.php" class="text-primary font-weight-bold">Convert to E-Funds &rarr;</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon icon-shape bg-gradient-success shadow-success text-center border-radius-md me-3">
                                <i class="material-icons opacity-10 text-white">bolt</i>
                            </div>
                            <div>
                                <p class="text-xs text-secondary mb-0">Potential Gross Retail Over-Ride</p>
                                <h5 class="mb-0"><?php echo format_currency($subsidy['eligible'] ? $subsidy['total_subsidy'] : 0); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon icon-shape bg-gradient-info shadow-info text-center border-radius-md me-3">
                                <i class="material-icons opacity-10 text-white">store</i>
                            </div>
                            <div>
                                <p class="text-xs text-secondary mb-0">Active Retailers</p>
                                <h5 class="mb-0"><?php echo count($subsidy['breakdown']); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon icon-shape bg-gradient-primary shadow-primary text-center border-radius-md me-3">
                                <i class="material-icons opacity-10 text-white">account_balance_wallet</i>
                            </div>
                            <div>
                                <p class="text-xs text-secondary mb-0">Total Earned (All Time)</p>
                                <h5 class="mb-0"><?php echo format_currency($total_earned); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Breakdown -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header pb-0"><h6>Weekly Breakdown</h6></div>
                    <div class="card-body pt-2">
                        <?php foreach ($weeks as $wk): ?>
                        <?php $wk_pct = $min > 0 ? min(100, round(($wk['total'] / $min) * 100, 1)) : 0; ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-sm"><?php echo $wk['label']; ?></span>
                                <span class="text-sm font-weight-bold"><?php echo format_currency($wk['total']); ?> <span class="text-xs text-secondary">(<?php echo $wk['orders']; ?> orders)</span></span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-gradient-info" style="width: <?php echo $wk_pct; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Retailer Contributions -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header pb-0"><h6>Top Retailers</h6></div>
                    <div class="card-body pt-2">
                        <?php
                        $sorted = $subsidy['breakdown'];
                        usort($sorted, fn($a, $b) => $b['orders_total'] <=> $a['orders_total']);
                        $shown = 0;
                        foreach ($sorted as $b):
                            if ($shown >= 5) break;
                            $r_pct = $subsidy['grand_total'] > 0 ? round(($b['orders_total'] / $subsidy['grand_total']) * 100, 1) : 0;
                        ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <span class="text-sm font-weight-bold"><?php echo sanitize($b['name']); ?></span>
                                    <span class="text-xs"><?php echo format_currency($b['orders_total']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-xs text-secondary"><?php echo sanitize($b['package']); ?> (<?php echo $b['rate'] > 0 ? round($b['rate'] * 100, 1) . '%' : '0%'; ?>)</span>
                                    <span class="text-xs text-success"><?php echo format_currency($b['subsidy']); ?> over-ride</span>
                                </div>
                                <div class="progress mt-1" style="height: 4px;">
                                    <div class="progress-bar bg-gradient-dark" style="width: <?php echo $r_pct; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php $shown++; endforeach; ?>

                        <?php if (empty($sorted)): ?>
                        <p class="text-sm text-secondary text-center py-3">No retailers tagged yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings History -->
        <?php if (!empty($history_rows)): ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header pb-0"><h6>Recent Earnings</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Period</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Retailer Orders</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Earned</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Converted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history_rows as $h): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-sm"><?php echo date('F', mktime(0,0,0,$h['month'],1)) . ' ' . $h['year']; ?></span></td>
                                        <td><span class="text-sm"><?php echo format_currency($h['total_orders_amount']); ?></span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($h['subsidy_amount']); ?></span></td>
                                        <td><span class="text-xs text-secondary"><?php echo $h['converted_at'] ? date('M d, Y', strtotime($h['converted_at'])) : '-'; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Withdraw My Earnings -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6><i class="material-icons align-middle text-success">account_balance_wallet</i> Withdraw My Earnings</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-sm mb-2">
                            E-Funds Balance: <strong class="text-success"><?php echo format_currency($user_balance); ?></strong>
                        </p>
                        <div class="alert alert-light text-sm border mb-3">
                            <strong>Withdrawal Processing:</strong><br>
                            Cut-off: <strong>Wednesday at 5:00 PM</strong><br>
                            Releasing: <strong>Every Monday</strong><br>
                            Minimum withdrawal: <strong>₱500.00</strong>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="withdraw" value="1">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline my-3">
                                        <label class="form-label">Withdrawal Amount (₱) *</label>
                                        <input type="number" name="withdraw_amount" class="form-control" step="0.01" min="500" max="<?php echo $user_balance; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-static my-3">
                                        <label class="ms-0">Withdrawal Method *</label>
                                        <select name="withdraw_method" class="form-control" required onchange="toggleWithdrawMethod(this.value)">
                                            <option value="">-- Select --</option>
                                            <option value="gcash">GCash</option>
                                            <option value="check">Via Check (Pick up at Santa Rosa Depot)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div id="gcashFields" style="display:none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="input-group input-group-outline mb-3">
                                            <label class="form-label">GCash Account Name *</label>
                                            <input type="text" name="gcash_name" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group input-group-outline mb-3">
                                            <label class="form-label">GCash Number *</label>
                                            <input type="text" name="gcash_number" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="checkInfo" style="display:none;">
                                <div class="alert alert-info text-white text-sm">
                                    <i class="material-icons align-middle text-sm">info</i>
                                    Check will be available for pick up at <strong>Santa Rosa Depot</strong> every Monday.
                                </div>
                            </div>

                            <button type="submit" class="btn bg-gradient-success">
                                <i class="material-icons align-middle">payments</i> Submit Withdrawal Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Withdrawal Requests -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header pb-0"><h6>Recent Withdrawals</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Method</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($withdrawal_requests->num_rows === 0): ?>
                                    <tr><td colspan="3" class="text-center text-sm py-4">No withdrawal requests yet</td></tr>
                                    <?php else: ?>
                                    <?php while ($wr = $withdrawal_requests->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-sm font-weight-bold"><?php echo format_currency($wr['amount']); ?></span></td>
                                        <td><span class="text-xs"><?php echo strtoupper($wr['method']); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $wr['status'] === 'approved' ? 'success' : ($wr['status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($wr['status']); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function toggleWithdrawMethod(val) {
    document.getElementById('gcashFields').style.display = val === 'gcash' ? 'block' : 'none';
    document.getElementById('checkInfo').style.display = val === 'check' ? 'block' : 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>
