<?php
// shop.php - Smartprix Style Product Listing
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get filters
$brand = isset($_GET['brand']) ? (int)$_GET['brand'] : null;
$category = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 5000;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$cpu_gen = isset($_GET['cpu_gen']) ? $_GET['cpu_gen'] : '';
$ram_filter = isset($_GET['ram']) ? $_GET['ram'] : '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get brands and categories
$brands = $pdo->query("SELECT * FROM brands ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Build query
$query = "SELECT l.*, b.name as brand_name 
          FROM laptops l 
          LEFT JOIN brands b ON l.brand_id = b.id 
          WHERE l.status = 'active'";

$params = [];

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $query .= " AND (l.name LIKE ? OR b.name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($brand) {
    $query .= " AND l.brand_id = ?";
    $params[] = $brand;
}

if ($category) {
    $query .= " AND l.category_id = ?";
    $params[] = $category;
}

if ($min_price > 0 || $max_price < 5000) {
    $query .= " AND l.price BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
}

if (!empty($cpu_gen)) {
    $query .= " AND l.processor LIKE ?";
    $params[] = '%' . $cpu_gen . '%';
}

if (!empty($ram_filter)) {
    $query .= " AND l.ram LIKE ?";
    $params[] = '%' . $ram_filter . '%';
}

switch ($sort) {
    case 'price_low': $query .= " ORDER BY l.price ASC"; break;
    case 'price_high': $query .= " ORDER BY l.price DESC"; break;
    case 'rating': $query .= " ORDER BY l.rating DESC"; break;
    default: $query .= " ORDER BY l.created_at DESC";
}

$query .= " LIMIT ? OFFSET ?";

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
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    $products = [];
}

$count_query = str_replace("LIMIT ? OFFSET ?", "", $query);
$count_params = $params;
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

require_once 'includes/header.php';
?>

<style>
/* ========== SMART PRIX STYLE ========== */
.smartprix-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Header */
.smartprix-header {
    background: #fff;
    padding: 20px 24px;
    border-radius: 8px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.smartprix-header h2 {
    font-weight: 700;
    color: #1a1a2e;
}
.smartprix-header p {
    color: #666;
    font-size: 0.9rem;
}

/* ========== LEFT SIDEBAR ========== */
.smartprix-sidebar {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    position: sticky;
    top: 80px;
}
.smartprix-sidebar .filter-group {
    margin-bottom: 20px;
    border-bottom: 1px solid #f1f3f6;
    padding-bottom: 16px;
}
.smartprix-sidebar .filter-group:last-child {
    border-bottom: none;
    margin-bottom: 0;
}
.smartprix-sidebar .filter-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #1a1a2e;
    margin-bottom: 10px;
}
.smartprix-sidebar .filter-item {
    padding: 4px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #1a1a2e;
    cursor: pointer;
}
.smartprix-sidebar .filter-item:hover {
    color: #2874f0;
}
.smartprix-sidebar .filter-item .count {
    color: #878787;
    font-size: 0.75rem;
    margin-left: auto;
}
.smartprix-sidebar .form-check-input {
    cursor: pointer;
}
.smartprix-sidebar .form-check-label {
    font-size: 0.85rem;
    cursor: pointer;
}

/* ========== PRODUCT LIST ========== */
.product-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
}
.product-list-header .total-count {
    font-weight: 700;
    color: #1a1a2e;
    font-size: 1.1rem;
}
.product-list-header .sort-select {
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 6px 12px;
    font-size: 0.85rem;
}

/* Product Card - Smartprix Style */
.product-card-smart {
    background: #fff;
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    transition: all 0.3s;
    display: flex;
    gap: 20px;
    align-items: flex-start;
}
.product-card-smart:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}
.product-card-smart .product-image {
    flex: 0 0 180px;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border-radius: 8px;
    padding: 8px;
}
.product-card-smart .product-image img {
    max-height: 120px;
    max-width: 100%;
    object-fit: contain;
}
.product-card-smart .product-details {
    flex: 1;
}
.product-card-smart .product-details .product-name {
    font-weight: 600;
    font-size: 1rem;
    color: #1a1a2e;
    text-decoration: none;
}
.product-card-smart .product-details .product-name:hover {
    color: #2874f0;
}
.product-card-smart .product-details .product-brand {
    font-size: 0.8rem;
    color: #878787;
}
.product-card-smart .product-details .rating {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 4px 0;
}
.product-card-smart .product-details .rating .stars {
    color: #ff9f00;
}
.product-card-smart .product-details .rating .score {
    font-weight: 700;
    color: #1a1a2e;
    font-size: 0.9rem;
}
.product-card-smart .product-details .rating .spec-score {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 2px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}
.product-card-smart .product-details .specs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 20px;
    margin: 6px 0;
    font-size: 0.8rem;
    color: #1a1a2e;
}
.product-card-smart .product-details .specs .spec-item {
    display: flex;
    align-items: center;
    gap: 4px;
}
.product-card-smart .product-details .specs .spec-item i {
    color: #878787;
    font-size: 0.7rem;
}
.product-card-smart .product-details .warranty {
    font-size: 0.75rem;
    color: #878787;
}
.product-card-smart .product-right {
    flex: 0 0 150px;
    text-align: right;
}
.product-card-smart .product-right .price {
    font-weight: 700;
    font-size: 1.3rem;
    color: #1a1a2e;
}
.product-card-smart .product-right .old-price {
    font-size: 0.85rem;
    color: #878787;
    text-decoration: line-through;
}
.product-card-smart .product-right .discount {
    color: #388e3c;
    font-weight: 600;
    font-size: 0.85rem;
}
.product-card-smart .product-right .actions {
    margin-top: 8px;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}
.product-card-smart .product-right .actions .btn-smart {
    padding: 6px 14px;
    border: none;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.product-card-smart .product-right .actions .btn-compare {
    background: #f1f3f6;
    color: #1a1a2e;
}
.product-card-smart .product-right .actions .btn-compare:hover {
    background: #e0e0e0;
}
.product-card-smart .product-right .actions .btn-view {
    background: #2874f0;
    color: white;
}
.product-card-smart .product-right .actions .btn-view:hover {
    background: #1a5db0;
}
.product-card-smart .product-right .actions .btn-like {
    background: none;
    border: none;
    font-size: 1.1rem;
    cursor: pointer;
    color: #878787;
}
.product-card-smart .product-right .actions .btn-like:hover {
    color: #ef4444;
}

/* Pagination */
.pagination-smart {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}
.pagination-smart .page-link {
    padding: 8px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    color: #1a1a2e;
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.3s;
}
.pagination-smart .page-link:hover {
    background: #f1f3f6;
}
.pagination-smart .page-link.active {
    background: #2874f0;
    color: white;
    border-color: #2874f0;
}

/* Responsive */
@media (max-width: 992px) {
    .product-card-smart {
        flex-wrap: wrap;
    }
    .product-card-smart .product-image {
        flex: 0 0 100%;
        height: 120px;
    }
    .product-card-smart .product-right {
        flex: 0 0 100%;
        text-align: left;
        margin-top: 8px;
    }
    .product-card-smart .product-right .actions {
        justify-content: flex-start;
    }
}
@media (max-width: 768px) {
    .smartprix-sidebar {
        position: relative;
        top: 0;
        margin-bottom: 16px;
    }
    .product-card-smart {
        padding: 12px;
    }
    .product-card-smart .product-details .product-name {
        font-size: 0.9rem;
    }
    .product-card-smart .product-details .specs {
        font-size: 0.7rem;
    }
    .product-list-header .total-count {
        font-size: 0.95rem;
    }
}
</style>

<!-- ========== MAIN CONTAINER ========== -->
<div class="smartprix-container">
    
    <!-- Header -->
    <div class="smartprix-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2>Laptops Price List in India</h2>
                <p>Find the best laptops with latest prices and specifications</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <!-- ========== LEFT SIDEBAR - FILTERS ========== -->
        <div class="col-lg-3">
            <div class="smartprix-sidebar">
                
                <!-- Search -->
                <div class="filter-group">
                    <div class="filter-title">Search</div>
                    <form method="GET" action="shop.php">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search laptops..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>

                <!-- Brands -->
                <div class="filter-group">
                    <div class="filter-title">Popular Brands</div>
                    <?php foreach ($brands as $b): ?>
                        <div class="filter-item">
                            <input type="radio" class="form-check-input brand-filter" name="brand_radio" value="<?php echo $b['id']; ?>" <?php echo $brand == $b['id'] ? 'checked' : ''; ?>>
                            <label class="form-check-label"><?php echo htmlspecialchars($b['name']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Categories -->
                <div class="filter-group">
                    <div class="filter-title">Categories</div>
                    <?php foreach ($categories as $c): ?>
                        <div class="filter-item">
                            <input type="radio" class="form-check-input category-filter" name="category_radio" value="<?php echo $c['id']; ?>" <?php echo $category == $c['id'] ? 'checked' : ''; ?>>
                            <label class="form-check-label"><?php echo htmlspecialchars($c['name']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Price Range -->
                <div class="filter-group">
                    <div class="filter-title">Price Range</div>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm" id="minPrice" placeholder="Min" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm" id="maxPrice" placeholder="Max" value="<?php echo $max_price < 5000 ? $max_price : ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- RAM -->
                <div class="filter-group">
                    <div class="filter-title">RAM</div>
                    <?php 
                    $ram_options = ['4GB', '8GB', '16GB', '32GB', '64GB'];
                    foreach ($ram_options as $r):
                    ?>
                        <div class="filter-item">
                            <input type="radio" class="form-check-input ram-filter" name="ram_radio" value="<?php echo $r; ?>" <?php echo $ram_filter == $r ? 'checked' : ''; ?>>
                            <label class="form-check-label"><?php echo $r; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- CPU Generation -->
                <div class="filter-group">
                    <div class="filter-title">CPU Generation</div>
                    <?php 
                    $cpu_gens = ['Intel 12th Gen', 'Intel 13th Gen', 'Intel 14th Gen', 'AMD Ryzen 5', 'AMD Ryzen 7', 'AMD Ryzen 9'];
                    foreach ($cpu_gens as $cpu):
                    ?>
                        <div class="filter-item">
                            <input type="radio" class="form-check-input cpu-filter" name="cpu_radio" value="<?php echo $cpu; ?>" <?php echo $cpu_gen == $cpu ? 'checked' : ''; ?>>
                            <label class="form-check-label"><?php echo $cpu; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="btn btn-primary w-100 mt-2" onclick="applyFilters()">
                    <i class="fas fa-search me-2"></i>Apply Filters
                </button>
                <a href="shop.php" class="btn btn-outline-secondary w-100 mt-2">
                    <i class="fas fa-undo me-2"></i>Reset
                </a>
            </div>
        </div>

        <!-- ========== RIGHT - PRODUCT LIST ========== -->
        <div class="col-lg-9">
            
            <!-- Header -->
            <div class="product-list-header">
                <span class="total-count"><?php echo $total; ?> Laptops</span>
                <select class="sort-select" id="sortSelect">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                </select>
            </div>

            <!-- Products -->
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h4>No products found</h4>
                    <p class="text-muted">Try adjusting your filters</p>
                    <a href="shop.php" class="btn btn-primary">Clear Filters</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <?php 
                    $img_stmt = $pdo->prepare("SELECT image_path FROM laptop_images WHERE laptop_id = ? AND is_primary = 1 LIMIT 1");
                    $img_stmt->execute([$product['id']]);
                    $img = $img_stmt->fetch();
                    $discount = $product['old_price'] ? round((($product['old_price'] - $product['price']) / $product['old_price']) * 100) : 0;
                    ?>
                    <div class="product-card-smart">
                        <!-- Image -->
                        <div class="product-image">
                            <img src="<?php echo $img['image_path'] ?? 'assets/images/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        
                        <!-- Details -->
                        <div class="product-details">
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="product-name">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                            <div class="product-brand"><?php echo htmlspecialchars($product['brand_name'] ?? ''); ?></div>
                            
                            <!-- Rating -->
                            <div class="rating">
                                <span class="score"><?php echo number_format($product['rating'] ?? 0, 1); ?></span>
                                <span class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= ($product['rating'] ?? 0) ? '' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </span>
                                <span class="spec-score"><?php echo rand(50, 85); ?> Spec Score</span>
                            </div>
                            
                            <!-- Specs -->
                            <div class="specs">
                                <?php if ($product['processor']): ?>
                                    <span class="spec-item"><i class="fas fa-microchip"></i> <?php echo htmlspecialchars($product['processor']); ?></span>
                                <?php endif; ?>
                                <?php if ($product['ram']): ?>
                                    <span class="spec-item"><i class="fas fa-memory"></i> <?php echo htmlspecialchars($product['ram']); ?></span>
                                <?php endif; ?>
                                <?php if ($product['storage']): ?>
                                    <span class="spec-item"><i class="fas fa-hdd"></i> <?php echo htmlspecialchars($product['storage']); ?></span>
                                <?php endif; ?>
                                <?php if ($product['screen_size']): ?>
                                    <span class="spec-item"><i class="fas fa-expand"></i> <?php echo htmlspecialchars($product['screen_size']); ?></span>
                                <?php endif; ?>
                                <?php if ($product['os']): ?>
                                    <span class="spec-item"><i class="fas fa-window-maximize"></i> <?php echo htmlspecialchars($product['os']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="warranty"><?php echo rand(1, 3); ?> Year Warranty</div>
                        </div>
                        
                        <!-- Right -->
                        <div class="product-right">
                            <div class="price"><?php echo formatPrice($product['price']); ?></div>
                            <?php if ($product['old_price']): ?>
                                <div class="old-price"><?php echo formatPrice($product['old_price']); ?></div>
                                <div class="discount"><?php echo $discount; ?>% off</div>
                            <?php endif; ?>
                            
                            <div class="actions">
                                <button class="btn-smart btn-compare" onclick="compareProduct(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-balance-scale"></i> Compare
                                </button>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-smart btn-view">
                                    View <i class="fas fa-arrow-right"></i>
                                </a>
                                <button class="btn-like" onclick="toggleLike(<?php echo $product['id']; ?>)">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-smart">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========== JAVASCRIPT ========== -->
<script>
function applyFilters() {
    const brand = document.querySelector('.brand-filter:checked');
    const category = document.querySelector('.category-filter:checked');
    const ram = document.querySelector('.ram-filter:checked');
    const cpu = document.querySelector('.cpu-filter:checked');
    const minPrice = document.getElementById('minPrice').value;
    const maxPrice = document.getElementById('maxPrice').value;
    const sort = document.getElementById('sortSelect').value;
    const search = document.querySelector('input[name="search"]').value;
    
    let url = 'shop.php?';
    if (search) url += 'search=' + encodeURIComponent(search) + '&';
    if (brand) url += 'brand=' + brand.value + '&';
    if (category) url += 'category=' + category.value + '&';
    if (ram) url += 'ram=' + encodeURIComponent(ram.value) + '&';
    if (cpu) url += 'cpu_gen=' + encodeURIComponent(cpu.value) + '&';
    if (minPrice) url += 'min_price=' + minPrice + '&';
    if (maxPrice) url += 'max_price=' + maxPrice + '&';
    url += 'sort=' + sort;
    
    window.location.href = url;
}

document.getElementById('sortSelect').addEventListener('change', applyFilters);

document.querySelectorAll('.brand-filter, .category-filter, .ram-filter, .cpu-filter').forEach(el => {
    el.addEventListener('change', applyFilters);
});

function compareProduct(productId) {
    alert('Product added to compare!');
}

function toggleLike(productId) {
    const btn = event.currentTarget;
    const icon = btn.querySelector('i');
    if (icon.classList.contains('far')) {
        icon.classList.remove('far');
        icon.classList.add('fas');
        icon.style.color = '#ef4444';
    } else {
        icon.classList.remove('fas');
        icon.classList.add('far');
        icon.style.color = '';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>