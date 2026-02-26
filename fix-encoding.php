<?php
/**
 * Script fix encoding tieng Viet trong database
 * Chay qua trinh duyet de tranh loi encoding cua Windows console
 */
require_once 'includes/config.php';

// Dam bao encoding UTF-8
header('Content-Type: text/html; charset=utf-8');

$conn = getDBConnection();

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Fix Encoding</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
h1 { color: #333; }
h2 { color: #666; margin-top: 30px; }
table { border-collapse: collapse; margin: 10px 0; background: white; }
th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
th { background: #4CAF50; color: white; }
.success { color: green; }
.info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
a { color: #2196F3; }
</style></head><body>";

echo "<h1>Fix Encoding Tieng Viet - Database hoctaptructuyen</h1>";

// ==========================================
// FIX BANG MON_HOC
// ==========================================
echo "<h2>1. Fix bang mon_hoc</h2>";

$mon_hoc_data = array(
    array('id' => 1, 'ten_mon' => 'Toán', 'icon' => 'calculator', 'mau_sac' => '#FF6B6B'),
    array('id' => 2, 'ten_mon' => 'Tiếng Việt', 'icon' => 'book-open', 'mau_sac' => '#4ECDC4'),
    array('id' => 3, 'ten_mon' => 'Tiếng Anh', 'icon' => 'globe', 'mau_sac' => '#45B7D1'),
    array('id' => 4, 'ten_mon' => 'Tự nhiên & Xã hội', 'icon' => 'leaf', 'mau_sac' => '#96CEB4'),
    array('id' => 5, 'ten_mon' => 'Đạo đức', 'icon' => 'heart', 'mau_sac' => '#DDA0DD')
);

foreach ($mon_hoc_data as $mon) {
    $stmt = $conn->prepare("UPDATE mon_hoc SET ten_mon = ? WHERE id = ?");
    $stmt->execute([$mon['ten_mon'], $mon['id']]);
    echo "<p>+ Cap nhat: <strong>" . htmlspecialchars($mon['ten_mon'], ENT_QUOTES, 'UTF-8') . "</strong></p>";
}

// ==========================================
// FIX BANG LOP_HOC
// ==========================================
echo "<h2>2. Fix bang lop_hoc</h2>";

$lop_hoc_data = array(
    array('id' => 1, 'ten_lop' => 'Lớp 1'),
    array('id' => 2, 'ten_lop' => 'Lớp 2'),
    array('id' => 3, 'ten_lop' => 'Lớp 3'),
    array('id' => 4, 'ten_lop' => 'Lớp 4'),
    array('id' => 5, 'ten_lop' => 'Lớp 5')
);

foreach ($lop_hoc_data as $lop) {
    $stmt = $conn->prepare("UPDATE lop_hoc SET ten_lop = ? WHERE id = ?");
    $stmt->execute([$lop['ten_lop'], $lop['id']]);
    echo "<p>+ Cap nhat: <strong>" . htmlspecialchars($lop['ten_lop'], ENT_QUOTES, 'UTF-8') . "</strong></p>";
}

// ==========================================
// FIX BANG TAI_LIEU
// ==========================================
echo "<h2>3. Fix bang tai_lieu (Google Drive documents)</h2>";

// Xoa cac ban ghi cu bi loi encoding
$stmt = $conn->prepare("DELETE FROM tai_lieu WHERE google_drive_id IS NOT NULL");
$stmt->execute();
echo "<p class='info'>Da xoa " . $stmt->rowCount() . " ban ghi cu bi loi encoding.</p>";

// Danh sach tai lieu can them
// mon_hoc_id: 1=Toan, 2=Tieng Viet
// lop_id: 3=Lop 3, 4=Lop 4
$documents = array(
    array(
        'google_drive_id' => '1NqNEtaL_Q2s3V-r3Z8u0b_DKr8C3QCGx',
        'tieu_de' => 'Bồi dưỡng Toán lớp 4 - Nâng cao',
        'mo_ta' => 'Tài liệu bồi dưỡng học sinh giỏi Toán lớp 4, bao gồm các dạng bài tập nâng cao và phương pháp giải',
        'loai_file' => 'pdf',
        'mon_hoc_id' => 1,
        'lop_id' => 4
    ),
    array(
        'google_drive_id' => '1cZGYCX_pWvNdY9aVlSsRAPlxvMWP3R6R',
        'tieu_de' => '50 Đề tăng nhanh điểm Tiếng Việt',
        'mo_ta' => 'Bộ 50 đề luyện tập Tiếng Việt giúp học sinh tăng điểm nhanh chóng',
        'loai_file' => 'pdf',
        'mon_hoc_id' => 2,
        'lop_id' => 3
    ),
    array(
        'google_drive_id' => '1FERdnLXNLHnEKt7W8rBFKM2L-U_xKgxT',
        'tieu_de' => 'Đáp án kiểm tra cuối kỳ 2 Toán 3 - Kết nối tri thức',
        'mo_ta' => 'Đáp án chi tiết bài kiểm tra cuối học kỳ 2 môn Toán lớp 3 theo chương trình Kết nối tri thức',
        'loai_file' => 'pdf',
        'mon_hoc_id' => 1,
        'lop_id' => 3
    ),
    array(
        'google_drive_id' => '1kzyxHB4u_37fGd6ZpSfLQKyc3Hp0DL-F',
        'tieu_de' => 'Đề kiểm tra Toán lớp 3 HK2 - Cánh diều',
        'mo_ta' => 'Đề kiểm tra học kỳ 2 môn Toán lớp 3 theo chương trình Cánh diều',
        'loai_file' => 'word',
        'mon_hoc_id' => 1,
        'lop_id' => 3
    ),
    array(
        'google_drive_id' => '1g8EwpqaZ8QKBJNPNPeKDPLCYp7QS_xPY',
        'tieu_de' => 'Đề kiểm tra cuối kỳ 2 Toán lớp 3',
        'mo_ta' => 'Bộ đề kiểm tra cuối học kỳ 2 môn Toán lớp 3 có đáp án',
        'loai_file' => 'word',
        'mon_hoc_id' => 1,
        'lop_id' => 3
    ),
    array(
        'google_drive_id' => '1d2x_HJqJSmjm1m1JQkqwpxUyP4vAB20e',
        'tieu_de' => 'Đề thi chính thức khối 4 - Năm 2024',
        'mo_ta' => 'Đề thi chính thức dành cho học sinh khối 4 năm học 2023-2024',
        'loai_file' => 'pdf',
        'mon_hoc_id' => 1,
        'lop_id' => 4
    )
);

$thu_tu = 1;
foreach ($documents as $doc) {
    $stmt = $conn->prepare("INSERT INTO tai_lieu (tieu_de, mo_ta, loai_file, mon_hoc_id, lop_id, google_drive_id, thu_tu, is_public, trang_thai, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, NOW())");
    $stmt->execute([
        $doc['tieu_de'],
        $doc['mo_ta'],
        $doc['loai_file'],
        $doc['mon_hoc_id'],
        $doc['lop_id'],
        $doc['google_drive_id'],
        $thu_tu
    ]);
    echo "<p>+ Da them: <strong>" . htmlspecialchars($doc['tieu_de'], ENT_QUOTES, 'UTF-8') . "</strong></p>";
    $thu_tu++;
}

// ==========================================
// KIEM TRA KET QUA
// ==========================================
echo "<h2>4. Kiem tra ket qua</h2>";

echo "<h3>Bang mon_hoc:</h3>";
$stmt = $conn->query("SELECT * FROM mon_hoc ORDER BY id");
echo "<table><tr><th>ID</th><th>Ten mon</th><th>Icon</th><th>Mau sac</th></tr>";
foreach ($stmt->fetchAll() as $row) {
    echo "<tr><td>{$row['id']}</td><td>" . htmlspecialchars($row['ten_mon'], ENT_QUOTES, 'UTF-8') . "</td><td>{$row['icon']}</td><td>{$row['mau_sac']}</td></tr>";
}
echo "</table>";

echo "<h3>Bang lop_hoc:</h3>";
$stmt = $conn->query("SELECT id, ten_lop, khoi FROM lop_hoc ORDER BY id");
echo "<table><tr><th>ID</th><th>Ten lop</th><th>Khoi</th></tr>";
foreach ($stmt->fetchAll() as $row) {
    echo "<tr><td>{$row['id']}</td><td>" . htmlspecialchars($row['ten_lop'], ENT_QUOTES, 'UTF-8') . "</td><td>{$row['khoi']}</td></tr>";
}
echo "</table>";

echo "<h3>Bang tai_lieu (Google Drive):</h3>";
$stmt = $conn->query("SELECT tl.*, mh.ten_mon, lh.ten_lop FROM tai_lieu tl
                      LEFT JOIN mon_hoc mh ON tl.mon_hoc_id = mh.id
                      LEFT JOIN lop_hoc lh ON tl.lop_id = lh.id
                      WHERE tl.google_drive_id IS NOT NULL ORDER BY tl.thu_tu");
echo "<table><tr><th>ID</th><th>Tieu de</th><th>Mo ta</th><th>Mon</th><th>Lop</th><th>Loai</th></tr>";
foreach ($stmt->fetchAll() as $row) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>" . htmlspecialchars($row['tieu_de'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars(mb_substr($row['mo_ta'], 0, 50), ENT_QUOTES, 'UTF-8') . "...</td>";
    echo "<td>" . htmlspecialchars($row['ten_mon'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($row['ten_lop'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . strtoupper($row['loai_file']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2 class='success'>Hoan thanh! Tat ca tieng Viet da duoc fix.</h2>";
echo "<p><a href='test-viewer.php'>=> Test Document Viewer</a></p>";
echo "<p><a href='index.php'>=> Trang chu</a></p>";
echo "</body></html>";
?>
