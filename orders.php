<?php
// orders.php - My Orders Page (FIXED)
session_start();

// Agar user login nahi hai toh login page pe redirect
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=orders.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get filter status
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// ✅ FIXED: Count total orders with filter
$count_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$count_params = [$user_id];

if ($filter_status != 'all') {
    $count_query .= " AND status = ?";
    $count_params[] = $filter_status;
}

$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_orders = $stmt->fetch()['total'] ?? 0;
$total_pages = ceil($total_orders / $limit);

// ✅ FIXED: Get orders with LIMIT and OFFSET as INT
$query = "
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    WHERE o.user_id = ?
";

$params = [$user_id];

if ($filter_status != 'all') {
    $query .= " AND o.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";

// ✅ Execute with proper parameter binding
try {
    $stmt = $pdo->prepare($query);
    
    // Bind all parameters
    $idx = 1;
    foreach ($params as $param) {
        $stmt->bindValue($idx, $param);
        $idx++;
    }
    
    // Bind LIMIT and OFFSET as INT
    $stmt->bindValue($idx, $limit, PDO::PARAM_INT);
    $idx++;
    $stmt->bindValue($idx, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
    $error_msg = $e->getMessage();
}

// Get order status counts
$status_counts = [];
$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
foreach ($statuses as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = ?");
    $stmt->execute([$user_id, $status]);
    $status_counts[$status] = $stmt->fetch()['count'] ?? 0;
}

require_once 'includes/header.php';
?>

<!-- ========== PAGE HEADER ========== -->
<div class="bg-primary text-white py-4" style="background: linear-gradient(135deg, #1a1a2e, #16213e) !important;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="display-5 fw-bold">
                    <i class="fas fa-shopping-bag me-3"></i>My Orders
                </h1>
                <p class="opacity-75 mb-0">View and track all your orders</p>
            </div>
            <a href="shop.php" class="btn btn-light btn-lg rounded-pill">
                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
            </a>
        </div>
    </div>
</div>

<!-- ========== ORDER STATUS FILTER ========== -->
<div class="container my-4">
    <div class="row g-2">
        <div class="col-auto">
            <a href="orders.php" class="btn <?php echo $filter_status == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill">
                All (<?php echo $total_orders; ?>)
            </a>
        </div>
        <?php foreach ($statuses as $status): ?>
            <div class="col-auto">
                <a href="orders.php?status=<?php echo $status; ?>" 
                   class="btn <?php echo $filter_status == $status ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill">
                    <?php echo ucfirst($status); ?> (<?php echo $status_counts[$status] ?? 0; ?>)
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ========== ORDERS LIST ========== -->
<div class="container my-4">
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>
    
    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <div class="bg-white p-5 rounded-4 shadow-sm">
                <i class="fas fa-shopping-bag fa-5x text-muted mb-3"></i>
                <h3>No orders found</h3>
                <p class="text-muted">Start shopping and place your first order!</p>
                <a href="shop.php" class="btn btn-primary btn-lg rounded-pill mt-3">
                    <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($orders as $order): ?>
                <div class="col-12">
                    <div class="bg-white p-4 rounded-4 shadow-sm">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <div class="text-center">
                                    <small class="text-muted">Order #</small>
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($order['order_number']); ?></h6>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <small class="text-muted">Date</small>
                                    <h6 class="fw-bold mb-0"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></h6>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <small class="text-muted">Items</small>
                                    <h6 class="fw-bold mb-0"><?php echo $order['item_count']; ?></h6>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <small class="text-muted">Total</small>
                                    <h6 class="fw-bold text-primary mb-0">$<?php echo number_format($order['grand_total'], 2); ?></h6>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <span class="badge <?php 
                                        echo $order['status'] == 'delivered' ? 'bg-success' : 
                                            ($order['status'] == 'cancelled' ? 'bg-danger' : 
                                            ($order['status'] == 'shipped' ? 'bg-info' : 
                                            ($order['status'] == 'processing' ? 'bg-warning' : 'bg-secondary'))); 
                                    ?> fs-6 py-2 px-3">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="d-grid">
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-primary btn-sm rounded-pill">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <button class="btn btn-outline-danger btn-sm rounded-pill mt-1" 
                                                onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Progress -->
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <?php 
                                $progress = 0;
                                switch($order['status']) {
                                    case 'pending': $progress = 25; break;
                                    case 'processing': $progress = 50; break;
                                    case 'shipped': $progress = 75; break;
                                    case 'delivered': $progress = 100; break;
                                    case 'cancelled': $progress = 0; break;
                                }
                                ?>
                                <div class="progress-bar <?php echo $order['status'] == 'cancelled' ? 'bg-danger' : 'bg-primary'; ?>" 
                                     role="progressbar" style="width: <?php echo $progress; ?>%;" 
                                     aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span>Pending</span>
                                <span>Processing</span>
                                <span>Shipped</span>
                                <span>Delivered</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $filter_status; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $filter_status; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        fetch('ajax/cancel_order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({order_id: orderId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order cancelled successfully!');
                location.reload();
            } else {
                alert(data.message || 'Failed to cancel order');
            }
        })
        .catch(error => {
            alert('Error cancelling order');
        });
    }
}
</script>

<style>
.btn-primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    border: none;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
}
</style>

<?php require_once 'includes/footer.php'; ?>