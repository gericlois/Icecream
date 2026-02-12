<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$index = (int)($input['cart_index'] ?? -1);
$qty = (int)($input['quantity_packs'] ?? 0);

if (!isset($_SESSION['cart'][$index])) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

if ($qty < 1) {
    array_splice($_SESSION['cart'], $index, 1);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
} else {
    $_SESSION['cart'][$index]['quantity_packs'] = $qty;
}

echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
