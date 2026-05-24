<?php
// AJAX endpoint for auto-saving inventory stock + low-stock threshold (admin only).
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in() || current_role() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$flavor_id   = (int)($input['flavor_id'] ?? 0);
$stock_value = max(0, (int)($input['stock_value'] ?? 0));
$threshold   = max(0, (int)($input['threshold'] ?? 0));
$notes       = trim($input['notes'] ?? '');

$fl = $conn->query("SELECT id FROM product_flavors WHERE id = $flavor_id")->fetch_assoc();
if (!$fl) {
    echo json_encode(['success' => false, 'message' => 'Flavor not found']);
    exit;
}

$stmt = $conn->prepare("UPDATE product_flavors SET low_stock_threshold = ? WHERE id = ?");
$stmt->bind_param("ii", $threshold, $flavor_id);
$stmt->execute();
$stmt->close();

$ok = set_stock($conn, $flavor_id, $stock_value, $notes !== '' ? $notes : null, current_user_id());
if ($ok === false) {
    echo json_encode(['success' => false, 'message' => 'Could not save stock']);
    exit;
}

// Badge state mirrors admin/inventory.php
if ($stock_value <= 0) {
    $badge_class = 'bg-gradient-danger';
    $label = 'Out (0)';
} elseif ($threshold > 0 && $stock_value <= $threshold) {
    $badge_class = 'bg-gradient-warning';
    $label = 'Low (' . $stock_value . ')';
} else {
    $badge_class = 'bg-gradient-success';
    $label = $stock_value . ' packs';
}

echo json_encode([
    'success'     => true,
    'stock'       => $stock_value,
    'threshold'   => $threshold,
    'badge_class' => $badge_class,
    'label'       => $label,
]);
