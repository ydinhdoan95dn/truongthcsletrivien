<?php
/**
 * Test Database Connection
 */
require_once 'includes/config.php';

echo "<h2>🔍 Kiểm tra kết nối Database</h2>";
echo "<hr>";

// 1. Thông tin từ config
echo "<h3>1. Config hiện tại:</h3>";
echo "DB_NAME: <b>" . DB_NAME . "</b><br>";
echo "DB_HOST: <b>" . DB_HOST . "</b><br>";
echo "DB_USER: <b>" . DB_USER . "</b><br>";
echo "BASE_URL: <b>" . BASE_URL . "</b><br>";
echo "<hr>";

// 2. Test kết nối
try {
    $conn = getDBConnection();
    echo "<h3>2. Kết nối thành công! ✅</h3>";

    // Kiểm tra database hiện tại
    $stmt = $conn->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch();
    echo "Database đang dùng: <b style='color:green; font-size:20px;'>" . $result['current_db'] . "</b><br>";
    echo "<hr>";

    // Đếm số bảng
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    echo "<h3>3. Danh sách bảng (" . count($tables) . " bảng):</h3>";
    echo "<ol>";
    foreach ($tables as $table) {
        $table_name = array_values($table)[0];

        // Đếm số record
        $stmt = $conn->query("SELECT COUNT(*) as count FROM `$table_name`");
        $count = $stmt->fetch();

        echo "<li><b>$table_name</b> - " . number_format($count['count']) . " records</li>";
    }
    echo "</ol>";
    echo "<hr>";

    // Kiểm tra một số bảng quan trọng
    echo "<h3>4. Kiểm tra dữ liệu:</h3>";

    // Lớp học
    $stmt = $conn->query("SELECT COUNT(*) as count FROM lop_hoc");
    $count = $stmt->fetch();
    echo "📚 Số lớp: <b>" . $count['count'] . "</b><br>";

    // Học sinh
    $stmt = $conn->query("SELECT COUNT(*) as count FROM hoc_sinh");
    $count = $stmt->fetch();
    echo "👨‍🎓 Số học sinh: <b>" . $count['count'] . "</b><br>";

    // Môn học
    $stmt = $conn->query("SELECT COUNT(*) as count FROM mon_hoc");
    $count = $stmt->fetch();
    echo "📖 Số môn học: <b>" . $count['count'] . "</b><br>";

    // Tuần học
    $stmt = $conn->query("SELECT COUNT(*) as count FROM tuan_hoc");
    $count = $stmt->fetch();
    echo "📅 Số tuần: <b>" . $count['count'] . "</b><br>";

    echo "<hr>";
    echo "<h3 style='color:green;'>✅ Website đang hoạt động bình thường!</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>❌ Lỗi kết nối!</h3>";
    echo "Error: " . $e->getMessage();
}

echo "<br><br>";
echo "<a href='index.php'>← Quay lại trang chủ</a>";
?>
