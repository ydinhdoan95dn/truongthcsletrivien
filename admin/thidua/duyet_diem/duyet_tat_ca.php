<?php
/**
 * ==============================================
 * DUYỆT TẤT CẢ ĐIỂM TRONG TUẦN
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';
require_once '../../../includes/thidua_helper.php';

requireTongPhuTrach();

$conn = getDBConnection();
$admin = getCurrentAdmin();

$tuan_id = isset($_GET['tuan']) ? intval($_GET['tuan']) : 0;

if ($tuan_id <= 0) {
    $_SESSION['error'] = 'Tham số tuần không hợp lệ!';
    header('Location: index.php');
    exit;
}

try {
    $conn->beginTransaction();

    // Lấy thông tin tuần
    $stmtTuan = $conn->prepare("SELECT * FROM tuan_hoc WHERE id = ?");
    $stmtTuan->execute([$tuan_id]);
    $tuanInfo = $stmtTuan->fetch();

    if (!$tuanInfo) {
        $_SESSION['error'] = 'Không tìm thấy tuần!';
        header('Location: index.php');
        exit;
    }

    // Đếm số điểm chờ duyệt
    $stmtCount = $conn->prepare("
        SELECT COUNT(*) as total,
               COUNT(DISTINCT lop_id) as so_lop
        FROM diem_thi_dua_tuan
        WHERE tuan_id = ?
          AND trang_thai IN ('cho_duyet', 'nhap')
    ");
    $stmtCount->execute([$tuan_id]);
    $countInfo = $stmtCount->fetch();

    if ($countInfo['total'] == 0) {
        $_SESSION['error'] = 'Không có điểm nào cần duyệt trong tuần này!';
        header("Location: index.php?tuan={$tuan_id}");
        exit;
    }

    // Duyệt tất cả
    $stmtUpdate = $conn->prepare("
        UPDATE diem_thi_dua_tuan
        SET trang_thai = 'da_duyet',
            nguoi_duyet = ?,
            ngay_duyet = CURDATE(),
            gui_tong_hop_luc = NOW(),
            duyet_luc = NOW()
        WHERE tuan_id = ?
          AND trang_thai IN ('cho_duyet', 'nhap')
    ");
    $stmtUpdate->execute([$admin['id'], $tuan_id]);

    // Log activity
    logThiduaActivity(
        'duyet_tat_ca',
        $admin['id'],
        'admin',
        "Duyệt tất cả điểm tuần #{$tuan_id} - {$countInfo['so_lop']} lớp, {$countInfo['total']} tiêu chí",
        $tuan_id,
        'tuan_hoc',
        null,
        [
            'tuan_id' => $tuan_id,
            'so_lop' => $countInfo['so_lop'],
            'so_tieu_chi' => $countInfo['total']
        ]
    );

    // ============================================================
    // AUTO-CALCULATE: Tính tổng điểm và xếp hạng
    // ============================================================

    // Gọi API tính toán xếp hạng
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, BASE_URL . '/admin/thidua/tinh_toan_xep_hang.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'tuan_id' => $tuan_id,
        'type' => 'tuan'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $response = curl_exec($ch);
    curl_close($ch);

    $calcResult = json_decode($response, true);
    $calcSuccess = isset($calcResult['success']) ? $calcResult['success'] : false;

    $conn->commit();

    if ($calcSuccess) {
        $_SESSION['success'] = "Đã duyệt tất cả thành công! {$countInfo['so_lop']} lớp, {$countInfo['total']} tiêu chí. Xếp hạng đã được cập nhật.";
    } else {
        $_SESSION['success'] = "Đã duyệt tất cả thành công! {$countInfo['so_lop']} lớp, {$countInfo['total']} tiêu chí. (Xếp hạng: " . (isset($calcResult['message']) ? $calcResult['message'] : 'Chưa tính') . ")";
    }

    header("Location: index.php?tuan={$tuan_id}&trang_thai=da_duyet");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = 'Lỗi khi duyệt: ' . $e->getMessage();
    header("Location: index.php?tuan={$tuan_id}");
    exit;
}
?>
