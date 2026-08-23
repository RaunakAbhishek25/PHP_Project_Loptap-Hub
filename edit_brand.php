<?php
// admin/manage/edit_brand.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
$stmt->execute([$id]);
$brand = $stmt->fetch();

if (!$brand) {
    header('Location: brands.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    
    if (empty($name)) {
        $error = 'Brand name is required';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE brands SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            $success = 'Brand updated successfully!';
            $brand['name'] = $name;
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
    <title>Edit Brand</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-md-block bg-dark vh-100 p-3">
            <h5 class="text-white">LaptopHub Admin</h5>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white-50" href="../dashboard.php"><i class="fas fa-dashboard me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="brands.php"><i class="fas fa-building me-2"></i>Brands</a></li>
                <li class="nav-item"><a class="nav-link text-white-50" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Edit Brand</h1>
                <a href="brands.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>
            
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            
            <form method="POST">
                <div class="card"><div class="card-body">
                    <div class="mb-3"><label class="form-label">Brand Name *</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($brand['name']) ?>" required></div>
                    <button type="submit" class="btn btn-primary">Update Brand</button>
                </div></div>
            </form>
        </main>
    </div>
</div>
</body>
</html>