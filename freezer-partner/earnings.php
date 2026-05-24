<?php
$page_title = 'My Earnings';
$active_page = 'earnings';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['freezer_partner']);

$uid = current_user_id();
$rate = 0.03;

// Month filter
$month = (int)($_GET['month'] ?? date('m'));
$year = (int)($_GET['year'] ?? date('Y'));
if ($month < 1 || $month > 12) $month = (int)date('m');
if ($year < 2020 || $year > 2099) $year = (int)date('Y');

$period_label = date('F Y', mktime(0, 0, 0, $month, 1, $year));

// Prev/next month nav
$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }
$is_current = $month === (int)date('m') && $year === (int)date('Y');

// Delivered orders for this month with retailer breakdown
$stmt = $conn->prepare("
    SELECT o.id, o.order_number, o.total_amount, o.delivered_at,
           u.full_name as retailer_name, u.freezer_serial, p.name as package_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN packages p ON u.package_info = p.slug
    WHERE u.freezer_partner_id = ? AND u.role = 'retailer'
      AND o.status = 'delivered'
      AND MONTH(o.delivered_at) = ? AND YEAR(o.delivered_at) = ?
    ORDER BY o.delivered_at DESC
");
$stmt->bind_param("iii", $uid, $month, $year);
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];
$total_sales = 0;
while ($row = $result->fetch_assoc()) {
    $row['earning'] = round($row['total_amount'] * $rate, 2);
    $total_sales += (float)$row['total_amount'];
    $transactions[] = $row;
}
$stmt->close();

$total_earnings = round($total_sales * $rate, 2);

// E-funds balance
$balance = (float)$conn->query("SELECT efunds_balance FROM users WHERE id = $uid")->fetch_assoc()['efunds_balance'];

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Month Navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-sm btn-outline-dark mb-0">
                                <i class="material-icons text-sm">chevron_left</i> <?php echo date('M Y', mktime(0,0,0,$prev_month,1,$prev_year)); ?>
                            </a>
                            <h5 class="mb-0"><i class="material-icons align-middle">payments</i> Earnings — <?php echo $period_label; ?></h5>
                            <?php if (!$is_current): ?>
                            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-sm btn-outline-dark mb-0">
                                <?php echo date('M Y', mktime(0,0,0,$next_month,1,$next_year)); ?> <i class="material-icons text-sm">chevron_right</i>
                            </a>
                            <?php else: ?>
                            <span class="btn btn-sm btn-outline-secondary mb-0 disabled">Current Month</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">payments</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Total Delivered Sales</p>
                            <h4 class="mb-0"><?php echo format_currency($total_sales); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">savings</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Earnings (3%)</p>
                            <h4 class="mb-0"><?php echo format_currency($total_earnings); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">account_balance_wallet</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">E-Funds Balance</p>
                            <h4 class="mb-0"><?php echo format_currency($balance); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0"><h6>Delivered Orders — <?php echo $period_label; ?></h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Order #</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Retailer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Freezer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Package</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Order Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Your 3%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transactions)): ?>
                                    <tr><td colspan="7" class="text-center text-sm py-4">No delivered orders this month</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($transactions as $t): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs"><?php echo date('M d, Y', strtotime($t['delivered_at'])); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo sanitize($t['order_number']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($t['retailer_name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($t['freezer_serial'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($t['package_name'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs"><?php echo format_currency($t['total_amount']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold text-success"><?php echo format_currency($t['earning']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="bg-light">
                                        <td class="ps-4" colspan="5"><span class="text-sm font-weight-bold">TOTAL (<?php echo count($transactions); ?> orders)</span></td>
                                        <td><span class="text-sm font-weight-bold"><?php echo format_currency($total_sales); ?></span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($total_earnings); ?></span></td>
                                    </tr>
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

<?php require_once '../includes/footer.php'; ?>
