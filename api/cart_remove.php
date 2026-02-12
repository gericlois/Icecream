<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$index = (int)($input['cart_index'] ?? -1);

if (isset($_SESSION['cart'][$index])) {
    array_splice($_SESSION['cart'], $index, 1);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
}

echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
