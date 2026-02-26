<?php
/**
 * Thi Dua Helper Functions
 * Các hàm xử lý logic tính điểm và tổng kết thi đua
 *
 * Trường THCS Lê Trí Viễn - Phường Điện Bàn Bắc - TP Đà Nẵng
 * Giáo viên: Đoàn Thị Ngọc Lĩnh
 */

// ============================================================
// TIÊU CHÍ ĐÁNH GIÁ
// ============================================================

/**
 * Lấy tất cả tiêu chí đánh giá đang hoạt động
 * @return array
 */
function getTieuChiDanhGia() {
    $conn = getDBConnection();
    $stmt = $conn->query("
        SELECT *
        FROM tieu_chi_thi_dua
        WHERE trang_thai = 'active'
        ORDER BY thu_tu
    ");

    return $stmt->fetchAll();
}

/**
 * Lấy thông tin một tiêu chí cụ thể
 * @param int $tieu_chi_id
 * @return array|null
 */
function getTieuChi($tieu_chi_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM tieu_chi_thi_dua WHERE id = ?");
    $stmt->execute([$tieu_chi_id]);

    return $stmt->fetch();
}

/**
 * Lấy tiêu chí theo mã
 * @param string $ma_tieu_chi
 * @return array|null
 */
function getTieuChiByMa($ma_tieu_chi) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM tieu_chi_thi_dua WHERE ma_tieu_chi = ?");
    $stmt->execute([$ma_tieu_chi]);

    return $stmt->fetch();
}

// ============================================================
// CHẤM ĐIỂM TUẦN
// ============================================================

/**
 * Lưu điểm thi đua cho một lớp trong tuần
 *
 * @param int $lop_id ID lớp
 * @param int $tuan_id ID tuần
 * @param int $tieu_chi_id ID tiêu chí
 * @param int $diem Điểm (0-10)
 * @param int $nguoi_cham ID học sinh Cờ đỏ chấm
 * @param string|null $ghi_chu Ghi chú
 * @return bool
 */
function saveDiemTuan($lop_id, $tuan_id, $tieu_chi_id, $diem, $nguoi_cham, $ghi_chu = null) {
    $conn = getDBConnection();

    // Kiểm tra đã có điểm chưa
    $stmt = $conn->prepare("
        SELECT id FROM diem_thi_dua_tuan
        WHERE lop_id = ? AND tuan_id = ? AND tieu_chi_id = ?
    ");
    $stmt->execute([$lop_id, $tuan_id, $tieu_chi_id]);
    $existing = $stmt->fetch();

    $ngay_cham = date('Y-m-d');

    if ($existing) {
        // Update
        $stmt = $conn->prepare("
            UPDATE diem_thi_dua_tuan
            SET diem = ?,
                nguoi_cham = ?,
                ngay_cham = ?,
                ghi_chu = ?,
                trang_thai = 'nhap'
            WHERE id = ?
        ");
        $result = $stmt->execute([$diem, $nguoi_cham, $ngay_cham, $ghi_chu, $existing['id']]);
    } else {
        // Insert
        $stmt = $conn->prepare("
            INSERT INTO diem_thi_dua_tuan (
                lop_id, tuan_id, tieu_chi_id, diem,
                nguoi_cham, ngay_cham, ghi_chu, trang_thai
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'nhap')
        ");
        $result = $stmt->execute([
            $lop_id, $tuan_id, $tieu_chi_id, $diem,
            $nguoi_cham, $ngay_cham, $ghi_chu
        ]);
    }

    // Log activity
    if ($result) {
        logThiduaActivity(
            'cham_diem',
            $nguoi_cham,
            'hoc_sinh',
            "Chấm điểm tuần cho lớp ID $lop_id, tiêu chí ID $tieu_chi_id: $diem điểm",
            $lop_id,
            'lop',
            null,
            ['diem' => $diem, 'tieu_chi_id' => $tieu_chi_id]
        );
    }

    return $result;
}

/**
 * Lấy điểm của một lớp trong tuần theo tiêu chí
 *
 * @param int $lop_id
 * @param int $tuan_id
 * @return array Mảng điểm theo từng tiêu chí
 */
function getDiemTuan($lop_id, $tuan_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT
            d.*,
            tc.ma_tieu_chi,
            tc.ten_tieu_chi,
            tc.trong_so,
            hs.ho_ten as ten_nguoi_cham
        FROM diem_thi_dua_tuan d
        JOIN tieu_chi_thi_dua tc ON d.tieu_chi_id = tc.id
        LEFT JOIN hoc_sinh hs ON d.nguoi_cham = hs.id
        WHERE d.lop_id = ? AND d.tuan_id = ?
        ORDER BY tc.thu_tu
    ");
    $stmt->execute([$lop_id, $tuan_id]);

    return $stmt->fetchAll();
}

/**
 * Tính tổng điểm trung bình có trọng số của lớp trong tuần
 * Công thức: Σ(Điểm tiêu chí × Tỷ trọng / 100)
 *
 * @param int $lop_id
 * @param int $tuan_id
 * @return float
 */
function tinhDiemTrungBinhTuan($lop_id, $tuan_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT
            SUM(d.diem * tc.trong_so / 100) as tong_diem
        FROM diem_thi_dua_tuan d
        JOIN tieu_chi_thi_dua tc ON d.tieu_chi_id = tc.id
        WHERE d.lop_id = ? AND d.tuan_id = ? AND d.trang_thai = 'da_duyet'
    ");
    $stmt->execute([$lop_id, $tuan_id]);
    $result = $stmt->fetch();

    return $result && $result['tong_diem'] ? round($result['tong_diem'], 2) : 0;
}

/**
 * Duyệt điểm tuần (TPT hoặc Admin)
 *
 * @param int $diem_id ID bản ghi điểm
 * @param int $nguoi_duyet ID người duyệt
 * @param bool $approve true = duyệt, false = từ chối
 * @param string|null $ly_do_tu_choi Lý do từ chối (nếu có)
 * @return bool
 */
function duyetDiemTuan($diem_id, $nguoi_duyet, $approve = true, $ly_do_tu_choi = null) {
    $conn = getDBConnection();

    $trang_thai = $approve ? 'da_duyet' : 'tu_choi';
    $ngay_duyet = date('Y-m-d');

    $stmt = $conn->prepare("
        UPDATE diem_thi_dua_tuan
        SET trang_thai = ?,
            nguoi_duyet = ?,
            ngay_duyet = ?,
            ly_do_tu_choi = ?
        WHERE id = ?
    ");

    $result = $stmt->execute([$trang_thai, $nguoi_duyet, $ngay_duyet, $ly_do_tu_choi, $diem_id]);

    // Log activity
    if ($result) {
        $action = $approve ? 'duyệt' : 'từ chối';
        logThiduaActivity(
            'duyet_diem',
            $nguoi_duyet,
            'admin',
            "Đã $action điểm tuần ID $diem_id",
            $diem_id,
            'diem_tuan',
            null,
            ['trang_thai' => $trang_thai, 'ly_do' => $ly_do_tu_choi]
        );
    }

    return $result;
}

// ============================================================
// TỔNG KẾT THÁNG
// ============================================================

/**
 * Tính điểm tổng kết tháng cho một lớp
 * Công thức: Trung bình điểm các tuần trong tháng
 *
 * @param int $lop_id
 * @param int $thang Tháng (1-12)
 * @param int $nam Năm
 * @return float
 */
function tinhDiemThang($lop_id, $thang, $nam) {
    $conn = getDBConnection();

    // Lấy các tuần trong tháng
    $stmt = $conn->prepare("
        SELECT id FROM tuan_hoc
        WHERE MONTH(ngay_bat_dau) = ? AND YEAR(ngay_bat_dau) = ?
    ");
    $stmt->execute([$thang, $nam]);
    $weeks = $stmt->fetchAll();

    if (empty($weeks)) {
        return 0;
    }

    // Tính trung bình điểm các tuần
    $tong_diem = 0;
    $so_tuan = 0;

    foreach ($weeks as $week) {
        $diem_tuan = tinhDiemTrungBinhTuan($lop_id, $week['id']);
        if ($diem_tuan > 0) {
            $tong_diem += $diem_tuan;
            $so_tuan++;
        }
    }

    return $so_tuan > 0 ? round($tong_diem / $so_tuan, 2) : 0;
}

/**
 * Lưu tổng kết tháng
 *
 * @param int $lop_id
 * @param int $thang
 * @param int $nam
 * @param float $tong_diem
 * @param int $nguoi_tong_ket
 * @param string|null $ghi_chu
 * @return bool
 */
function saveTongKetThang($lop_id, $thang, $nam, $tong_diem, $nguoi_tong_ket, $ghi_chu = null) {
    $conn = getDBConnection();

    $xep_loai = getXepLoai($tong_diem);
    $thu_hang = tinhThuHangThang($lop_id, $thang, $nam);
    $ngay_tong_ket = date('Y-m-d');

    // Kiểm tra đã tồn tại chưa
    $stmt = $conn->prepare("
        SELECT id FROM tong_ket_thang
        WHERE lop_id = ? AND thang = ? AND nam = ?
    ");
    $stmt->execute([$lop_id, $thang, $nam]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update
        $stmt = $conn->prepare("
            UPDATE tong_ket_thang
            SET tong_diem = ?,
                xep_loai = ?,
                thu_hang = ?,
                ghi_chu = ?,
                nguoi_tong_ket = ?,
                ngay_tong_ket = ?
            WHERE id = ?
        ");
        $result = $stmt->execute([
            $tong_diem, $xep_loai, $thu_hang, $ghi_chu,
            $nguoi_tong_ket, $ngay_tong_ket, $existing['id']
        ]);
    } else {
        // Insert
        $stmt = $conn->prepare("
            INSERT INTO tong_ket_thang (
                lop_id, thang, nam, tong_diem, xep_loai,
                thu_hang, ghi_chu, nguoi_tong_ket, ngay_tong_ket
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $lop_id, $thang, $nam, $tong_diem, $xep_loai,
            $thu_hang, $ghi_chu, $nguoi_tong_ket, $ngay_tong_ket
        ]);
    }

    // Log activity
    if ($result) {
        logThiduaActivity(
            'tong_ket',
            $nguoi_tong_ket,
            'admin',
            "Tổng kết tháng $thang/$nam cho lớp ID $lop_id: $tong_diem điểm ($xep_loai)",
            $lop_id,
            'tong_ket_thang',
            null,
            ['tong_diem' => $tong_diem, 'xep_loai' => $xep_loai, 'thu_hang' => $thu_hang]
        );
    }

    return $result;
}

/**
 * Tính thứ hạng của lớp trong tháng
 *
 * @param int $lop_id
 * @param int $thang
 * @param int $nam
 * @return int
 */
function tinhThuHangThang($lop_id, $thang, $nam) {
    $conn = getDBConnection();

    // Lấy tất cả các lớp và điểm tháng
    $stmt = $conn->prepare("
        SELECT lop_id, tong_diem
        FROM tong_ket_thang
        WHERE thang = ? AND nam = ?
        ORDER BY tong_diem DESC
    ");
    $stmt->execute([$thang, $nam]);
    $rankings = $stmt->fetchAll();

    $thu_hang = 1;
    foreach ($rankings as $rank) {
        if ($rank['lop_id'] == $lop_id) {
            return $thu_hang;
        }
        $thu_hang++;
    }

    return null;
}

// ============================================================
// TỔNG KẾT HỌC KỲ
// ============================================================

/**
 * Tính điểm tổng kết học kỳ cho một lớp
 * Công thức: Trung bình điểm các tháng trong học kỳ
 *
 * @param int $lop_id
 * @param int $hoc_ky 1 hoặc 2
 * @param string $nam_hoc VD: '2024-2025'
 * @return float
 */
function tinhDiemHocKy($lop_id, $hoc_ky, $nam_hoc) {
    $conn = getDBConnection();

    // Xác định các tháng trong học kỳ
    // HK1: tháng 9-12, HK2: tháng 1-5
    list($nam_start, $nam_end) = explode('-', $nam_hoc);

    if ($hoc_ky == 1) {
        $months = [9, 10, 11, 12];
        $nam = (int)$nam_start;
    } else {
        $months = [1, 2, 3, 4, 5];
        $nam = (int)$nam_end;
    }

    // Lấy điểm các tháng
    $tong_diem = 0;
    $so_thang = 0;

    foreach ($months as $thang) {
        $stmt = $conn->prepare("
            SELECT tong_diem FROM tong_ket_thang
            WHERE lop_id = ? AND thang = ? AND nam = ?
        ");
        $stmt->execute([$lop_id, $thang, $nam]);
        $result = $stmt->fetch();

        if ($result && $result['tong_diem'] > 0) {
            $tong_diem += $result['tong_diem'];
            $so_thang++;
        }
    }

    return $so_thang > 0 ? round($tong_diem / $so_thang, 2) : 0;
}

/**
 * Lưu tổng kết học kỳ
 *
 * @param int $lop_id
 * @param int $hoc_ky
 * @param string $nam_hoc
 * @param float $tong_diem
 * @param string|null $danh_hieu
 * @param int $nguoi_tong_ket
 * @param string|null $ghi_chu
 * @return bool
 */
function saveTongKetHocKy($lop_id, $hoc_ky, $nam_hoc, $tong_diem, $danh_hieu, $nguoi_tong_ket, $ghi_chu = null) {
    $conn = getDBConnection();

    $xep_loai = getXepLoai($tong_diem);
    $thu_hang = tinhThuHangHocKy($lop_id, $hoc_ky, $nam_hoc);
    $ngay_tong_ket = date('Y-m-d');

    // Kiểm tra đã tồn tại chưa
    $stmt = $conn->prepare("
        SELECT id FROM tong_ket_hoc_ky
        WHERE lop_id = ? AND hoc_ky = ? AND nam_hoc = ?
    ");
    $stmt->execute([$lop_id, $hoc_ky, $nam_hoc]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update
        $stmt = $conn->prepare("
            UPDATE tong_ket_hoc_ky
            SET tong_diem = ?,
                xep_loai = ?,
                thu_hang = ?,
                danh_hieu = ?,
                ghi_chu = ?,
                nguoi_tong_ket = ?,
                ngay_tong_ket = ?
            WHERE id = ?
        ");
        $result = $stmt->execute([
            $tong_diem, $xep_loai, $thu_hang, $danh_hieu,
            $ghi_chu, $nguoi_tong_ket, $ngay_tong_ket, $existing['id']
        ]);
    } else {
        // Insert
        $stmt = $conn->prepare("
            INSERT INTO tong_ket_hoc_ky (
                lop_id, hoc_ky, nam_hoc, tong_diem, xep_loai,
                thu_hang, danh_hieu, ghi_chu, nguoi_tong_ket, ngay_tong_ket
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $lop_id, $hoc_ky, $nam_hoc, $tong_diem, $xep_loai,
            $thu_hang, $danh_hieu, $ghi_chu, $nguoi_tong_ket, $ngay_tong_ket
        ]);
    }

    return $result;
}

/**
 * Tính thứ hạng của lớp trong học kỳ
 *
 * @param int $lop_id
 * @param int $hoc_ky
 * @param string $nam_hoc
 * @return int|null
 */
function tinhThuHangHocKy($lop_id, $hoc_ky, $nam_hoc) {
    $conn = getDBConnection();

    $stmt = $conn->prepare("
        SELECT lop_id, tong_diem
        FROM tong_ket_hoc_ky
        WHERE hoc_ky = ? AND nam_hoc = ?
        ORDER BY tong_diem DESC
    ");
    $stmt->execute([$hoc_ky, $nam_hoc]);
    $rankings = $stmt->fetchAll();

    $thu_hang = 1;
    foreach ($rankings as $rank) {
        if ($rank['lop_id'] == $lop_id) {
            return $thu_hang;
        }
        $thu_hang++;
    }

    return null;
}

// ============================================================
// TỔNG KẾT NĂM HỌC
// ============================================================

/**
 * Tính điểm tổng kết năm học
 * Công thức: Trung bình điểm HK1 và HK2
 *
 * @param int $lop_id
 * @param string $nam_hoc
 * @return float
 */
function tinhDiemNamHoc($lop_id, $nam_hoc) {
    $diem_hk1 = tinhDiemHocKy($lop_id, 1, $nam_hoc);
    $diem_hk2 = tinhDiemHocKy($lop_id, 2, $nam_hoc);

    if ($diem_hk1 > 0 && $diem_hk2 > 0) {
        return round(($diem_hk1 + $diem_hk2) / 2, 2);
    } elseif ($diem_hk1 > 0) {
        return $diem_hk1;
    } elseif ($diem_hk2 > 0) {
        return $diem_hk2;
    }

    return 0;
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

/**
 * Xếp loại dựa trên điểm
 *
 * @param float $diem
 * @return string 'xuat_sac', 'kha', 'trung_binh', 'yeu'
 */
function getXepLoai($diem) {
    if ($diem >= 9.0) {
        return 'xuat_sac';
    } elseif ($diem >= 7.0) {
        return 'kha';
    } elseif ($diem >= 5.0) {
        return 'trung_binh';
    } else {
        return 'yeu';
    }
}

/**
 * Label xếp loại
 *
 * @param string $xep_loai
 * @return string
 */
function getXepLoaiLabel($xep_loai) {
    $labels = [
        'xuat_sac' => 'Xuất sắc',
        'kha' => 'Khá',
        'trung_binh' => 'Trung bình',
        'yeu' => 'Yếu'
    ];

    return isset($labels[$xep_loai]) ? $labels[$xep_loai] : '';
}

/**
 * CSS class cho xếp loại
 *
 * @param string $xep_loai
 * @return string
 */
function getXepLoaiClass($xep_loai) {
    $classes = [
        'xuat_sac' => 'success',
        'kha' => 'info',
        'trung_binh' => 'warning',
        'yeu' => 'danger'
    ];

    return isset($classes[$xep_loai]) ? $classes[$xep_loai] : 'secondary';
}

/**
 * Lấy danh sách tuần trong tháng
 *
 * @param int $thang
 * @param int $nam
 * @return array
 */
function getTuanTrongThang($thang, $nam) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT *
        FROM tuan_hoc
        WHERE MONTH(ngay_bat_dau) = ? AND YEAR(ngay_bat_dau) = ?
        ORDER BY ngay_bat_dau
    ");
    $stmt->execute([$thang, $nam]);

    return $stmt->fetchAll();
}

/**
 * Lấy tuần hiện tại
 *
 * @return array|null
 */
function getTuanHienTai() {
    $conn = getDBConnection();
    $today = date('Y-m-d');

    $stmt = $conn->prepare("
        SELECT *
        FROM tuan_hoc
        WHERE ? BETWEEN ngay_bat_dau AND ngay_ket_thuc
        LIMIT 1
    ");
    $stmt->execute([$today]);

    return $stmt->fetch();
}

/**
 * Format điểm hiển thị
 *
 * @param float $diem
 * @return string
 */
function formatDiem($diem) {
    return number_format($diem, 2, '.', '');
}

/**
 * Lấy màu sắc theo thứ hạng
 *
 * @param int $thu_hang
 * @return string CSS class
 */
function getThuHangClass($thu_hang) {
    if ($thu_hang == 1) {
        return 'text-warning'; // Vàng - Hạng 1
    } elseif ($thu_hang == 2) {
        return 'text-secondary'; // Bạc - Hạng 2
    } elseif ($thu_hang == 3) {
        return 'text-danger'; // Đồng - Hạng 3
    } else {
        return 'text-dark';
    }
}

/**
 * Lấy icon thứ hạng
 *
 * @param int $thu_hang
 * @return string
 */
function getThuHangIcon($thu_hang) {
    if ($thu_hang == 1) {
        return '🥇';
    } elseif ($thu_hang == 2) {
        return '🥈';
    } elseif ($thu_hang == 3) {
        return '🥉';
    } else {
        return '📍';
    }
}

?>
