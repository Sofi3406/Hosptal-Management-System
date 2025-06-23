<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Check if user exists
            $query = "SELECT id, first_name, last_name, role, username FROM users WHERE email = :email AND is_active = 1";
            $stmt = $conn->prepare($query);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // For demo purposes, we'll show a temporary password reset
                $temp_password = 'temp' . rand(1000, 9999);
                $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                
                // Update user password temporarily
                $query = "UPDATE users SET password = :password WHERE id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':password' => $hashed_password,
                    ':user_id' => $user['id']
                ]);
                
                $message = "Your password has been temporarily reset.<br><br>
                          <strong>Username:</strong> " . htmlspecialchars($user['username']) . "<br>
                          <strong>Temporary Password:</strong> <code>$temp_password</code><br><br>
                          <small>Please login and change your password immediately.</small>";
                
            } else {
                // Don't reveal if email exists or not for security
                $message = "If an account with that email exists, a temporary password has been set.";
            }
            
        } catch (Exception $e) {
            $error = "An error occurred while processing your request: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Hospital Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST">
            <div class="logo" style="justify-content: center; margin-bottom: 2rem;">
                <i class="fas fa-key"></i>
                <h2>Password Reset</h2>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$message): ?>
            <p style="text-align: center; color: #666; margin-bottom: 2rem;">
                Enter your email address to reset your password.
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
                <i class="fas fa-key"></i> Reset Password
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
