<?php
// admin/manage/reviews.php - Enhanced Reviews Management
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Filters
$filter_rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$filter_product = isset($_GET['product']) ? (int)$_GET['product'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT r.*, u.fullname, u.email, l.name as laptop_name, l.slug
          FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          JOIN laptops l ON r.laptop_id = l.id";
$params = [];

$where_conditions = [];

if ($filter_rating > 0) {
    $where_conditions[] = "r.rating = ?";
    $params[] = $filter_rating;
}

if ($filter_product > 0) {
    $where_conditions[] = "r.laptop_id = ?";
    $params[] = $filter_product;
}

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $where_conditions[] = "(u.fullname LIKE ? OR u.email LIKE ? OR l.name LIKE ? OR r.comment LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";

// Count total
$count_query = str_replace("LIMIT ? OFFSET ?", "", $query);
$count_params = $params;

try {
    $stmt = $pdo->prepare($query);
    $idx = 1;
    foreach ($params as $param) {
        $stmt->bindValue($idx, $param);
        $idx++;
    }
    $stmt->bindValue($idx, $limit, PDO::PARAM_INT);
    $idx++;
    $stmt->bindValue($idx, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll();
} catch (Exception $e) {
    $reviews = [];
}

try {
    $stmt = $pdo->prepare($count_query);
    $idx = 1;
    foreach ($count_params as $param) {
        $stmt->bindValue($idx, $param);
        $idx++;
    }
    $stmt->execute();
    $total = $stmt->rowCount();
} catch (Exception $e) {
    $total = 0;
}
$total_pages = ceil($total / $limit);

// Stats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
$total_reviews = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT AVG(rating) as avg FROM reviews");
$avg_rating = round($stmt->fetch()['avg'] ?? 0, 1);

$stmt = $pdo->query("SELECT rating, COUNT(*) as count FROM reviews GROUP BY rating ORDER BY rating DESC");
$rating_counts = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$weekly_reviews = $stmt->fetch()['total'];

// Get products for filter
$products = $pdo->query("SELECT id, name FROM laptops WHERE status = 'active' ORDER BY name")->fetchAll();

// Helper functions
function getStarRating($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star text-warning"></i>';
        } else {
            $stars .= '<i class="fas fa-star text-muted"></i>';
        }
    }
    return $stars;
}

function getRatingColor($rating) {
    if ($rating >= 4) return 'success';
    if ($rating >= 3) return 'primary';
    if ($rating >= 2) return 'warning';
    return 'danger';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - LaptopHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
        }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .sidebar .nav-link.active {
            color: white;
            background: rgba(37,99,235,0.2);
            border-left: 3px solid #2563eb;
        }
        .sidebar .nav-link i { width: 22px; margin-right: 10px; }
        
        .main-content { padding: 24px; }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.04);
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
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
        .stat-card.teal .stat-icon { background: #14b8a6; }
        
        /* Rating Distribution */
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 2px 0;
        }
        .rating-bar .bar-track {
            flex: 1;
            height: 6px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        .rating-bar .bar-fill {
            height: 100%;
            border-radius: 10px;
            background: #2563eb;
            transition: width 1s ease;
        }
        .rating-bar .bar-fill.gold { background: #f59e0b; }
        .rating-bar .bar-fill.blue { background: #2563eb; }
        .rating-bar .bar-fill.green { background: #22c55e; }
        .rating-bar .bar-fill.red { background: #ef4444; }
        
        /* Table */
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
        
        .review-comment {
            max-width: 300px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .filter-btn {
            padding: 6px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            border: 2px solid #e2e8f0;
            background: white;
            color: #1a1a2e;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .filter-btn:hover { border-color: #2563eb; color: #2563eb; }
        .filter-btn.active { background: #2563eb; border-color: #2563eb; color: white; }
        .filter-btn .badge-count {
            background: rgba(0,0,0,0.1);
            padding: 1px 8px;
            border-radius: 50px;
            font-size: 0.7rem;
            margin-left: 4px;
        }
        .filter-btn.active .badge-count { background: rgba(255,255,255,0.2); }
        
        .btn-sm { border-radius: 10px; padding: 6px 12px; }
        
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
            .filter-btn { font-size: 0.75rem; padding: 4px 12px; }
            .review-comment { max-width: 150px; }
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
                <li class="nav-item"><a class="nav-link" href="brands.php"><i class="fas fa-building"></i>Brands</a></li>
                <li class="nav-item"><a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i>Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users"></i>Users</a></li>
                <li class="nav-item"><a class="nav-link active" href="reviews.php"><i class="fas fa-star"></i>Reviews</a></li>
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
                        <i class="fas fa-star text-warning me-2"></i>Manage Reviews
                    </h1>
                    <p class="text-muted">View and manage all customer reviews</p>
                </div>
                <div>
                    <span class="badge bg-secondary rounded-pill">Total: <?= $total_reviews ?></span>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6 animate-fade-in delay-1">
                    <div class="stat-card blue">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $total_reviews ?></div>
                                <div class="stat-label">Total Reviews</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-star"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-2">
                    <div class="stat-card green">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $avg_rating ?></div>
                                <div class="stat-label">Average Rating</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-star-half-alt"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-3">
                    <div class="stat-card orange">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $weekly_reviews ?></div>
                                <div class="stat-label">This Week</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-4">
                    <div class="stat-card purple">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $rating_counts[0]['count'] ?? 0 ?></div>
                                <div class="stat-label">5 Star Reviews</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-star"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rating Distribution -->
            <div class="bg-white p-4 rounded-16 mb-4" style="border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.04);">
                <h6 class="fw-bold mb-3">Rating Distribution</h6>
                <div class="row">
                    <?php 
                    $rating_counts_array = [];
                    foreach ($rating_counts as $rc) {
                        $rating_counts_array[$rc['rating']] = $rc['count'];
                    }
                    for ($i = 5; $i >= 1; $i--): 
                        $count = $rating_counts_array[$i] ?? 0;
                        $percentage = $total_reviews > 0 ? round(($count / $total_reviews) * 100) : 0;
                        $bar_color = $i >= 4 ? 'gold' : ($i >= 3 ? 'blue' : ($i >= 2 ? 'green' : 'red'));
                    ?>
                        <div class="col-md-6">
                            <div class="rating-bar">
                                <span class="fw-semibold" style="min-width: 80px;"><?= $i ?> Star</span>
                                <div class="bar-track">
                                    <div class="bar-fill <?= $bar_color ?>" style="width: <?= $percentage ?>%;"></div>
                                </div>
                                <span class="text-muted small" style="min-width: 50px;"><?= $count ?></span>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Filters -->
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="?rating=0" class="filter-btn <?= $filter_rating == 0 ? 'active' : '' ?>">
                    All <span class="badge-count"><?= $total_reviews ?></span>
                </a>
                <?php for ($i = 5; $i >= 1; $i--): 
                    $count = $rating_counts_array[$i] ?? 0;
                ?>
                    <a href="?rating=<?= $i ?>" 
                       class="filter-btn <?= $filter_rating == $i ? 'active' : '' ?>">
                        <?= $i ?> ★ <span class="badge-count"><?= $count ?></span>
                    </a>
                <?php endfor; ?>
            </div>

            <!-- Search & Product Filter -->
            <form method="GET" class="mb-3 d-flex flex-wrap gap-2">
                <div class="input-group" style="max-width: 300px;">
                    <input type="hidden" name="rating" value="<?= $filter_rating ?>">
                    <input type="text" name="search" class="form-control" placeholder="Search reviews..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </div>
                <select name="product" class="form-select" style="max-width: 200px;" onchange="this.form.submit()">
                    <option value="0">All Products</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $filter_product == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <a href="reviews.php" class="btn btn-outline-secondary">Reset</a>
            </form>

            <!-- Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>User</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reviews)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-star fa-2x d-block mb-2"></i>
                                        No reviews found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reviews as $review): ?>
                                    <tr>
                                        <td><span class="fw-bold">#<?= $review['id'] ?></span></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/product.php?id=<?= $review['laptop_id'] ?>" 
                                               target="_blank" class="text-decoration-none">
                                                <?= htmlspecialchars($review['laptop_name']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($review['fullname']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($review['email']) ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column align-items-start">
                                                <div><?= getStarRating($review['rating']) ?></div>
                                                <span class="badge bg-<?= getRatingColor($review['rating']) ?> mt-1">
                                                    <?= $review['rating'] ?> / 5
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="review-comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></div>
                                        </td>
                                        <td>
                                            <div><?= date('M d, Y', strtotime($review['created_at'])) ?></div>
                                            <div class="text-muted small"><?= date('h:i A', strtotime($review['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="<?= BASE_URL ?>/product.php?id=<?= $review['laptop_id'] ?>#reviews" 
                                                   class="btn btn-sm btn-info" title="View on Site" target="_blank">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <a href="delete_review.php?id=<?= $review['id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this review?')" 
                                                   title="Delete Review">
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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&rating=<?= $filter_rating ?>&product=<?= $filter_product ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&rating=<?= $filter_rating ?>&product=<?= $filter_product ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&rating=<?= $filter_rating ?>&product=<?= $filter_product ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        </main>
    </div>
</div>

</body>
</html>