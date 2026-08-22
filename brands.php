<?php
// admin/manage/brands.php - Enhanced Brands Management
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';

// Get brands with product count
$brands = $pdo->query("
    SELECT b.*, COUNT(l.id) as product_count 
    FROM brands b 
    LEFT JOIN laptops l ON l.brand_id = b.id AND l.status = 'active' 
    GROUP BY b.id 
    ORDER BY b.name
")->fetchAll();

// Get total products
$stmt = $pdo->query("SELECT COUNT(*) as total FROM laptops WHERE status = 'active'");
$total_products = $stmt->fetch()['total'] ?? 0;

// Brand Logo Colors
function getBrandColor($name) {
    $colors = [
        'Apple' => '#555555',
        'Dell' => '#0078D4',
        'ASUS' => '#00529B',
        'Lenovo' => '#E2231A',
        'HP' => '#0096D6',
        'Acer' => '#83B81A',
        'MSI' => '#FF0000',
        'Samsung' => '#1428A0',
        'Microsoft' => '#F25022'
    ];
    return $colors[$name] ?? '#6b7280';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Brands - LaptopHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ========== GLOBAL ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f0f4f8;
        }
        
        /* ========== SIDEBAR ========== */
        .sidebar {
            background: linear-gradient(180deg, #0f172a, #1a1a2e);
            min-height: 100vh;
            padding: 20px 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar .brand {
            color: white;
            font-size: 1.3rem;
            font-weight: 800;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            text-align: center;
        }
        .sidebar .brand i { color: #60a5fa; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.6);
            padding: 12px 20px;
            margin: 4px 12px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .sidebar .nav-link.active {
            color: white;
            background: rgba(37,99,235,0.2);
            border-left: 3px solid #2563eb;
        }
        .sidebar .nav-link i { width: 22px; margin-right: 10px; }
        
        /* ========== MAIN ========== */
        .main-content { padding: 24px; }
        
        /* ========== STATS CARDS ========== */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.04);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        .stat-card .stat-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1a1a2e;
            line-height: 1.2;
        }
        .stat-card .stat-label {
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        .stat-card.blue .stat-icon { background: #2563eb; }
        .stat-card.green .stat-icon { background: #22c55e; }
        .stat-card.orange .stat-icon { background: #f59e0b; }
        .stat-card.purple .stat-icon { background: #8b5cf6; }
        
        /* ========== TABLE ========== */
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .table th {
            font-weight: 700;
            color: #1a1a2e;
            border-bottom: 2px solid #f1f5f9;
            padding: 12px 8px;
        }
        .table td {
            padding: 12px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .table tr:hover { background: #f8fafc; }
        
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            color: white;
        }
        .brand-badge .brand-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .btn-sm { border-radius: 10px; padding: 6px 12px; }
        .btn-warning { color: white; }
        .btn-warning:hover { color: white; }
        
        /* ========== ANIMATIONS ========== */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeInUp 0.5s ease-out both; }
        .delay-1 { animation-delay: 0.05s; }
        .delay-2 { animation-delay: 0.1s; }
        .delay-3 { animation-delay: 0.15s; }
        .delay-4 { animation-delay: 0.2s; }
        
        @media (max-width: 768px) {
            .sidebar { min-height: auto; height: auto; position: relative; }
            .main-content { padding: 16px; }
            .stat-card .stat-number { font-size: 1.3rem; }
        }
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
                <li class="nav-item"><a class="nav-link" href="laptops.php"><i class="fas fa-laptop"></i>Laptops</a></li>
                <li class="nav-item"><a class="nav-link" href="categories.php"><i class="fas fa-tags"></i>Categories</a></li>
                <li class="nav-item"><a class="nav-link active" href="brands.php"><i class="fas fa-building"></i>Brands</a></li>
                <li class="nav-item"><a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i>Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users"></i>Users</a></li>
                <li class="nav-item"><a class="nav-link" href="reviews.php"><i class="fas fa-star"></i>Reviews</a></li>
                <li class="nav-item"><a class="nav-link" href="coupons.php"><i class="fas fa-ticket"></i>Coupons</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="col-md-10 ms-sm-auto px-4 main-content">
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-in">
                <div>
                    <h1 class="h2 fw-bold mb-0">
                        <i class="fas fa-building text-primary me-2"></i>Manage Brands
                    </h1>
                    <p class="text-muted">Manage your laptop brands</p>
                </div>
                <a href="add_brand.php" class="btn btn-primary rounded-pill">
                    <i class="fas fa-plus me-2"></i>Add Brand
                </a>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 animate-fade-in delay-1">
                    <div class="stat-card blue">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= count($brands) ?></div>
                                <div class="stat-label">Total Brands</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-building"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 animate-fade-in delay-2">
                    <div class="stat-card green">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $total_products ?></div>
                                <div class="stat-label">Total Products</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-laptop"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 animate-fade-in delay-3">
                    <div class="stat-card orange">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $total_products > 0 ? round(($total_products / count($brands))) : 0 ?></div>
                                <div class="stat-label">Avg. Products/Brand</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-chart-simple"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container animate-fade-in delay-4">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Brand</th>
                                <th>Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($brands)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="fas fa-building fa-2x d-block mb-2"></i>
                                        No brands found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($brands as $brand): ?>
                                    <tr>
                                        <td><span class="fw-bold">#<?= $brand['id'] ?></span></td>
                                        <td>
                                            <span class="brand-badge" style="background: <?= getBrandColor($brand['name']) ?>;">
                                                <span class="brand-dot" style="background: <?= getBrandColor($brand['name']) ?>;"></span>
                                                <?= htmlspecialchars($brand['name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary rounded-pill"><?= $brand['product_count'] ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="edit_brand.php?id=<?= $brand['id'] ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   title="Edit Brand">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_brand.php?id=<?= $brand['id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this brand?')" 
                                                   title="Delete Brand">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

</body>
</html>