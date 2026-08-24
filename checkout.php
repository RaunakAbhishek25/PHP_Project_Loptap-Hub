<?php
// ============================================
// FILE: checkout.php
// PURPOSE: Checkout page with correct INR prices (No GST)
// ============================================

session_start();
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_items = getCartItems($user_id);

// Redirect if cart is empty
if (empty($cart_items)) {
    header('Location: cart.php?empty=1');
    exit;
}

// Calculate totals in USD (from database)
$subtotal_usd = calculateCartTotal($user_id);
$shipping_usd = 0; // Free shipping
$tax_usd = 0; // NO GST - Removed
$discount_usd = 0;
$coupon_code = '';
$coupon_discount_usd = 0;
$grand_total_usd = $subtotal_usd + $shipping_usd + $tax_usd - $discount_usd;

// Convert to INR for display (1 USD = 83 INR)
$conversion_rate = 83;
$subtotal_inr = $subtotal_usd * $conversion_rate;
$shipping_inr = $shipping_usd * $conversion_rate;
$tax_inr = 0; // NO GST
$discount_inr = $discount_usd * $conversion_rate;
$grand_total_inr = $grand_total_usd * $conversion_rate;

// Handle coupon application
if (isset($_POST['apply_coupon']) && !empty($_POST['coupon_code'])) {
    $coupon_code = trim($_POST['coupon_code']);
    $coupon_result = validateCoupon($coupon_code, $subtotal_usd);
    
    if ($coupon_result) {
        $discount_usd = $coupon_result['discount'];
        $coupon_discount_usd = $discount_usd;
        $_SESSION['coupon_id'] = $coupon_result['coupon_id'];
        $_SESSION['coupon_code'] = $coupon_code;
        $grand_total_usd = $subtotal_usd + $shipping_usd + $tax_usd - $discount_usd;
        
        // Recalculate INR
        $discount_inr = $discount_usd * $conversion_rate;
        $grand_total_inr = $grand_total_usd * $conversion_rate;
        $success_message = "Coupon applied! You saved ₹" . number_format($discount_inr, 2);
    } else {
        $error_message = "Invalid or expired coupon code.";
    }
}

// Handle order placement
$order_error = '';
$order_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Validate required fields
    $required_fields = ['billing_address', 'city', 'state', 'zip_code'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $missing_fields[] = ucfirst(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missing_fields)) {
        $order_error = "Please fill in: " . implode(', ', $missing_fields);
    } else {
        try {
            // Prepare order data (store in USD in database)
            $order_data = [
                'payment_method' => $_POST['payment_method'] ?? 'cod',
                'billing_address' => trim($_POST['billing_address']),
                'shipping_address' => isset($_POST['same_address']) ? trim($_POST['billing_address']) : trim($_POST['shipping_address'] ?? ''),
                'shipping' => $shipping_usd,
                'discount' => $discount_usd,
                'coupon_code' => $_SESSION['coupon_code'] ?? null
            ];
            
            // Add shipping address if different
            if (!isset($_POST['same_address']) && !empty($_POST['shipping_address'])) {
                $order_data['shipping_address'] = trim($_POST['shipping_address']);
            }
            
            // Create order
            $order_id = createOrder($user_id, $order_data);
            
            if ($order_id) {
                // Apply coupon if used
                if (isset($_SESSION['coupon_id'])) {
                    applyCoupon($_SESSION['coupon_id']);
                    unset($_SESSION['coupon_id']);
                    unset($_SESSION['coupon_code']);
                }
                
                $order_success = true;
                
                // Clear cart after successful order
                clearCart($user_id);
                
                // Redirect to order confirmation
                header('Location: order_confirmation.php?order_id=' . $order_id);
                exit;
            } else {
                $order_error = "Failed to place order. Please try again.";
            }
        } catch (Exception $e) {
            $order_error = "Error: " . $e->getMessage();
        }
    }
}

// Get user details for pre-filling
$user = getUserById($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - LaptopHub</title>
    <style>
        /* ========== CHECKOUT STYLES ========== */
        .checkout-page {
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .checkout-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .checkout-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #1a1a2e;
        }
        
        .checkout-header p {
            color: #94a3b8;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 30px;
        }
        
        /* ========== BILLING FORM ========== */
        .checkout-form {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.04);
        }
        
        .checkout-form .section-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 20px;
            color: #1a1a2e;
        }
        
        .checkout-form .section-title i {
            color: #2563eb;
            margin-right: 10px;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #1a1a2e;
            margin-bottom: 6px;
            display: block;
        }
        
        .form-group label .required {
            color: #ef4444;
            margin-left: 2px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 10px 14px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            width: 100%;
        }
        
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            outline: none;
        }
        
        .form-control.error {
            border-color: #ef4444;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        
        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #2563eb;
            cursor: pointer;
        }
        
        .form-check label {
            font-weight: 500;
            cursor: pointer;
            margin: 0;
        }
        
        .payment-methods {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .payment-method {
            flex: 1;
            min-width: 120px;
        }
        
        .payment-method input[type="radio"] {
            display: none;
        }
        
        .payment-method label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .payment-method input:checked + label {
            border-color: #2563eb;
            background: #eff6ff;
            color: #2563eb;
        }
        
        .payment-method label:hover {
            border-color: #2563eb;
        }
        
        /* ========== ORDER SUMMARY ========== */
        .order-summary {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.04);
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .order-summary .summary-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 20px;
            color: #1a1a2e;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 8px;
            background: #f8fafc;
            padding: 4px;
        }
        
        .order-item .item-details {
            flex: 1;
        }
        
        .order-item .item-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .order-item .item-qty {
            color: #94a3b8;
            font-size: 0.8rem;
        }
        
        .order-item .item-price {
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.95rem;
        }
        
        .summary-row.total {
            border-top: 2px solid #e2e8f0;
            padding-top: 16px;
            margin-top: 8px;
            font-size: 1.2rem;
            font-weight: 800;
        }
        
        .summary-row .label {
            color: #94a3b8;
        }
        
        .summary-row .value {
            font-weight: 600;
            color: #1a1a2e;
        }
        
        .summary-row.total .value {
            color: #2563eb;
            font-size: 1.4rem;
        }
        
        /* ========== COUPON ========== */
        .coupon-section {
            margin: 16px 0;
        }
        
        .coupon-section .coupon-input-group {
            display: flex;
            gap: 8px;
        }
        
        .coupon-section .coupon-input-group input {
            flex: 1;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        
        .coupon-section .coupon-input-group input:focus {
            border-color: #2563eb;
            outline: none;
        }
        
        .coupon-section .btn-apply {
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            background: #2563eb;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .coupon-section .btn-apply:hover {
            background: #1d4ed8;
        }
        
        .coupon-applied {
            background: #dcfce7;
            padding: 10px 14px;
            border-radius: 10px;
            color: #16a34a;
            font-weight: 600;
            margin-top: 8px;
        }
        
        /* ========== PLACE ORDER BUTTON ========== */
        .btn-place-order {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
            margin-top: 16px;
        }
        
        .btn-place-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(37, 99, 235, 0.4);
        }
        
        .btn-place-order:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* ========== ALERTS ========== */
        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            .order-summary {
                position: relative;
                top: 0;
            }
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .payment-methods {
                flex-direction: column;
            }
            .checkout-form {
                padding: 20px;
            }
            .order-summary {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="checkout-page">
    <div class="checkout-container">
        
        <!-- Header -->
        <div class="checkout-header">
            <h1><i class="fas fa-shopping-bag text-primary me-2"></i>Checkout</h1>
            <p>Complete your purchase securely</p>
        </div>

        <div class="checkout-grid">
            
            <!-- ========== LEFT: FORM ========== -->
            <div class="checkout-form">
                
                <!-- Error Message -->
                <?php if ($order_error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($order_error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="checkoutForm">
                    
                    <!-- ===== BILLING ADDRESS ===== -->
                    <div class="section-title">
                        <i class="fas fa-address-card"></i>Billing Address
                    </div>
                    
                    <div class="form-group">
                        <label>Full Address <span class="required">*</span></label>
                        <textarea name="billing_address" class="form-control" rows="3" 
                                  placeholder="Enter your billing address" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="city" class="form-control" 
                                   placeholder="Enter city" required value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>State <span class="required">*</span></label>
                            <input type="text" name="state" class="form-control" 
                                   placeholder="Enter state" required value="<?= htmlspecialchars($user['state'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Zip Code <span class="required">*</span></label>
                        <input type="text" name="zip_code" class="form-control" 
                               placeholder="Enter zip code" required value="<?= htmlspecialchars($user['zip_code'] ?? '') ?>">
                    </div>
                    
                    <!-- ===== SHIPPING ADDRESS ===== -->
                    <div class="section-title mt-4">
                        <i class="fas fa-truck"></i>Shipping Address
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" name="same_address" id="same_address" checked 
                               onchange="toggleShippingAddress()">
                        <label for="same_address">Shipping address same as billing</label>
                    </div>
                    
                    <div id="shipping_address_container" style="display: none;">
                        <div class="form-group">
                            <label>Shipping Address</label>
                            <textarea name="shipping_address" class="form-control" rows="3" 
                                      placeholder="Enter shipping address"></textarea>
                        </div>
                    </div>
                    
                    <!-- ===== PAYMENT METHOD ===== -->
                    <div class="section-title mt-4">
                        <i class="fas fa-credit-card"></i>Payment Method
                    </div>
                    
                    <div class="payment-methods">
                        <div class="payment-method">
                            <input type="radio" name="payment_method" value="cod" id="cod" checked>
                            <label for="cod">
                                <i class="fas fa-money-bill-wave"></i> Cash on Delivery
                            </label>
                        </div>
                        <div class="payment-method">
                            <input type="radio" name="payment_method" value="card" id="card">
                            <label for="card">
                                <i class="fas fa-credit-card"></i> Credit/Debit Card
                            </label>
                        </div>
                        <div class="payment-method">
                            <input type="radio" name="payment_method" value="upi" id="upi">
                            <label for="upi">
                                <i class="fas fa-mobile-alt"></i> UPI
                            </label>
                        </div>
                    </div>
                    
                    <!-- ===== PLACE ORDER ===== -->
                    <button type="submit" name="place_order" class="btn-place-order" id="placeOrderBtn">
                        <i class="fas fa-check-circle me-2"></i>Place Order
                    </button>
                    
                </form>
            </div>
            
            <!-- ========== RIGHT: ORDER SUMMARY (NO GST) ========== -->
            <div class="order-summary">
                <div class="summary-title">
                    <i class="fas fa-shopping-cart text-primary me-2"></i>Order Summary
                </div>
                
                <!-- Order Items -->
                <?php foreach ($cart_items as $item): 
                    // Calculate item price in INR
                    $item_price_inr = $item['price'] * $conversion_rate;
                    $item_total_inr = $item_price_inr * $item['quantity'];
                ?>
                    <div class="order-item">
                        <img src="<?= $item['image'] ?? 'assets/images/placeholder.jpg' ?>" 
                             alt="<?= htmlspecialchars($item['name']) ?>">
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars(substr($item['name'], 0, 25)) . (strlen($item['name']) > 25 ? '...' : '') ?></div>
                            <div class="item-qty">Qty: <?= $item['quantity'] ?></div>
                        </div>
                        <div class="item-price">₹<?= number_format($item_total_inr, 2) ?></div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Totals in INR (NO GST) -->
                <div class="summary-row">
                    <span class="label">Subtotal</span>
                    <span class="value">₹<?= number_format($subtotal_inr, 2) ?></span>
                </div>
                
                <div class="summary-row">
                    <span class="label">Shipping</span>
                    <span class="value"><?= $shipping_inr == 0 ? 'FREE' : '₹' . number_format($shipping_inr, 2) ?></span>
                </div>
                
                <?php if ($discount_inr > 0): ?>
                    <div class="summary-row" style="color: #16a34a;">
                        <span class="label">Discount <i class="fas fa-tag"></i></span>
                        <span class="value">-₹<?= number_format($discount_inr, 2) ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Total -->
                <div class="summary-row total">
                    <span class="label">Total</span>
                    <span class="value">₹<?= number_format($grand_total_inr, 2) ?></span>
                </div>
                
                <!-- Coupon -->
                <div class="coupon-section">
                    <form method="POST" action="">
                        <div class="coupon-input-group">
                            <input type="text" name="coupon_code" placeholder="Enter coupon code" 
                                   value="<?= htmlspecialchars($coupon_code) ?>">
                            <button type="submit" name="apply_coupon" class="btn-apply">Apply</button>
                        </div>
                    </form>
                    
                    <?php if ($discount_inr > 0): ?>
                        <div class="coupon-applied">
                            <i class="fas fa-check-circle me-2"></i>
                            Coupon applied! You saved ₹<?= number_format($discount_inr, 2) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        
    </div>
</div>

<!-- ========== JAVASCRIPT ========== -->
<script>
// ==========================================
// TOGGLE SHIPPING ADDRESS
// ==========================================
function toggleShippingAddress() {
    const checkbox = document.getElementById('same_address');
    const container = document.getElementById('shipping_address_container');
    container.style.display = checkbox.checked ? 'none' : 'block';
}

// ==========================================
// FORM VALIDATION
// ==========================================
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('placeOrderBtn');
    const originalText = btn.innerHTML;
    
    // Disable button to prevent double submission
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    btn.disabled = true;
    
    // Re-enable after 5 seconds if stuck (safety)
    setTimeout(() => {
        if (!btn.disabled) return;
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 5000);
});

// ==========================================
// SHOW LOADING ON COUPON APPLY
// ==========================================
document.querySelectorAll('button[name="apply_coupon"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const input = this.parentElement.querySelector('input');
        if (!input.value.trim()) {
            e.preventDefault();
            alert('Please enter a coupon code');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>