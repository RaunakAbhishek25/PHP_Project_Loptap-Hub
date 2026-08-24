<?php
// admin/manage/users.php - Enhanced Users Management
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT * FROM users";
$params = [];

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $query .= " WHERE fullname LIKE ? OR email LIKE ? OR phone LIKE ?";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
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
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
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

// Get user stats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$new_users = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders GROUP BY user_id");
$active_users = $stmt->rowCount();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$weekly_users = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - LaptopHub Admin</title>
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
        .stat-card.teal .stat-icon { background: #14b8a6; }
        .stat-card.red .stat-icon { background: #ef4444; }
        
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
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: white;
        }
        .badge-status {
            padding: 4px 12px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.7rem;
        }
        .badge-status.active { background: #dcfce7; color: #16a34a; }
        .badge-status.inactive { background: #fee2e2; color: #dc2626; }
        
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
                <li class="nav-item"><a class="nav-link active" href="users.php"><i class="fas fa-users"></i>Users</a></li>
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
                        <i class="fas fa-users text-primary me-2"></i>Manage Users
                    </h1>
                    <p class="text-muted">View and manage all registered users</p>
                </div>
                <div>
                    <span class="badge bg-secondary rounded-pill">Total: <?= $total_users ?></span>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6 animate-fade-in delay-1">
                    <div class="stat-card blue">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $total_users ?></div>
                                <div class="stat-label">Total Users</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-2">
                    <div class="stat-card green">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $new_users ?></div>
                                <div class="stat-label">New (30 Days)</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-3">
                    <div class="stat-card orange">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $active_users ?></div>
                                <div class="stat-label">Active Users</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 animate-fade-in delay-4">
                    <div class="stat-card purple">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?= $weekly_users ?></div>
                                <div class="stat-label">New This Week</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <form method="GET" class="mb-3">
                <div class="input-group" style="max-width: 350px;">
                    <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
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
                                <th>User</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-users fa-2x d-block mb-2"></i>
                                        No users found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><span class="fw-bold">#<?= $user['id'] ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="user-avatar" style="background: <?= '#' . substr(md5($user['fullname']), 0, 6) ?>;">
                                                    <?= strtoupper(substr($user['fullname'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($user['fullname']) ?></div>
                                                    <?php if (!empty($user['address'])): ?>
                                                        <div class="text-muted small"><?= htmlspecialchars(substr($user['address'], 0, 30)) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="text-decoration-none">
                                                <i class="fas fa-envelope text-muted me-1"></i>
                                                <?= htmlspecialchars($user['email']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (!empty($user['phone'])): ?>
                                                <i class="fas fa-phone text-muted me-1"></i>
                                                <?= htmlspecialchars($user['phone']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?= date('M d, Y', strtotime($user['created_at'])) ?></div>
                                            <div class="text-muted small"><?= date('h:i A', strtotime($user['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= ($user['email_verified'] ?? 0) ? 'active' : 'inactive' ?>">
                                                <i class="fas <?= ($user['email_verified'] ?? 0) ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                                <?= ($user['email_verified'] ?? 0) ? 'Verified' : 'Pending' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_user.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-sm btn-info" title="View User">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="user_orders.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-sm btn-primary" title="View Orders">
                                                <i class="fas fa-shopping-bag"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="deleteUser(<?= $user['id'] ?>)" 
                                                    title="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
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
function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        fetch('ajax/delete_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error deleting user');
            }
        })
        .catch(() => alert('Error deleting user'));
    }
}
</script>

</body>
</html>