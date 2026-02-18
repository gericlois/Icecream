<?php
$page_title = 'Order Details';
$active_page = 'orders';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$id = (int)($_GET['id'] ?? 0);

// Handle status actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'approve') {
        // Check if efunds payment - deduct balance
        $order_check = $conn->query("SELECT * FROM orders WHERE id = $id AND status = 'pending'")->fetch_assoc();
        if ($order_check) {
            if ($order_check['payment_method'] === 'efunds') {
                $user = $conn->query("SELECT efunds_balance FROM users WHERE id = " . $order_check['user_id'])->fetch_assoc();
                if ($user['efunds_balance'] < $order_check['total_amount']) {
                    flash_message('danger', 'Cannot approve: Customer has insufficient e-funds balance (' . format_currency($user['efunds_balance']) . ').');
                    redirect(BASE_URL . '/admin/order_view.php?id=' . $id);
                }
                debit_efunds($conn, $order_check['user_id'], $order_check['total_amount'], 'order', $id,
                    'Payment for order ' . $order_check['order_number'], current_user_id());
            }
            $admin_id = current_user_id();
            $stmt = $conn->prepare("UPDATE orders SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?");
            $stmt->bind_param("ii", $admin_id, $id);
            $stmt->execute();
            $stmt->close();
            flash_message('success', 'Order approved successfully.');
        }
    } elseif ($action === 'for_delivery') {
        $conn->query("UPDATE orders SET status='for_delivery' WHERE id=$id AND status='approved'");
        flash_message('success', 'Order marked for delivery.');
    } elseif ($action === 'delivered') {
        $conn->query("UPDATE orders SET status='delivered', delivered_at=NOW() WHERE id=$id AND status='for_delivery'");
        flash_message('success', 'Order marked as delivered.');
    } elseif ($action === 'cancel') {
        $order_check = $conn->query("SELECT * FROM orders WHERE id = $id")->fetch_assoc();
        if ($order_check && $order_check['status'] === 'pending') {
            $conn->query("UPDATE orders SET status='cancelled' WHERE id=$id");
            flash_message('success', 'Order cancelled.');
        } elseif ($order_check && in_array($order_check['status'], ['approved', 'for_delivery']) && $order_check['payment_method'] === 'efunds') {
            // Refund efunds
            credit_efunds($conn, $order_check['user_id'], $order_check['total_amount'], 'adjustment', 'order', $id,
                'Refund for cancelled order ' . $order_check['order_number'], current_user_id());
            $conn->query("UPDATE orders SET status='cancelled' WHERE id=$id");
            flash_message('success', 'Order cancelled and e-funds refunded.');
        } else {
            $conn->query("UPDATE orders SET status='cancelled' WHERE id=$id");
            flash_message('success', 'Order cancelled.');
        }
    }
    redirect(BASE_URL . '/admin/order_view.php?id=' . $id);
}

// Fetch order
$stmt = $conn->prepare("
    SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, u.address as customer_address,
           a.full_name as agent_name, ap.full_name as approved_by_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN users a ON o.agent_id = a.id
    LEFT JOIN users ap ON o.approved_by = ap.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    flash_message('danger', 'Order not found.');
    redirect(BASE_URL . '/admin/orders.php');
}

// Fetch items
$items = $conn->query("SELECT * FROM order_items WHERE order_id = $id ORDER BY id");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="row">
                            <div class="col-6">
                                <h6>Order <?php echo sanitize($order['order_number']); ?></h6>
                            </div>
                            <div class="col-6 text-end">
                                <?php echo get_status_badge($order['status']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Order Items -->
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Product</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Flavor</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Packs</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Units</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-end">Unit Price</th>
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
                                        <td class="text-end"><span class="text-sm"><?php echo format_currency($item['unit_price']); ?></span></td>
                                        <td class="text-end"><span class="text-sm font-weight-bold"><?php echo format_currency($item['line_total']); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end"><strong><?php echo format_currency($order['subtotal']); ?></strong></td>
                                    </tr>
                                    <?php if ($order['discount_amount'] > 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-end text-success">Discount (<?php echo $order['discount_percent']; ?>%):</td>
                                        <td class="text-end text-success">-<?php echo format_currency($order['discount_amount']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td colspan="5" class="text-end"><h6 class="mb-0">Total:</h6></td>
                                        <td class="text-end"><h6 class="mb-0"><?php echo format_currency($order['total_amount']); ?></h6></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <!-- Order Info -->
                <div class="card mb-4">
                    <div class="card-header pb-0"><h6 class="mb-0">Order Info</h6></div>
                    <div class="card-body text-sm">
                        <p><strong>Customer:</strong> <?php echo sanitize($order['customer_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo sanitize($order['customer_phone'] ?? '-'); ?></p>
                        <p><strong>Address:</strong> <?php echo sanitize($order['customer_address'] ?? '-'); ?></p>
                        <p><strong>Agent:</strong> <?php echo sanitize($order['agent_name'] ?? '-'); ?></p>
                        <p><strong>Payment:</strong> <span class="badge bg-gradient-<?php echo $order['payment_method'] === 'cod' ? 'secondary' : 'info'; ?>"><?php echo strtoupper($order['payment_method']); ?></span></p>
                        <p><strong>Ordered:</strong> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        <?php if ($order['delivery_start_date']): ?>
                        <p><strong>Scheduled Delivery:</strong> <?php echo date('M d', strtotime($order['delivery_start_date'])) . '-' . date('d, Y', strtotime($order['delivery_end_date'])); ?></p>
                        <?php endif; ?>
                        <?php if ($order['approved_at']): ?>
                        <p><strong>Approved:</strong> <?php echo date('M d, Y h:i A', strtotime($order['approved_at'])); ?></p>
                        <p><strong>Approved by:</strong> <?php echo sanitize($order['approved_by_name'] ?? '-'); ?></p>
                        <?php endif; ?>
                        <?php if ($order['delivered_at']): ?>
                        <p><strong>Delivered:</strong> <?php echo date('M d, Y h:i A', strtotime($order['delivered_at'])); ?></p>
                        <?php endif; ?>
                        <?php if ($order['notes']): ?>
                        <p><strong>Notes:</strong> <?php echo sanitize($order['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-header pb-0"><h6 class="mb-0">Actions</h6></div>
                    <div class="card-body">
                        <?php if ($order['status'] === 'pending'): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn bg-gradient-success w-100 mb-2" onclick="return confirm('Approve this order?')">
                                <i class="material-icons">check_circle</i> Approve Order
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn bg-gradient-danger w-100 mb-2" onclick="return confirm('Cancel this order?')">
                                <i class="material-icons">cancel</i> Cancel Order
                            </button>
                        </form>
                        <?php elseif ($order['status'] === 'approved'): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="for_delivery">
                            <button type="submit" class="btn bg-gradient-primary w-100 mb-2">
                                <i class="material-icons">local_shipping</i> Mark For Delivery
                            </button>
                        </form>
                        <?php elseif ($order['status'] === 'for_delivery'): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delivered">
                            <button type="submit" class="btn bg-gradient-success w-100 mb-2" onclick="return confirm('Confirm delivery?')">
                                <i class="material-icons">done_all</i> Mark as Delivered
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php if (in_array($order['status'], ['approved', 'for_delivery', 'delivered'])): ?>
                        <a href="<?php echo BASE_URL; ?>/admin/order_receipt.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn bg-gradient-dark w-100 mb-2">
                            <i class="material-icons">print</i> Print Receipt
                        </a>
                        <?php endif; ?>

                        <?php if (in_array($order['status'], ['approved', 'for_delivery'])): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Cancel this order? E-funds will be refunded if applicable.')">
                                Cancel Order
                            </button>
                        </form>
                        <?php endif; ?>

                        <a href="<?php echo BASE_URL; ?>/admin/orders.php" class="btn btn-outline-secondary w-100 mt-2">Back to Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
