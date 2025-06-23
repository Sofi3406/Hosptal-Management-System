<?php
// Test file to check if all paths are working correctly
echo "<h2>Path Testing</h2>";

echo "<h3>Current Directory Structure:</h3>";
echo "Current file: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Parent directory: " . dirname(__DIR__) . "<br>";

echo "<h3>Checking Required Files:</h3>";

// Check config/database.php
$config_path = __DIR__ . '/config/database.php';
echo "Config path: " . $config_path . "<br>";
echo "Config exists: " . (file_exists($config_path) ? "✅ YES" : "❌ NO") . "<br>";

// Check includes/auth.php
$auth_path = __DIR__ . '/includes/auth.php';
echo "Auth path: " . $auth_path . "<br>";
echo "Auth exists: " . (file_exists($auth_path) ? "✅ YES" : "❌ NO") . "<br>";

// List all files in current directory
echo "<h3>Files in current directory:</h3>";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo $file . (is_dir(__DIR__ . '/' . $file) ? ' (directory)' : ' (file)') . "<br>";
    }
}

// Check if config directory exists
if (is_dir(__DIR__ . '/config')) {
    echo "<h3>Files in config directory:</h3>";
    $config_files = scandir(__DIR__ . '/config');
    foreach ($config_files as $file) {
        if ($file != '.' && $file != '..') {
            echo $file . "<br>";
        }
    }
} else {
    echo "<h3>❌ Config directory does not exist!</h3>";
}

// Check if includes directory exists
if (is_dir(__DIR__ . '/includes')) {
    echo "<h3>Files in includes directory:</h3>";
    $include_files = scandir(__DIR__ . '/includes');
    foreach ($include_files as $file) {
        if ($file != '.' && $file != '..') {
            echo $file . "<br>";
        }
    }
} else {
    echo "<h3>❌ Includes directory does not exist!</h3>";
}
?>
