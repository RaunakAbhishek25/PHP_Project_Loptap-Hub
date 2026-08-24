<?php
// admin/manage/laptops.php - Manage Laptops with INR Price
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php'; // ✅ formatPrice function के लिए

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

try {
    $stmt = $pdo->prepare("
        SELECT l.*, b.name as brand_name, c.name as category_name 
        FROM laptops l 
        LEFT JOIN brands b ON l.brand_id = b.id 
        LEFT JOIN categories c ON l.category_id = c.id 
        WHERE l.name LIKE ? OR b.name LIKE ? 
        ORDER BY l.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $stmt->bindValue(1, $search, PDO::PARAM_STR);
    $stmt->bindValue(2, $search, PDO::PARAM_STR);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $laptops = $stmt->fetchAll();
} catch (Exception $e) {
    $laptops = [];
    $error_msg = $e->getMessage();
}

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM laptops l 
        LEFT JOIN brands b ON l.brand_id = b.id 
        WHERE l.name LIKE ? OR b.name LIKE ?
    ");
    $stmt->execute([$search, $search]);
    $total = $stmt->fetch()['total'] ?? 0;
    $total_pages = ceil($total / $limit);
} catch (Exception $e) {
    $total = 0;
    $total_pages = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Laptops - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }
        .sidebar { background: linear-gradient(180deg, #0f172a, #1a1a2e); min-height: 100vh; padding: 20px 0; }
        .sidebar .brand { color: white; font-size: 1.3rem; font-weight: 800; padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; }
        .sidebar .brand i { color: #60a5fa; }
        .sidebar .nav-link { color: rgba(255,255,255,0.6); padding: 12px 20px; margin: 4px 12px; border-radius: 12px; transition: all 0.3s ease; font-weight: 500; }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .sidebar .nav-link.active { color: white; background: rgba(37,99,235,0.2); border-left: 3px solid #2563eb; }
        .sidebar .nav-link i { width: 22px; margin-right: 10px; }
        .main-content { padding: 24px; }
        .table th { font-weight: 600; color: #1a1a2e; }
        .btn-sm { border-radius: 10px; }
        .price-inr { font-weight: 600; color: #1a1a2e; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-md-block sidebar">
            <div class="brand"><i class="fas fa-laptop me-2"></i>LaptopHub</div>
            <ul class="nav flex-column mt-3">
                <li class="nav-item"><a class="nav-link" href="../dashboard.php"><i class="fas fa-dashboard"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="laptops.php"><i class="fas fa-laptop"></i>Laptops</a></li>
                <li class="nav-item"><a class="nav-link" href="categories.php"><i class="fas fa-tags"></i>Categories</a></li>
                <li class="nav-item"><a class="nav-link" href="brands.php"><i class="fas fa-building"></i>Brands</a></li>
                <li class="nav-item"><a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i>Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users"></i>Users</a></li>
                <li class="nav-item"><a class="nav-link" href="reviews.php"><i class="fas fa-star"></i>Reviews</a></li>
                <li class="nav-item"><a class="nav-link" href="coupons.php"><i class="fas fa-ticket"></i>Coupons</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>
        
        <!-- Main -->
        <main class="col-md-10 ms-sm-auto px-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h3 fw-bold">Manage Laptops</h1>
                <a href="add_laptop.php" class="btn btn-primary btn-sm rounded-pill">
                    <i class="fas fa-plus me-2"></i>Add Laptop
                </a>
            </div>
            
            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>
            
            <!-- Search -->
            <form method="GET" class="mb-3">
                <div class="input-group" style="max-width: 400px;">
                    <input type="text" name="search" class="form-control" placeholder="Search laptops..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </div>
            </form>
            
            <!-- Table -->
            <div class="bg-white rounded-4 shadow-sm p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Brand</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($laptops)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-box-open fa-2x d-block mb-2"></i>
                                        No laptops found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($laptops as $laptop): ?>
                                <tr>
                                    <td><?= $laptop['id'] ?></td>
                                    <td>
                                        <?php 
                                        $img_stmt = $pdo->prepare("SELECT image_path FROM laptop_images WHERE laptop_id = ? AND is_primary = 1 LIMIT 1");
                                        $img_stmt->execute([$laptop['id']]);
                                        $img = $img_stmt->fetch();
                                        ?>
                                        <img src="<?= '../../' . ($img['image_path'] ?? 'assets/images/placeholder.jpg') ?>" 
                                             style="width:50px;height:50px;object-fit:cover;border-radius:10px;">
                                    </td>
                                    <td><strong><?= htmlspecialchars($laptop['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($laptop['brand_name'] ?? '-') ?></td>
                                    <td>
                                        <!-- ✅ INR Price -->
                                        <span class="price-inr"><?php echo formatPrice($laptop['price']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($laptop['stock'] < 5 && $laptop['stock'] > 0): ?>
                                            <span class="badge bg-warning text-dark"><?= $laptop['stock'] ?></span>
                                        <?php elseif ($laptop['stock'] == 0): ?>
                                            <span class="badge bg-danger"><?= $laptop['stock'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?= $laptop['stock'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $laptop['status'] == 'active' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($laptop['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_laptop.php?id=<?= $laptop['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_laptop.php?id=<?= $laptop['id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this laptop?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>
</body>
</html>