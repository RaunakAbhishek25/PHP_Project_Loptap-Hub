<?php
// ajax/add_to_cart.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$laptop_id = $data['laptop_id'] ?? 0;
$quantity = $data['quantity'] ?? 1;

if (!$laptop_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check stock
$stmt = $pdo->prepare("SELECT stock FROM laptops WHERE id = ? AND status = 'active'");
$stmt->execute([$laptop_id]);
$product = $stmt->fetch();

if (!$product || $product['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
    exit;
}

$success = addToCart($user_id, $laptop_id, $quantity);

echo json_encode(['success' => $success, 'message' => $success ? 'Added to cart' : 'Error adding to cart']);
?>