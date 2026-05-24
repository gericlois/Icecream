<?php
$page_title = 'Dashboard';
$active_page = 'dashboard';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['freezer_partner']);

$uid = current_user_id();
$month = (int)date('m');
$year = (int)date('Y');
$rate = 0.03; // 3%

// Tagged retailers count
$retailer_count = (int)$conn->query("SELECT COUNT(*) as cnt FROM users WHERE freezer_partner_id = $uid AND role = 'retailer' AND status = 'active'")->fetch_assoc()['cnt'];

// Monthly delivered gross (all my retailers)
$monthly_gross = (float)$conn->query("
    SELECT COALESCE(SUM(o.total_amount), 0) as total
    FROM orders o JOIN users u ON o.user_id = u.id
    WHERE u.freezer_partner_id = $uid AND u.role = 'retailer'
      AND o.status = 'delivered'
      AND MONTH(o.delivered_at) = $month AND YEAR(o.delivered_at) = $year
")->fetch_assoc()['total'];

// All-time delivered
$all_time_gross = (float)$conn->query("
    SELECT COALESCE(SUM(o.total_amount), 0) as total
    FROM orders o JOIN users u ON o.user_id = u.id
    WHERE u.freezer_partner_id = $uid AND u.role = 'retailer' AND o.status = 'delivered'
")->fetch_assoc()['total'];

// Pending orders
$pending_orders = (int)$conn->query("
    SELECT COUNT(*) as cnt FROM orders o JOIN users u ON o.user_id = u.id
    WHERE u.freezer_partner_id = $uid AND o.status IN ('pending','approved','for_delivery')
")->fetch_assoc()['cnt'];

$monthly_earnings = round($monthly_gross * $rate, 2);
$all_time_earnings = round($all_time_gross * $rate, 2);

// E-funds balance
$balance = (float)$conn->query("SELECT efunds_balance FROM users WHERE id = $uid")->fetch_assoc()['efunds_balance'];

// Recent orders
$recent = $conn->query("
    SELECT o.*, u.full_name as customer_name, u.freezer_serial
    FROM orders o JOIN users u ON o.user_id = u.id
    WHERE u.freezer_partner_id = $uid
    ORDER BY o.created_at DESC LIMIT 10
");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Stat Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">ac_unit</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">My Retailers</p>
                            <h4 class="mb-0"><?php echo $retailer_count; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-warning shadow-warning text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">hourglass_empty</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Pending Orders</p>
                            <h4 class="mb-0"><?php echo $pending_orders; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">payments</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Monthly Earnings (3%)</p>
                            <h4 class="mb-0"><?php echo format_currency($monthly_earnings); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
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

        <!-- Summary -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header pb-0"><h6><?php echo date('F Y'); ?> Gross Sales</h6></div>
                    <div class="card-body">
                        <h3 class="mb-1"><?php echo format_currency($monthly_gross); ?></h3>
                        <p class="text-sm text-success mb-0">Your 3% earnings: <strong><?php echo format_currency($monthly_earnings); ?></strong></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header pb-0"><h6>All-Time Gross Sales</h6></div>
                    <div class="card-body">
                        <h3 class="mb-1"><?php echo format_currency($all_time_gross); ?></h3>
                        <p class="text-sm text-success mb-0">Total 3% earned: <strong><?php echo format_currency($all_time_earnings); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="row">
                            <div class="col-6"><h6>Recent Orders</h6></div>
                            <div class="col-6 text-end"><a href="<?php echo BASE_URL; ?>/freezer-partner/orders.php" class="btn btn-sm bg-gradient-primary">View All</a></div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Order #</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Retailer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Freezer Code</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent->num_rows === 0): ?>
                                    <tr><td colspan="6" class="text-center text-sm py-4">No orders yet</td></tr>
                                    <?php else: ?>
                                    <?php while ($o = $recent->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($o['order_number']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($o['customer_name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($o['freezer_serial'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo format_currency($o['total_amount']); ?></span></td>
                                        <td><?php echo get_status_badge($o['status']); ?></td>
                                        <td><span class="text-xs"><?php echo time_ago($o['created_at']); ?></span></td>
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

<?php require_once '../includes/footer.php'; ?>
