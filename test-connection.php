<?php
// Test database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hospital_management", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h2>✅ Database Connection Successful!</h2>";
    
    // Test if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>📋 Tables in database:</h3>";
    echo "<ul>";
    foreach($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Test if users exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>👥 Total users:</strong> " . $userCount['count'] . "</p>";
    
} catch(PDOException $e) {
    echo "<h2>❌ Database Connection Failed!</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<h3>🔧 Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure WAMP is running (green icon)</li>";
    echo "<li>Check if MySQL service is started</li>";
    echo "<li>Verify database 'hospital_management' exists</li>";
    echo "<li>Check username/password in config/database.php</li>";
    echo "</ul>";
}
?>