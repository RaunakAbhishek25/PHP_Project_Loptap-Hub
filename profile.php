<?php
// profile.php
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $state = sanitize($_POST['state']);
    $zipcode = sanitize($_POST['zipcode']);
    
    $stmt = $pdo->prepare("UPDATE users SET fullname = ?, phone = ?, address = ?, city = ?, state = ?, zipcode = ? WHERE id = ?");
    $stmt->execute([$fullname, $phone, $address, $city, $state, $zipcode, $user_id]);
    
    $_SESSION['user_name'] = $fullname;
    $success = 'Profile updated successfully!';
    
    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

// Change Password
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if (!password_verify($current, $user['password'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters';
    } elseif ($new !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
        $success = 'Password changed successfully!';
    }
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="profile.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-user me-2"></i>My Profile
                </a>
                <a href="orders.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-shopping-bag me-2"></i>My Orders
                </a>
                <a href="wishlist.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-heart me-2"></i>Wishlist
                </a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4>My Profile</h4>
                    <hr>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($user['state'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="zipcode" class="form-control" value="<?= htmlspecialchars($user['zipcode'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                    
                    <hr class="mt-4">
                    
                    <h5>Change Password</h5>
                    <form method="POST" class="mt-3">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="password" name="current_password" class="form-control" placeholder="Current Password" required>
                            </div>
                            <div class="col-md-4">
                                <input type="password" name="new_password" class="form-control" placeholder="New Password (min 8 chars)" required>
                            </div>
                            <div class="col-md-4">
                                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning mt-2">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>