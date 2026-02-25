<?php
$page_title = 'Shopping Cart';
$active_page = 'cart';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['retailer']);

$uid = current_user_id();
$user = $conn->query("SELECT efunds_balance FROM users WHERE id = $uid")->fetch_assoc();
$discount_pct = (float)get_setting($conn, 'efunds_discount_percent');

// Handle place order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) {
        flash_message('danger', 'Your cart is empty.');
        redirect(BASE_URL . '/retailer/cart.php');
    }

    $payment_method = $_POST['payment_method'] ?? 'cod';
    $notes = trim($_POST['notes'] ?? '');

    // Calculate totals
    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += $item['quantity_packs'] * $item['qty_per_pack'] * $item['unit_price'];
    }

    // Minimum order amount
    if ($subtotal < 2000) {
        flash_message('danger', 'Minimum order amount is ₱2,000.00. Your current total is ' . format_currency($subtotal) . '.');
        redirect(BASE_URL . '/retailer/cart.php');
    }

    $discount_amount = 0;
    $actual_discount_pct = 0;
    if ($payment_method === 'efunds' && $discount_pct > 0) {
        $actual_discount_pct = $discount_pct;
        $discount_amount = round($subtotal * ($discount_pct / 100), 2);
    }
    $total = $subtotal - $discount_amount;

    // Validate efunds balance (just a check, deduction happens on admin approval)
    if ($payment_method === 'efunds' && $user['efunds_balance'] < $total) {
        flash_message('danger', 'Insufficient e-funds balance. You have ' . format_currency($user['efunds_balance']) . ' but need ' . format_currency($total));
        redirect(BASE_URL . '/retailer/cart.php');
    }

    // Create order
    $order_number = generate_order_number($conn);
    $agent_id = $_SESSION['agent_id'];
    $delivery = get_delivery_window();
    $delivery_start = $delivery['start'];
    $delivery_end = $delivery['end'];

    $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, agent_id, payment_method, discount_percent, subtotal, discount_amount, total_amount, notes, delivery_start_date, delivery_end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisddddsss", $order_number, $uid, $agent_id, $payment_method, $actual_discount_pct, $subtotal, $discount_amount, $total, $notes, $delivery_start, $delivery_end);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();

    // Insert order items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_flavor_id, product_name, flavor_name, qty_per_pack, unit_price, quantity_packs, quantity_units, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($cart as $item) {
        $units = $item['quantity_packs'] * $item['qty_per_pack'];
        $line_total = $units * $item['unit_price'];
        $stmt->bind_param("iissidiid", $order_id, $item['product_flavor_id'], $item['product_name'], $item['flavor_name'], $item['qty_per_pack'], $item['unit_price'], $item['quantity_packs'], $units, $line_total);
        $stmt->execute();
    }
    $stmt->close();

    // Clear cart
    $_SESSION['cart'] = [];

    flash_message('success', 'Order ' . $order_number . ' placed successfully! Waiting for admin approval.');
    redirect(BASE_URL . '/retailer/order_view.php?id=' . $order_id);
}

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['quantity_packs'] * $item['qty_per_pack'] * $item['unit_price'];
}
$delivery = get_delivery_window();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <?php if (empty($cart)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="material-icons" style="font-size:64px;color:#ccc;">shopping_cart</i>
                <h5 class="mt-3">Your cart is empty</h5>
                <p class="text-muted">Browse our catalog and add some ice cream!</p>
                <a href="<?php echo BASE_URL; ?>/retailer/catalog.php" class="btn bg-gradient-primary">Browse Catalog</a>
            </div>
        </div>
        <?php else: ?>
        <form method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header pb-0">
                            <h6>Cart Items (<?php echo count($cart); ?>)</h6>
                        </div>
                        <div class="card-body px-0 pt-0 pb-2">
                            <div class="table-responsive p-0">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Product</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Packs</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Units</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-end">Price/Unit</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-end">Total</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart as $i => $item):
                                            $units = $item['quantity_packs'] * $item['qty_per_pack'];
                                            $line = $units * $item['unit_price'];
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <h6 class="mb-0 text-sm"><?php echo sanitize($item['product_name']); ?></h6>
                                                <p class="text-xs text-secondary mb-0"><?php echo sanitize($item['flavor_name']); ?></p>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary mb-0 px-2" onclick="updateCartQty(<?php echo $i; ?>, <?php echo $item['quantity_packs'] - 1; ?>)">-</button>
                                                    <span class="mx-2 font-weight-bold"><?php echo $item['quantity_packs']; ?></span>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary mb-0 px-2" onclick="updateCartQty(<?php echo $i; ?>, <?php echo $item['quantity_packs'] + 1; ?>)">+</button>
                                                </div>
                                            </td>
                                            <td class="text-center"><span class="text-sm"><?php echo $units; ?></span></td>
                                            <td class="text-end"><span class="text-sm"><?php echo format_currency($item['unit_price']); ?></span></td>
                                            <td class="text-end"><span class="text-sm font-weight-bold"><?php echo format_currency($line); ?></span></td>
                                            <td>
                                                <button type="button" class="btn btn-sm text-danger mb-0" onclick="removeCartItem(<?php echo $i; ?>)">
                                                    <i class="material-icons">delete</i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="material-icons text-info me-2">local_shipping</i>
                                <h6 class="mb-0 text-sm">Delivery Schedule</h6>
                            </div>
                            <p class="text-sm mb-1"><strong>Order Cut-off:</strong> <?php echo $delivery['cutoff_label']; ?></p>
                            <p class="text-sm mb-0"><strong>Expected Delivery:</strong> <?php echo $delivery['label']; ?></p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header pb-0"><h6>Order Summary</h6></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <strong id="subtotalDisplay"><?php echo format_currency($subtotal); ?></strong>
                            </div>

                            <hr>
                            <h6 class="text-sm">Payment Method</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" value="cod" id="pmCod" checked onchange="togglePaymentMethod()">
                                <label class="form-check-label" for="pmCod">Cash on Delivery (COD)</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" value="efunds" id="pmEfunds" onchange="togglePaymentMethod()">
                                <label class="form-check-label" for="pmEfunds">E-Funds</label>
                            </div>

                            <div id="efundsInfo" style="display:none;" class="alert alert-info text-white text-sm mt-2">
                                <p class="mb-1">Your Balance: <strong><?php echo format_currency($user['efunds_balance']); ?></strong></p>
                                <?php if ($discount_pct > 0): ?>
                                <p class="mb-1">Discount: <strong><?php echo $discount_pct; ?>%</strong> (-<?php echo format_currency($subtotal * $discount_pct / 100); ?>)</p>
                                <p class="mb-0">Total: <strong><?php echo format_currency($subtotal - ($subtotal * $discount_pct / 100)); ?></strong></p>
                                <?php endif; ?>
                                <?php if ($user['efunds_balance'] < $subtotal): ?>
                                <p class="mb-0 text-warning"><strong>Warning:</strong> Insufficient balance</p>
                                <?php endif; ?>
                            </div>

                            <div class="input-group input-group-outline my-3">
                                <label class="form-label">Notes (optional)</label>
                                <input type="text" name="notes" class="form-control">
                            </div>

                            <?php if ($subtotal < 2000): ?>
                            <div class="alert alert-warning text-white text-sm py-2 mb-3">
                                <i class="material-icons align-middle text-sm">warning</i>
                                Minimum order is <strong>₱2,000.00</strong>. You need <strong><?php echo format_currency(2000 - $subtotal); ?></strong> more.
                            </div>
                            <?php endif; ?>
                            <button type="submit" name="place_order" value="1" class="btn bg-gradient-success w-100 <?php echo $subtotal < 2000 ? 'disabled' : ''; ?>" <?php echo $subtotal < 2000 ? 'disabled' : 'onclick="return confirm(\'Place this order?\')"'; ?>>
                                <i class="material-icons">shopping_bag</i> Place Order
                            </button>
                            <a href="<?php echo BASE_URL; ?>/retailer/catalog.php" class="btn btn-outline-primary w-100 mt-2">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
