<?php
/**
 * Debug file - Xoa sau khi test xong
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Info</h2>";
echo "<pre>";

// 1. PHP Info
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Host: " . $_SERVER['HTTP_HOST'] . "\n";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 'off') . "\n\n";

// 2. Test config
echo "=== CONFIG ===\n";
require_once 'includes/config.php';
echo "BASE_URL: " . BASE_URL . "\n";
echo "BASE_PATH: " . BASE_PATH . "\n\n";

// 3. Test DB
echo "=== DATABASE ===\n";
try {
    $conn = getDBConnection();
    echo "DB Connection: OK\n";

    $stmt = $conn->query("SELECT COUNT(*) as total FROM admins");
    $result = $stmt->fetch();
    echo "Admins count: " . $result['total'] . "\n";

    $stmt = $conn->query("SELECT username, ho_ten FROM admins LIMIT 1");
    $admin = $stmt->fetch();
    if ($admin) {
        echo "Admin: " . $admin['username'] . " - " . $admin['ho_ten'] . "\n";
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

echo "\n=== EXTENSIONS ===\n";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($required as $ext) {
    echo $ext . ": " . (extension_loaded($ext) ? "OK" : "MISSING") . "\n";
}

echo "</pre>";
?>
