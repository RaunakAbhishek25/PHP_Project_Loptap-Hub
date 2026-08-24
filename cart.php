<?php
// cart.php - Shopping Cart (No Shipping, No Tax)
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=cart.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_items = getCartItems($user_id);
$subtotal = calculateCartTotal($user_id);
$total = $subtotal;

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove'])) {
        removeFromCart($user_id, $_POST['laptop_id']);
        header('Location: cart.php');
        exit;
    }
    
    if (isset($_POST['update_quantity'])) {
        $quantity = max(1, intval($_POST['quantity']));
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND laptop_id = ?");
        $stmt->execute([$quantity, $user_id, $_POST['laptop_id']]);
        header('Location: cart.php');
        exit;
    }
}

require_once 'includes/header.php';
?>

<style>
/* ========== CART PAGE STYLES ========== */
.cart-page {
    background: #f8fafc;
    min-height: 70vh;
    padding: 30px 0;
}
.cart-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    transition: all 0.3s;
}
.cart-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
}
.cart-item-img {
    width: 100px;
    height: 100px;
    object-fit: contain;
    padding: 10px;
}
.cart-item-title {
    font-weight: 600;
    color: #1a1a2e;
    font-size: 1rem;
}
.cart-item-brand {
    font-size: 0.85rem;
    color: #94a3b8;
}
.cart-item-price {
    font-weight: 700;
    font-size: 1.2rem;
    color: #2563eb;
}
.cart-item-old-price {
    font-size: 0.9rem;
    color: #94a3b8;
    text-decoration: line-through;
    margin-left: 8px;
}
.cart-item-total {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1a1a2e;
}
.quantity-input {
    width: 70px;
    text-align: center;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
    padding: 6px 8px;
}
.quantity-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
.btn-remove {
    background: none;
    border: none;
    color: #94a3b8;
    transition: 0.3s;
}
.btn-remove:hover {
    color: #ef4444;
}
.summary-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    position: sticky;
    top: 100px;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
}
.summary-total {
    display: flex;
    justify-content: space-between;
    font-size: 1.4rem;
    font-weight: 700;
    color: #1a1a2e;
    border-top: 2px solid #e2e8f0;
    padding-top: 12px;
    margin-top: 8px;
}
.summary-total .total-price {
    color: #2563eb;
}
.btn-checkout {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    border: none;
    border-radius: 12px;
    padding: 14px;
    font-weight: 700;
    font-size: 1.1rem;
    color: white;
    width: 100%;
    transition: all 0.3s;
    margin-top: 16px;
}
.btn-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37,99,235,0.3);
    color: white;
}
.btn-continue {
    border-radius: 12px;
    padding: 12px;
    border: 2px solid #e2e8f0;
    background: white;
    color: #1a1a2e;
    font-weight: 600;
    width: 100%;
    transition: 0.3s;
}
.btn-continue:hover {
    border-color: #2563eb;
    color: #2563eb;
}
.empty-cart {
    text-align: center;
    padding: 60px 20px;
}
.empty-cart i {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 20px;
}
.empty-cart h3 {
    font-weight: 700;
    color: #1a1a2e;
}
.empty-cart p {
    color: #94a3b8;
}
@media (max-width: 768px) {
    .cart-item-img { width: 70px; height: 70px; }
    .cart-item-title { font-size: 0.9rem; }
    .cart-item-price { font-size: 1rem; }
    .quantity-input { width: 55px; }
    .summary-total { font-size: 1.2rem; }
}
</style>

<div class="cart-page">
    <div class="container">
        
        <!-- ========== BREADCRUMB ========== -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active">Shopping Cart</li>
            </ol>
        </nav>

        <h2 class="fw-bold mb-4">
            <i class="fas fa-shopping-cart text-primary me-2"></i>Shopping Cart
        </h2>

        <?php if (empty($cart_items)): ?>
            <!-- ========== EMPTY CART ========== -->
            <div class="cart-card">
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any items to your cart yet.</p>
                    <a href="shop.php" class="btn btn-primary rounded-pill px-4 mt-3">
                        <i class="fas fa-arrow-left me-2"></i>Start Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- ========== CART ITEMS ========== -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-card">
                            <div class="row align-items-center g-3">
                                <!-- Image -->
                                <div class="col-md-2 col-3 text-center">
                                    <img src="<?php echo $item['image'] ?? 'assets/images/placeholder.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="cart-item-img">
                                </div>
                                
                                <!-- Info -->
                                <div class="col-md-4 col-9">
                                    <div class="cart-item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="cart-item-brand"><?php echo htmlspecialchars($item['brand_name'] ?? ''); ?></div>
                                </div>
                                
                                <!-- Price -->
                                <div class="col-md-2 col-4">
                                    <div class="cart-item-price"><?php echo formatPrice($item['price']); ?></div>
                                    <?php if ($item['old_price']): ?>
                                        <div class="cart-item-old-price"><?php echo formatPrice($item['old_price']); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Quantity -->
                                <div class="col-md-2 col-4">
                                    <form method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="laptop_id" value="<?php echo $item['laptop_id']; ?>">
                                        <input type="number" name="quantity" class="quantity-input" 
                                               value="<?php echo $item['quantity']; ?>" min="1" max="10">
                                        <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-secondary rounded-pill" title="Update">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Total & Remove -->
                                <div class="col-md-2 col-4 text-end">
                                    <div class="cart-item-total"><?php echo formatPrice($item['price'] * $item['quantity']); ?></div>
                                    <form method="POST">
                                        <input type="hidden" name="laptop_id" value="<?php echo $item['laptop_id']; ?>">
                                        <button type="submit" name="remove" class="btn-remove" title="Remove">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Continue Shopping -->
                    <a href="shop.php" class="btn-continue">
                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                    </a>
                </div>
                
                <!-- ========== ORDER SUMMARY ========== -->
                <div class="col-lg-4">
                    <div class="summary-card">
                        <h5 class="fw-bold mb-3">
                            <i class="fas fa-receipt text-primary me-2"></i>Order Summary
                        </h5>
                        
                        <div class="summary-row">
                            <span>Subtotal (<?php echo count($cart_items); ?> items)</span>
                            <span><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        
                        <!-- ✅ Total and Proceed Button - Separate -->
                        <div class="summary-total">
                            <span>Total</span>
                            <span class="total-price"><?php echo formatPrice($total); ?></span>
                        </div>
                        
                        <!-- ✅ Proceed to Checkout Button - Total के नीचे -->
                        <a href="checkout.php" class="btn-checkout">
                            <i class="fas fa-lock me-2"></i>Proceed to Checkout
                        </a>
                    </div>
                    
                    <!-- Coupon -->
                    <div class="summary-card mt-3">
                        <h6 class="fw-bold"><i class="fas fa-ticket text-primary me-2"></i>Apply Coupon</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" id="couponInput" placeholder="Enter coupon code">
                            <button class="btn btn-primary" onclick="applyCoupon()">Apply</button>
                        </div>
                        <div id="couponMessage" class="mt-2 small"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ========== APPLY COUPON ==========
function applyCoupon() {
    const code = document.getElementById('couponInput').value;
    const subtotal = <?php echo $subtotal; ?>;
    
    if (!code) {
        document.getElementById('couponMessage').innerHTML = '<span class="text-danger">Please enter coupon code</span>';
        return;
    }
    
    fetch('ajax/validate_coupon.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({coupon: code, subtotal: subtotal})
    })
    .then(res => res.json())
    .then(data => {
        const msg = document.getElementById('couponMessage');
        if (data.success) {
            msg.innerHTML = `<span class="text-success">✅ ${data.message}</span>`;
            setTimeout(() => location.reload(), 2000);
        } else {
            msg.innerHTML = `<span class="text-danger">❌ ${data.message}</span>`;
        }
    })
    .catch(() => {
        document.getElementById('couponMessage').innerHTML = '<span class="text-danger">Error applying coupon</span>';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>