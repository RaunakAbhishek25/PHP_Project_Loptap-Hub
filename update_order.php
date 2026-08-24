<?php
// admin/manage/update_order.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    header('Location: orders.php');
    exit;
}

$stmt = $pdo->prepare("SELECT o.*, u.fullname, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4>Update Order Status</h4>
                    <hr>
                    <p><strong>Order #:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                    <p><strong>Customer:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
                    <p><strong>Total:</strong> $<?= number_format($order['grand_total'], 2) ?></p>
                    <p><strong>Current Status:</strong> <span class="badge bg-<?= $order['status'] == 'delivered' ? 'success' : 'warning' ?>"><?= ucfirst($order['status']) ?></span></p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Update Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Status</button>
                        <a href="orders.php" class="btn btn-secondary w-100 mt-2">Back</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>