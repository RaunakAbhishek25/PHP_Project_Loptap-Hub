<?php
// ============================================
// FILE: includes/db.php
// PURPOSE: Database connection
// ============================================

// Database configuration
$host = 'localhost';
$dbname = 'phpdbuh';  // Your database name
$username = 'root';
$password = '';  // Your MySQL password (default is empty for Laragon)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>