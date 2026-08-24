<?php
// register.php - Clean Registration Page with Background
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Agar already login hai toh dashboard pe redirect
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone'] ?? '');
    
    $form_data = [
        'fullname' => $fullname,
        'email' => $email,
        'phone' => $phone
    ];
    
    // Validation
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'This email is already registered. Please login.';
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, phone) VALUES (?, ?, ?, ?)");
                $stmt->execute([$fullname, $email, $hashed_password, $phone]);
                
                $success = 'Registration successful! Redirecting to login...';
                $form_data = [];
                
                // Redirect to login after 2 seconds
                echo '<meta http-equiv="refresh" content="2;url=login.php">';
            }
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<style>
    /* ========== REGISTER PAGE STYLES ========== */
    .register-page-wrapper {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        position: relative;
        background: url('https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1920&q=80') no-repeat center center/cover;
    }
    
    .register-page-wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(26, 26, 46, 0.9));
        z-index: 1;
    }
    
    .register-card {
        position: relative;
        z-index: 2;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 30px;
        padding: 40px 45px;
        max-width: 480px;
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
    
    .register-card .brand-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563eb, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 2rem;
        color: white;
        box-shadow: 0 10px 40px rgba(37, 99, 235, 0.35);
        transition: transform 0.3s ease;
    }
    
    .register-card .brand-icon:hover {
        transform: scale(1.05) rotate(-5deg);
    }
    
    .register-card h2 {
        font-weight: 800;
        color: #1a1a2e;
        text-align: center;
        margin-bottom: 5px;
        font-size: 1.8rem;
    }
    
    .register-card p.subtitle {
        text-align: center;
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 25px;
    }
    
    .register-card .form-label {
        font-weight: 600;
        color: #1a1a2e;
        font-size: 0.9rem;
        margin-bottom: 6px;
    }
    
    .register-card .input-group {
        border-radius: 16px;
        overflow: hidden;
    }
    
    .register-card .input-group-text {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-right: none;
        border-radius: 16px 0 0 16px;
        color: #64748b;
        padding: 0 16px;
        font-size: 1rem;
    }
    
    .register-card .form-control {
        border-radius: 0 16px 16px 0;
        padding: 12px 18px;
        border: 2px solid #e2e8f0;
        border-left: none;
        transition: all 0.3s ease;
        background: #f8fafc;
        font-size: 0.95rem;
        color: #1a1a2e;
    }
    
    .register-card .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        background: white;
    }
    
    .register-card .form-control::placeholder {
        color: #94a3b8;
    }
    
    .register-card .btn-show-password {
        border: 2px solid #e2e8f0;
        border-left: none;
        border-radius: 0 16px 16px 0;
        background: #f8fafc;
        color: #64748b;
        padding: 0 16px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .register-card .btn-show-password:hover {
        background: #e2e8f0;
        color: #1a1a2e;
    }
    
    .register-card .password-hint {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .register-card .password-hint i {
        font-size: 0.7rem;
    }
    
    .register-card .btn-register {
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
    
    .register-card .btn-register:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(37, 99, 235, 0.35);
    }
    
    .register-card .btn-register:active {
        transform: translateY(0);
    }
    
    .register-card .btn-register::after {
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
    
    .register-card .btn-register:hover::after {
        transform: rotate(45deg) translateX(100%);
    }
    
    .register-card .form-check-label {
        color: #64748b;
        font-size: 0.9rem;
    }
    
    .register-card .form-check-label a {
        color: #2563eb;
        text-decoration: none;
        transition: 0.3s;
    }
    
    .register-card .form-check-label a:hover {
        text-decoration: underline;
    }
    
    .register-card .login-link {
        text-align: center;
        margin-top: 20px;
        color: #64748b;
        font-size: 0.95rem;
    }
    
    .register-card .login-link a {
        color: #2563eb;
        font-weight: 700;
        text-decoration: none;
        transition: 0.3s;
    }
    
    .register-card .login-link a:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }
    
    /* Alert */
    .register-card .alert {
        border-radius: 16px;
        border: none;
        padding: 14px 20px;
        font-size: 0.95rem;
    }
    
    .register-card .alert-danger {
        background: #fef2f2;
        color: #dc2626;
    }
    
    .register-card .alert-success {
        background: #f0fdf4;
        color: #16a34a;
    }
    
    /* Responsive */
    @media (max-width: 576px) {
        .register-card {
            padding: 25px 20px;
            margin: 0 10px;
        }
        .register-card .brand-icon {
            width: 55px;
            height: 55px;
            font-size: 1.5rem;
        }
        .register-card h2 {
            font-size: 1.4rem;
        }
        .register-card .form-control,
        .register-card .input-group-text,
        .register-card .btn-show-password {
            font-size: 0.9rem;
            padding: 10px 14px;
        }
        .register-card .btn-register {
            font-size: 1rem;
            padding: 14px;
        }
    }
    
    @media (max-width: 400px) {
        .register-card {
            padding: 18px 12px;
        }
        .register-card .brand-icon {
            width: 45px;
            height: 45px;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .register-card h2 {
            font-size: 1.2rem;
        }
        .register-card p.subtitle {
            font-size: 0.8rem;
            margin-bottom: 15px;
        }
        .register-card .form-label {
            font-size: 0.8rem;
        }
    }
</style>

<div class="register-page-wrapper">
    <div class="register-card">
        <!-- Brand Icon -->
        <div class="brand-icon">
            <i class="fas fa-user-plus"></i>
        </div>
        
        <h2>Create Account</h2>
        <p class="subtitle">Join LaptopHub and start shopping</p>
        
        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Registration Form -->
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="fullname" class="form-control" 
                           placeholder="Enter your full name" required
                           value="<?php echo htmlspecialchars($form_data['fullname'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" 
                           placeholder="Enter your email" required
                           value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Phone Number <span class="text-muted">(Optional)</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="tel" name="phone" class="form-control" 
                           placeholder="Enter your phone number"
                           value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Minimum 8 characters" required id="passwordField">
                    <button type="button" class="btn-show-password" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <div class="password-hint">
                    <i class="fas fa-info-circle"></i> Password must be at least 8 characters
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                    <input type="password" name="confirm_password" class="form-control" 
                           placeholder="Re-enter your password" required>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a>
                </label>
            </div>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
        </form>
        
        <!-- Login Link -->
        <div class="login-link">
            Already have an account? <a href="login.php">Login Now</a>
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