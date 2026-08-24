<?php
// wishlist.php - My Wishlist Page
session_start();

// Agar user login nahi hai toh login page pe redirect
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=wishlist.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get wishlist items
$stmt = $pdo->prepare("
    SELECT w.*, l.id as laptop_id, l.name, l.price, l.old_price, l.rating, l.reviews_count, l.stock,
           b.name as brand_name,
           (SELECT image_path FROM laptop_images WHERE laptop_id = l.id AND is_primary = 1 LIMIT 1) as image
    FROM wishlist w 
    JOIN laptops l ON w.laptop_id = l.id 
    LEFT JOIN brands b ON l.brand_id = b.id 
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();

// Handle remove from wishlist
if (isset($_GET['remove']) && isset($_GET['id'])) {
    $remove_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND laptop_id = ?");
    $stmt->execute([$user_id, $remove_id]);
    header('Location: wishlist.php?removed=1');
    exit;
}

// Handle add to cart from wishlist
if (isset($_GET['add_to_cart']) && isset($_GET['id'])) {
    $cart_id = (int)$_GET['id'];
    addToCart($user_id, $cart_id, 1);
    header('Location: wishlist.php?added=1');
    exit;
}

$removed = isset($_GET['removed']);
$added = isset($_GET['added']);

require_once 'includes/header.php';
?>

<!-- ========== PAGE HEADER ========== -->
<div class="bg-primary text-white py-4" style="background: linear-gradient(135deg, #1a1a2e, #16213e) !important;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="display-5 fw-bold">
                    <i class="fas fa-heart me-3"></i>My Wishlist
                </h1>
                <p class="opacity-75 mb-0">Save your favorite laptops for later</p>
            </div>
            <a href="shop.php" class="btn btn-light btn-lg rounded-pill">
                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
            </a>
        </div>
    </div>
</div>

<!-- ========== MESSAGES ========== -->
<div class="container my-3">
    <?php if ($removed): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>Item removed from wishlist!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($added): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>Item added to cart successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
</div>

<!-- ========== WISHLIST ITEMS ========== -->
<div class="container my-4">
    <?php if (empty($wishlist_items)): ?>
        <div class="text-center py-5">
            <div class="bg-white p-5 rounded-4 shadow-sm">
                <i class="fas fa-heart fa-5x text-muted mb-3" style="color: #e2e8f0 !important;"></i>
                <h3>Your wishlist is empty</h3>
                <p class="text-muted">Start adding your favorite laptops to your wishlist!</p>
                <a href="shop.php" class="btn btn-primary btn-lg rounded-pill mt-3">
                    <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col-md-4 col-6">
                    <div class="bg-white rounded-4 shadow-sm product-card-premium">
                        <div class="position-relative">
                            <img src="<?php echo $item['image'] ?? 'assets/images/placeholder.jpg'; ?>" 
                                 class="w-100" alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 style="height: 200px; object-fit: contain; padding: 10px;">
                            <?php if ($item['old_price']): ?>
                                <?php $discount = round((($item['old_price'] - $item['price']) / $item['old_price']) * 100); ?>
                                <span class="position-absolute top-0 start-0 badge bg-danger m-2">
                                    -<?php echo $discount; ?>%
                                </span>
                            <?php endif; ?>
                            <?php if ($item['stock'] <= 0): ?>
                                <span class="position-absolute top-0 end-0 badge bg-secondary m-2">
                                    Out of Stock
                                </span>
                            <?php endif; ?>
                            <a href="wishlist.php?remove=1&id=<?php echo $item['laptop_id']; ?>" 
                               class="position-absolute top-0 end-0 btn btn-sm btn-danger m-2" 
                               onclick="return confirm('Remove this item from wishlist?')"
                               style="border-radius: 50%; width: 32px; height: 32px; padding: 0; line-height: 32px;">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                        <div class="p-3">
                            <span class="badge bg-light text-dark mb-1"><?php echo htmlspecialchars($item['brand_name'] ?? ''); ?></span>
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <div>
                                <span class="fw-bold h5 text-primary">$<?php echo number_format($item['price'], 2); ?></span>
                                <?php if ($item['old_price']): ?>
                                    <span class="text-decoration-line-through text-muted ms-2">$<?php echo number_format($item['old_price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-1">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= ($item['rating'] ?? 0) ? 'text-warning' : 'text-muted'; ?>" style="font-size: 12px;"></i>
                                <?php endfor; ?>
                                <span class="small text-muted ms-1">(<?php echo $item['reviews_count'] ?? 0; ?>)</span>
                            </div>
                            <div class="mt-2 d-grid gap-2">
                                <?php if ($item['stock'] > 0): ?>
                                    <a href="wishlist.php?add_to_cart=1&id=<?php echo $item['laptop_id']; ?>" 
                                       class="btn btn-primary btn-sm rounded-pill">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm rounded-pill" disabled>
                                        <i class="fas fa-times me-1"></i>Out of Stock
                                    </button>
                                <?php endif; ?>
                                <a href="product.php?id=<?php echo $item['laptop_id']; ?>" 
                                   class="btn btn-outline-primary btn-sm rounded-pill">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.product-card-premium {
    transition: all 0.4s ease;
    border: 1px solid rgba(0,0,0,0.04);
    overflow: hidden;
}
.product-card-premium::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #2563eb, #8b5cf6);
    transform: scaleX(0);
    transform-origin: left;
    transition: 0.4s;
}
.product-card-premium:hover::before {
    transform: scaleX(1);
}
.product-card-premium:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
}
</style>

<?php require_once 'includes/footer.php'; ?>