<?php
// admin/manage/add_laptop.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$brands = $pdo->query("SELECT * FROM brands ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $slug = sanitize(strtolower(str_replace(' ', '-', $name)));
    $brand_id = $_POST['brand_id'] ?: null;
    $category_id = $_POST['category_id'] ?: null;
    $price = floatval($_POST['price']);
    $old_price = $_POST['old_price'] ? floatval($_POST['old_price']) : null;
    $description = sanitize($_POST['description']);
    $processor = sanitize($_POST['processor']);
    $ram = sanitize($_POST['ram']);
    $storage = sanitize($_POST['storage']);
    $graphics = sanitize($_POST['graphics']);
    $os = sanitize($_POST['os']);
    $screen_size = sanitize($_POST['screen_size']);
    $stock = intval($_POST['stock']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO laptops (name, slug, brand_id, category_id, price, old_price, description, processor, ram, storage, graphics, os, screen_size, stock, is_featured, is_bestseller, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $brand_id, $category_id, $price, $old_price, $description, $processor, $ram, $storage, $graphics, $os, $screen_size, $stock, $is_featured, $is_bestseller, $status]);
        $laptop_id = $pdo->lastInsertId();
        
        if (!empty($_FILES['images']['name'][0])) {
            $target_dir = '../../uploads/laptops/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name)) {
                    $filename = time() . '_' . basename($_FILES['images']['name'][$key]);
                    if (move_uploaded_file($tmp_name, $target_dir . $filename)) {
                        $is_primary = ($key == 0) ? 1 : 0;
                        $stmt = $pdo->prepare("INSERT INTO laptop_images (laptop_id, image_path, is_primary) VALUES (?, ?, ?)");
                        $stmt->execute([$laptop_id, 'uploads/laptops/' . $filename, $is_primary]);
                    }
                }
            }
        }
        $success = 'Laptop added successfully!';
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Laptop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-md-block bg-dark vh-100 p-3">
            <h5 class="text-white">LaptopHub Admin</h5>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white-50" href="../dashboard.php"><i class="fas fa-dashboard me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="laptops.php"><i class="fas fa-laptop me-2"></i>Laptops</a></li>
                <li class="nav-item"><a class="nav-link text-white-50" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Add New Laptop</h1>
                <a href="laptops.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>
            
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-3"><div class="card-body">
                            <h5>Basic Information</h5>
                            <div class="mb-3"><label class="form-label">Product Name *</label><input type="text" name="name" class="form-control" required></div>
                            <div class="row">
                                <div class="col-md-6"><label class="form-label">Brand</label><select name="brand_id" class="form-select"><option value="">Select Brand</option><?php foreach ($brands as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-6"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="">Select Category</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
                            </div>
                        </div></div>
                        
                        <div class="card mb-3"><div class="card-body">
                            <h5>Pricing & Stock</h5>
                            <div class="row">
                                <div class="col-md-4"><label class="form-label">Price *</label><input type="number" name="price" class="form-control" step="0.01" required></div>
                                <div class="col-md-4"><label class="form-label">Old Price</label><input type="number" name="old_price" class="form-control" step="0.01"></div>
                                <div class="col-md-4"><label class="form-label">Stock *</label><input type="number" name="stock" class="form-control" required></div>
                            </div>
                        </div></div>
                        
                        <div class="card mb-3"><div class="card-body">
                            <h5>Specifications</h5>
                            <div class="row">
                                <div class="col-md-4"><label class="form-label">Processor</label><input type="text" name="processor" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">RAM</label><input type="text" name="ram" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">Storage</label><input type="text" name="storage" class="form-control"></div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4"><label class="form-label">Graphics</label><input type="text" name="graphics" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">OS</label><input type="text" name="os" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">Screen Size</label><input type="text" name="screen_size" class="form-control"></div>
                            </div>
                        </div></div>
                        
                        <div class="card mb-3"><div class="card-body">
                            <h5>Description</h5>
                            <textarea name="description" class="form-control" rows="4"></textarea>
                        </div></div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card mb-3"><div class="card-body">
                            <h5>Images</h5>
                            <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                            <small class="text-muted">Upload multiple images (first will be primary)</small>
                        </div></div>
                        
                        <div class="card mb-3"><div class="card-body">
                            <h5>Status</h5>
                            <select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                        </div></div>
                        
                        <div class="card mb-3"><div class="card-body">
                            <h5>Features</h5>
                            <div class="form-check"><input type="checkbox" name="is_featured" class="form-check-input"><label class="form-check-label">Featured Product</label></div>
                            <div class="form-check"><input type="checkbox" name="is_bestseller" class="form-check-input"><label class="form-check-label">Best Seller</label></div>
                        </div></div>
                        
                        <button type="submit" class="btn btn-primary w-100">Add Laptop</button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>
</body>
</html>