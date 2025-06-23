<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

$message = '';
$error = '';
$debug_info = '';

// Check if password_reset_tokens table exists
try {
    $query = "SHOW TABLES LIKE 'password_reset_tokens'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        $error = 'Password reset feature is not properly configured. Please contact the administrator.';
        $debug_info = 'Debug: password_reset_tokens table does not exist.';
    }
} catch (Exception $e) {
    $error = 'Database connection error. Please try again later.';
    $debug_info = 'Debug: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Check if user exists
            $query = "SELECT id, first_name, last_name, role FROM users WHERE email = :email AND is_active = 1";
            $stmt = $conn->prepare($query);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Delete any existing tokens for this user
                $query = "DELETE FROM password_reset_tokens WHERE user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->execute([':user_id' => $user['id']]);
                
                // Insert new token
                $query = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':token' => $token,
                    ':expires_at' => $expires_at
                ]);
                
                // Create reset link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $path = dirname($_SERVER['PHP_SELF']);
                $reset_link = $protocol . "://" . $host . $path . "/reset-password.php?token=" . $token;
                
                $message = "Password reset instructions have been prepared for your email.<br><br>
                          <strong>Demo Mode:</strong> Use this link to reset your password:<br>
                          <a href='$reset_link' style='color: #2c5aa0; word-break: break-all;'>$reset_link</a><br><br>
                          <small>This link will expire in 1 hour.</small>";
                
            } else {
                // Don't reveal if email exists or not for security
                $message = "If an account with that email exists, password reset instructions have been sent.";
            }
            
        } catch (Exception $e) {
            $error = "An error occurred while processing your request. Please try again later.";
            $debug_info = 'Debug: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Hospital Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST">
            <div class="logo" style="justify-content: center; margin-bottom: 2rem;">
                <i class="fas fa-key"></i>
                <h2>Forgot Password</h2>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php if ($debug_info): ?>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-top: 1rem; font-size: 0.9rem;">
                        <?php echo htmlspecialchars($debug_info); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (!$message): ?>
            <p style="text-align: center; color: #666; margin-bottom: 2rem;">
                Enter your email address and we'll send you instructions to reset your password.
            </p>
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter your registered email">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-paper-plane"></i> Send Reset Instructions
            </button>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="login.php" style="color: #2c5aa0; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
            
            <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px; font-size: 0.9rem;">
                <strong>Demo Email Addresses:</strong><br>
                <small>
                    • Admin: admin@hospital.com<br>
                    • Doctor: dr.smith@hospital.com<br>
                    • Patient: patient1@email.com
                </small>
            </div>
        </form>
    </div>
</body>
</html>
