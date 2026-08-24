<?php
// ============================================
// FILE: product.php
// PURPOSE: Product details page with reviews
// ============================================

require_once 'includes/functions.php';
require_once 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProductById($id);

if (!$product) {
    echo '<div class="container py-5"><h3>Product not found</h3></div>';
    require_once 'includes/footer.php';
    exit;
}

$images = getProductImages($id);

// Related products (same category)
$stmt = $pdo->prepare("
    SELECT l.*, b.name as brand_name 
    FROM laptops l 
    LEFT JOIN brands b ON l.brand_id = b.id 
    WHERE l.category_id = ? AND l.id != ? AND l.status = 'active' 
    LIMIT 4
");
$stmt->execute([$product['category_id'], $id]);
$related = $stmt->fetchAll();

// Get reviews with pagination
$page = isset($_GET['review_page']) ? (int)$_GET['review_page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("
    SELECT r.*, u.fullname 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.laptop_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT {$limit} OFFSET {$offset}
");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

// Get total reviews count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE laptop_id = ?");
$stmt->execute([$id]);
$total_reviews = $stmt->fetchColumn();

// Check if product is in wishlist
$in_wishlist = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND laptop_id = ?");
    $stmt->execute([$_SESSION['user_id'], $id]);
    $in_wishlist = $stmt->fetch() !== false;
}

// Calculate rating breakdown
$rating_counts = [];
for ($i = 1; $i <= 5; $i++) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE laptop_id = ? AND rating = ?");
    $stmt->execute([$id, $i]);
    $rating_counts[$i] = $stmt->fetchColumn();
}
$total_ratings = array_sum($rating_counts);
$average_rating = $total_ratings > 0 ? round(($product['rating'] ?? 0), 1) : 0;

// Get product specifications
$specs = json_decode($product['specifications'], true);
if (!is_array($specs)) $specs = [];

$spec_fields = [
    'processor' => 'Processor',
    'ram' => 'RAM',
    'storage' => 'Storage',
    'graphics' => 'Graphics',
    'os' => 'Operating System',
    'screen_size' => 'Screen Size',
    'resolution' => 'Resolution',
    'battery' => 'Battery Life',
    'weight' => 'Weight'
];

foreach ($spec_fields as $field => $label) {
    if (!empty($product[$field]) && !isset($specs[$label])) {
        $specs[$label] = $product[$field];
    }
}
?>

<!-- CSS STYLES -->
<style>
:root {
    --primary-gradient: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
    --shadow-hover: 0 20px 60px rgba(37, 99, 235, 0.15);
    --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.product-page {
    background: #f8fafc;
}

.image-gallery {
    position: relative;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    transition: var(--transition-smooth);
}

.image-gallery:hover {
    box-shadow: 0 12px 48px rgba(0,0,0,0.12);
}

.main-image-wrapper {
    position: relative;
    width: 100%;
    padding-top: 75%;
    background: #f8fafc;
    overflow: hidden;
}

.main-image-wrapper img {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 85%;
    max-height: 85%;
    object-fit: contain;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.image-gallery:hover .main-image-wrapper img {
    transform: translate(-50%, -50%) scale(1.05);
}

.zoom-indicator {
    position: absolute;
    bottom: 20px;
    right: 20px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 8px 16px;
    border-radius: 30px;
    font-size: 12px;
    backdrop-filter: blur(10px);
    opacity: 0;
    transition: opacity 0.3s;
}

.image-gallery:hover .zoom-indicator {
    opacity: 1;
}

.thumbnail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 12px;
    padding: 16px 0;
}

.thumbnail-item {
    position: relative;
    padding-top: 100%;
    border-radius: 12px;
    overflow: hidden;
    border: 3px solid transparent;
    transition: var(--transition-smooth);
    cursor: pointer;
    background: #f1f5f9;
}

.thumbnail-item.active {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
}

.thumbnail-item img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition-smooth);
}

.thumbnail-item:hover img {
    transform: scale(1.1);
}

.badge-custom {
    padding: 8px 16px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: 0.3px;
}

.discount-badge {
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: white;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.featured-badge {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    color: #1f2937;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.price-section {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    padding: 20px 24px;
    border-radius: 16px;
    margin: 16px 0;
}

.price-current {
    font-size: 2.5rem;
    font-weight: 800;
    color: #2563eb;
}

.price-old {
    font-size: 1.25rem;
    color: #94a3b8;
    text-decoration: line-through;
    margin-left: 12px;
}

.price-save {
    display: inline-block;
    background: #22c55e;
    color: white;
    padding: 4px 14px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    margin-left: 12px;
}

.stock-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 14px;
}

.stock-in {
    background: #dcfce7;
    color: #16a34a;
}

.stock-out {
    background: #fee2e2;
    color: #dc2626;
}

.stock-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    animation: pulse-dot 2s infinite;
}

.stock-dot.in {
    background: #22c55e;
}

.stock-dot.out {
    background: #ef4444;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.quantity-selector {
    display: inline-flex;
    align-items: center;
    gap: 0;
    border-radius: 30px;
    overflow: hidden;
    border: 2px solid #e2e8f0;
    background: white;
    transition: var(--transition-smooth);
}

.quantity-selector:focus-within {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}

.qty-btn {
    width: 44px;
    height: 44px;
    border: none;
    background: transparent;
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    cursor: pointer;
    transition: var(--transition-smooth);
}

.qty-btn:hover {
    background: #f1f5f9;
}

.qty-btn:active {
    transform: scale(0.9);
}

.qty-input {
    width: 60px;
    height: 44px;
    border: none;
    border-left: 2px solid #e2e8f0;
    border-right: 2px solid #e2e8f0;
    text-align: center;
    font-weight: 600;
    font-size: 16px;
    background: transparent;
    outline: none;
}

.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.qty-input[type="number"] {
    -moz-appearance: textfield;
}

.btn-add-cart {
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 14px 24px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 16px;
    transition: var(--transition-smooth);
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
    flex: 1;
}

.btn-add-cart:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(37, 99, 235, 0.4);
}

.btn-add-cart:active {
    transform: translateY(0);
}

.btn-add-cart:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-buy-now {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    border: none;
    padding: 14px 24px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 16px;
    transition: var(--transition-smooth);
    box-shadow: 0 4px 16px rgba(245, 158, 11, 0.3);
    white-space: nowrap;
    min-width: 140px;
}

.btn-buy-now:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(245, 158, 11, 0.4);
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
}

.btn-buy-now:active {
    transform: translateY(0);
}

.btn-buy-now:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-wishlist {
    padding: 14px 24px;
    border-radius: 30px;
    font-weight: 600;
    transition: var(--transition-smooth);
    border: 2px solid #e2e8f0;
    background: white;
    width: 100%;
}

.btn-wishlist.active {
    background: #fee2e2;
    border-color: #dc2626;
    color: #dc2626;
}

.btn-wishlist:hover {
    border-color: #dc2626;
    background: #fef2f2;
}

.spec-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.spec-item {
    background: #f8fafc;
    padding: 16px 20px;
    border-radius: 12px;
    transition: var(--transition-smooth);
}

.spec-item:hover {
    background: #f1f5f9;
    transform: translateY(-2px);
}

.spec-label {
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.spec-value {
    font-weight: 600;
    color: #1e293b;
    margin-top: 4px;
}

.review-card {
    padding: 20px;
    border-radius: 16px;
    background: #f8fafc;
    transition: var(--transition-smooth);
    border: 1px solid transparent;
}

.review-card:hover {
    background: white;
    border-color: #e2e8f0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.review-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 20px;
    color: white;
    flex-shrink: 0;
}

.product-card-modern {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    transition: var(--transition-smooth);
    border: 1px solid rgba(0,0,0,0.04);
    height: 100%;
}

.product-card-modern:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
}

.product-card-modern .card-image {
    padding: 20px;
    background: #f8fafc;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-card-modern .card-image img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
    transition: var(--transition-smooth);
}

.product-card-modern:hover .card-image img {
    transform: scale(1.05);
}

.product-card-modern .card-body {
    padding: 16px 20px 20px;
}

.product-card-modern .product-name {
    font-weight: 600;
    font-size: 15px;
    color: #1e293b;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 44px;
}

.toast-custom {
    position: fixed;
    bottom: 30px;
    right: 30px;
    padding: 16px 24px;
    border-radius: 16px;
    color: white;
    font-weight: 600;
    z-index: 9999;
    max-width: 420px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    animation: slideUp 0.5s ease-out;
    display: flex;
    align-items: center;
    gap: 12px;
}

@keyframes slideUp {
    from { transform: translateY(100px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.toast-success { background: linear-gradient(135deg, #22c55e, #16a34a); }
.toast-error { background: linear-gradient(135deg, #ef4444, #dc2626); }
.toast-info { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.toast-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }

@media (max-width: 768px) {
    .price-current {
        font-size: 2rem;
    }
    .spec-grid {
        grid-template-columns: 1fr 1fr;
    }
    .thumbnail-grid {
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        gap: 8px;
    }
    .btn-buy-now {
        min-width: 100px;
        font-size: 14px;
        padding: 12px 16px;
    }
    .btn-add-cart {
        font-size: 14px;
        padding: 12px 16px;
    }
}
</style>

<!-- MAIN PRODUCT PAGE HTML -->
<div class="product-page">
    <div class="container py-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-white p-3 rounded-4 shadow-sm">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item"><a href="shop.php" class="text-decoration-none">Shop</a></li>
                <li class="breadcrumb-item"><a href="shop.php?category=<?php echo $product['category_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($product['category_name'] ?? 'Category'); ?></a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars(substr($product['name'], 0, 40)) . (strlen($product['name']) > 40 ? '...' : ''); ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <!-- PRODUCT IMAGES -->
            <div class="col-lg-6">
                <div class="image-gallery">
                    <div class="main-image-wrapper">
                        <img src="<?php echo !empty($images) ? $images[0]['image_path'] : 'assets/images/placeholder.jpg'; ?>" 
                             class="img-fluid" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             id="mainImage">

                        <div class="position-absolute top-0 start-0 p-3" style="z-index: 2;">
                            <?php if ($product['old_price']): ?>
                                <?php $discount = round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>
                                <span class="badge-custom discount-badge d-inline-block">
                                    <i class="fas fa-bolt me-1"></i>-<?php echo $discount; ?>% OFF
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="position-absolute top-0 end-0 p-3" style="z-index: 2;">
                            <?php if ($product['is_featured']): ?>
                                <span class="badge-custom featured-badge d-inline-block">
                                    <i class="fas fa-crown me-1"></i>Featured
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="zoom-indicator">
                            <i class="fas fa-search-plus me-1"></i> Hover to zoom
                        </div>
                    </div>

                    <?php if (!empty($images) && count($images) > 1): ?>
                        <div class="thumbnail-grid">
                            <?php foreach ($images as $index => $img): ?>
                                <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                                     onclick="changeMainImage(this, '<?php echo $img['image_path']; ?>')">
                                    <img src="<?php echo $img['image_path']; ?>" 
                                         alt="Thumbnail <?php echo $index + 1; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-4">
                        <div class="bg-white rounded-4 p-3 text-center shadow-sm">
                            <i class="fas fa-truck text-primary fs-4"></i>
                            <small class="d-block text-muted mt-1">Free Shipping</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-white rounded-4 p-3 text-center shadow-sm">
                            <i class="fas fa-undo text-primary fs-4"></i>
                            <small class="d-block text-muted mt-1">30 Days Return</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="bg-white rounded-4 p-3 text-center shadow-sm">
                            <i class="fas fa-shield-alt text-primary fs-4"></i>
                            <small class="d-block text-muted mt-1">2 Year Warranty</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRODUCT INFO -->
            <div class="col-lg-6">
                <div class="bg-white rounded-4 shadow-sm p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-light text-dark py-2 px-3 rounded-pill">
                            <?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?>
                        </span>
                        <?php if ($product['stock'] > 0): ?>
                            <span class="stock-indicator stock-in">
                                <span class="stock-dot in"></span> In Stock
                            </span>
                        <?php else: ?>
                            <span class="stock-indicator stock-out">
                                <span class="stock-dot out"></span> Out of Stock
                            </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="h2 fw-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $average_rating ? 'text-warning' : 'text-muted'; ?>" style="font-size: 18px;"></i>
                            <?php endfor; ?>
                            <span class="fw-bold ms-1"><?php echo number_format($average_rating, 1); ?></span>
                        </div>
                        <span class="text-muted">(<?php echo $total_ratings; ?> reviews)</span>
                        <?php if ($total_ratings > 0): ?>
                            <span class="badge bg-success rounded-pill">Verified</span>
                        <?php endif; ?>
                    </div>

                    <div class="price-section">
                        <div class="d-flex align-items-center flex-wrap">
                            <span class="price-current"><?php echo formatPrice($product['price']); ?></span>
                            <?php if ($product['old_price']): ?>
                                <span class="price-old"><?php echo formatPrice($product['old_price']); ?></span>
                                <span class="price-save">
                                    Save <?php echo round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($product['stock'] > 0 && $product['stock'] < 10): ?>
                            <small class="text-danger d-block mt-1">
                                <i class="fas fa-exclamation-circle"></i> Only <?php echo $product['stock']; ?> units left - Order soon!
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <h6 class="fw-bold mb-2">Description</h6>
                        <p class="text-muted mb-0" style="line-height: 1.8;">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </p>
                    </div>

                    <?php 
                    $highlights = [
                        'processor' => 'Processor',
                        'ram' => 'RAM',
                        'storage' => 'Storage',
                        'graphics' => 'Graphics',
                        'screen_size' => 'Screen Size'
                    ];
                    $has_highlights = false;
                    foreach ($highlights as $field => $label) {
                        if (!empty($product[$field])) $has_highlights = true;
                    }
                    if ($has_highlights): 
                    ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="row g-2">
                            <?php foreach ($highlights as $field => $label): ?>
                                <?php if (!empty($product[$field])): ?>
                                    <div class="col-md-6">
                                        <small class="text-muted"><?php echo $label; ?></small>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($product[$field]); ?></div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ADD TO CART & BUY NOW -->
                    <?php if (isset($_SESSION['user_id']) && $product['stock'] > 0): ?>
                        <div class="mt-4">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="fw-semibold small text-muted mb-1">Quantity</label>
                                    <div class="quantity-selector">
                                        <button type="button" class="qty-btn" onclick="updateQuantity(-1)">−</button>
                                        <input type="number" id="quantity" class="qty-input" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                        <button type="button" class="qty-btn" onclick="updateQuantity(1)">+</button>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-box me-1"></i><?php echo $product['stock']; ?> units available
                                    </small>
                                </div>
                                <div class="col-md-8">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-shopping-bag me-2"></i>Add to Cart
                                        </button>
                                        <button class="btn-buy-now" onclick="buyNow(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-bolt me-2"></i>Buy Now
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button class="btn-wishlist <?php echo $in_wishlist ? 'active' : ''; ?> mt-2" 
                                    onclick="toggleWishlist(<?php echo $product['id']; ?>)">
                                <i class="fas fa-heart me-2"></i>
                                <?php echo $in_wishlist ? 'In Wishlist' : 'Add to Wishlist'; ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert <?php echo isset($_SESSION['user_id']) ? 'alert-warning' : 'alert-info'; ?> mt-3 rounded-4">
                            <i class="fas <?php echo isset($_SESSION['user_id']) ? 'fa-exclamation-triangle' : 'fa-sign-in-alt'; ?> me-2"></i>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <strong>Out of Stock</strong> - This product is currently unavailable
                            <?php else: ?>
                                <a href="login.php" class="alert-link fw-bold">Login</a> to purchase this product
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SPECIFICATIONS -->
        <?php if (!empty($specs)): ?>
            <div class="bg-white rounded-4 shadow-sm p-4 mt-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-microchip text-primary me-2"></i>Technical Specifications
                </h5>
                <div class="spec-grid">
                    <?php foreach ($specs as $key => $value): ?>
                        <div class="spec-item">
                            <div class="spec-label"><?php echo htmlspecialchars($key); ?></div>
                            <div class="spec-value"><?php echo htmlspecialchars($value); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- REVIEWS SECTION -->
        <div class="bg-white rounded-4 shadow-sm p-4 mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    <i class="fas fa-comments text-primary me-2"></i>Customer Reviews
                </h5>
                <?php if ($total_ratings > 0): ?>
                    <span class="badge bg-light text-dark py-2 px-3 rounded-pill">
                        <?php echo $total_ratings; ?> Reviews
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($total_ratings > 0): ?>
                <div class="row g-4 mb-4">
                    <div class="col-md-3 text-center">
                        <div class="display-2 fw-bold text-primary"><?php echo number_format($average_rating, 1); ?></div>
                        <div>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $average_rating ? 'text-warning' : 'text-muted'; ?>" style="font-size: 20px;"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="text-muted mt-1">Based on <?php echo $total_ratings; ?> reviews</div>
                    </div>
                    <div class="col-md-9">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-semibold" style="min-width: 30px;"><?php echo $i; ?>★</span>
                                <div class="flex-grow-1 bg-light rounded-pill" style="height: 8px; overflow: hidden;">
                                    <div class="bg-warning rounded-pill" style="height: 100%; width: <?php echo $total_ratings > 0 ? ($rating_counts[$i] / $total_ratings * 100) : 0; ?>%;"></div>
                                </div>
                                <span class="text-muted small" style="min-width: 40px;"><?php echo $rating_counts[$i]; ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-comment-slash text-muted fa-3x mb-3"></i>
                    <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card mb-3">
                        <div class="d-flex gap-3">
                            <div class="review-avatar" style="background: <?php echo '#' . substr(md5($review['fullname']), 0, 6); ?>;">
                                <?php echo strtoupper(substr($review['fullname'], 0, 2)); ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div>
                                        <strong class="fs-5"><?php echo htmlspecialchars($review['fullname']); ?></strong>
                                        <span class="text-muted small ms-2"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <div>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>" style="font-size: 14px;"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="mt-2 mb-0 text-muted"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($total_reviews > $limit): ?>
                    <nav class="mt-3">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= ceil($total_reviews / $limit); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?id=<?php echo $id; ?>&review_page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ========================================== -->
            <!-- WRITE REVIEW FORM - SIMPLE VERSION (WORKS!) -->
            <!-- ========================================== -->
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <hr class="my-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-pen text-primary me-2"></i>Write a Review
                </h6>
                
                <?php
                // ========== HANDLE FORM SUBMISSION ==========
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
                    $laptop_id = (int)$_POST['laptop_id'];
                    $rating = (int)$_POST['rating'];
                    $comment = trim($_POST['comment']);
                    $user_id = $_SESSION['user_id'];
                    
                    $errors = [];
                    $success = false;
                    
                    if ($rating < 1 || $rating > 5) {
                        $errors[] = 'Please select a valid rating (1-5)';
                    }
                    if (empty($comment)) {
                        $errors[] = 'Please write a review';
                    }
                    if (strlen($comment) < 3) {
                        $errors[] = 'Review must be at least 3 characters';
                    }
                    
                    if (empty($errors)) {
                        try {
                            // Check if already reviewed
                            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND laptop_id = ?");
                            $stmt->execute([$user_id, $laptop_id]);
                            $existing = $stmt->fetch();
                            
                            if ($existing) {
                                $errors[] = 'You have already reviewed this product';
                            } else {
                                // Insert review
                                $stmt = $pdo->prepare("
                                    INSERT INTO reviews (laptop_id, user_id, rating, comment, created_at) 
                                    VALUES (?, ?, ?, ?, NOW())
                                ");
                                $result = $stmt->execute([$laptop_id, $user_id, $rating, $comment]);
                                
                                if ($result) {
                                    // Update product rating
                                    $stmt = $pdo->prepare("
                                        UPDATE laptops 
                                        SET rating = (
                                            SELECT COALESCE(AVG(rating), 0) 
                                            FROM reviews 
                                            WHERE laptop_id = ?
                                        ),
                                        reviews_count = (
                                            SELECT COUNT(*) 
                                            FROM reviews 
                                            WHERE laptop_id = ?
                                        )
                                        WHERE id = ?
                                    ");
                                    $stmt->execute([$laptop_id, $laptop_id, $laptop_id]);
                                    
                                    $success = true;
                                    echo '<div class="alert alert-success rounded-4">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Success!</strong> Review submitted successfully!
                                    </div>';
                                    
                                    // Refresh after 2 seconds
                                    echo '<meta http-equiv="refresh" content="2;url=' . $_SERVER['REQUEST_URI'] . '">';
                                } else {
                                    $errors[] = 'Failed to submit review. Please try again.';
                                }
                            }
                        } catch (PDOException $e) {
                            $errors[] = 'Database error: ' . $e->getMessage();
                        } catch (Exception $e) {
                            $errors[] = 'Error: ' . $e->getMessage();
                        }
                    }
                    
                    // Show errors
                    if (!empty($errors) && !$success) {
                        foreach ($errors as $error) {
                            echo '<div class="alert alert-danger rounded-4">
                                <i class="fas fa-exclamation-circle me-2"></i>' . htmlspecialchars($error) . '
                            </div>';
                        }
                    }
                }
                ?>
                
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Rating</label>
                            <select name="rating" class="form-select rounded-pill" required>
                                <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent)</option>
                                <option value="4">⭐⭐⭐⭐ (4 - Good)</option>
                                <option value="3" selected>⭐⭐⭐ (3 - Average)</option>
                                <option value="2">⭐⭐ (2 - Poor)</option>
                                <option value="1">⭐ (1 - Terrible)</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Your Review</label>
                            <textarea name="comment" class="form-control rounded-4" rows="3" 
                                      placeholder="Share your experience with this product..." 
                                      required minlength="3"></textarea>
                            <small class="text-muted">Minimum 3 characters</small>
                        </div>
                    </div>
                    <input type="hidden" name="laptop_id" value="<?php echo $product['id']; ?>">
                    <button type="submit" name="submit_review" class="btn btn-primary rounded-pill px-5 mt-3">
                        <i class="fas fa-paper-plane me-2"></i>Submit Review
                    </button>
                </form>
            <?php else: ?>
                <hr class="my-4">
                <div class="text-center">
                    <p class="text-muted">
                        <a href="login.php" class="fw-bold text-decoration-none">Login</a> to write a review
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- RELATED PRODUCTS -->
        <?php if (!empty($related)): ?>
            <div class="mt-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-lightbulb text-primary me-2"></i>You Might Also Like
                </h5>
                <div class="row g-4">
                    <?php foreach ($related as $rel): ?>
                        <div class="col-lg-3 col-md-4 col-6">
                            <div class="product-card-modern">
                                <div class="card-image">
                                    <?php 
                                    $img_stmt = $pdo->prepare("SELECT image_path FROM laptop_images WHERE laptop_id = ? AND is_primary = 1 LIMIT 1");
                                    $img_stmt->execute([$rel['id']]);
                                    $rel_img = $img_stmt->fetch();
                                    ?>
                                    <img src="<?php echo $rel_img['image_path'] ?? 'assets/images/placeholder.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($rel['name']); ?>">
                                </div>
                                <div class="card-body">
                                    <div class="product-name"><?php echo htmlspecialchars($rel['name']); ?></div>
                                    <div class="mt-2">
                                        <span class="fw-bold text-primary fs-5"><?php echo formatPrice($rel['price']); ?></span>
                                        <?php if ($rel['old_price']): ?>
                                            <span class="text-decoration-line-through text-muted small ms-1"><?php echo formatPrice($rel['old_price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="product.php?id=<?php echo $rel['id']; ?>" class="btn btn-outline-primary btn-sm w-100 mt-2 rounded-pill">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JAVASCRIPT -->
<script>
// ========== THUMBNAIL SWITCH ==========
function changeMainImage(element, imagePath) {
    document.querySelectorAll('.thumbnail-item').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
    document.getElementById('mainImage').src = imagePath;
}

// ========== QUANTITY CONTROLS ==========
function updateQuantity(delta) {
    const input = document.getElementById('quantity');
    let val = parseInt(input.value) + delta;
    const max = parseInt(input.max);
    if (val < 1) val = 1;
    if (val > max) val = max;
    input.value = val;
}

document.getElementById('quantity')?.addEventListener('change', function() {
    let val = parseInt(this.value);
    const max = parseInt(this.max);
    if (isNaN(val) || val < 1) this.value = 1;
    if (val > max) this.value = max;
});

// ========== ADD TO CART ==========
function addToCart(productId) {
    const quantity = document.getElementById('quantity')?.value || 1;
    const btn = document.querySelector('.btn-add-cart');
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
    btn.disabled = true;

    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({laptop_id: productId, quantity: quantity})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check me-2"></i>Added!';
            btn.style.background = 'linear-gradient(135deg, #22c55e, #16a34a)';
            showToast('✅ Added to cart successfully!', 'success');
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '';
                btn.disabled = false;
            }, 2000);
        } else {
            btn.innerHTML = originalText;
            btn.disabled = false;
            showToast(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(error => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        showToast('Error adding to cart', 'error');
    });
}

// ========== BUY NOW ==========
function buyNow(productId) {
    const quantity = document.getElementById('quantity')?.value || 1;
    const btn = document.querySelector('.btn-buy-now');
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    btn.disabled = true;

    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({laptop_id: productId, quantity: quantity})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('✅ Redirecting to checkout...', 'success');
            setTimeout(() => {
                window.location.href = 'checkout.php';
            }, 800);
        } else {
            btn.innerHTML = originalText;
            btn.disabled = false;
            showToast(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(error => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        showToast('Error processing request', 'error');
    });
}

// ========== TOGGLE WISHLIST ==========
function toggleWishlist(productId) {
    const btn = document.querySelector('.btn-wishlist');
    const isInWishlist = btn.classList.contains('active');
    const url = isInWishlist ? 'ajax/remove_from_wishlist.php' : 'ajax/add_to_wishlist.php';

    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
    btn.disabled = true;

    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({laptop_id: productId})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (isInWishlist) {
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-heart me-2"></i>Add to Wishlist';
                showToast('Removed from wishlist', 'info');
            } else {
                btn.classList.add('active');
                btn.innerHTML = '<i class="fas fa-heart me-2"></i>In Wishlist';
                showToast('Added to wishlist ❤️', 'success');
            }
            btn.disabled = false;
        } else {
            btn.innerHTML = isInWishlist ? '<i class="fas fa-heart me-2"></i>In Wishlist' : '<i class="fas fa-heart me-2"></i>Add to Wishlist';
            btn.disabled = false;
            showToast(data.message || 'Error', 'error');
        }
    })
    .catch(error => {
        btn.innerHTML = isInWishlist ? '<i class="fas fa-heart me-2"></i>In Wishlist' : '<i class="fas fa-heart me-2"></i>Add to Wishlist';
        btn.disabled = false;
        showToast('Error', 'error');
    });
}

// ========== TOAST NOTIFICATION ==========
function showToast(message, type = 'success') {
    document.querySelectorAll('.toast-custom').forEach(el => el.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast-custom toast-${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} fa-lg"></i>
        <span class="flex-grow-1">${message}</span>
        <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.remove()"></button>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(100px)';
            toast.style.transition = 'all 0.5s ease-out';
            setTimeout(() => toast.remove(), 500);
        }
    }, 4000);
}

// ========== KEYBOARD SHORTCUTS ==========
document.addEventListener('keydown', function(e) {
    if (e.key === 'q' && !e.ctrlKey && !e.metaKey) {
        const qtyInput = document.getElementById('quantity');
        if (qtyInput) {
            e.preventDefault();
            qtyInput.focus();
            qtyInput.select();
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>