<?php
// ============================================
// FILE: includes/functions.php
// PURPOSE: Complete Functions File with INR Currency
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';

// ========== CURRENCY FUNCTION ==========
function formatPrice($price) {
    // USD to INR conversion (1 USD = 83 INR)
    $rate = 83;
    $inr = $price * $rate;
    return '₹' . number_format($inr, 2);
}

// ========== SECURITY FUNCTIONS ==========

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ========== USER FUNCTIONS ==========

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCartCount($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    } catch(Exception $e) {
        return 0;
    }
}

function getUserById($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch(Exception $e) {
        return null;
    }
}

// ========== PRODUCT FUNCTIONS ==========

function getFeaturedProducts($limit = 8) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT l.*, b.name as brand_name 
                               FROM laptops l 
                               LEFT JOIN brands b ON l.brand_id = b.id 
                               WHERE l.status = 'active' AND l.is_featured = 1 
                               ORDER BY l.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function getLatestProducts($limit = 4) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT l.*, b.name as brand_name 
                               FROM laptops l 
                               LEFT JOIN brands b ON l.brand_id = b.id 
                               WHERE l.status = 'active' 
                               ORDER BY l.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function getBestSellers($limit = 4) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT l.*, b.name as brand_name 
            FROM laptops l 
            LEFT JOIN brands b ON l.brand_id = b.id 
            WHERE l.status = 'active' AND l.reviews_count > 0 
            ORDER BY l.reviews_count DESC, l.rating DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function getProductById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT l.*, b.name as brand_name, c.name as category_name 
                               FROM laptops l 
                               LEFT JOIN brands b ON l.brand_id = b.id 
                               LEFT JOIN categories c ON l.category_id = c.id 
                               WHERE l.id = ? AND l.status = 'active'");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        // FIX: Ensure specifications is never null to prevent json_decode error
        if ($product && $product['specifications'] === null) {
            $product['specifications'] = '[]';
        }
        
        return $product;
    } catch(Exception $e) {
        return null;
    }
}

function getProductImages($laptop_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM laptop_images WHERE laptop_id = ? ORDER BY is_primary DESC");
        $stmt->execute([$laptop_id]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function getAllProducts($limit = 12, $offset = 0) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT l.*, b.name as brand_name 
                               FROM laptops l 
                               LEFT JOIN brands b ON l.brand_id = b.id 
                               WHERE l.status = 'active' 
                               ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function countAllProducts() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM laptops WHERE status = 'active'");
        return $stmt->fetch()['total'] ?? 0;
    } catch(Exception $e) {
        return 0;
    }
}

function getProductsByCategory($category_id, $limit = 12) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT l.*, b.name as brand_name 
            FROM laptops l 
            LEFT JOIN brands b ON l.brand_id = b.id 
            WHERE l.category_id = ? AND l.status = 'active' 
            ORDER BY l.created_at DESC LIMIT ?
        ");
        $stmt->execute([$category_id, $limit]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function getProductsByBrand($brand_id, $limit = 12) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT l.*, b.name as brand_name 
            FROM laptops l 
            LEFT JOIN brands b ON l.brand_id = b.id 
            WHERE l.brand_id = ? AND l.status = 'active' 
            ORDER BY l.created_at DESC LIMIT ?
        ");
        $stmt->execute([$brand_id, $limit]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function searchProducts($query, $limit = 12) {
    global $pdo;
    try {
        $search_term = '%' . $query . '%';
        $stmt = $pdo->prepare("
            SELECT l.*, b.name as brand_name 
            FROM laptops l 
            LEFT JOIN brands b ON l.brand_id = b.id 
            WHERE l.status = 'active' 
            AND (l.name LIKE ? OR l.description LIKE ? OR b.name LIKE ?) 
            ORDER BY l.created_at DESC LIMIT ?
        ");
        $stmt->execute([$search_term, $search_term, $search_term, $limit]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

// ========== CART FUNCTIONS ==========

function addToCart($user_id, $laptop_id, $quantity = 1) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, laptop_id, quantity) 
                               VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE quantity = quantity + ?");
        return $stmt->execute([$user_id, $laptop_id, $quantity, $quantity]);
    } catch(Exception $e) {
        return false;
    }
}

function removeFromCart($user_id, $laptop_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND laptop_id = ?");
        return $stmt->execute([$user_id, $laptop_id]);
    } catch(Exception $e) {
        return false;
    }
}

function updateCartQuantity($user_id, $laptop_id, $quantity) {
    global $pdo;
    try {
        if ($quantity <= 0) {
            return removeFromCart($user_id, $laptop_id);
        }
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND laptop_id = ?");
        return $stmt->execute([$quantity, $user_id, $laptop_id]);
    } catch(Exception $e) {
        return false;
    }
}

function getCartItems($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, l.name, l.price, l.old_price,
                   (SELECT image_path FROM laptop_images WHERE laptop_id = l.id AND is_primary = 1 LIMIT 1) as image
            FROM cart c 
            JOIN laptops l ON c.laptop_id = l.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function calculateCartTotal($user_id) {
    $items = getCartItems($user_id);
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

function clearCart($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    } catch(Exception $e) {
        return false;
    }
}

// ========== WISHLIST FUNCTIONS ==========

function addToWishlist($user_id, $laptop_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, laptop_id) VALUES (?, ?)");
        return $stmt->execute([$user_id, $laptop_id]);
    } catch(Exception $e) {
        return false;
    }
}

function removeFromWishlist($user_id, $laptop_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND laptop_id = ?");
        return $stmt->execute([$user_id, $laptop_id]);
    } catch(Exception $e) {
        return false;
    }
}

function getWishlistItems($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT w.*, l.*, b.name as brand_name,
                   (SELECT image_path FROM laptop_images WHERE laptop_id = l.id AND is_primary = 1 LIMIT 1) as image
            FROM wishlist w 
            JOIN laptops l ON w.laptop_id = l.id 
            LEFT JOIN brands b ON l.brand_id = b.id 
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function isInWishlist($user_id, $laptop_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND laptop_id = ?");
        $stmt->execute([$user_id, $laptop_id]);
        return $stmt->fetch() !== false;
    } catch(Exception $e) {
        return false;
    }
}

// ========== ORDER FUNCTIONS ==========

function createOrder($user_id, $data) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $order_number = 'ORD-' . date('Ymd') . '-' . uniqid();
        $cart_items = getCartItems($user_id);
        $subtotal = calculateCartTotal($user_id);
        $shipping = $data['shipping'] ?? 0;
        $tax = $subtotal * 0.18;
        $grand_total = $subtotal + $shipping + $tax - ($data['discount'] ?? 0);
        
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, shipping_charge, tax_amount, grand_total, 
                              payment_method, billing_address, shipping_address, coupon_code) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $user_id, $order_number, $subtotal, $shipping, $tax, $grand_total,
            $data['payment_method'], $data['billing_address'], 
            $data['shipping_address'] ?? $data['billing_address'],
            $data['coupon_code'] ?? null
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, laptop_id, quantity, price, total) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['laptop_id'], $item['quantity'], 
                          $item['price'], $item['price'] * $item['quantity']]);
        }
        
        clearCart($user_id);
        
        $pdo->commit();
        return $order_id;
    } catch(Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function getUserOrders($user_id, $limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE user_id = ? 
            ORDER BY created_at DESC LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function getOrderDetails($order_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.fullname, u.email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if ($order) {
            $stmt = $pdo->prepare("
                SELECT oi.*, l.name, l.price as current_price,
                       (SELECT image_path FROM laptop_images WHERE laptop_id = l.id AND is_primary = 1 LIMIT 1) as image
                FROM order_items oi 
                JOIN laptops l ON oi.laptop_id = l.id 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order['items'] = $stmt->fetchAll();
        }
        
        return $order;
    } catch(Exception $e) {
        return null;
    }
}

function updateOrderStatus($order_id, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $order_id]);
    } catch(Exception $e) {
        return false;
    }
}

// ========== COUPON FUNCTIONS ==========

function validateCoupon($code, $subtotal) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' 
                               AND valid_from <= CURDATE() AND valid_to >= CURDATE() 
                               AND used_count < usage_limit");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();
        
        if (!$coupon) return false;
        if ($subtotal < $coupon['min_order_amount']) return false;
        
        $discount = ($coupon['discount_type'] == 'percentage') 
                    ? ($subtotal * $coupon['discount_value'] / 100) 
                    : $coupon['discount_value'];
                    
        if (isset($coupon['max_discount']) && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
        
        return ['discount' => $discount, 'coupon_id' => $coupon['id']];
    } catch(Exception $e) {
        return false;
    }
}

function applyCoupon($coupon_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        return $stmt->execute([$coupon_id]);
    } catch(Exception $e) {
        return false;
    }
}

// ========== REVIEW FUNCTIONS ==========

function addReview($user_id, $laptop_id, $rating, $comment) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, laptop_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $laptop_id, $rating, $comment]);
        
        // Update laptop rating
        $stmt = $pdo->prepare("
            UPDATE laptops 
            SET rating = (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE laptop_id = ?),
                reviews_count = (SELECT COUNT(*) FROM reviews WHERE laptop_id = ?)
            WHERE id = ?
        ");
        $stmt->execute([$laptop_id, $laptop_id, $laptop_id]);
        
        $pdo->commit();
        return true;
    } catch(Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function getReviews($laptop_id, $limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, u.fullname 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.laptop_id = ? 
            ORDER BY r.created_at DESC LIMIT ?
        ");
        $stmt->execute([$laptop_id, $limit]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

// ========== UPLOAD FUNCTIONS ==========

function uploadImage($file, $target_dir = 'uploads/laptops/') {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    $filename = time() . '_' . basename($file['name']);
    $target_file = $target_dir . $filename;
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed)) {
        return false;
    }
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_dir . $filename;
    }
    return false;
}

function deleteImage($file_path) {
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}

// ========== CATEGORY FUNCTIONS ==========

function getAllCategories() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function getCategoryById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(Exception $e) {
        return null;
    }
}

// ========== BRAND FUNCTIONS ==========

function getAllBrands() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM brands ORDER BY name");
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

function getBrandById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(Exception $e) {
        return null;
    }
}

// ========== DASHBOARD STATS ==========

function getDashboardStats() {
    global $pdo;
    try {
        $stats = [];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $stats['users'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM laptops WHERE status = 'active'");
        $stats['products'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
        $stats['orders'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM orders WHERE status != 'cancelled'");
        $stats['revenue'] = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
        $stats['pending_orders'] = $stmt->fetch()['total'];
        
        return $stats;
    } catch(Exception $e) {
        return [
            'users' => 0,
            'products' => 0,
            'orders' => 0,
            'revenue' => 0,
            'pending_orders' => 0
        ];
    }
}

function getMonthlySales() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT DATE_FORMAT(created_at, '%b') as month, 
                   COALESCE(SUM(grand_total), 0) as total 
            FROM orders 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
            AND status != 'cancelled' 
            GROUP BY MONTH(created_at) 
            ORDER BY created_at ASC
        ");
        return $stmt->fetchAll();
    } catch(Exception $e) {
        return [];
    }
}

// ========== DATABASE SETUP FUNCTION ==========
function checkAndCreateTables() {
    global $pdo;
    try {
        // Check if reviews table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'reviews'");
        if ($stmt->rowCount() == 0) {
            // Create reviews table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS reviews (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    laptop_id INT NOT NULL,
                    user_id INT NOT NULL,
                    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                    comment TEXT NOT NULL,
                    created_at DATETIME NOT NULL,
                    FOREIGN KEY (laptop_id) REFERENCES laptops(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_review (laptop_id, user_id)
                )
            ");
        }
        
        // Check and add columns to laptops
        $columns = ['rating', 'reviews_count'];
        foreach ($columns as $column) {
            $stmt = $pdo->query("SHOW COLUMNS FROM laptops LIKE '$column'");
            if ($stmt->rowCount() == 0) {
                if ($column == 'rating') {
                    $pdo->exec("ALTER TABLE laptops ADD COLUMN rating DECIMAL(3,2) DEFAULT 0");
                } else {
                    $pdo->exec("ALTER TABLE laptops ADD COLUMN reviews_count INT DEFAULT 0");
                }
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Table creation error: " . $e->getMessage());
        return false;
    }
}

// Call this function to ensure tables exist
checkAndCreateTables();

?>