<?php
/**
 * ==============================================
 * XÓA PHÂN CÔNG CHẤM ĐIỂM
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';

// Kiểm tra quyền Admin
requireAdmin();

$conn = getDBConnection();
$admin = getCurrentAdmin();

// ============================================================
// XỬ LÝ XÓA
// ============================================================
if (isset($_GET['id']) && $_GET['id'] > 0) {
    $id = intval($_GET['id']);

    try {
        // Lấy thông tin phân công trước khi xóa (để log)
        $stmtInfo = $conn->prepare("
            SELECT
                pc.*,
                hs.ho_ten as ten_hoc_sinh,
                hs.ma_hs,
                lh.ten_lop as lop_duoc_cham
            FROM phan_cong_cham_diem pc
            JOIN hoc_sinh hs ON pc.hoc_sinh_id = hs.id
            JOIN lop_hoc lh ON pc.lop_duoc_cham_id = lh.id
            WHERE pc.id = ?
        ");
        $stmtInfo->execute([$id]);
        $phanCong = $stmtInfo->fetch();

        if (!$phanCong) {
            $_SESSION['error'] = 'Không tìm thấy phân công cần xóa!';
            header('Location: index.php');
            exit;
        }

        // Kiểm tra xem đã có điểm chấm nào chưa
        $stmtCheckDiem = $conn->prepare("
            SELECT COUNT(*) as count
            FROM diem_thi_dua_tuan
            WHERE nguoi_cham = ? AND lop_id = ?
        ");
        $stmtCheckDiem->execute([$phanCong['hoc_sinh_id'], $phanCong['lop_duoc_cham_id']]);
        $hasDiem = $stmtCheckDiem->fetch()['count'] > 0;

        if ($hasDiem) {
            $_SESSION['error'] = 'Không thể xóa! Học sinh này đã chấm điểm cho lớp được phân công. Vui lòng đặt trạng thái "Tạm dừng" thay vì xóa.';
            header('Location: index.php');
            exit;
        }

        // Thực hiện xóa
        $stmtDelete = $conn->prepare("DELETE FROM phan_cong_cham_diem WHERE id = ?");
        $result = $stmtDelete->execute([$id]);

        if ($result) {
            // Log activity
            logThiduaActivity(
                'phan_cong_cham_diem',
                $admin['id'],
                'admin',
                "Xóa phân công: {$phanCong['ten_hoc_sinh']} ({$phanCong['ma_hs']}) chấm lớp {$phanCong['lop_duoc_cham']}",
                $id,
                'phan_cong',
                [
                    'hoc_sinh_id' => $phanCong['hoc_sinh_id'],
                    'lop_duoc_cham_id' => $phanCong['lop_duoc_cham_id'],
                    'ten_hoc_sinh' => $phanCong['ten_hoc_sinh'],
                    'lop_duoc_cham' => $phanCong['lop_duoc_cham']
                ],
                null
            );

            $_SESSION['success'] = 'Xóa phân công thành công!';
        } else {
            $_SESSION['error'] = 'Không thể xóa phân công. Vui lòng thử lại!';
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = 'Lỗi database: ' . $e->getMessage();
    }

} else {
    $_SESSION['error'] = 'ID phân công không hợp lệ!';
}

// Redirect về trang danh sách
header('Location: index.php');
exit;
?>
