<?php
/**
 * ==============================================
 * DOWNLOAD TÀI LIỆU
 * ==============================================
 */

require_once '../includes/config.php';

// Cho phép cả admin và học sinh tải
if (!isAdminLoggedIn() && !isStudentLoggedIn()) {
    header('HTTP/1.0 403 Forbidden');
    die('Không có quyền truy cập');
}

$conn = getDBConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('HTTP/1.0 404 Not Found');
    die('Không tìm thấy tài liệu');
}

$stmt = $conn->prepare("SELECT file_path, file_name FROM tai_lieu WHERE id = ?");
$stmt->execute(array($id));
$doc = $stmt->fetch();

if (!$doc || empty($doc['file_path'])) {
    header('HTTP/1.0 404 Not Found');
    die('File không tồn tại');
}

$filePath = '../' . $doc['file_path'];
if (!file_exists($filePath)) {
    header('HTTP/1.0 404 Not Found');
    die('File không tồn tại trên server');
}

$fileName = !empty($doc['file_name']) ? $doc['file_name'] : basename($doc['file_path']);

// Detect MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');

readfile($filePath);
exit;
