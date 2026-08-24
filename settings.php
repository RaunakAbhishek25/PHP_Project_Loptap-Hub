<?php
// admin/manage/settings.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $working_hours = sanitize($_POST['working_hours']);
    
    // Update settings
    $pdo->exec("UPDATE settings SET setting_value = '$address' WHERE setting_key = 'address'");
    $pdo->exec("UPDATE settings SET setting_value = '$phone' WHERE setting_key = 'phone'");
    $pdo->exec("UPDATE settings SET setting_value = '$email' WHERE setting_key = 'email'");
    $pdo->exec("UPDATE settings SET setting_value = '$working_hours' WHERE setting_key = 'working_hours'");
    
    $success = 'Settings updated successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-md-block bg-dark vh-100 p-3">
            <h5 class="text-white">LaptopHub Admin</h5>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white-50" href="../dashboard.php"><i class="fas fa-dashboard me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                <li class="nav-item"><a class="nav-link text-white-50" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Contact Settings</h1>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="card"><div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" name="email" class="form-control" value="<?= htmlspecialchars($settings['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Working Hours</label>
                        <input type="text" name="working_hours" class="form-control" value="<?= htmlspecialchars($settings['working_hours'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Settings</button>
                </div></div>
            </form>
        </main>
    </div>
</div>
</body>
</html>