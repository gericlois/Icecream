<?php
$page_title = 'Order Details';
$active_page = 'orders';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['retailer']);

$id = (int)($_GET['id'] ?? 0);
$uid = current_user_id();

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $uid);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    flash_message('danger', 'Order not found.');
    redirect(BASE_URL . '/retailer/orders.php');
}

$items = $conn->query("SELECT * FROM order_items WHERE order_id = $id ORDER BY id");

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
                    <div class="col-8">
                        <h6>Order <?php echo sanitize($order['order_number']); ?></h6>
                        <p class="text-sm text-muted mb-1"><?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        <?php if ($order['delivery_start_date']): ?>
                        <p class="text-sm mb-0"><i class="material-icons text-info" style="font-size:14px;vertical-align:middle;">local_shipping</i> Delivery: <strong><?php echo date('M d', strtotime($order['delivery_start_date'])) . '-' . date('d, Y', strtotime($order['delivery_end_date'])); ?></strong></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-4 text-end">
                        <?php echo get_status_badge($order['status']); ?>
                        <br>
                        <span class="badge bg-gradient-<?php echo $order['payment_method'] === 'cod' ? 'secondary' : 'info'; ?> mt-1"><?php echo strtoupper($order['payment_method']); ?></span>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Product</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Packs</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Units</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-end">Price</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $items->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <h6 class="mb-0 text-sm"><?php echo sanitize($item['product_name']); ?></h6>
                                    <p class="text-xs text-secondary mb-0"><?php echo sanitize($item['flavor_name']); ?></p>
                                </td>
                                <td class="text-center"><span class="text-sm"><?php echo $item['quantity_packs']; ?></span></td>
                                <td class="text-center"><span class="text-sm"><?php echo $item['quantity_units']; ?></span></td>
                                <td class="text-end"><span class="text-sm"><?php echo format_currency($item['unit_price']); ?></span></td>
                                <td class="text-end"><span class="text-sm font-weight-bold"><?php echo format_currency($item['line_total']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">
                    <div class="d-flex justify-content-end">
                        <div class="text-end">
                            <p class="text-sm mb-1">Subtotal: <strong><?php echo format_currency($order['subtotal']); ?></strong></p>
                            <?php if ($order['discount_amount'] > 0): ?>
                            <p class="text-sm mb-1 text-success">Discount (<?php echo $order['discount_percent']; ?>%): <strong>-<?php echo format_currency($order['discount_amount']); ?></strong></p>
                            <?php endif; ?>
                            <h5>Total: <?php echo format_currency($order['total_amount']); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="<?php echo BASE_URL; ?>/retailer/orders.php" class="btn btn-outline-primary">Back to Orders</a>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
