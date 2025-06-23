<?php
require_once 'config/database.php';

$status = '';
$error = '';

// Auto-fix when page is loaded
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Could not connect to database");
    }
    
    // Check if table exists
    $query = "SHOW TABLES LIKE 'password_reset_tokens'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Create the table
        $create_table_sql = "
        CREATE TABLE password_reset_tokens (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        )";
        
        $stmt = $conn->prepare($create_table_sql);
        $stmt->execute();
        
        $status = "✅ Password reset table created successfully!";
    } else {
        $status = "✅ Password reset table already exists!";
    }
    
    // Test the functionality
    $query = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $status .= "<br>✅ Found $user_count active users in the system.";
    $status .= "<br>✅ Password reset feature is now ready!";
    
} catch (Exception $e) {
    $error = "❌ Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Password Reset - Hospital Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="logo" style="justify-content: center; margin-bottom: 2rem;">
                <i class="fas fa-tools"></i>
                <h2>Password Reset Fix</h2>
            </div>
            
            <?php if ($status): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $status; ?>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="forgot-password.php" class="btn btn-primary" style="margin-right: 1rem;">
                        <i class="fas fa-key"></i> Test Password Reset
                    </a>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                
                <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                    <h4>Manual Fix:</h4>
                    <p>If the automatic fix didn't work, run this SQL command in your database:</p>
                    <code style="display: block; background: #e9ecef; padding: 10px; border-radius: 3px; font-size: 12px;">
                        CREATE TABLE password_reset_tokens (<br>
                        &nbsp;&nbsp;id INT PRIMARY KEY AUTO_INCREMENT,<br>
                        &nbsp;&nbsp;user_id INT NOT NULL,<br>
                        &nbsp;&nbsp;token VARCHAR(255) NOT NULL,<br>
                        &nbsp;&nbsp;expires_at TIMESTAMP NOT NULL,<br>
                        &nbsp;&nbsp;used BOOLEAN DEFAULT FALSE,<br>
                        &nbsp;&nbsp;created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,<br>
                        &nbsp;&nbsp;FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE<br>
                        );
                    </code>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="index.html" style="color: #2c5aa0; text-decoration: none;">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
