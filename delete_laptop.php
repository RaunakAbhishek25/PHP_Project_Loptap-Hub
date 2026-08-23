<?php
// admin/manage/delete_laptop.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM laptops WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: laptops.php');
exit;
?>