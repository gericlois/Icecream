<?php
$page_title = 'Orders';
$active_page = 'orders';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['freezer_partner']);

$uid = current_user_id();

$status_filter = $_GET['status'] ?? 'all';
$where = "WHERE u.freezer_partner_id = ?";
$params = [$uid];
$types = "i";

if (in_array($status_filter, ['pending','approved','for_delivery','delivered','cancelled'])) {
    $where .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$stmt = $conn->prepare("
    SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, u.freezer_serial
    FROM orders o JOIN users u ON o.user_id = u.id
    $where
    ORDER BY o.created_at DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6>My Retailers' Orders</h6>
                        <ul class="nav nav-tabs mt-2">
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>" href="?status=all">All</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="?status=pending">Pending</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" href="?status=approved">Approved</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'for_delivery' ? 'active' : ''; ?>" href="?status=for_delivery">For Delivery</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>" href="?status=delivered">Delivered</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>" href="?status=cancelled">Cancelled</a></li>
                        </ul>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Order #</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Retailer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Phone</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Freezer Code</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Your 3%</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($orders->num_rows === 0): ?>
                                    <tr><td colspan="8" class="text-center text-sm py-4">No orders found</td></tr>
                                    <?php else: ?>
                                    <?php while ($o = $orders->fetch_assoc()): ?>
                                    <?php $earning = $o['status'] === 'delivered' ? round($o['total_amount'] * 0.03, 2) : 0; ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($o['order_number']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($o['customer_name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($o['customer_phone'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($o['freezer_serial'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo format_currency($o['total_amount']); ?></span></td>
                                        <td><span class="text-xs <?php echo $earning > 0 ? 'font-weight-bold text-success' : 'text-muted'; ?>"><?php echo $earning > 0 ? format_currency($earning) : '—'; ?></span></td>
                                        <td><?php echo get_status_badge($o['status']); ?></td>
                                        <td><span class="text-xs"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></span></td>
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
