<?php
/**
 * Permission Helper Functions
 * Các hàm kiểm tra quyền truy cập cho hệ thống thi đua
 *
 * Trường THCS Lê Trí Viễn - Phường Điện Bàn Bắc - TP Đà Nẵng
 * Giáo viên: Đoàn Thị Ngọc Lĩnh
 */

// ============================================================
// ADMIN PERMISSIONS
// ============================================================

/**
 * Kiểm tra user hiện tại có phải Admin không
 * @return bool
 */
if (!function_exists('isAdmin')) {
function isAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT vai_tro FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    return $admin && $admin['vai_tro'] === 'admin';
}
}

/**
 * Kiểm tra user hiện tại có phải Tổng phụ trách không
 * @return bool
 */
function isTongPhuTrach() {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT vai_tro FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    return $admin && ($admin['vai_tro'] === 'tong_phu_trach' || $admin['vai_tro'] === 'admin');
}

/**
 * Kiểm tra user hiện tại có phải Giáo viên không
 * @return bool
 */
function isGiaoVien() {
    return isset($_SESSION['admin_id']);
}

/**
 * Lấy vai trò của admin hiện tại
 * @return string|null 'admin', 'tong_phu_trach', 'giao_vien' hoặc null
 */
if (!function_exists('getAdminRole')) {
function getAdminRole() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT vai_tro FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    return $admin ? $admin['vai_tro'] : null;
}
}

// ============================================================
// STUDENT PERMISSIONS (CỜ ĐỎ)
// ============================================================

/**
 * Kiểm tra học sinh hiện tại có phải Cờ đỏ không
 * @param int|null $hoc_sinh_id ID học sinh (null = lấy từ session)
 * @return bool
 */
function isCoDo($hoc_sinh_id = null) {
    if ($hoc_sinh_id === null) {
        if (!isset($_SESSION['hoc_sinh_id'])) {
            return false;
        }
        $hoc_sinh_id = $_SESSION['hoc_sinh_id'];
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT la_co_do FROM hoc_sinh WHERE id = ?");
    $stmt->execute([$hoc_sinh_id]);
    $student = $stmt->fetch();

    return $student && $student['la_co_do'] == 1;
}

/**
 * Lấy danh sách các lớp mà học sinh Cờ đỏ được phân công chấm (CHẤM CHÉO)
 * @param int|null $hoc_sinh_id ID học sinh (null = lấy từ session)
 * @return array Mảng các lớp được phân công
 */
function getLopDuocCham($hoc_sinh_id = null) {
    if ($hoc_sinh_id === null) {
        if (!isset($_SESSION['hoc_sinh_id'])) {
            return [];
        }
        $hoc_sinh_id = $_SESSION['hoc_sinh_id'];
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT
            pc.id as phan_cong_id,
            pc.lop_duoc_cham_id,
            pc.ngay_phan_cong,
            pc.ghi_chu,
            lh.ten_lop,
            lh.khoi,
            lh.khoi_label
        FROM phan_cong_cham_diem pc
        JOIN lop_hoc lh ON pc.lop_duoc_cham_id = lh.id
        WHERE pc.hoc_sinh_id = ?
          AND pc.trang_thai = 'active'
        ORDER BY lh.ten_lop
    ");
    $stmt->execute([$hoc_sinh_id]);

    return $stmt->fetchAll();
}

/**
 * Kiểm tra học sinh Cờ đỏ có được phép chấm lớp này không
 * @param int $hoc_sinh_id ID học sinh Cờ đỏ
 * @param int $lop_id ID lớp cần kiểm tra
 * @return bool
 */
function canChamLop($hoc_sinh_id, $lop_id) {
    // Kiểm tra học sinh có phải Cờ đỏ không
    if (!isCoDo($hoc_sinh_id)) {
        return false;
    }

    // Lấy lớp của chính học sinh
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT lop_id FROM hoc_sinh WHERE id = ?");
    $stmt->execute([$hoc_sinh_id]);
    $student = $stmt->fetch();

    // Không được chấm lớp của chính mình (LOGIC CHẤM CHÉO)
    if ($student && $student['lop_id'] == $lop_id) {
        return false;
    }

    // Kiểm tra có phân công chấm lớp này không
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM phan_cong_cham_diem
        WHERE hoc_sinh_id = ?
          AND lop_duoc_cham_id = ?
          AND trang_thai = 'active'
    ");
    $stmt->execute([$hoc_sinh_id, $lop_id]);
    $result = $stmt->fetch();

    return $result && $result['count'] > 0;
}

/**
 * Lấy ID lớp của học sinh
 * @param int|null $hoc_sinh_id ID học sinh (null = lấy từ session)
 * @return int|null ID lớp hoặc null
 */
function getStudentClassId($hoc_sinh_id = null) {
    if ($hoc_sinh_id === null) {
        if (!isset($_SESSION['hoc_sinh_id'])) {
            return null;
        }
        $hoc_sinh_id = $_SESSION['hoc_sinh_id'];
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT lop_id FROM hoc_sinh WHERE id = ?");
    $stmt->execute([$hoc_sinh_id]);
    $student = $stmt->fetch();

    return $student ? $student['lop_id'] : null;
}

// ============================================================
// SCORING PERMISSIONS
// ============================================================

/**
 * Kiểm tra có thể chấm điểm cho tuần này không
 * @param int $tuan_id ID tuần học
 * @return bool
 */
function canScoreWeek($tuan_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT
            ngay_bat_dau,
            ngay_ket_thuc,
            trang_thai
        FROM tuan_hoc
        WHERE id = ?
    ");
    $stmt->execute([$tuan_id]);
    $week = $stmt->fetch();

    if (!$week) {
        return false;
    }

    // Kiểm tra tuần đang hoạt động
    if ($week['trang_thai'] !== 'active') {
        return false;
    }

    // Kiểm tra trong khoảng thời gian cho phép (ví dụ: trong tuần hoặc sau tuần 2 ngày)
    $today = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime($week['ngay_ket_thuc'] . ' +2 days'));

    return $today <= $end_date;
}

/**
 * Kiểm tra có thể duyệt điểm không (chỉ TPT và Admin)
 * @return bool
 */
function canApproveDiem() {
    return isTongPhuTrach() || isAdmin();
}

/**
 * Kiểm tra có thể tổng kết không (chỉ TPT và Admin)
 * @return bool
 */
function canTongKet() {
    return isTongPhuTrach() || isAdmin();
}

/**
 * Kiểm tra có thể xóa/sửa phân công chấm điểm không (chỉ Admin)
 * @return bool
 */
function canManagePhanCong() {
    return isAdmin();
}

// ============================================================
// GIÁO VIÊN CHỦ NHIỆM (GVCN) PERMISSIONS
// ============================================================

/**
 * Kiểm tra admin hiện tại có phải GVCN của lớp này không
 * @param int $lop_id ID lớp cần kiểm tra
 * @return bool
 */
if (!function_exists('isGVCN')) {
function isGVCN($lop_id) {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT gvcn_id FROM lop_hoc WHERE id = ?");
    $stmt->execute([$lop_id]);
    $class = $stmt->fetch();

    return $class && $class['gvcn_id'] == $_SESSION['admin_id'];
}
}

/**
 * Lấy danh sách các lớp mà admin đang làm GVCN
 * @param int|null $admin_id ID admin (null = lấy từ session)
 * @return array Mảng các lớp
 */
function getMyClasses($admin_id = null) {
    if ($admin_id === null) {
        if (!isset($_SESSION['admin_id'])) {
            return [];
        }
        $admin_id = $_SESSION['admin_id'];
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT
            id,
            ten_lop,
            khoi,
            khoi_label,
            si_so
        FROM lop_hoc
        WHERE gvcn_id = ?
        ORDER BY ten_lop
    ");
    $stmt->execute([$admin_id]);

    return $stmt->fetchAll();
}

/**
 * Kiểm tra có thể xem điểm của lớp này không
 * Quy tắc:
 * - Admin: Xem tất cả
 * - TPT: Xem tất cả
 * - GVCN: Chỉ xem lớp mình chủ nhiệm
 * - Học sinh Cờ đỏ: Xem lớp được phân công chấm
 *
 * @param int $lop_id ID lớp
 * @param string $user_type 'admin' hoặc 'hoc_sinh'
 * @param int|null $user_id ID người dùng
 * @return bool
 */
function canViewScores($lop_id, $user_type = 'admin', $user_id = null) {
    if ($user_type === 'admin') {
        // Admin và TPT xem tất cả
        if (isAdmin() || isTongPhuTrach()) {
            return true;
        }

        // GVCN chỉ xem lớp mình chủ nhiệm
        return isGVCN($lop_id);
    }

    if ($user_type === 'hoc_sinh') {
        if ($user_id === null) {
            if (!isset($_SESSION['hoc_sinh_id'])) {
                return false;
            }
            $user_id = $_SESSION['hoc_sinh_id'];
        }

        // Học sinh Cờ đỏ xem lớp được phân công chấm
        if (isCoDo($user_id)) {
            return canChamLop($user_id, $lop_id);
        }

        // Học sinh thường chỉ xem lớp mình
        $student_class_id = getStudentClassId($user_id);
        return $student_class_id == $lop_id;
    }

    return false;
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

/**
 * Redirect với thông báo lỗi không có quyền
 * @param string $redirect_url URL redirect (mặc định về trang chủ)
 */
function denyAccess($redirect_url = 'index.php') {
    $_SESSION['error'] = 'Bạn không có quyền truy cập chức năng này!';
    header("Location: $redirect_url");
    exit();
}

/**
 * Kiểm tra và yêu cầu quyền Admin
 */
if (!function_exists('requireAdmin')) {
function requireAdmin() {
    if (!isAdmin()) {
        denyAccess();
    }
}
}

/**
 * Kiểm tra và yêu cầu quyền Tổng phụ trách hoặc Admin
 */
function requireTongPhuTrach() {
    if (!isTongPhuTrach() && !isAdmin()) {
        denyAccess();
    }
}

/**
 * Kiểm tra và yêu cầu đăng nhập học sinh
 */
function requireStudent() {
    if (!isStudentLoggedIn()) {
        $_SESSION['error'] = 'Bạn cần đăng nhập để truy cập!';
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

/**
 * Kiểm tra và yêu cầu quyền Cờ đỏ
 */
function requireCoDo() {
    if (!isset($_SESSION['hoc_sinh_id']) || !isCoDo()) {
        denyAccess('student/index.php');
    }
}

/**
 * Log hoạt động vào bảng lich_su_hoat_dong
 *
 * @param string $loai_hoat_dong Loại hoạt động
 * @param int $nguoi_thuc_hien ID người thực hiện
 * @param string $loai_nguoi 'admin' hoặc 'hoc_sinh'
 * @param string $mo_ta Mô tả hoạt động
 * @param int|null $doi_tuong_id ID đối tượng liên quan
 * @param string|null $doi_tuong_loai Loại đối tượng
 * @param mixed $du_lieu_cu Dữ liệu cũ
 * @param mixed $du_lieu_moi Dữ liệu mới
 */
function logThiduaActivity($loai_hoat_dong, $nguoi_thuc_hien, $loai_nguoi, $mo_ta, $doi_tuong_id = null, $doi_tuong_loai = null, $du_lieu_cu = null, $du_lieu_moi = null) {
    $conn = getDBConnection();

    $stmt = $conn->prepare("
        INSERT INTO lich_su_hoat_dong (
            loai_hoat_dong,
            nguoi_thuc_hien,
            loai_nguoi,
            doi_tuong_id,
            doi_tuong_loai,
            mo_ta,
            du_lieu_cu,
            du_lieu_moi,
            ip_address,
            user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $loai_hoat_dong,
        $nguoi_thuc_hien,
        $loai_nguoi,
        $doi_tuong_id,
        $doi_tuong_loai,
        $mo_ta,
        $du_lieu_cu ? json_encode($du_lieu_cu, JSON_UNESCAPED_UNICODE) : null,
        $du_lieu_moi ? json_encode($du_lieu_moi, JSON_UNESCAPED_UNICODE) : null,
        isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
        isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
    ]);
}

?>
