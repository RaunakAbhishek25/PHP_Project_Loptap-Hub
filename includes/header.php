<?php
// includes/header.php - Premium Enhanced Header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$cart_count = isset($_SESSION['user_id']) ? getCartCount($_SESSION['user_id']) : 0;
$user_name = $_SESSION['user_name'] ?? 'Guest';
$is_logged_in = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaptopHub - Premium Laptop Store</title>
    
    <!-- ========== BOOTSTRAP 5 ========== -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- ========== FONT AWESOME 6 ========== -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- ========== GOOGLE FONTS - INTER ========== -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- ========== ANIMATE.CSS ========== -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- ========== CUSTOM CSS ========== -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        /* ========== GLOBAL ========== */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #60a5fa;
            --secondary: #64748b;
            --accent: #8b5cf6;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
        }
        
        /* ========== NAVBAR ========== */
        .navbar {
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            padding: 10px 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 1px solid rgba(0,0,0,0.04);
        }
        .navbar-scrolled {
            box-shadow: 0 4px 30px rgba(0,0,0,0.08);
            background: rgba(255, 255, 255, 0.98) !important;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        
        /* ========== BRAND ========== */
        .navbar-brand {
            font-weight: 900;
            font-size: 1.8rem;
            color: #1a1a2e !important;
            letter-spacing: -0.5px;
            position: relative;
            padding: 4px 0;
        }
        .navbar-brand i {
            background: linear-gradient(135deg, #2563eb, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }
        .navbar-brand .brand-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #2563eb;
            border-radius: 50%;
            margin-left: 2px;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        
        /* ========== NAV LINKS ========== */
        .nav-link {
            font-weight: 600;
            color: #1e293b !important;
            transition: all 0.3s ease;
            padding: 8px 16px !important;
            border-radius: 10px;
            position: relative;
            font-size: 0.9rem;
        }
        .nav-link i {
            font-size: 0.85rem;
            margin-right: 6px;
            color: #94a3b8;
            transition: 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: #2563eb !important;
            background: rgba(37, 99, 235, 0.06);
        }
        .nav-link:hover i, .nav-link.active i {
            color: #2563eb;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 6px;
            left: 50%;
            width: 0;
            height: 2.5px;
            background: linear-gradient(90deg, #2563eb, #8b5cf6);
            transition: all 0.3s ease;
            transform: translateX(-50%);
            border-radius: 10px;
        }
        .nav-link:hover::after, .nav-link.active::after {
            width: 50%;
        }
        
        /* ========== DROPDOWN ========== */
        .dropdown-menu {
            border: none;
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            border-radius: 16px;
            padding: 8px;
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
            min-width: 220px;
            animation: dropdownFade 0.3s ease-out;
        }
        @keyframes dropdownFade {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dropdown-item {
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
            color: #1e293b;
            font-size: 0.9rem;
        }
        .dropdown-item:hover {
            background: rgba(37, 99, 235, 0.06);
            color: #2563eb;
            transform: translateX(4px);
        }
        .dropdown-item i {
            width: 24px;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .dropdown-item:hover i {
            color: #2563eb;
        }
        .dropdown-divider {
            margin: 6px 0;
            border-color: rgba(0,0,0,0.06);
        }
        
        /* ========== SEARCH ========== */
        .search-wrapper {
            position: relative;
        }
        .search-input {
            border-radius: 50px;
            padding: 9px 20px 9px 42px;
            border: 2px solid #e8edf5;
            font-size: 0.85rem;
            width: 200px;
            transition: all 0.3s ease;
            background: #f8fafc;
            color: #1e293b;
        }
        .search-input::placeholder {
            color: #94a3b8;
        }
        .search-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
            width: 260px;
            background: white;
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.85rem;
            pointer-events: none;
        }
        
        /* ========== CART BADGE ========== */
        .cart-badge {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border-radius: 50%;
            padding: 1px 7px;
            font-size: 0.65rem;
            font-weight: 700;
            position: absolute;
            top: -4px;
            right: -6px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            min-width: 18px;
            text-align: center;
        }
        .cart-icon {
            position: relative;
            font-size: 1.2rem;
            color: #1e293b;
            transition: all 0.3s ease;
            padding: 8px;
            border-radius: 50%;
        }
        .cart-icon:hover {
            color: #2563eb;
            background: rgba(37, 99, 235, 0.06);
        }
        
        /* ========== USER AVATAR ========== */
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #8b5cf6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
        .user-name {
            font-weight: 600;
            color: #1e293b;
            margin-left: 8px;
            font-size: 0.9rem;
        }
        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #1e293b;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 50px;
            transition: 0.3s;
        }
        .user-dropdown-toggle:hover {
            background: rgba(0,0,0,0.04);
        }
        .user-dropdown-toggle::after {
            margin-left: 4px;
            font-size: 0.7rem;
        }
        
        /* ========== BUTTONS ========== */
        .btn-primary-custom {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            border-radius: 50px;
            padding: 7px 22px;
            font-weight: 600;
            font-size: 0.85rem;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }
        .btn-outline-primary-custom {
            border-radius: 50px;
            padding: 7px 22px;
            font-weight: 600;
            font-size: 0.85rem;
            border: 2px solid #2563eb;
            color: #2563eb;
            background: transparent;
            transition: all 0.3s ease;
        }
        .btn-outline-primary-custom:hover {
            background: #2563eb;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.15);
        }
        
        /* ========== NOTIFICATION DOT ========== */
        .notification-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            position: absolute;
            top: 4px;
            right: 4px;
            border: 2px solid white;
        }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .search-input { width: 140px; }
            .search-input:focus { width: 180px; }
            .navbar-brand { font-size: 1.5rem; }
        }
        @media (max-width: 768px) {
            .navbar { padding: 8px 0; }
            .navbar-brand { font-size: 1.2rem; }
            .search-input { width: 100%; }
            .search-input:focus { width: 100%; }
            .btn-primary-custom, .btn-outline-primary-custom { 
                padding: 5px 14px;
                font-size: 0.8rem;
                width: 100%;
            }
            .user-name { display: none; }
            .nav-link::after { display: none; }
        }
        @media (max-width: 576px) {
            .navbar-brand { font-size: 1rem; }
            .navbar-brand i { font-size: 1.3rem; }
            .cart-badge { font-size: 0.55rem; padding: 0 5px; min-width: 16px; }
        }
    </style>
</head>
<body>

<!-- ============================================================ -->
<!-- NAVBAR -->
<!-- ============================================================ -->
<nav class="navbar navbar-expand-lg sticky-top" id="mainNavbar">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand" href="<?php echo $is_logged_in ? 'dashboard.php' : 'index.php'; ?>">
            <i class="fas fa-laptop me-1"></i>LaptopHub<span class="brand-dot"></span>
        </a>
        
        <!-- Toggler -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="fas fa-grip"></i>Dashboard
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" 
                           href="index.php">
                            <i class="fas fa-home"></i>Home
                        </a>
                    </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'shop.php' ? 'active' : ''; ?>" 
                       href="shop.php">
                        <i class="fas fa-store"></i>Shop
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-tags"></i>Categories
                    </a>
                    <ul class="dropdown-menu">
                        <?php
                        try {
                            $cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
                            $categories_list = $cat_stmt->fetchAll();
                            
                            if (!empty($categories_list)) {
                                foreach ($categories_list as $cat) {
                                    $icon = 'fa-tag';
                                    switch($cat['slug']) {
                                        case 'gaming': $icon = 'fa-gamepad'; break;
                                        case 'ultrabook': $icon = 'fa-laptop'; break;
                                        case 'business': $icon = 'fa-briefcase'; break;
                                        case 'student': $icon = 'fa-graduation-cap'; break;
                                    }
                                    echo '<li><a class="dropdown-item" href="categories.php?id=' . $cat['id'] . '">
                                            <i class="fas ' . $icon . ' me-2"></i>' . htmlspecialchars($cat['name']) . '
                                          </a></li>';
                                }
                            } else {
                                $fallback_cats = [
                                    ['gaming', 'fa-gamepad', 'Gaming'],
                                    ['ultrabook', 'fa-laptop', 'Ultrabook'],
                                    ['business', 'fa-briefcase', 'Business'],
                                    ['student', 'fa-graduation-cap', 'Student']
                                ];
                                foreach ($fallback_cats as $fc) {
                                    echo '<li><a class="dropdown-item" href="categories.php?slug=' . $fc[0] . '">
                                            <i class="fas ' . $fc[1] . ' me-2"></i>' . $fc[2] . '
                                          </a></li>';
                                }
                            }
                        } catch (Exception $e) {
                            echo '<li><a class="dropdown-item" href="categories.php?slug=gaming"><i class="fas fa-gamepad me-2"></i>Gaming</a></li>
                                  <li><a class="dropdown-item" href="categories.php?slug=ultrabook"><i class="fas fa-laptop me-2"></i>Ultrabook</a></li>
                                  <li><a class="dropdown-item" href="categories.php?slug=business"><i class="fas fa-briefcase me-2"></i>Business</a></li>
                                  <li><a class="dropdown-item" href="categories.php?slug=student"><i class="fas fa-graduation-cap me-2"></i>Student</a></li>';
                        }
                        ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item fw-bold" href="categories.php">
                            <i class="fas fa-th-large me-2"></i>All Categories
                        </a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" 
                       href="about.php">
                        <i class="fas fa-info-circle"></i>About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'contact.php' ? 'active' : ''; ?>" 
                       href="contact.php">
                        <i class="fas fa-envelope"></i>Contact
                    </a>
                </li>
            </ul>
            
            <!-- Search -->
            <div class="search-wrapper me-2">
                <i class="fas fa-search search-icon"></i>
                <form action="shop.php" method="GET">
                    <input class="search-input" type="search" name="search" placeholder="Search laptops...">
                </form>
            </div>
            
            <!-- Right Icons -->
            <div class="d-flex align-items-center gap-1">
                <!-- Wishlist -->
                <a href="<?php echo $is_logged_in ? 'wishlist.php' : 'login.php'; ?>" 
                   class="text-dark position-relative p-2" title="Wishlist">
                    <i class="fas fa-heart fa-lg"></i>
                </a>
                
                <!-- Cart -->
                <a href="<?php echo $is_logged_in ? 'cart.php' : 'login.php'; ?>" 
                   class="text-dark cart-icon" title="Cart">
                    <i class="fas fa-shopping-cart fa-lg"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <?php if ($is_logged_in): ?>
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="user-dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <span class="user-name d-none d-lg-inline"><?php echo htmlspecialchars($user_name); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-grip"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-shopping-bag"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart"></i>Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary-custom btn-sm">Login</a>
                    <a href="register.php" class="btn btn-primary-custom btn-sm">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- ============================================================ -->
<!-- MAIN CONTENT START -->
<!-- ============================================================ -->
<main>