<?php
/**
 * ==============================================
 * AJAX HANDLER: GẮN/GỠ CỜ ĐỎ CHO HỌC SINH
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';

// Set JSON response header
header('Content-Type: application/json');

// Kiểm tra quyền Admin
if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền thực hiện thao tác này!'
    ]);
    exit;
}

// Chỉ nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không hợp lệ!'
    ]);
    exit;
}

$conn = getDBConnection();
$admin = getCurrentAdmin();

// Lấy dữ liệu
$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

// Validation
if ($student_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID học sinh không hợp lệ!'
    ]);
    exit;
}

if (!in_array($action, ['assign', 'remove'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Hành động không hợp lệ!'
    ]);
    exit;
}

try {
    // Kiểm tra học sinh có tồn tại không
    $stmtCheck = $conn->prepare("
        SELECT id, ho_ten, ma_hs, lop_id, la_co_do
        FROM hoc_sinh
        WHERE id = ? AND trang_thai = 1
    ");
    $stmtCheck->execute([$student_id]);
    $student = $stmtCheck->fetch();

    if (!$student) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy học sinh!'
        ]);
        exit;
    }

    // Thực hiện gắn/gỡ Cờ đỏ
    if ($action === 'assign') {
        // GẮN CỜ ĐỎ

        // Kiểm tra đã là Cờ đỏ chưa
        if ($student['la_co_do'] == 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Học sinh đã là Cờ đỏ rồi!'
            ]);
            exit;
        }

        // Update học sinh
        $stmtUpdate = $conn->prepare("
            UPDATE hoc_sinh
            SET la_co_do = 1,
                ngay_gan_co_do = CURDATE(),
                nguoi_gan = ?
            WHERE id = ?
        ");
        $result = $stmtUpdate->execute([$admin['id'], $student_id]);

        if ($result) {
            // Log activity
            logThiduaActivity(
                'gan_co_do',
                $admin['id'],
                'admin',
                "Gắn Cờ đỏ cho học sinh: {$student['ho_ten']} ({$student['ma_hs']})",
                $student_id,
                'hoc_sinh',
                ['la_co_do' => 0],
                ['la_co_do' => 1]
            );

            echo json_encode([
                'success' => true,
                'message' => "Đã gắn Cờ đỏ cho {$student['ho_ten']}",
                'action' => 'assign',
                'student_id' => $student_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể gắn Cờ đỏ. Vui lòng thử lại!'
            ]);
        }

    } else {
        // GỠ CỜ ĐỎ

        // Kiểm tra đang là Cờ đỏ không
        if ($student['la_co_do'] == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Học sinh không phải là Cờ đỏ!'
            ]);
            exit;
        }

        // Kiểm tra xem có đang có phân công chấm điểm không
        $stmtCheckPhanCong = $conn->prepare("
            SELECT COUNT(*) as count
            FROM phan_cong_cham_diem
            WHERE hoc_sinh_id = ? AND trang_thai = 'active'
        ");
        $stmtCheckPhanCong->execute([$student_id]);
        $hasPhanCong = $stmtCheckPhanCong->fetch()['count'] > 0;

        if ($hasPhanCong) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể gỡ Cờ đỏ! Học sinh đang có phân công chấm điểm. Vui lòng xóa phân công trước.'
            ]);
            exit;
        }

        // Kiểm tra xem đã chấm điểm chưa
        $stmtCheckDiem = $conn->prepare("
            SELECT COUNT(*) as count
            FROM diem_thi_dua_tuan
            WHERE nguoi_cham = ?
        ");
        $stmtCheckDiem->execute([$student_id]);
        $hasDiem = $stmtCheckDiem->fetch()['count'] > 0;

        if ($hasDiem) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể gỡ Cờ đỏ! Học sinh đã có lịch sử chấm điểm. Nếu cần, hãy liên hệ quản trị viên.'
            ]);
            exit;
        }

        // Update học sinh
        $stmtUpdate = $conn->prepare("
            UPDATE hoc_sinh
            SET la_co_do = 0,
                ngay_gan_co_do = NULL,
                nguoi_gan = NULL
            WHERE id = ?
        ");
        $result = $stmtUpdate->execute([$student_id]);

        if ($result) {
            // Log activity
            logThiduaActivity(
                'go_co_do',
                $admin['id'],
                'admin',
                "Gỡ Cờ đỏ cho học sinh: {$student['ho_ten']} ({$student['ma_hs']})",
                $student_id,
                'hoc_sinh',
                ['la_co_do' => 1],
                ['la_co_do' => 0]
            );

            echo json_encode([
                'success' => true,
                'message' => "Đã gỡ Cờ đỏ cho {$student['ho_ten']}",
                'action' => 'remove',
                'student_id' => $student_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể gỡ Cờ đỏ. Vui lòng thử lại!'
            ]);
        }
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi database: ' . $e->getMessage()
    ]);
}
?>
