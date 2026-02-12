<?php
$page_title = 'Orders';
$active_page = 'orders';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['subdealer']);

$uid = current_user_id();
$status_filter = $_GET['status'] ?? 'all';
$where = "WHERE (o.agent_id = $uid OR o.user_id IN (SELECT id FROM users WHERE agent_id = $uid))";
if (in_array($status_filter, ['pending', 'approved', 'for_delivery', 'delivered', 'cancelled'])) {
    $where .= " AND o.status = '" . $conn->real_escape_string($status_filter) . "'";
}

$orders = $conn->query("
    SELECT o.*, u.full_name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $where
    ORDER BY o.created_at DESC
");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="card">
            <div class="card-header pb-0">
                <div class="row">
                    <div class="col-6"><h6>Orders</h6></div>
                    <div class="col-6 text-end"><a href="<?php echo BASE_URL; ?>/agent/order_create.php" class="btn btn-sm bg-gradient-primary">New Order</a></div>
                </div>
                <ul class="nav nav-tabs mt-2">
                    <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>" href="?status=all">All</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="?status=pending">Pending</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" href="?status=approved">Approved</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>" href="?status=delivered">Delivered</a></li>
                </ul>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Order #</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Customer</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Payment</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders->num_rows === 0): ?>
                            <tr><td colspan="6" class="text-center text-sm py-4">No orders found</td></tr>
                            <?php else: ?>
                            <?php while ($o = $orders->fetch_assoc()): ?>
                            <tr style="cursor:pointer" onclick="window.location='<?php echo BASE_URL; ?>/agent/order_view.php?id=<?php echo $o['id']; ?>'">
                                <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($o['order_number']); ?></span></td>
                                <td><span class="text-xs"><?php echo sanitize($o['customer_name']); ?></span></td>
                                <td><span class="badge bg-gradient-<?php echo $o['payment_method'] === 'cod' ? 'secondary' : 'info'; ?>"><?php echo strtoupper($o['payment_method']); ?></span></td>
                                <td><span class="text-xs font-weight-bold"><?php echo format_currency($o['total_amount']); ?></span></td>
                                <td><?php echo get_status_badge($o['status']); ?></td>
                                <td><span class="text-xs"><?php echo date('M d', strtotime($o['created_at'])); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
