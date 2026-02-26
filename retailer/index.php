<?php
$page_title = 'Dashboard';
$active_page = 'dashboard';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['retailer']);

$uid = current_user_id();

// Get balance
$user = $conn->query("SELECT efunds_balance FROM users WHERE id = $uid")->fetch_assoc();

// Recent orders
$recent = $conn->query("SELECT * FROM orders WHERE user_id = $uid ORDER BY created_at DESC LIMIT 5");

// This month subsidy progress
$month = (int)date('m');
$year = (int)date('Y');
$subsidy = calculate_subsidy($conn, $uid, $month, $year);
$fda = calculate_fda($conn, $uid, $month, $year);

// Order count (all active + delivered)
$total_orders = (int)$conn->query("SELECT COUNT(*) as cnt FROM orders WHERE user_id = $uid AND status IN ('pending','approved','for_delivery','delivered')")->fetch_assoc()['cnt'];

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Welcome & Balance -->
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <a href="<?php echo BASE_URL; ?>/retailer/efunds.php" class="text-decoration-none">
                <div class="card" style="cursor:pointer;">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">account_balance_wallet</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-secondary">E-Funds Balance</p>
                            <h4 class="mb-0 text-dark"><?php echo format_currency($user['efunds_balance']); ?></h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <span class="text-sm text-primary">View E-Funds &rarr;</span>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <a href="<?php echo BASE_URL; ?>/retailer/orders.php" class="text-decoration-none">
                <div class="card" style="cursor:pointer;">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-warning shadow-warning text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">local_shipping</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-secondary">Track Orders</p>
                            <h4 class="mb-0 text-dark"><?php echo $total_orders; ?></h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <span class="text-sm text-primary">View Orders &rarr;</span>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <a href="<?php echo BASE_URL; ?>/retailer/catalog.php" class="text-decoration-none">
                <div class="card" style="cursor:pointer;">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">storefront</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-secondary">Quick Action</p>
                            <h5 class="mb-0 text-dark">Order Now</h5>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <span class="text-sm text-primary">Browse Catalog &rarr;</span>
                    </div>
                </div>
                </a>
            </div>
        </div>

        <!-- Freezer Display Allowance Module -->
        <div class="row mb-4">
            <div class="col-md-6">
                <a href="<?php echo BASE_URL; ?>/retailer/fda.php" class="text-decoration-none">
                <div class="card" style="cursor:pointer;">
                    <div class="card-header pb-0">
                        <div class="d-flex align-items-center">
                            <i class="material-icons text-info me-2">ac_unit</i>
                            <h6 class="mb-0">Freezer Display Allowance - <?php echo date('F Y'); ?></h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($fda['package'] && $fda['allowance'] > 0): ?>
                        <p class="text-sm mb-2">
                            Package: <strong><?php echo sanitize($fda['package']); ?></strong> &mdash;
                            <?php echo format_currency($fda['allowance']); ?>/month
                        </p>
                        <div class="row text-center mb-2">
                            <div class="col-6">
                                <p class="text-xs text-secondary mb-0">Registered Day</p>
                                <h5 class="mb-0"><?php echo $fda['reg_day']; ?><sup><?php echo date('S', mktime(0,0,0,1,$fda['reg_day'])); ?></sup> of the month</h5>
                            </div>
                            <div class="col-6">
                                <p class="text-xs text-secondary mb-0">First Eligible</p>
                                <h5 class="mb-0"><?php echo $fda['first_eligible_month']; ?></h5>
                            </div>
                        </div>
                        <?php if ($fda['eligible']): ?>
                        <div class="alert alert-success text-white text-sm py-2 mb-0">
                            <i class="material-icons align-middle text-sm">check_circle</i>
                            Qualified! <?php echo format_currency($fda['allowance']); ?> allowance available.
                            <a href="<?php echo BASE_URL; ?>/retailer/fda.php" class="text-white text-decoration-underline ms-1">Convert now &rarr;</a>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-light text-sm py-2 mb-0 border">
                            Available at end of <strong><?php echo $fda['first_eligible_month']; ?></strong>.
                        </div>
                        <?php endif; ?>
                        <?php elseif ($fda['package']): ?>
                        <p class="text-sm text-muted mb-0">Your package does not include Freezer Display Allowance.</p>
                        <?php else: ?>
                        <p class="text-sm text-muted mb-0">No package assigned. Contact admin for FDA eligibility.</p>
                        <?php endif; ?>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="<?php echo BASE_URL; ?>/retailer/subsidy.php" class="text-decoration-none">
                <div class="card" style="cursor:pointer;">
                    <div class="card-header pb-0">
                        <div class="d-flex align-items-center">
                            <i class="material-icons text-warning me-2">bolt</i>
                            <h6 class="mb-0">Electric Subsidy - <?php echo date('F Y'); ?></h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($subsidy['package']): ?>
                        <p class="text-sm mb-2">
                            Package: <strong><?php echo sanitize($subsidy['package']); ?></strong> &mdash;
                            <?php echo round($subsidy['rate'] * 100, 1); ?>% rate
                        </p>
                        <div class="row text-center mb-2">
                            <div class="col-6">
                                <p class="text-xs text-secondary mb-0">Your Orders</p>
                                <h5 class="mb-0"><?php echo format_currency($subsidy['total']); ?></h5>
                            </div>
                            <div class="col-6">
                                <p class="text-xs text-secondary mb-0">Minimum Required</p>
                                <h5 class="mb-0"><?php echo format_currency($subsidy['min']); ?></h5>
                            </div>
                        </div>
                        <?php if ($subsidy['min'] > 0): ?>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-gradient-<?php echo $subsidy['eligible'] ? 'success' : 'warning'; ?>"
                                 style="width: <?php echo min(100, ($subsidy['total'] / $subsidy['min']) * 100); ?>%"></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($subsidy['eligible']): ?>
                        <div class="alert alert-success text-white text-sm py-2 mb-0">
                            <i class="material-icons align-middle text-sm">check_circle</i>
                            Qualified! <?php echo format_currency($subsidy['subsidy']); ?> subsidy available.
                            <a href="<?php echo BASE_URL; ?>/retailer/subsidy.php" class="text-white text-decoration-underline ms-1">Convert now &rarr;</a>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-light text-sm py-2 mb-0 border">
                            <?php echo format_currency($subsidy['min'] - $subsidy['total']); ?> more in orders to qualify.
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <p class="text-sm text-muted mb-0">No package assigned. Contact admin for subsidy eligibility.</p>
                        <?php endif; ?>
                    </div>
                </div>
                </a>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="row">
                            <div class="col-6"><h6>Recent Orders</h6></div>
                            <div class="col-6 text-end"><a href="<?php echo BASE_URL; ?>/retailer/orders.php" class="btn btn-sm bg-gradient-primary">View All</a></div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Order #</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent->num_rows === 0): ?>
                                    <tr><td colspan="4" class="text-center text-sm py-4">No orders yet. <a href="<?php echo BASE_URL; ?>/retailer/catalog.php">Start ordering!</a></td></tr>
                                    <?php else: ?>
                                    <?php while ($o = $recent->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><a href="<?php echo BASE_URL; ?>/retailer/order_view.php?id=<?php echo $o['id']; ?>" class="text-xs font-weight-bold text-primary"><?php echo sanitize($o['order_number']); ?></a></td>
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
