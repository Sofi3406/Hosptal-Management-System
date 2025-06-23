<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

$error = '';
$success = '';
$valid_token = false;
$user_info = null;

// Check if token is provided and valid
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $query = "SELECT prt.*, u.first_name, u.last_name, u.email, u.role 
              FROM password_reset_tokens prt 
              JOIN users u ON prt.user_id = u.id 
              WHERE prt.token = :token 
              AND prt.expires_at > NOW() 
              AND prt.used = FALSE";
    $stmt = $conn->prepare($query);
    $stmt->execute([':token' => $token]);
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($token_data) {
        $valid_token = true;
        $user_info = $token_data;
    } else {
        $error = 'Invalid or expired reset token. Please request a new password reset.';
    }
} else {
    $error = 'No reset token provided.';
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $conn->beginTransaction();
            
            // Update user password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = :password WHERE id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':password' => $hashed_password,
                ':user_id' => $user_info['user_id']
            ]);
            
            // Mark token as used
            $query = "UPDATE password_reset_tokens SET used = TRUE WHERE token = :token";
            $stmt = $conn->prepare($query);
            $stmt->execute([':token' => $token]);
            
            $conn->commit();
            
            $success = 'Your password has been successfully reset! You can now login with your new password.';
            $valid_token = false; // Hide the form
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'An error occurred while resetting your password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Hospital Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="logo" style="justify-content: center; margin-bottom: 2rem;">
                <i class="fas fa-lock"></i>
                <h2>Reset Password</h2>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="forgot-password.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Request New Reset
                    </a>
                </div>
            <?php elseif ($valid_token): ?>
                <div style="text-align: center; margin-bottom: 2rem;">
                    <p><strong>Hello, <?php echo htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?></strong></p>
                    <p style="color: #666;">Enter your new password below</p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="new_password">
                            <i class="fas fa-lock"></i> New Password
                        </label>
                        <input type="password" id="new_password" name="new_password" required 
                               minlength="6" placeholder="Enter new password">
                        <small style="color: #666;">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Confirm New Password
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               minlength="6" placeholder="Confirm new password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </form>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="login.php" style="color: #2c5aa0; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirm = this.value;
            
            if (confirm && password !== confirm) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
