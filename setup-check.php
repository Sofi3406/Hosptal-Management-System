<?php
// Setup verification script
echo "<!DOCTYPE html>";
echo "<html><head><title>Hospital Management System - Setup Check</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:40px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";
echo "</head><body>";

echo "<h1>🏥 Hospital Management System - Setup Verification</h1>";

// Check PHP version
echo "<h2>📋 System Requirements</h2>";
echo "PHP Version: " . PHP_VERSION;
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo " <span class='success'>✅ OK</span><br>";
} else {
    echo " <span class='error'>❌ Requires PHP 7.4+</span><br>";
}

// Check required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'session'];
echo "<h3>Required PHP Extensions:</h3>";
foreach ($required_extensions as $ext) {
    echo "- " . $ext . ": ";
    if (extension_loaded($ext)) {
        echo "<span class='success'>✅ Loaded</span><br>";
    } else {
        echo "<span class='error'>❌ Missing</span><br>";
    }
}

// Check directory structure
echo "<h2>📁 Directory Structure</h2>";
$required_dirs = ['config', 'includes', 'dashboard', 'assets/css', 'assets/js', 'scripts'];
foreach ($required_dirs as $dir) {
    echo "- " . $dir . ": ";
    if (is_dir(__DIR__ . '/' . $dir)) {
        echo "<span class='success'>✅ Exists</span><br>";
    } else {
        echo "<span class='error'>❌ Missing</span><br>";
    }
}

// Check required files
echo "<h2>📄 Required Files</h2>";
$required_files = [
    'config/database.php',
    'includes/auth.php',
    'login.php',
    'index.html',
    'assets/css/style.css',
    'assets/js/main.js'
];

foreach ($required_files as $file) {
    echo "- " . $file . ": ";
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<span class='success'>✅ Exists</span><br>";
    } else {
        echo "<span class='error'>❌ Missing</span><br>";
    }
}

// Test database connection
echo "<h2>🗄️ Database Connection</h2>";
try {
    if (file_exists(__DIR__ . '/config/database.php')) {
        require_once __DIR__ . '/config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        if ($conn) {
            echo "<span class='success'>✅ Database connection successful</span><br>";
            
            // Check if tables exist
            $tables = ['users', 'patients', 'appointments', 'billing', 'staff', 'departments', 'inventory'];
            echo "<h3>Database Tables:</h3>";
            foreach ($tables as $table) {
                $query = "SHOW TABLES LIKE '$table'";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    echo "- " . $table . ": <span class='success'>✅ Exists</span><br>";
                } else {
                    echo "- " . $table . ": <span class='error'>❌ Missing</span><br>";
                }
            }
        } else {
            echo "<span class='error'>❌ Database connection failed</span><br>";
        }
    } else {
        echo "<span class='error'>❌ Database config file missing</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Database error: " . $e->getMessage() . "</span><br>";
}

echo "<h2>🚀 Next Steps</h2>";
echo "<ol>";
echo "<li>If any files are missing, make sure all files from the code project are uploaded</li>";
echo "<li>If database tables are missing, run the SQL scripts in the 'scripts' folder</li>";
echo "<li>Update database credentials in config/database.php if needed</li>";
echo "<li>Once everything is green, access <a href='login.php'>login.php</a> to start using the system</li>";
echo "</ol>";

echo "<h2>🔐 Default Login Credentials</h2>";
echo "<div style='background:#f0f0f0;padding:15px;border-radius:5px;'>";
echo "<strong>Admin:</strong> username: admin, password: password<br>";
echo "<strong>Doctor:</strong> username: dr.smith, password: password<br>";
echo "<strong>Patients:</strong> Created by admin with unique credentials";
echo "</div>";

echo "</body></html>";
?>
