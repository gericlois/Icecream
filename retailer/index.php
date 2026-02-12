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

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Welcome & Balance -->
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">account_balance_wallet</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">E-Funds Balance</p>
                            <h4 class="mb-0"><?php echo format_currency($user['efunds_balance']); ?></h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <a href="<?php echo BASE_URL; ?>/retailer/reload.php" class="text-sm text-primary">Reload E-Funds &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-warning shadow-warning text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">bolt</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Electric Subsidy</p>
                            <h4 class="mb-0"><?php echo $subsidy['eligible'] ? format_currency($subsidy['subsidy']) : 'Not yet'; ?></h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-gradient-warning" style="width: <?php echo min(100, ($subsidy['total'] / $subsidy['min']) * 100); ?>%"></div>
                        </div>
                        <small class="text-xs"><?php echo format_currency($subsidy['total']); ?> / <?php echo format_currency($subsidy['min']); ?> min orders</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">storefront</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Quick Action</p>
                            <h5 class="mb-0">Order Now</h5>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <a href="<?php echo BASE_URL; ?>/retailer/catalog.php" class="text-sm text-primary">Browse Catalog &rarr;</a>
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
