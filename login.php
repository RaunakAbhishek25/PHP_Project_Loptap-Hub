<?php
// login.php - Clean Login Page with Background
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Agar already login hai toh dashboard pe redirect
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill all fields';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_email'] = $user['email'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<style>
    /* ========== LOGIN PAGE STYLES ========== */
    .login-page-wrapper {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        position: relative;
        background: url('https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1920&q=80') no-repeat center center/cover;
    }
    
    .login-page-wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(26, 26, 46, 0.9));
        z-index: 1;
    }
    
    .login-card {
        position: relative;
        z-index: 2;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 30px;
        padding: 50px 45px;
        max-width: 420px;
        width: 100%;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.2);
        animation: fadeInUp 0.8s ease-out;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(40px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .login-card .brand-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563eb, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2.5rem;
        color: white;
        box-shadow: 0 10px 40px rgba(37, 99, 235, 0.35);
        transition: transform 0.3s ease;
    }
    
    .login-card .brand-icon:hover {
        transform: scale(1.05) rotate(-5deg);
    }
    
    .login-card h2 {
        font-weight: 800;
        color: #1a1a2e;
        text-align: center;
        margin-bottom: 8px;
        font-size: 1.8rem;
    }
    
    .login-card p.subtitle {
        text-align: center;
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 30px;
    }
    
    .login-card .form-label {
        font-weight: 600;
        color: #1a1a2e;
        font-size: 0.9rem;
        margin-bottom: 6px;
    }
    
    .login-card .input-group {
        border-radius: 16px;
        overflow: hidden;
    }
    
    .login-card .input-group-text {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-right: none;
        border-radius: 16px 0 0 16px;
        color: #64748b;
        padding: 0 16px;
        font-size: 1rem;
    }
    
    .login-card .form-control {
        border-radius: 0 16px 16px 0;
        padding: 14px 18px;
        border: 2px solid #e2e8f0;
        border-left: none;
        transition: all 0.3s ease;
        background: #f8fafc;
        font-size: 1rem;
        color: #1a1a2e;
    }
    
    .login-card .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        background: white;
    }
    
    .login-card .form-control::placeholder {
        color: #94a3b8;
    }
    
    .login-card .btn-show-password {
        border: 2px solid #e2e8f0;
        border-left: none;
        border-radius: 0 16px 16px 0;
        background: #f8fafc;
        color: #64748b;
        padding: 0 16px;
        transition: all 0.3s ease;
    }
    
    .login-card .btn-show-password:hover {
        background: #e2e8f0;
        color: #1a1a2e;
    }
    
    .login-card .btn-login {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border: none;
        border-radius: 16px;
        padding: 16px;
        font-weight: 700;
        font-size: 1.1rem;
        color: white;
        transition: all 0.3s ease;
        width: 100%;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }
    
    .login-card .btn-login:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(37, 99, 235, 0.35);
    }
    
    .login-card .btn-login:active {
        transform: translateY(0);
    }
    
    .login-card .btn-login::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transform: rotate(45deg) translateX(-100%);
        transition: 0.6s;
    }
    
    .login-card .btn-login:hover::after {
        transform: rotate(45deg) translateX(100%);
    }
    
    .login-card .form-check-label {
        color: #64748b;
        font-size: 0.9rem;
    }
    
    .login-card .forgot-link {
        color: #2563eb;
        font-weight: 600;
        text-decoration: none;
        font-size: 0.9rem;
        transition: 0.3s;
    }
    
    .login-card .forgot-link:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }
    
    .login-card .register-link {
        text-align: center;
        margin-top: 20px;
        color: #64748b;
        font-size: 0.95rem;
    }
    
    .login-card .register-link a {
        color: #2563eb;
        font-weight: 700;
        text-decoration: none;
        transition: 0.3s;
    }
    
    .login-card .register-link a:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }
    
    /* Alert */
    .login-card .alert {
        border-radius: 16px;
        border: none;
        padding: 14px 20px;
        font-size: 0.95rem;
    }
    
    .login-card .alert-danger {
        background: #fef2f2;
        color: #dc2626;
    }
    
    .login-card .alert-success {
        background: #f0fdf4;
        color: #16a34a;
    }
    
    /* Responsive */
    @media (max-width: 576px) {
        .login-card {
            padding: 30px 20px;
            margin: 0 10px;
        }
        .login-card .brand-icon {
            width: 60px;
            height: 60px;
            font-size: 2rem;
        }
        .login-card h2 {
            font-size: 1.5rem;
        }
        .login-card .form-control,
        .login-card .input-group-text,
        .login-card .btn-show-password {
            font-size: 0.9rem;
            padding: 12px 14px;
        }
        .login-card .btn-login {
            font-size: 1rem;
            padding: 14px;
        }
    }
    
    @media (max-width: 400px) {
        .login-card {
            padding: 20px 15px;
        }
        .login-card .brand-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        .login-card h2 {
            font-size: 1.3rem;
        }
        .login-card p.subtitle {
            font-size: 0.85rem;
            margin-bottom: 20px;
        }
    }
</style>

<div class="login-page-wrapper">
    <div class="login-card">
        <!-- Brand Icon -->
        <div class="brand-icon">
            <i class="fas fa-laptop"></i>
        </div>
        
        <h2>Welcome Back</h2>
        <p class="subtitle">Login to your LaptopHub account</p>
        
        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" 
                           placeholder="Enter your email" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Enter your password" required id="passwordField">
                    <button type="button" class="btn-show-password" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>
        
        <!-- Register Link -->
        <div class="register-link">
            Don't have an account? <a href="register.php">Create Account</a>
        </div>
    </div>
</div>

<script>
// ========== TOGGLE PASSWORD VISIBILITY ==========
function togglePassword() {
    const passwordField = document.getElementById('passwordField');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>