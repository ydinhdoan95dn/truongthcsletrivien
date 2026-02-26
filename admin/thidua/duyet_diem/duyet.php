<?php
/**
 * ==============================================
 * DUYỆT ĐIỂM THI ĐUA
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';
require_once '../../../includes/thidua_helper.php';

requireTongPhuTrach();

$conn = getDBConnection();
$admin = getCurrentAdmin();

// Get parameters
$lop_id = isset($_GET['lop']) ? intval($_GET['lop']) : 0;
$tuan_id = isset($_GET['tuan']) ? intval($_GET['tuan']) : 0;

if ($lop_id <= 0 || $tuan_id <= 0) {
    $_SESSION['error'] = 'Tham số không hợp lệ!';
    header('Location: index.php');
    exit;
}

try {
    $conn->beginTransaction();

    // Lấy tất cả điểm của lớp trong tuần
    $stmtDiem = $conn->prepare("
        SELECT * FROM diem_thi_dua_tuan
        WHERE lop_id = ? AND tuan_id = ?
          AND trang_thai IN ('cho_duyet', 'nhap')
    ");
    $stmtDiem->execute([$lop_id, $tuan_id]);
    $danhSachDiem = $stmtDiem->fetchAll();

    if (count($danhSachDiem) === 0) {
        $_SESSION['error'] = 'Không có điểm nào cần duyệt!';
        header("Location: chi_tiet.php?lop={$lop_id}&tuan={$tuan_id}");
        exit;
    }

    // Lấy thông tin lớp
    $stmtLop = $conn->prepare("SELECT ten_lop FROM lop_hoc WHERE id = ?");
    $stmtLop->execute([$lop_id]);
    $lopInfo = $stmtLop->fetch();

    // Update tất cả điểm thành đã duyệt
    $stmtUpdate = $conn->prepare("
        UPDATE diem_thi_dua_tuan
        SET trang_thai = 'da_duyet',
            nguoi_duyet = ?,
            ngay_duyet = CURDATE(),
            gui_tong_hop_luc = NOW(),
            duyet_luc = NOW()
        WHERE lop_id = ? AND tuan_id = ?
          AND trang_thai IN ('cho_duyet', 'nhap')
    ");
    $stmtUpdate->execute([$admin['id'], $lop_id, $tuan_id]);

    // Log activity
    logThiduaActivity(
        'duyet_diem',
        $admin['id'],
        'admin',
        "Duyệt điểm tuần cho lớp {$lopInfo['ten_lop']} - Tuần #{$tuan_id}",
        $lop_id,
        'diem_tuan',
        null,
        ['lop_id' => $lop_id, 'tuan_id' => $tuan_id, 'so_tieu_chi' => count($danhSachDiem)]
    );

    // TODO: Tính tổng điểm và cập nhật bảng xếp hạng tuần
    // Sẽ implement trong Module 4

    $conn->commit();

    $_SESSION['success'] = "Đã duyệt thành công {$lopInfo['ten_lop']} - " . count($danhSachDiem) . " tiêu chí!";
    header("Location: index.php?tuan={$tuan_id}");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = 'Lỗi khi duyệt điểm: ' . $e->getMessage();
    header("Location: chi_tiet.php?lop={$lop_id}&tuan={$tuan_id}");
    exit;
}
?>
