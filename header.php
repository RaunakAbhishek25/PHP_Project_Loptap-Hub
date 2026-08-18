<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$cart_count = isset($_SESSION['user_id']) ? getCartCount($_SESSION['user_id']) : 0;
$user_name = $_SESSION['user_name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaptopHub - Premium Laptop Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3" href="index.php">
            <i class="fas fa-laptop text-primary"></i> LaptopHub
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="shop.php">Shop</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Categories</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="shop.php?category=gaming">Gaming</a></li>
                        <li><a class="dropdown-item" href="shop.php?category=ultrabook">Ultrabook</a></li>
                        <li><a class="dropdown-item" href="shop.php?category=business">Business</a></li>
                        <li><a class="dropdown-item" href="shop.php?category=student">Student</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            </ul>
            
            <form class="d-flex me-3" action="shop.php" method="GET">
                <input class="form-control form-control-sm rounded-pill" type="search" name="search" placeholder="Search laptops...">
            </form>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Wishlist -->
                <a href="wishlist.php" class="text-dark position-relative">
                    <i class="fas fa-heart fa-lg"></i>
                </a>
                
                <!-- Cart -->
                <a href="cart.php" class="text-dark position-relative">
                    <i class="fas fa-shopping-cart fa-lg"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                        <?= $cart_count ?>
                    </span>
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="text-dark dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle fa-2x"></i>
                            <span class="d-none d-md-inline"><?= htmlspecialchars($user_name) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-shopping-bag me-2"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart me-2"></i>Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary btn-sm">Login</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main></main>