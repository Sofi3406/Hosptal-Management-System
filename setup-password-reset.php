<?php
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Password Reset Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:40px;} .success{color:green;} .error{color:red;}</style>";
echo "</head><body>";

echo "<h1>🔐 Password Reset Feature Setup</h1>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Could not connect to database");
    }
    
    echo "<h2>Database Connection</h2>";
    echo "<span class='success'>✅ Connected successfully</span><br><br>";
    
    // Check if table exists
    echo "<h2>Checking password_reset_tokens table...</h2>";
    $query = "SHOW TABLES LIKE 'password_reset_tokens'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✅ Table already exists</span><br>";
    } else {
        echo "<span class='error'>❌ Table does not exist. Creating now...</span><br>";
        
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
        
        echo "<span class='success'>✅ Table created successfully</span><br>";
    }
    
    // Verify table structure
    echo "<h2>Verifying table structure...</h2>";
    $query = "DESCRIBE password_reset_tokens";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Testing functionality...</h2>";
    
    // Test inserting a dummy token
    $test_user_query = "SELECT id FROM users LIMIT 1";
    $stmt = $conn->prepare($test_user_query);
    $stmt->execute();
    $test_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($test_user) {
        $test_token = 'test_' . bin2hex(random_bytes(16));
        $test_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $insert_query = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
        $stmt = $conn->prepare($insert_query);
        $stmt->execute([
            ':user_id' => $test_user['id'],
            ':token' => $test_token,
            ':expires_at' => $test_expires
        ]);
        
        echo "<span class='success'>✅ Test token inserted successfully</span><br>";
        
        // Clean up test token
        $delete_query = "DELETE FROM password_reset_tokens WHERE token = :token";
        $stmt = $conn->prepare($delete_query);
        $stmt->execute([':token' => $test_token]);
        
        echo "<span class='success'>✅ Test token cleaned up</span><br>";
    }
    
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<p>The password reset feature is now ready to use.</p>";
    echo "<p><a href='forgot-password.php'>Test Forgot Password</a> | <a href='login.php'>Go to Login</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<span class='error'>Error: " . $e->getMessage() . "</span><br>";
    echo "<p>Please check your database configuration and try again.</p>";
}

echo "</body></html>";
?>
