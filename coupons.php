<?php
// admin/manage/coupons.php - Enhanced Coupons Management
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT * FROM coupons";
$params = [];
$where_conditions = [];

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $where_conditions[] = "(code LIKE ?)";
    $params[] = $search_term;
}

if ($status_filter != 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($type_filter != 'all') {
    $where_conditions[] = "discount_type = ?";
    $params[] = $type_filter;
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

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
    $coupons = $stmt->fetchAll();
} catch (Exception $e) {
    $coupons = [];
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
$stmt = $pdo->query("SELECT COUNT(*) as total FROM coupons");
$total_coupons = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM coupons WHERE status = 'active'");
$active_coupons = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM coupons WHERE status = 'inactive'");
$inactive_coupons = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM coupons WHERE valid_to < CURDATE() AND status = 'active'");
$expired_coupons = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM coupons WHERE used_count >= usage_limit AND status = 'active'");
$used_coupons = $stmt->fetch()['total'];

// Get coupon usage stats
$stmt = $pdo->query("SELECT SUM(used_count) as total_used FROM coupons");
$total_used = $stmt->fetch()['total_used'] ?? 0;

$stmt = $pdo->query("SELECT SUM(usage_limit) as total_limit FROM coupons");
$total_limit = $stmt->fetch()['total_limit'] ?? 0;

// Helper functions
function getCouponStatusBadge($status) {
    if ($status == 'active') {
        return '<span class="badge-status active"><i class="fas fa-check-circle"></i> Active</span>';
    } else {
        return '<span class="badge-status inactive"><i class="fas fa-times-circle"></i> Inactive</span>';
    }
}

function getCouponTypeBadge($type) {
    if ($type == 'percentage') {
        return '<span class="badge bg-primary">% Percentage</span>';
    } else {
        return '<span class="badge bg-success">₹ Fixed</span>';
    }
}

function isCouponExpired($valid_to) {
    return strtotime($valid_to) < time();
}

function isCouponUsedUp($used_count, $usage_limit) {
    return $used_count >= $usage_limit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coupons - LaptopHub Admin</title>
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
        .stat-card.red .stat-icon { background: #ef4444; }
        .stat-card.teal .stat-icon { background: #14b8a6; }
        
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
        
        .coupon-code {
            font-family: 'Courier New', monospace;
            font-weight: 800;
            font-size: 1rem;
            letter-spacing: 1px;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 6px;
        }
        
        .badge-status {
            padding: 4px 12px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .badge-status.active { background: #dcfce7; color: #16a34a; }
        .badge-status.active i { margin-right: 4px; }
        .badge-status.inactive { background: #fee2e2; color: #dc2626; }
        .badge-status.inactive i { margin-right: 4px; }
        .badge-status.expired { background: #fef3c7; color: #d97706; }
        .badge-status.expired i { margin-right: 4px; }
        .badge-status.used { background: #dbeafe; color: #2563eb; }
        .badge-status.used i { margin-right: 4px; }
        
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
                <li class="nav-item"><a class="nav-link" href="reviews.php"><i class="fas fa-star"></i>Reviews</a></li>
                <li class="nav-item"><a class="nav-link active" href="coupons.php"><i class="fas fa-ticket"></i>Coupons</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="col-md-10 ms-sm-auto px-4 main-content">
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-in">
                <div>
                    <h1 class="h2 fw-bold mb-0">
                        <i class="fas fa-ticket text-primary me-2"></i>Manage Coupons
                    </h1>
                    <p class="text-muted">Create and manage discount coupons</p>
                </div>
                <a href="add_coupon.php" class="btn btn-primary rounded-pill">
                    <i class="fas fa-plus me-2"></i>Add Coupon
                </a>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6 animate-fade-in delay-1">
                    <div class="stat-card blue">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $total_coupons ?></div>
                                <div class="stat-label">Total Coupons</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-ticket"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-2">
                    <div class="stat-card green">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $active_coupons ?></div>
                                <div class="stat-label">Active</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-3">
                    <div class="stat-card orange">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $total_used ?></div>
                                <div class="stat-label">Total Used</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-4">
                    <div class="stat-card red">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $expired_coupons + $used_coupons ?></div>
                                <div class="stat-label">Expired/Used</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="?status=all" class="filter-btn <?= $status_filter == 'all' ? 'active' : '' ?>">
                    All <span class="badge-count"><?= $total_coupons ?></span>
                </a>
                <a href="?status=active" class="filter-btn <?= $status_filter == 'active' ? 'active' : '' ?>">
                    <i class="fas fa-check-circle"></i> Active <span class="badge-count"><?= $active_coupons ?></span>
                </a>
                <a href="?status=inactive" class="filter-btn <?= $status_filter == 'inactive' ? 'active' : '' ?>">
                    <i class="fas fa-times-circle"></i> Inactive <span class="badge-count"><?= $inactive_coupons ?></span>
                </a>
                <a href="?type=percentage" class="filter-btn <?= $type_filter == 'percentage' ? 'active' : '' ?>">
                    <i class="fas fa-percent"></i> Percentage
                </a>
                <a href="?type=fixed" class="filter-btn <?= $type_filter == 'fixed' ? 'active' : '' ?>">
                    <i class="fas fa-rupee-sign"></i> Fixed
                </a>
            </div>

            <!-- Search -->
            <form method="GET" class="mb-3">
                <div class="input-group" style="max-width: 350px;">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type_filter) ?>">
                    <input type="text" name="search" class="form-control" placeholder="Search coupons..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </div>
            </form>

            <!-- Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Min Order</th>
                                <th>Usage</th>
                                <th>Valid</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="fas fa-ticket fa-2x d-block mb-2"></i>
                                        No coupons found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $coupon): 
                                    $is_expired = isCouponExpired($coupon['valid_to']);
                                    $is_used_up = isCouponUsedUp($coupon['used_count'], $coupon['usage_limit']);
                                    $status_class = 'active';
                                    $status_text = 'Active';
                                    if ($is_expired) { $status_class = 'expired'; $status_text = 'Expired'; }
                                    if ($is_used_up && !$is_expired) { $status_class = 'used'; $status_text = 'Used Up'; }
                                    if ($coupon['status'] == 'inactive') { $status_class = 'inactive'; $status_text = 'Inactive'; }
                                ?>
                                    <tr>
                                        <td><span class="fw-bold">#<?= $coupon['id'] ?></span></td>
                                        <td>
                                            <span class="coupon-code"><?= htmlspecialchars($coupon['code']) ?></span>
                                        </td>
                                        <td><?= getCouponTypeBadge($coupon['discount_type']) ?></td>
                                        <td>
                                            <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                                <span class="fw-bold"><?= $coupon['discount_value'] ?>%</span>
                                            <?php else: ?>
                                                <span class="fw-bold">₹<?= number_format($coupon['discount_value'], 2) ?></span>
                                            <?php endif; ?>
                                            <?php if ($coupon['max_discount']): ?>
                                                <br><small class="text-muted">Max: ₹<?= number_format($coupon['max_discount'], 2) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>₹<?= number_format($coupon['min_order_amount'], 2) ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span><?= $coupon['used_count'] ?> / <?= $coupon['usage_limit'] ?></span>
                                                <div class="progress" style="height: 4px; width: 80px;">
                                                    <div class="progress-bar bg-primary" style="width: <?= ($coupon['usage_limit'] > 0) ? ($coupon['used_count'] / $coupon['usage_limit']) * 100 : 0 ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?= date('M d', strtotime($coupon['valid_from'])) ?> - <?= date('M d', strtotime($coupon['valid_to'])) ?></div>
                                            <?php if ($is_expired): ?>
                                                <span class="text-danger small"><i class="fas fa-exclamation-circle"></i> Expired</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $status_class ?>">
                                                <i class="fas <?= $status_class == 'active' ? 'fa-check-circle' : ($status_class == 'inactive' ? 'fa-times-circle' : ($status_class == 'expired' ? 'fa-clock' : 'fa-check')) ?>"></i>
                                                <?= $status_text ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="edit_coupon.php?id=<?= $coupon['id'] ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit Coupon">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-info" 
                                                        title="View Usage" 
                                                        onclick="showUsage(<?= $coupon['id'] ?>, '<?= htmlspecialchars($coupon['code']) ?>')">
                                                    <i class="fas fa-chart-simple"></i>
                                                </button>
                                                <a href="delete_coupon.php?id=<?= $coupon['id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this coupon?')" 
                                                   title="Delete Coupon">
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
                                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&type=<?= $type_filter ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&type=<?= $type_filter ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&type=<?= $type_filter ?>&search=<?= urlencode($search) ?>">
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

<script>
function showUsage(couponId, code) {
    alert('Usage details for coupon: ' + code + '\n\nClick "Edit" to view full details.');
    // You can extend this to show a modal with usage details
}
</script>

</body>
</html>