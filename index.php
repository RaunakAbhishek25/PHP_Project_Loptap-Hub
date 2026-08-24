<?php
// admin/index.php - Admin Login Page
session_start();

// Agar already login hai toh dashboard pe redirect
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill all fields';
    } else {
        try {
            // ✅ Database se Admin fetch करो
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                $error = 'Invalid username or email';
            } else {
                // ✅ Password verify करो
                if (password_verify($password, $admin['password'])) {
                    // ✅ Login Success
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid password! Please check your password.';
                }
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - LaptopHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1920&q=80') no-repeat center center/cover;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(26, 26, 46, 0.9));
            z-index: 0;
        }
        
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 45px 40px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
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
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .login-card .subtitle {
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
            cursor: pointer;
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
        
        .login-card .demo-box {
            background: #f1f5f9;
            border-radius: 16px;
            padding: 12px 16px;
            text-align: center;
            border: 1px dashed #94a3b8;
            margin-top: 15px;
        }
        .login-card .demo-box small {
            color: #64748b;
            font-size: 0.8rem;
        }
        .login-card .demo-box strong {
            color: #2563eb;
        }
        
        @media (max-width: 576px) {
            .login-card {
                padding: 30px 20px;
            }
            .login-card .brand-icon {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
            .login-card h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <!-- Brand Icon -->
        <div class="brand-icon">
            <i class="fas fa-laptop"></i>
        </div>
        
        <h2>Admin Login</h2>
        <p class="subtitle">Login to manage your store</p>
        
        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Enter username or email" required
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
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
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>
        
        <!-- Demo Credentials -->
        <div class="demo-box">
            <small>
                <i class="fas fa-info-circle me-1"></i>
                Demo: <strong>admin@laptophub.com</strong> / <strong>admin123</strong>
            </small>
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

</body>
</html>