<?php
// dashboard.php - User Dashboard
session_start();

// Agar user login nahi hai toh home page pe redirect
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get user stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_wishlist = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_cart = $stmt->fetch()['total'] ?? 0;

// Get recent orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

require_once 'includes/header.php';
?>

<!-- ========== DASHBOARD CONTENT ========== -->
<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="bg-white p-4 rounded-4 shadow-sm">
                <div class="text-center mb-3">
                    <div class="user-avatar mx-auto" style="width: 80px; height: 80px; font-size: 2.5rem; background: linear-gradient(135deg, #2563eb, #8b5cf6);">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <h5 class="mt-2"><?php echo htmlspecialchars($user_name); ?></h5>
                    <small class="text-muted"><?php echo htmlspecialchars($user['email'] ?? ''); ?></small>
                </div>
                <hr>
                <a href="dashboard.php" class="d-block p-2 rounded-3 text-decoration-none text-dark fw-semibold" style="background: rgba(37, 99, 235, 0.1);">
                    <i class="fas fa-dashboard me-2"></i>Dashboard
                </a>
                <a href="profile.php" class="d-block p-2 rounded-3 text-decoration-none text-dark">
                    <i class="fas fa-user me-2"></i>My Profile
                </a>
                <a href="orders.php" class="d-block p-2 rounded-3 text-decoration-none text-dark">
                    <i class="fas fa-shopping-bag me-2"></i>My Orders
                </a>
                <a href="wishlist.php" class="d-block p-2 rounded-3 text-decoration-none text-dark">
                    <i class="fas fa-heart me-2"></i>Wishlist
                </a>
                <a href="cart.php" class="d-block p-2 rounded-3 text-decoration-none text-dark">
                    <i class="fas fa-shopping-cart me-2"></i>Cart
                </a>
                <hr>
                <a href="logout.php" class="d-block p-2 rounded-3 text-decoration-none text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <h2 class="fw-bold mb-4">
                <i class="fas fa-dashboard text-primary me-2"></i>Dashboard
            </h2>
            
            <!-- Welcome -->
            <div class="bg-white p-4 rounded-4 shadow-sm mb-4">
                <h5 class="mb-0">👋 Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h5>
                <p class="text-muted mb-0">Here's what's happening with your account</p>
            </div>
            
            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="bg-white p-4 rounded-4 shadow-sm text-center">
                        <i class="fas fa-shopping-bag fa-3x text-primary mb-2"></i>
                        <h2 class="fw-bold"><?php echo $total_orders; ?></h2>
                        <p class="text-muted">Total Orders</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-white p-4 rounded-4 shadow-sm text-center">
                        <i class="fas fa-heart fa-3x text-danger mb-2"></i>
                        <h2 class="fw-bold"><?php echo $total_wishlist; ?></h2>
                        <p class="text-muted">Wishlist Items</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-white p-4 rounded-4 shadow-sm text-center">
                        <i class="fas fa-shopping-cart fa-3x text-success mb-2"></i>
                        <h2 class="fw-bold"><?php echo $total_cart; ?></h2>
                        <p class="text-muted">Cart Items</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="bg-white p-4 rounded-4 shadow-sm">
                <h5 class="fw-bold"><i class="fas fa-clock me-2"></i>Recent Orders</h5>
                <?php if (empty($recent_orders)): ?>
                    <p class="text-muted my-3">No orders yet. <a href="shop.php" class="text-primary">Start Shopping!</a></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>$<?php echo number_format($order['grand_total'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $order['status'] == 'delivered' ? 'bg-success' : ($order['status'] == 'pending' ? 'bg-warning' : 'bg-info'); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #8b5cf6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 2.5rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>