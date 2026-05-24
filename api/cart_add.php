<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$flavor_id = (int)($input['product_flavor_id'] ?? 0);
$qty = (int)($input['quantity_packs'] ?? 1);

if ($flavor_id < 1 || $qty < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Get product and flavor info
$stmt = $conn->prepare("
    SELECT pf.id as flavor_id, pf.flavor_name, pf.stock_packs, p.id as product_id, p.name as product_name, p.qty_per_pack, p.unit_price
    FROM product_flavors pf
    JOIN products p ON pf.product_id = p.id
    WHERE pf.id = ? AND pf.status = 'active' AND p.status = 'active'
");
$stmt->bind_param("i", $flavor_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Stock check (counted in packs), accounting for what's already in the cart
$stock = (int)$result['stock_packs'];
if ($stock <= 0) {
    echo json_encode(['success' => false, 'message' => 'This flavor is out of stock']);
    exit;
}
$in_cart = 0;
foreach ($_SESSION['cart'] as $ci) {
    if ($ci['product_flavor_id'] == $flavor_id) { $in_cart = (int)$ci['quantity_packs']; break; }
}
if ($in_cart + $qty > $stock) {
    echo json_encode(['success' => false, 'message' => 'Only ' . $stock . ' pack(s) in stock' . ($in_cart > 0 ? '; you already have ' . $in_cart . ' in your cart' : '') . '.']);
    exit;
}

// Check if already in cart - increment quantity
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['product_flavor_id'] == $flavor_id) {
        $item['quantity_packs'] += $qty;
        $found = true;
        break;
    }
}
unset($item);

if (!$found) {
    $_SESSION['cart'][] = [
        'product_flavor_id' => $result['flavor_id'],
        'product_name' => $result['product_name'],
        'flavor_name' => $result['flavor_name'],
        'qty_per_pack' => $result['qty_per_pack'],
        'unit_price' => (float)$result['unit_price'],
        'quantity_packs' => $qty,
    ];
}

echo json_encode([
    'success' => true,
    'message' => 'Added to cart',
    'cart_count' => count($_SESSION['cart'])
]);
