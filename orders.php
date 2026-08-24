<?php
// admin/manage/orders.php - Enhanced Orders Management
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT o.*, u.fullname, u.email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id";
$params = [];

if ($status_filter != 'all') {
    $query .= " WHERE o.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    if ($status_filter != 'all') {
        $query .= " AND (o.order_number LIKE ? OR u.fullname LIKE ? OR u.email LIKE ?)";
    } else {
        $query .= " WHERE (o.order_number LIKE ? OR u.fullname LIKE ? OR u.email LIKE ?)";
    }
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";

// Count total
$count_query = str_replace("LIMIT ? OFFSET ?", "", $query);
$count_params = $params;

// Execute
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
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
}

// Count total
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

// Status counts
$status_counts = [];
$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
foreach ($statuses as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = ?");
    $stmt->execute([$status]);
    $status_counts[$status] = $stmt->fetch()['count'] ?? 0;
}
$total_orders = array_sum($status_counts);

// Get order status badge color
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'processing' => 'primary',
        'shipped' => 'info',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

function getStatusIcon($status) {
    $icons = [
        'pending' => 'fa-clock',
        'processing' => 'fa-spinner fa-spin',
        'shipped' => 'fa-truck',
        'delivered' => 'fa-check-circle',
        'cancelled' => 'fa-times-circle'
    ];
    return $icons[$status] ?? 'fa-circle';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - LaptopHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f0f4f8;
        }
        
        /* Sidebar */
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
        .sidebar .nav-link .badge-sidebar {
            float: right;
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.6);
            font-size: 0.65rem;
            padding: 2px 8px;
            border-radius: 50px;
        }
        .sidebar .nav-link:hover .badge-sidebar { background: rgba(255,255,255,0.2); color: white; }
        
        .main-content { padding: 24px; }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.04);
            cursor: pointer;
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
        
        /* Status Filters */
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
        .filter-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
        }
        .filter-btn.active {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
        }
        .filter-btn .badge-count {
            background: rgba(0,0,0,0.1);
            padding: 1px 8px;
            border-radius: 50px;
            font-size: 0.7rem;
            margin-left: 4px;
        }
        .filter-btn.active .badge-count { background: rgba(255,255,255,0.2); }
        
        .badge-status {
            padding: 4px 14px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-status i { margin-right: 4px; }
        .badge-status.pending { background: #fef3c7; color: #d97706; }
        .badge-status.processing { background: #dbeafe; color: #2563eb; }
        .badge-status.shipped { background: #cce7ff; color: #0891b2; }
        .badge-status.delivered { background: #dcfce7; color: #16a34a; }
        .badge-status.cancelled { background: #fee2e2; color: #dc2626; }
        
        .btn-sm { border-radius: 10px; padding: 6px 12px; }
        
        /* Animations */
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
                <li class="nav-item"><a class="nav-link active" href="orders.php"><i class="fas fa-shopping-cart"></i>Orders</a></li>
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
                        <i class="fas fa-shopping-cart text-primary me-2"></i>Manage Orders
                    </h1>
                    <p class="text-muted">View and manage all customer orders</p>
                </div>
                <div>
                    <span class="badge bg-secondary rounded-pill">Total: <?= $total_orders ?></span>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6 animate-fade-in delay-1">
                    <a href="?status=all" class="text-decoration-none">
                        <div class="stat-card blue">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= $total_orders ?></div>
                                    <div class="stat-label">Total Orders</div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-2">
                    <a href="?status=pending" class="text-decoration-none">
                        <div class="stat-card orange">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= $status_counts['pending'] ?? 0 ?></div>
                                    <div class="stat-label">Pending</div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-3">
                    <a href="?status=processing" class="text-decoration-none">
                        <div class="stat-card purple">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= $status_counts['processing'] ?? 0 ?></div>
                                    <div class="stat-label">Processing</div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-4">
                    <a href="?status=delivered" class="text-decoration-none">
                        <div class="stat-card green">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= $status_counts['delivered'] ?? 0 ?></div>
                                    <div class="stat-label">Delivered</div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="?status=all" class="filter-btn <?= $status_filter == 'all' ? 'active' : '' ?>">
                    All <span class="badge-count"><?= $total_orders ?></span>
                </a>
                <?php foreach ($statuses as $status): ?>
                    <a href="?status=<?= $status ?>" 
                       class="filter-btn <?= $status_filter == $status ? 'active' : '' ?>">
                        <i class="fas <?= getStatusIcon($status) ?>"></i>
                        <?= ucfirst($status) ?>
                        <span class="badge-count"><?= $status_counts[$status] ?? 0 ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Search -->
            <form method="GET" class="mb-3">
                <div class="input-group" style="max-width: 350px;">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                    <input type="text" name="search" class="form-control" placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </div>
            </form>

            <!-- Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Items</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-shopping-bag fa-2x d-block mb-2"></i>
                                        No orders found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?= htmlspecialchars($order['order_number']) ?></strong>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($order['fullname']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($order['email']) ?></div>
                                        </td>
                                        <td>
                                            <div><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                                            <div class="text-muted small"><?= date('h:i A', strtotime($order['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-primary"><?php echo formatPrice($order['grand_total']); ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $item_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
                                            $item_stmt->execute([$order['id']]);
                                            $item_count = $item_stmt->fetch()['count'];
                                            ?>
                                            <span class="badge bg-secondary rounded-pill"><?= $item_count ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $order['payment_status'] == 'paid' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($order['payment_method']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $order['status'] ?>">
                                                <i class="fas <?= getStatusIcon($order['status']) ?>"></i>
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="update_order.php?id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-primary" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="order_details.php?id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-info" title="View Details" target="_blank">
                                                <i class="fas fa-eye"></i>
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
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
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