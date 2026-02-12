<?php
$page_title = 'Dashboard';
$active_page = 'dashboard';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['subdealer']);

$uid = current_user_id();

// My retailers count
$r = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE agent_id = $uid AND status = 'active'")->fetch_assoc();
$retailer_count = $r['cnt'];

// Pending orders (for my retailers)
$r = $conn->query("
    SELECT COUNT(*) as cnt FROM orders
    WHERE status = 'pending' AND (agent_id = $uid OR user_id IN (SELECT id FROM users WHERE agent_id = $uid))
")->fetch_assoc();
$pending_orders = $r['cnt'];

// This month sales
$month_start = date('Y-m-01');
$r = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0) as total FROM orders
    WHERE status = 'delivered' AND delivered_at >= '$month_start'
    AND (agent_id = $uid OR user_id IN (SELECT id FROM users WHERE agent_id = $uid))
")->fetch_assoc();
$monthly_sales = $r['total'];

// Total orders
$r = $conn->query("
    SELECT COUNT(*) as cnt FROM orders
    WHERE agent_id = $uid OR user_id IN (SELECT id FROM users WHERE agent_id = $uid)
")->fetch_assoc();
$total_orders = $r['cnt'];

// Recent orders
$recent = $conn->query("
    SELECT o.*, u.full_name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.agent_id = $uid OR o.user_id IN (SELECT id FROM users WHERE agent_id = $uid)
    ORDER BY o.created_at DESC LIMIT 10
");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="row">
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">store</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">My Retailers</p>
                            <h4 class="mb-0"><?php echo $retailer_count; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-warning shadow-warning text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">pending_actions</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Pending Orders</p>
                            <h4 class="mb-0"><?php echo $pending_orders; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">payments</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Monthly Sales</p>
                            <h4 class="mb-0"><?php echo format_currency($monthly_sales); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">receipt_long</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Total Orders</p>
                            <h4 class="mb-0"><?php echo $total_orders; ?></h4>
                        </div>
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
                            <div class="col-6 text-end">
                                <a href="<?php echo BASE_URL; ?>/agent/order_create.php" class="btn btn-sm bg-gradient-primary">New Order</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Order #</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Customer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($o = $recent->fetch_assoc()): ?>
                                    <tr style="cursor:pointer" onclick="window.location='<?php echo BASE_URL; ?>/agent/order_view.php?id=<?php echo $o['id']; ?>'">
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($o['order_number']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($o['customer_name']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo format_currency($o['total_amount']); ?></span></td>
                                        <td><?php echo get_status_badge($o['status']); ?></td>
                                        <td><span class="text-xs"><?php echo date('M d', strtotime($o['created_at'])); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
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
