<?php
// forgot-password.php - Forgot Password Page
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
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Save token in database
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                $stmt->execute([$token, $expires, $user['id']]);
                
                // In a real application, send email here
                // For demo, we'll just show success message
                $success = 'A password reset link has been sent to your email address.';
                $email_sent = true;
                
                // In production, you would send an email with this link:
                // $reset_link = "http://localhost/laptophub/reset-password.php?token=" . $token;
                
            } else {
                // Don't reveal if email exists or not for security
                $success = 'If your email exists in our system, you will receive a password reset link.';
                $email_sent = true;
            }
        } catch (Exception $e) {
            $error = 'Failed to process your request. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<style>
    /* ========== FORGOT PASSWORD PAGE STYLES ========== */
    .forgot-wrapper {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        position: relative;
        background: url('https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1920&q=80') no-repeat center center/cover;
    }
    
    .forgot-wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(26, 26, 46, 0.9));
        z-index: 1;
    }
    
    .forgot-card {
        position: relative;
        z-index: 2;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 30px;
        padding: 45px 40px;
        max-width: 440px;
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
    
    .forgot-card .brand-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 2rem;
        color: white;
        box-shadow: 0 10px 40px rgba(245, 158, 11, 0.35);
        transition: transform 0.3s ease;
    }
    
    .forgot-card .brand-icon:hover {
        transform: scale(1.05) rotate(-5deg);
    }
    
    .forgot-card h2 {
        font-weight: 800;
        color: #1a1a2e;
        text-align: center;
        margin-bottom: 5px;
        font-size: 1.6rem;
    }
    
    .forgot-card p.subtitle {
        text-align: center;
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 25px;
        line-height: 1.5;
    }
    
    .forgot-card .form-label {
        font-weight: 600;
        color: #1a1a2e;
        font-size: 0.9rem;
        margin-bottom: 6px;
    }
    
    .forgot-card .input-group {
        border-radius: 16px;
        overflow: hidden;
    }
    
    .forgot-card .input-group-text {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-right: none;
        border-radius: 16px 0 0 16px;
        color: #64748b;
        padding: 0 16px;
        font-size: 1rem;
    }
    
    .forgot-card .form-control {
        border-radius: 0 16px 16px 0;
        padding: 14px 18px;
        border: 2px solid #e2e8f0;
        border-left: none;
        transition: all 0.3s ease;
        background: #f8fafc;
        font-size: 1rem;
        color: #1a1a2e;
    }
    
    .forgot-card .form-control:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.15);
        background: white;
    }
    
    .forgot-card .form-control::placeholder {
        color: #94a3b8;
    }
    
    .forgot-card .btn-reset {
        background: linear-gradient(135deg, #f59e0b, #d97706);
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
    
    .forgot-card .btn-reset:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(245, 158, 11, 0.4);
    }
    
    .forgot-card .btn-reset:active {
        transform: translateY(0);
    }
    
    .forgot-card .btn-reset::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.15), transparent);
        transform: rotate(45deg) translateX(-100%);
        transition: 0.6s;
    }
    
    .forgot-card .btn-reset:hover::after {
        transform: rotate(45deg) translateX(100%);
    }
    
    .forgot-card .back-link {
        text-align: center;
        margin-top: 20px;
        color: #64748b;
        font-size: 0.95rem;
    }
    
    .forgot-card .back-link a {
        color: #2563eb;
        font-weight: 700;
        text-decoration: none;
        transition: 0.3s;
    }
    
    .forgot-card .back-link a:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }
    
    .forgot-card .alert {
        border-radius: 16px;
        border: none;
        padding: 14px 20px;
        font-size: 0.95rem;
    }
    
    .forgot-card .alert-danger {
        background: #fef2f2;
        color: #dc2626;
    }
    
    .forgot-card .alert-success {
        background: #f0fdf4;
        color: #16a34a;
    }
    
    /* Responsive */
    @media (max-width: 576px) {
        .forgot-card {
            padding: 30px 20px;
            margin: 0 10px;
        }
        .forgot-card .brand-icon {
            width: 55px;
            height: 55px;
            font-size: 1.5rem;
        }
        .forgot-card h2 {
            font-size: 1.3rem;
        }
        .forgot-card p.subtitle {
            font-size: 0.85rem;
        }
        .forgot-card .form-control,
        .forgot-card .input-group-text {
            font-size: 0.9rem;
            padding: 12px 14px;
        }
        .forgot-card .btn-reset {
            font-size: 1rem;
            padding: 14px;
        }
    }
</style>

<div class="forgot-wrapper">
    <div class="forgot-card">
        <!-- Brand Icon -->
        <div class="brand-icon">
            <i class="fas fa-key"></i>
        </div>
        
        <h2>Forgot Password</h2>
        <p class="subtitle">
            Enter your email address and we'll send you a link to reset your password.
        </p>
        
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
        
        <?php if (!$email_sent): ?>
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
                
                <button type="submit" class="btn-reset">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                </button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left me-2"></i>Back to Login</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>