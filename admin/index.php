<?php
$page_title = 'Dashboard';
$active_page = 'dashboard';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

// KPI queries
$today = date('Y-m-d');
$month_start = date('Y-m-01');

// Orders today
$r = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = '$today'")->fetch_assoc();
$orders_today = $r['cnt'];

// Pending orders
$r = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'")->fetch_assoc();
$pending_orders = $r['cnt'];

// Revenue this month
$r = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'delivered' AND delivered_at >= '$month_start'")->fetch_assoc();
$monthly_revenue = $r['total'];

// Active users
$r = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE status = 'active'")->fetch_assoc();
$active_users = $r['cnt'];

// Pending registrations
$pending_users = $conn->query("SELECT * FROM users WHERE status = 'inactive' AND registered_by IS NULL ORDER BY created_at DESC");
$pending_count = $pending_users->num_rows;

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_user'])) {
    $uid = (int)$_POST['user_id'];
    $action = $_POST['action_user'];
    if ($action === 'approve') {
        $conn->query("UPDATE users SET status = 'active' WHERE id = $uid");
        flash_message('success', 'Retailer approved successfully.');
    } elseif ($action === 'reject') {
        $conn->query("DELETE FROM users WHERE id = $uid AND status = 'inactive'");
        flash_message('success', 'Registration rejected and removed.');
    }
    redirect(BASE_URL . '/admin/index.php');
}

// Recent pending orders
$recent_orders = $conn->query("
    SELECT o.*, u.full_name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.status = 'pending'
    ORDER BY o.created_at DESC
    LIMIT 10
");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg" style="background-color: #f8f9fa;">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- KPI Cards -->
        <div class="row">
            <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">shopping_cart</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-capitalize">Orders Today</p>
                            <h4 class="mb-0"><?php echo $orders_today; ?></h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <p class="mb-0 text-sm"><span class="text-success font-weight-bolder"><?php echo date('M d, Y'); ?></span></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-warning shadow-warning text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">pending_actions</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-capitalize">Pending Orders</p>
                            <h4 class="mb-0"><?php echo $pending_orders; ?></h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <a href="<?php echo BASE_URL; ?>/admin/orders.php?status=pending" class="mb-0 text-sm text-primary">View pending &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">payments</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-capitalize">Revenue (Month)</p>
                            <h4 class="mb-0"><?php echo format_currency($monthly_revenue); ?></h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <p class="mb-0 text-sm"><?php echo date('F Y'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">people</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-capitalize">Active Users</p>
                            <h4 class="mb-0"><?php echo $active_users; ?></h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <a href="<?php echo BASE_URL; ?>/admin/users.php" class="mb-0 text-sm text-primary">Manage users &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Registrations -->
        <?php if ($pending_count > 0): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-start border-warning border-3">
                    <div class="card-header pb-0">
                        <h6><i class="material-icons text-warning align-middle">person_add</i> Pending Registrations (<?php echo $pending_count; ?>)</h6>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Name</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Username</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Phone</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Address</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Registered</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($pu = $pending_users->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($pu['full_name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($pu['username']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($pu['phone'] ?: '-'); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($pu['address'] ?: '-'); ?></span></td>
                                        <td><span class="text-xs"><?php echo time_ago($pu['created_at']); ?></span></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $pu['id']; ?>">
                                                <button type="submit" name="action_user" value="approve" class="btn btn-sm bg-gradient-success mb-0">Approve</button>
                                                <button type="submit" name="action_user" value="reject" class="btn btn-sm bg-gradient-danger mb-0" onclick="return confirm('Reject this registration?')">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Pending Orders -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="row">
                            <div class="col-6">
                                <h6>Recent Pending Orders</h6>
                            </div>
                            <div class="col-6 text-end">
                                <a href="<?php echo BASE_URL; ?>/admin/orders.php" class="btn btn-sm bg-gradient-primary">View All Orders</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Order #</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Customer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Payment</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Total</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_orders->num_rows === 0): ?>
                                    <tr><td colspan="6" class="text-center text-sm py-4">No pending orders</td></tr>
                                    <?php else: ?>
                                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($order['order_number']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($order['customer_name']); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $order['payment_method'] === 'cod' ? 'secondary' : 'info'; ?>"><?php echo strtoupper($order['payment_method']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo format_currency($order['total_amount']); ?></span></td>
                                        <td><span class="text-xs"><?php echo date('M d, h:i A', strtotime($order['created_at'])); ?></span></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/admin/order_view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm bg-gradient-info mb-0">View</a>
                                        </td>
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
