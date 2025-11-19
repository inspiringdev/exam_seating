<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $success = 'Profile updated successfully';
    } else {
        $error = 'Failed to update profile';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    $user = $conn->query("SELECT password FROM users WHERE id = $user_id")->fetch_assoc();
    
    if (password_verify($current, $user['password'])) {
        if ($new_pass === $confirm) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password = '$hashed' WHERE id = $user_id");
            $success = 'Password changed successfully';
        } else {
            $error = 'Passwords do not match';
        }
    } else {
        $error = 'Current password is incorrect';
    }
}

$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Exam Seating System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1>My Profile</h1>
                <p>Manage your account settings</p>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="profile-grid">
            <div class="card">
                <div class="card-header">
                    <h2>Profile Information</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="profile-avatar">
                            <div class="avatar-large">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                            <div class="avatar-info">
                                <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Change Password</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" minlength="6" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" minlength="6" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            Change Password
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Account Details</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Role</label>
                            <p><?php echo ucfirst($user['role']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Member Since</label>
                            <p><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>