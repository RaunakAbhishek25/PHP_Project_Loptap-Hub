<?php
// ajax/add_to_wishlist.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$laptop_id = $data['laptop_id'] ?? 0;

if (!$laptop_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Check if already in wishlist
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND laptop_id = ?");
    $stmt->execute([$user_id, $laptop_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Already in wishlist']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, laptop_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $laptop_id]);
    
    echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist']);
}
?>