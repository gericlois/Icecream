<?php
$page_title = 'New Order';
$active_page = 'new_order';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['subdealer']);

$uid = current_user_id();

// Get my retailers
$retailers = $conn->query("SELECT id, full_name, username, efunds_balance FROM users WHERE agent_id = $uid AND role = 'retailer' AND status = 'active' ORDER BY full_name");

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $retailer_id = (int)($_POST['retailer_id'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $notes = trim($_POST['notes'] ?? '');
    $items = $_POST['items'] ?? [];

    // Verify retailer belongs to this agent
    $check = $conn->query("SELECT id, efunds_balance FROM users WHERE id = $retailer_id AND agent_id = $uid")->fetch_assoc();
    if (!$check) {
        flash_message('danger', 'Invalid retailer selected.');
        redirect(BASE_URL . '/agent/order_create.php');
    }

    // Build order items
    $order_items = [];
    $subtotal = 0;
    foreach ($items as $item) {
        $fid = (int)($item['flavor_id'] ?? 0);
        $packs = (int)($item['packs'] ?? 0);
        if ($fid < 1 || $packs < 1) continue;

        $pf = $conn->query("
            SELECT pf.flavor_name, p.name as product_name, p.qty_per_pack, p.unit_price
            FROM product_flavors pf JOIN products p ON pf.product_id = p.id
            WHERE pf.id = $fid
        ")->fetch_assoc();
        if (!$pf) continue;

        $units = $packs * $pf['qty_per_pack'];
        $line = $units * $pf['unit_price'];
        $subtotal += $line;
        $order_items[] = [
            'product_flavor_id' => $fid,
            'product_name' => $pf['product_name'],
            'flavor_name' => $pf['flavor_name'],
            'qty_per_pack' => $pf['qty_per_pack'],
            'unit_price' => $pf['unit_price'],
            'quantity_packs' => $packs,
            'quantity_units' => $units,
            'line_total' => $line,
        ];
    }

    if (empty($order_items)) {
        flash_message('danger', 'Please add at least one item.');
        redirect(BASE_URL . '/agent/order_create.php');
    }

    $discount_pct = ($payment_method === 'efunds') ? (float)get_setting($conn, 'efunds_discount_percent') : 0;
    $discount_amount = round($subtotal * ($discount_pct / 100), 2);
    $total = $subtotal - $discount_amount;

    $order_number = generate_order_number($conn);
    $delivery = get_delivery_window();
    $delivery_start = $delivery['start'];
    $delivery_end = $delivery['end'];
    $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, agent_id, payment_method, discount_percent, subtotal, discount_amount, total_amount, notes, delivery_start_date, delivery_end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisddddsss", $order_number, $retailer_id, $uid, $payment_method, $discount_pct, $subtotal, $discount_amount, $total, $notes, $delivery_start, $delivery_end);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_flavor_id, product_name, flavor_name, qty_per_pack, unit_price, quantity_packs, quantity_units, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($order_items as $oi) {
        $stmt->bind_param("iissidiid", $order_id, $oi['product_flavor_id'], $oi['product_name'], $oi['flavor_name'], $oi['qty_per_pack'], $oi['unit_price'], $oi['quantity_packs'], $oi['quantity_units'], $oi['line_total']);
        $stmt->execute();
    }
    $stmt->close();

    flash_message('success', 'Order ' . $order_number . ' placed successfully for retailer!');
    redirect(BASE_URL . '/agent/order_view.php?id=' . $order_id);
}

// Get products for catalog
$products = $conn->query("
    SELECT p.name as product_name, p.qty_per_pack, p.unit_price, pf.id as flavor_id, pf.flavor_name
    FROM products p JOIN product_flavors pf ON p.id = pf.product_id
    WHERE p.status = 'active' AND pf.status = 'active'
    ORDER BY p.sort_order, pf.sort_order
");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <form method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header pb-0"><h6>Select Products</h6></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Flavor</th>
                                            <th>Pack Size</th>
                                            <th>Price/Unit</th>
                                            <th style="width:80px;">Packs</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 0; while ($p = $products->fetch_assoc()): ?>
                                        <tr>
                                            <td class="text-sm"><?php echo sanitize($p['product_name']); ?></td>
                                            <td class="text-sm"><?php echo sanitize($p['flavor_name']); ?></td>
                                            <td class="text-sm"><?php echo $p['qty_per_pack']; ?>/pack</td>
                                            <td class="text-sm"><?php echo format_currency($p['unit_price']); ?></td>
                                            <td>
                                                <input type="hidden" name="items[<?php echo $i; ?>][flavor_id]" value="<?php echo $p['flavor_id']; ?>">
                                                <input type="number" name="items[<?php echo $i; ?>][packs]" class="form-control form-control-sm qty-input" value="0" min="0" style="width:60px;">
                                            </td>
                                        </tr>
                                        <?php $i++; endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <?php $delivery_info = get_delivery_window(); ?>
                    <div class="card mb-3">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="material-icons text-info me-2">local_shipping</i>
                                <h6 class="mb-0 text-sm">Delivery Schedule</h6>
                            </div>
                            <p class="text-sm mb-1"><strong>Order Cut-off:</strong> <?php echo $delivery_info['cutoff_label']; ?></p>
                            <p class="text-sm mb-0"><strong>Expected Delivery:</strong> <?php echo $delivery_info['label']; ?></p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header pb-0"><h6>Order Details</h6></div>
                        <div class="card-body">
                            <div class="input-group input-group-static my-3">
                                <label class="ms-0">Select Retailer *</label>
                                <select name="retailer_id" class="form-control" required>
                                    <option value="">-- Select Retailer --</option>
                                    <?php while ($r = $retailers->fetch_assoc()): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo sanitize($r['full_name']); ?> (<?php echo format_currency($r['efunds_balance']); ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="input-group input-group-static my-3">
                                <label class="ms-0">Payment Method</label>
                                <select name="payment_method" class="form-control">
                                    <option value="cod">COD (No Discount)</option>
                                    <option value="efunds">E-Funds</option>
                                </select>
                            </div>
                            <div class="input-group input-group-outline my-3">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" class="form-control">
                            </div>
                            <button type="submit" name="place_order" value="1" class="btn bg-gradient-success w-100" onclick="return confirm('Place this order?')">
                                <i class="material-icons">shopping_bag</i> Place Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
