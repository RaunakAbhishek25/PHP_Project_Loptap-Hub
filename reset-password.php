<?php
// reset-password.php - Reset Password Page
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
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Validate token
if (empty($token)) {
    $error = 'Invalid reset link. Please try again.';
} else {
    // Check if token is valid
    $stmt = $pdo->prepare("SELECT id, fullname, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = 'Invalid or expired reset link. Please request a new one.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill all fields';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear reset token
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $stmt->execute([$hashed_password, $user['id']]);
            
            $success = 'Password reset successfully! You can now login with your new password.';
            $token = ''; // Clear token after success
            
            // Auto redirect to login after 3 seconds
            echo '<meta http-equiv="refresh" content="3;url=login.php">';
        } catch (Exception $e) {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<style>
    .reset-wrapper {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        position: relative;
        background: url('https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1920&q=80') no-repeat center center/cover;
    }
    
    .reset-wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(26, 26, 46, 0.9));
        z-index: 1;
    }
    
    .reset-card {
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
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .reset-card .brand-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 2rem;
        color: white;
        box-shadow: 0 10px 40px rgba(34, 197, 94, 0.35);
        transition: transform 0.3s ease;
    }
    
    .reset-card .brand-icon:hover {
        transform: scale(1.05) rotate(-5deg);
    }
    
    .reset-card h2 {
        font-weight: 800;
        color: #1a1a2e;
        text-align: center;
        margin-bottom: 5px;
        font-size: 1.6rem;
    }
    
    .reset-card p.subtitle {
        text-align: center;
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 25px;
    }
    
    .reset-card .form-label {
        font-weight: 600;
        color: #1a1a2e;
        font-size: 0.9rem;
        margin-bottom: 6px;
    }
    
    .reset-card .input-group {
        border-radius: 16px;
        overflow: hidden;
    }
    
    .reset-card .input-group-text {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-right: none;
        border-radius: 16px 0 0 16px;
        color: #64748b;
        padding: 0 16px;
        font-size: 1rem;
    }
    
    .reset-card .form-control {
        border-radius: 0 16px 16px 0;
        padding: 14px 18px;
        border: 2px solid #e2e8f0;
        border-left: none;
        transition: all 0.3s ease;
        background: #f8fafc;
        font-size: 1rem;
        color: #1a1a2e;
    }
    
    .reset-card .form-control:focus {
        border-color: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
        background: white;
    }
    
    .reset-card .btn-reset {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border: none;
        border-radius: 16px;
        padding: 16px;
        font-weight: 700;
        font-size: 1.1rem;
        color: white;
        transition: all 0.3s ease;
        width: 100%;
        cursor: pointer;
    }
    
    .reset-card .btn-reset:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(34, 197, 94, 0.4);
    }
    
    .reset-card .btn-reset:active {
        transform: translateY(0);
    }
    
    .reset-card .back-link {
        text-align: center;
        margin-top: 20px;
        color: #64748b;
        font-size: 0.95rem;
    }
    
    .reset-card .back-link a {
        color: #2563eb;
        font-weight: 700;
        text-decoration: none;
        transition: 0.3s;
    }
    
    .reset-card .back-link a:hover {
        text-decoration: underline;
    }
    
    .reset-card .alert {
        border-radius: 16px;
        border: none;
        padding: 14px 20px;
        font-size: 0.95rem;
    }
    
    .reset-card .alert-danger {
        background: #fef2f2;
        color: #dc2626;
    }
    
    .reset-card .alert-success {
        background: #f0fdf4;
        color: #16a34a;
    }
    
    .password-hint {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    @media (max-width: 576px) {
        .reset-card {
            padding: 25px 18px;
            margin: 0 10px;
        }
        .reset-card .brand-icon {
            width: 55px;
            height: 55px;
            font-size: 1.5rem;
        }
        .reset-card h2 {
            font-size: 1.3rem;
        }
        .reset-card .form-control,
        .reset-card .input-group-text {
            font-size: 0.9rem;
            padding: 12px 14px;
        }
        .reset-card .btn-reset {
            font-size: 1rem;
            padding: 14px;
        }
    }
</style>

<div class="reset-wrapper">
    <div class="reset-card">
        <div class="brand-icon">
            <i class="fas fa-lock-open"></i>
        </div>
        
        <h2>Reset Password</h2>
        <p class="subtitle">
            <?php if (!empty($user)): ?>
                Hi <strong><?php echo htmlspecialchars($user['fullname']); ?></strong>, enter your new password below.
            <?php else: ?>
                Set your new password
            <?php endif; ?>
        </p>
        
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
        
        <?php if (empty($error) && !$success && !empty($token)): ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Minimum 8 characters" required id="passwordField">
                        <button type="button" class="btn btn-outline-secondary" 
                                style="border-radius: 0 16px 16px 0; border-left: none;"
                                onclick="togglePassword()">
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
                
                <button type="submit" class="btn-reset">
                    <i class="fas fa-save me-2"></i>Reset Password
                </button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left me-2"></i>Back to Login</a>
        </div>
    </div>
</div>

<script>
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