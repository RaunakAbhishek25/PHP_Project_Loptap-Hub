<?php
// ============================================
// FILE: ajax/add_review.php
// PURPOSE: Handle review submission
// ============================================

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Include database
require_once __DIR__ . '/../includes/db.php';

// Set JSON header
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get data
$laptop_id = isset($_POST['laptop_id']) ? (int)$_POST['laptop_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$user_id = $_SESSION['user_id'];

// Validate
if ($laptop_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Please select rating 1-5']);
    exit;
}

if (empty($comment) || strlen($comment) < 3) {
    echo json_encode(['success' => false, 'message' => 'Review must be at least 3 characters']);
    exit;
}

try {
    // Check if already reviewed
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND laptop_id = ?");
    $stmt->execute([$user_id, $laptop_id]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo json_encode(['success' => false, 'message' => 'You already reviewed this product']);
        exit;
    }
    
    // Insert review
    $stmt = $pdo->prepare("
        INSERT INTO reviews (laptop_id, user_id, rating, comment, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $result = $stmt->execute([$laptop_id, $user_id, $rating, $comment]);
    
    if ($result) {
        // Update product rating
        $stmt = $pdo->prepare("
            UPDATE laptops 
            SET rating = (
                SELECT COALESCE(AVG(rating), 0) 
                FROM reviews 
                WHERE laptop_id = ?
            ),
            reviews_count = (
                SELECT COUNT(*) 
                FROM reviews 
                WHERE laptop_id = ?
            )
            WHERE id = ?
        ");
        $stmt->execute([$laptop_id, $laptop_id, $laptop_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Review submitted successfully!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert review']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>