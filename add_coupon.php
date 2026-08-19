<?php
// admin/manage/add_coupon.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(sanitize($_POST['code']));
    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $min_order_amount = floatval($_POST['min_order_amount']);
    $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
    $valid_from = $_POST['valid_from'];
    $valid_to = $_POST['valid_to'];
    $usage_limit = intval($_POST['usage_limit']);
    $status = $_POST['status'];
    
    if (empty($code) || $discount_value <= 0) {
        $error = 'Please fill all required fields';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_discount, valid_from, valid_to, usage_limit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, $max_discount, $valid_from, $valid_to, $usage_limit, $status]);
            $success = 'Coupon added successfully!';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Coupon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-md-block bg-dark vh-100 p-3">
            <h5 class="text-white">LaptopHub Admin</h5>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white-50" href="../dashboard.php"><i class="fas fa-dashboard me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="coupons.php"><i class="fas fa-ticket me-2"></i>Coupons</a></li>
                <li class="nav-item"><a class="nav-link text-white-50" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Add Coupon</h1>
                <a href="coupons.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>
            
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            
            <form method="POST">
                <div class="card"><div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Coupon Code *</label><input type="text" name="code" class="form-control" placeholder="SAVE20" required></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Discount Type</label>
                                <select name="discount_type" class="form-select">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed ($)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Discount Value *</label><input type="number" name="discount_value" class="form-control" step="0.01" required></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Min Order Amount</label><input type="number" name="min_order_amount" class="form-control" step="0.01" value="0"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Max Discount</label><input type="number" name="max_discount" class="form-control" step="0.01" placeholder="Optional"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Usage Limit</label><input type="number" name="usage_limit" class="form-control" value="1"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Valid From</label><input type="date" name="valid_from" class="form-control" required></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Valid To</label><input type="date" name="valid_to" class="form-control" required></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add Coupon</button>
                </div></div>
            </form>
        </main>
    </div>
</div>
</body>
</html>