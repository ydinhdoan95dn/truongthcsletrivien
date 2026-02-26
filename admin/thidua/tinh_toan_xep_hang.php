<?php
/**
 * ==============================================
 * TÍNH TOÁN VÀ XẾP HẠNG TỰ ĐỘNG
 * Module: Admin - Hệ thống Thi đua
 *
 * Chức năng:
 * - Tính tổng điểm có trọng số cho mỗi lớp
 * - Xếp hạng toàn trường và cùng khối
 * - Xếp loại (Xuất sắc, Tốt, Khá, Trung bình, Cần cố gắng)
 * - Được gọi tự động sau khi Admin duyệt điểm tuần
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/permission_helper.php';

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
$tuan_id = isset($_POST['tuan_id']) ? intval($_POST['tuan_id']) : 0;
$type = isset($_POST['type']) ? sanitize($_POST['type']) : 'tuan'; // tuan, thang, hoc_ky

// Validation
if ($tuan_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID tuần không hợp lệ!'
    ]);
    exit;
}

try {
    // ============================================================
    // BƯỚC 1: TÍNH TỔNG ĐIỂM CHO MỖI LỚP
    // ============================================================

    // Lấy danh sách lớp
    $stmtLop = $conn->query("
        SELECT id, ten_lop, khoi, khoi_label
        FROM lop_hoc
        WHERE trang_thai = 1
        ORDER BY khoi, ten_lop
    ");
    $cacLop = $stmtLop->fetchAll();

    $conn->beginTransaction();

    $soLopDaTinh = 0;

    foreach ($cacLop as $lop) {
        $lop_id = $lop['id'];

        // Lấy điểm các tiêu chí đã duyệt
        $stmtDiem = $conn->prepare("
            SELECT
                dtd.tieu_chi_id,
                dtd.diem,
                tc.diem_toi_da,
                tc.trong_so,
                tc.ma_tieu_chi
            FROM diem_thi_dua_tuan dtd
            JOIN tieu_chi_thi_dua tc ON dtd.tieu_chi_id = tc.id
            WHERE dtd.lop_id = ?
              AND dtd.tuan_id = ?
              AND dtd.trang_thai = 'da_duyet'
        ");
        $stmtDiem->execute([$lop_id, $tuan_id]);
        $cacDiem = $stmtDiem->fetchAll();

        // Nếu không có điểm, bỏ qua lớp này
        if (count($cacDiem) == 0) {
            continue;
        }

        // Tính điểm từng tiêu chí và tổng điểm
        $tongDiemTho = 0;
        $tongDiemCoTrongSo = 0;

        $diemHocTap = 0;
        $diemNeNep = 0;
        $diemVeSinh = 0;
        $diemHoatDong = 0;
        $diemDoanKet = 0;

        foreach ($cacDiem as $diem) {
            // Công thức: (điểm / điểm_toi_da) × trọng_số
            $diemCoTrongSo = round(($diem['diem'] / $diem['diem_toi_da']) * $diem['trong_so'], 2);

            $tongDiemTho += $diem['diem'];
            $tongDiemCoTrongSo += $diemCoTrongSo;

            // Lưu điểm từng tiêu chí
            switch ($diem['ma_tieu_chi']) {
                case 'hoc_tap':
                    $diemHocTap = $diemCoTrongSo;
                    break;
                case 'ne_nep':
                    $diemNeNep = $diemCoTrongSo;
                    break;
                case 've_sinh':
                    $diemVeSinh = $diemCoTrongSo;
                    break;
                case 'hoat_dong':
                    $diemHoatDong = $diemCoTrongSo;
                    break;
                case 'doan_ket':
                    $diemDoanKet = $diemCoTrongSo;
                    break;
            }
        }

        // Xếp loại
        $xepLoai = xepLoaiDiem($tongDiemCoTrongSo);

        // Lưu vào bảng xep_hang_lop_tuan
        $stmtInsert = $conn->prepare("
            INSERT INTO xep_hang_lop_tuan
            (
                lop_id, tuan_id,
                tong_diem_tho, tong_diem_co_trong_so,
                diem_hoc_tap, diem_ne_nep, diem_ve_sinh,
                diem_hoat_dong, diem_doan_ket,
                xep_loai,
                created_at, updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                tong_diem_tho = VALUES(tong_diem_tho),
                tong_diem_co_trong_so = VALUES(tong_diem_co_trong_so),
                diem_hoc_tap = VALUES(diem_hoc_tap),
                diem_ne_nep = VALUES(diem_ne_nep),
                diem_ve_sinh = VALUES(diem_ve_sinh),
                diem_hoat_dong = VALUES(diem_hoat_dong),
                diem_doan_ket = VALUES(diem_doan_ket),
                xep_loai = VALUES(xep_loai),
                updated_at = NOW()
        ");

        $stmtInsert->execute([
            $lop_id, $tuan_id,
            $tongDiemTho, $tongDiemCoTrongSo,
            $diemHocTap, $diemNeNep, $diemVeSinh,
            $diemHoatDong, $diemDoanKet,
            $xepLoai
        ]);

        $soLopDaTinh++;
    }

    // ============================================================
    // BƯỚC 2: XẾP HẠNG TOÀN TRƯỜNG
    // ============================================================

    $stmtRank = $conn->prepare("
        SELECT id, tong_diem_co_trong_so
        FROM xep_hang_lop_tuan
        WHERE tuan_id = ?
        ORDER BY tong_diem_co_trong_so DESC, id ASC
    ");
    $stmtRank->execute([$tuan_id]);
    $xepHang = $stmtRank->fetchAll();

    $thuHang = 1;
    foreach ($xepHang as $item) {
        $stmtUpdate = $conn->prepare("
            UPDATE xep_hang_lop_tuan
            SET thu_hang_toan_truong = ?
            WHERE id = ?
        ");
        $stmtUpdate->execute([$thuHang, $item['id']]);
        $thuHang++;
    }

    // ============================================================
    // BƯỚC 3: XẾP HẠNG CÙNG KHỐI
    // ============================================================

    // Lấy danh sách khối
    $stmtKhoi = $conn->query("
        SELECT DISTINCT khoi
        FROM lop_hoc
        WHERE trang_thai = 1
        ORDER BY khoi
    ");
    $cacKhoi = $stmtKhoi->fetchAll();

    foreach ($cacKhoi as $khoiRow) {
        $khoi = $khoiRow['khoi'];

        $stmtRankKhoi = $conn->prepare("
            SELECT xh.id, xh.tong_diem_co_trong_so
            FROM xep_hang_lop_tuan xh
            JOIN lop_hoc lh ON xh.lop_id = lh.id
            WHERE xh.tuan_id = ? AND lh.khoi = ?
            ORDER BY xh.tong_diem_co_trong_so DESC, xh.id ASC
        ");
        $stmtRankKhoi->execute([$tuan_id, $khoi]);
        $xepHangKhoi = $stmtRankKhoi->fetchAll();

        $thuHangKhoi = 1;
        foreach ($xepHangKhoi as $item) {
            $stmtUpdateKhoi = $conn->prepare("
                UPDATE xep_hang_lop_tuan
                SET thu_hang_cung_khoi = ?
                WHERE id = ?
            ");
            $stmtUpdateKhoi->execute([$thuHangKhoi, $item['id']]);
            $thuHangKhoi++;
        }
    }

    $conn->commit();

    // Log activity
    logThiduaActivity(
        'tinh_toan_xep_hang',
        $admin['id'],
        'admin',
        "Tính toán và xếp hạng tuần ID: {$tuan_id} - Số lớp: {$soLopDaTinh}",
        $tuan_id,
        'tuan_hoc'
    );

    echo json_encode([
        'success' => true,
        'message' => "Tính toán xếp hạng thành công! Đã xử lý {$soLopDaTinh} lớp.",
        'data' => [
            'so_lop' => $soLopDaTinh,
            'tuan_id' => $tuan_id
        ]
    ]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Lỗi database: ' . $e->getMessage()
    ]);
}

/**
 * Hàm xếp loại dựa trên điểm
 */
function xepLoaiDiem($diem) {
    if ($diem >= 90) return 'xuat_sac';       // >= 90
    if ($diem >= 80) return 'tot';            // >= 80
    if ($diem >= 70) return 'kha';            // >= 70
    if ($diem >= 50) return 'trung_binh';     // >= 50
    return 'can_co_gang';                      // < 50
}
?>
