<?php
// ajax/validate_coupon.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$code = $data['coupon'] ?? '';
$subtotal = $data['subtotal'] ?? 0;

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter coupon code']);
    exit;
}

$result = validateCoupon($code, $subtotal);

if ($result) {
    echo json_encode([
        'success' => true, 
        'message' => 'Coupon applied! You saved $' . number_format($result['discount'], 2),
        'discount' => $result['discount']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon']);
}
?>