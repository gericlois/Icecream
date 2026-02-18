<?php
$page_title = 'Order Details';
$active_page = 'orders';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['subdealer']);

$id = (int)($_GET['id'] ?? 0);
$uid = current_user_id();

$stmt = $conn->prepare("
    SELECT o.*, u.full_name as customer_name, u.address as customer_address, u.phone as customer_phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND (o.agent_id = ? OR o.user_id IN (SELECT id FROM users WHERE agent_id = ?))
");
$stmt->bind_param("iii", $id, $uid, $uid);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    flash_message('danger', 'Order not found.');
    redirect(BASE_URL . '/agent/orders.php');
}

$items = $conn->query("SELECT * FROM order_items WHERE order_id = $id ORDER BY id");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="row">
                            <div class="col-6"><h6>Order <?php echo sanitize($order['order_number']); ?></h6></div>
                            <div class="col-6 text-end"><?php echo get_status_badge($order['status']); ?></div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Product</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Flavor</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Packs</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Units</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = $items->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-sm"><?php echo sanitize($item['product_name']); ?></span></td>
                                        <td><span class="text-sm"><?php echo sanitize($item['flavor_name']); ?></span></td>
                                        <td class="text-center"><span class="text-sm"><?php echo $item['quantity_packs']; ?></span></td>
                                        <td class="text-center"><span class="text-sm"><?php echo $item['quantity_units']; ?></span></td>
                                        <td class="text-end"><span class="text-sm font-weight-bold"><?php echo format_currency($item['line_total']); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 py-3 text-end">
                            <p class="text-sm">Subtotal: <strong><?php echo format_currency($order['subtotal']); ?></strong></p>
                            <?php if ($order['discount_amount'] > 0): ?>
                            <p class="text-sm text-success">Discount: -<?php echo format_currency($order['discount_amount']); ?></p>
                            <?php endif; ?>
                            <h5>Total: <?php echo format_currency($order['total_amount']); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header pb-0"><h6>Customer Info</h6></div>
                    <div class="card-body text-sm">
                        <p><strong>Customer:</strong> <?php echo sanitize($order['customer_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo sanitize($order['customer_phone'] ?? '-'); ?></p>
                        <p><strong>Address:</strong> <?php echo sanitize($order['customer_address'] ?? '-'); ?></p>
                        <p><strong>Payment:</strong> <?php echo strtoupper($order['payment_method']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        <?php if ($order['delivery_start_date']): ?>
                        <p><strong>Delivery:</strong> <?php echo date('M d', strtotime($order['delivery_start_date'])) . '-' . date('d, Y', strtotime($order['delivery_end_date'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/agent/orders.php" class="btn btn-outline-primary w-100 mt-3">Back to Orders</a>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
