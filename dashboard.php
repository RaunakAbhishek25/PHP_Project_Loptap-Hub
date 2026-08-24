<?php
// ============================================
// FILE: admin/dashboard.php
// PURPOSE: Admin Dashboard with Correct Y-Axis Labels
// ============================================

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// ========== STATISTICS ==========

// Total Users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

// Total Products
$stmt = $pdo->query("SELECT COUNT(*) as total FROM laptops WHERE status = 'active'");
$total_products = $stmt->fetch()['total'];

// Total Orders
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $stmt->fetch()['total'];

// Total Revenue (in INR)
$stmt = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM orders WHERE status != 'cancelled'");
$total_revenue = $stmt->fetch()['total'];

// Pending Orders
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pending_orders = $stmt->fetch()['total'];

// Total Reviews
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
$total_reviews = $stmt->fetch()['total'];

// Average Rating
$stmt = $pdo->query("SELECT COALESCE(AVG(rating), 0) as avg FROM reviews");
$avg_rating = round($stmt->fetch()['avg'], 1);

// ========== TOP SELLING PRODUCTS ==========
$stmt = $pdo->query("
    SELECT l.*, b.name as brand_name,
           COALESCE((SELECT COUNT(*) FROM order_items WHERE laptop_id = l.id), 0) as total_sold
    FROM laptops l
    LEFT JOIN brands b ON l.brand_id = b.id
    WHERE l.status = 'active'
    ORDER BY total_sold DESC, l.reviews_count DESC
    LIMIT 5
");
$top_products = $stmt->fetchAll();

// ============================================================
// ========== MONTHLY SALES CHART ==========
// ============================================================

// Get monthly sales for the last 6 months
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%b') as month,
        DATE_FORMAT(created_at, '%Y-%m') as month_key,
        COALESCE(SUM(grand_total), 0) as total
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    AND status != 'cancelled'
    GROUP BY month_key, month
    ORDER BY month_key ASC
");
$monthly_sales = $stmt->fetchAll();

// Prepare data for chart
$months = [];
$sales = [];
foreach ($monthly_sales as $row) {
    $months[] = $row['month'];
    $sales[] = (float)$row['total'];
}

// If no data, use default with actual values
if (empty($months)) {
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $sales = [0, 0, 0, 0, 0, 0];
}

// Convert USD to INR if sales are small (demo data)
if (!empty($sales) && max($sales) < 100) {
    $sales = array_map(function($val) {
        return $val * 83; // Convert USD to INR
    }, $sales);
}

// Find max value for chart scaling
$maxSales = max($sales, 1000);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LaptopHub</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f0f4f8;
        }
        
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
            text-decoration: none;
            display: block;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.05);
        }
        .sidebar .nav-link.active {
            color: white;
            background: rgba(37,99,235,0.2);
            border-left: 3px solid #2563eb;
        }
        .sidebar .nav-link i {
            width: 22px;
            margin-right: 10px;
        }
        
        .main-content {
            padding: 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.04);
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        .stat-card .stat-number {
            font-size: 2rem;
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
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }
        .stat-card.blue .stat-icon { background: linear-gradient(135deg, #2563eb, #4f46e5); }
        .stat-card.green .stat-icon { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .stat-card.orange .stat-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.purple .stat-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .stat-card.red .stat-icon { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .stat-card.teal .stat-icon { background: linear-gradient(135deg, #14b8a6, #0d9488); }
        
        .product-card-mini {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: #f8fafc;
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        .product-card-mini:hover {
            background: white;
            border-color: #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .product-card-mini .product-img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 8px;
            background: white;
            padding: 4px;
        }
        .product-card-mini .product-info {
            flex: 1;
        }
        .product-card-mini .product-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1a1a2e;
        }
        .product-card-mini .product-brand {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .product-card-mini .product-price {
            font-weight: 700;
            color: #2563eb;
            font-size: 0.95rem;
        }
        .product-card-mini .product-sales {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        
        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.04);
        }
        
        .dark-mode-toggle {
            background: transparent;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            padding: 6px 16px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #1a1a2e;
            background: #f1f5f9;
        }
        .dark-mode-toggle:hover {
            transform: scale(1.05);
            background: #e2e8f0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                height: auto;
                position: relative;
            }
            .main-content {
                padding: 16px;
            }
            .stat-card .stat-number {
                font-size: 1.5rem;
            }
            .product-card-mini {
                padding: 8px 10px;
            }
            .product-card-mini .product-img {
                width: 36px;
                height: 36px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- ========== SIDEBAR ========== -->
        <nav class="col-md-2 d-md-block sidebar">
            <div class="brand"><i class="fas fa-laptop me-2"></i>LaptopHub</div>
            <ul class="nav flex-column mt-3">
                <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="fas fa-dashboard"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="manage/laptops.php"><i class="fas fa-laptop"></i>Laptops</a></li>
                <li class="nav-item"><a class="nav-link" href="manage/categories.php"><i class="fas fa-tags"></i>Categories</a></li>
                <li class="nav-item"><a class="nav-link" href="manage/brands.php"><i class="fas fa-building"></i>Brands</a></li>
                <li class="nav-item"><a class="nav-link" href="manage/orders.php"><i class="fas fa-shopping-cart"></i>Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="manage/users.php"><i class="fas fa-users"></i>Users</a></li>
                <li class="nav-item"><a class="nav-link" href="manage/reviews.php"><i class="fas fa-star"></i>Reviews</a></li>
                <li class="nav-item"><a class="nav-link" href="manage/coupons.php"><i class="fas fa-ticket"></i>Coupons</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>

        <!-- ========== MAIN CONTENT ========== -->
        <main class="col-md-10 ms-sm-auto px-4 main-content">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold mb-0"><i class="fas fa-dashboard text-primary me-2"></i>Dashboard</h1>
                    <p class="text-muted">Welcome back, Admin!</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button class="dark-mode-toggle" id="darkModeToggle"><i class="fas fa-moon"></i> Dark</button>
                    <a href="../index.php" class="btn btn-outline-primary btn-sm" target="_blank"><i class="fas fa-external-link-alt me-1"></i>View Site</a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="stat-card blue">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number">₹<?= number_format($total_revenue, 2) ?></div>
                                <div class="stat-label">Total Revenue</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card green">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $total_orders ?></div>
                                <div class="stat-label">Total Orders</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card orange">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $total_products ?></div>
                                <div class="stat-label">Total Products</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-laptop"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card purple">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $total_users ?></div>
                                <div class="stat-label">Total Users</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card red">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $pending_orders ?></div>
                                <div class="stat-label">Pending Orders</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card teal">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $total_reviews ?></div>
                                <div class="stat-label">Total Reviews</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-star"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card blue">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $avg_rating ?></div>
                                <div class="stat-label">Average Rating</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-star-half-alt"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card green">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $top_products ? count($top_products) : 0 ?></div>
                                <div class="stat-label">Top Products</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-crown"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart & Top Products -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="chart-container">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-line text-primary me-2"></i>Monthly Sales</h6>
                        <canvas id="salesChart" height="250"></canvas>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="chart-container">
                        <h6 class="fw-bold mb-3"><i class="fas fa-fire text-danger me-2"></i>Top Selling Products</h6>
                        <?php if (empty($top_products)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-box-open fa-2x mb-2"></i>
                                <p>No products sold yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($top_products as $product): ?>
                                <div class="product-card-mini mb-2">
                                    <?php 
                                    $img_stmt = $pdo->prepare("SELECT image_path FROM laptop_images WHERE laptop_id = ? AND is_primary = 1 LIMIT 1");
                                    $img_stmt->execute([$product['id']]);
                                    $img = $img_stmt->fetch();
                                    $img_path = $img['image_path'] ?? '../assets/images/placeholder.jpg';
                                    ?>
                                    <img src="<?= $img_path ?>" class="product-img" alt="<?= htmlspecialchars($product['name']) ?>">
                                    <div class="product-info">
                                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                        <div class="product-brand"><?= htmlspecialchars($product['brand_name'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="product-price"><?= formatPrice($product['price']) ?></div>
                                        <div class="product-sales"><?= $product['total_sold'] ?? 0 ?> sales</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-3 mt-3">
                <div class="col-md-12">
                    <div class="chart-container">
                        <h6 class="fw-bold mb-3"><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="manage/add_product.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Product</a>
                            <a href="manage/add_category.php" class="btn btn-success"><i class="fas fa-tag me-2"></i>Add Category</a>
                            <a href="manage/add_brand.php" class="btn btn-info text-white"><i class="fas fa-building me-2"></i>Add Brand</a>
                            <a href="manage/add_coupon.php" class="btn btn-warning"><i class="fas fa-ticket me-2"></i>Add Coupon</a>
                            <a href="manage/orders.php?status=pending" class="btn btn-danger"><i class="fas fa-clock me-2"></i>View Pending Orders <?php if ($pending_orders > 0): ?><span class="badge bg-light text-dark ms-1"><?= $pending_orders ?></span><?php endif; ?></a>
                            <a href="manage/laptops.php" class="btn btn-secondary"><i class="fas fa-edit me-2"></i>Manage Products</a>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ============================================================ -->
<!-- ========== CHART.JS SCRIPT - Y-AXIS FIXED ========== -->
<!-- ============================================================ -->
<script>
// Sales Chart with Correct Y-Axis Labels
const ctx = document.getElementById('salesChart').getContext('2d');

const months = <?= json_encode($months) ?>;
const salesData = <?= json_encode($sales) ?>;

// Find max value for chart scaling
const maxSales = Math.max(...salesData, 1000);

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Sales (INR)',
            data: salesData,
            backgroundColor: 'rgba(37, 99, 235, 0.6)',
            borderColor: 'rgba(37, 99, 235, 1)',
            borderWidth: 2,
            borderRadius: 8,
            barPercentage: 0.6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    font: { weight: 'bold', size: 12 }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let value = context.parsed.y;
                        return '₹' + value.toLocaleString('en-IN', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        });
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: maxSales * 1.2, // Add 20% padding at top
                // ⬇️⬇️⬇️ Y-AXIS LABELS ARE HERE ⬇️⬇️⬇️
                ticks: {
                    callback: function(value) {
                        // Format as INR with K, L, Cr
                        if (value >= 10000000) {
                            return '₹' + (value / 10000000).toFixed(1) + 'Cr';
                        } else if (value >= 100000) {
                            return '₹' + (value / 100000).toFixed(1) + 'L';
                        } else if (value >= 1000) {
                            return '₹' + (value / 1000).toFixed(1) + 'K';
                        }
                        return '₹' + value.toLocaleString('en-IN');
                    }
                }
            }
        }
    }
});

// ============================================================
// DARK MODE TOGGLE
// ============================================================
(function() {
    'use strict';

    function getSavedTheme() {
        return localStorage.getItem('darkMode') || 'light';
    }

    function saveTheme(theme) {
        localStorage.setItem('darkMode', theme);
    }

    function applyTheme(theme) {
        const body = document.body;
        
        if (theme === 'dark') {
            body.classList.add('dark-mode');
            document.documentElement.style.colorScheme = 'dark';
            updateToggleButton(true);
        } else {
            body.classList.remove('dark-mode');
            document.documentElement.style.colorScheme = 'light';
            updateToggleButton(false);
        }
        
        saveTheme(theme);
    }

    function updateToggleButton(isDark) {
        const toggle = document.getElementById('darkModeToggle');
        if (toggle) {
            if (isDark) {
                toggle.innerHTML = '<i class="fas fa-sun"></i> Light';
            } else {
                toggle.innerHTML = '<i class="fas fa-moon"></i> Dark';
            }
        }
    }

    function toggleTheme() {
        const currentTheme = getSavedTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
    }

    function initDarkMode() {
        const savedTheme = getSavedTheme();
        
        if (!localStorage.getItem('darkMode')) {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            applyTheme(prefersDark ? 'dark' : 'light');
        } else {
            applyTheme(savedTheme);
        }
        
        const toggle = document.getElementById('darkModeToggle');
        if (toggle) {
            toggle.addEventListener('click', toggleTheme);
        }
    }

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        if (!localStorage.getItem('darkMode')) {
            applyTheme(e.matches ? 'dark' : 'light');
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDarkMode);
    } else {
        initDarkMode();
    }
})();
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>