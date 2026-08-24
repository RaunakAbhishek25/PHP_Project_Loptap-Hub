<?php
// ajax/quick_view.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo '<div class="alert alert-danger">Invalid product</div>';
    exit;
}

$product = getProductById($id);

if (!$product) {
    echo '<div class="alert alert-danger">Product not found</div>';
    exit;
}

$images = getProductImages($id);
?>

<div class="row">
    <div class="col-md-5">
        <img src="<?php echo !empty($images) ? $images[0]['image_path'] : 'assets/images/placeholder.jpg'; ?>" 
             class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>">
        <div class="row g-2 mt-2">
            <?php foreach ($images as $img): ?>
                <div class="col-3">
                    <img src="<?php echo $img['image_path']; ?>" class="img-fluid rounded cursor-pointer" 
                         style="height:60px; object-fit:cover; cursor:pointer;" 
                         onclick="this.parentElement.parentElement.parentElement.querySelector('img:first-child').src=this.src">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-md-7">
        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
        <p class="text-muted">Brand: <?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></p>
        <div class="mt-2">
            <span class="h3 text-primary">$<?php echo number_format($product['price'], 2); ?></span>
            <?php if ($product['old_price']): ?>
                <span class="text-decoration-line-through text-muted ms-2">$<?php echo number_format($product['old_price'], 2); ?></span>
            <?php endif; ?>
        </div>
        <div class="mt-2">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star <?php echo $i <= ($product['rating'] ?? 0) ? 'text-warning' : 'text-muted'; ?>"></i>
            <?php endfor; ?>
            <span class="text-muted ms-1">(<?php echo $product['reviews_count'] ?? 0; ?> reviews)</span>
        </div>
        <p class="mt-3"><?php echo nl2br(htmlspecialchars(substr($product['description'] ?? '', 0, 200))); ?></p>
        <div class="row g-2 mt-2">
            <?php if ($product['processor']): ?>
                <div class="col-6"><strong>Processor:</strong> <?php echo htmlspecialchars($product['processor']); ?></div>
            <?php endif; ?>
            <?php if ($product['ram']): ?>
                <div class="col-6"><strong>RAM:</strong> <?php echo htmlspecialchars($product['ram']); ?></div>
            <?php endif; ?>
            <?php if ($product['storage']): ?>
                <div class="col-6"><strong>Storage:</strong> <?php echo htmlspecialchars($product['storage']); ?></div>
            <?php endif; ?>
            <?php if ($product['os']): ?>
                <div class="col-6"><strong>OS:</strong> <?php echo htmlspecialchars($product['os']); ?></div>
            <?php endif; ?>
        </div>
        <div class="mt-3 d-flex gap-2">
            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Full Details</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="btn btn-success add-to-cart" data-id="<?php echo $product['id']; ?>">
                    <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>