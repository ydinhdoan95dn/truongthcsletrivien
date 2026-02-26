<?php
/**
 * ==============================================
 * CHẤM ĐIỂM THI ĐUA (Học sinh Cờ đỏ)
 * Module: Student - Hệ thống Thi đua
 *
 * Logic chấm chéo:
 * - Học sinh Cờ đỏ KHÔNG chấm lớp mình
 * - Chỉ chấm lớp được phân công bởi Admin
 * - Xem trong bảng phan_cong_cham_diem
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/permission_helper.php';

requireStudent();

$conn = getDBConnection();
$student = getCurrentStudent();

// ============================================================
// CHECK: Có phải Cờ đỏ không?
// ============================================================
if ($student['la_co_do'] != 1) {
    $_SESSION['error'] = 'Chỉ học sinh Cờ đỏ mới được chấm điểm!';
    header('Location: ../dashboard.php');
    exit;
}

define('PAGE_TITLE', 'Chấm điểm thi đua');

// ============================================================
// LẤY LỚP ĐƯỢC PHÂN CÔNG CHẤM
// ============================================================
$stmtPhanCong = $conn->prepare("
    SELECT
        pc.lop_duoc_cham_id,
        lh.ten_lop,
        lh.khoi,
        lh.khoi_label
    FROM phan_cong_cham_diem pc
    JOIN lop_hoc lh ON pc.lop_duoc_cham_id = lh.id
    WHERE pc.hoc_sinh_id = ?
      AND pc.trang_thai = 'active'
");
$stmtPhanCong->execute([$student['id']]);
$cacLopDuocCham = $stmtPhanCong->fetchAll();

if (count($cacLopDuocCham) == 0) {
    $_SESSION['error'] = 'Bạn chưa được phân công chấm lớp nào. Vui lòng liên hệ giáo viên!';
    header('Location: ../dashboard.php');
    exit;
}

// ============================================================
// FILTER
// ============================================================
$lop_cham_id = isset($_GET['lop']) ? intval($_GET['lop']) : $cacLopDuocCham[0]['lop_duoc_cham_id'];
$tuan_id = isset($_GET['tuan']) ? intval($_GET['tuan']) : 0;

// Validate: Lớp có trong danh sách được phân công không?
$allowed = false;
foreach ($cacLopDuocCham as $lop) {
    if ($lop['lop_duoc_cham_id'] == $lop_cham_id) {
        $allowed = true;
        break;
    }
}

if (!$allowed) {
    $_SESSION['error'] = 'Bạn không có quyền chấm lớp này!';
    header('Location: cham_diem.php');
    exit;
}

// Lấy tuần hiện tại nếu không chọn
if ($tuan_id == 0) {
    $stmtCurrentTuan = $conn->query("
        SELECT id FROM tuan_hoc
        WHERE CURDATE() BETWEEN ngay_bat_dau AND ngay_ket_thuc
        LIMIT 1
    ");
    $currentTuan = $stmtCurrentTuan->fetch();
    $tuan_id = $currentTuan ? $currentTuan['id'] : 0;
}

// Lấy thông tin lớp chấm
$stmtLopCham = $conn->prepare("SELECT * FROM lop_hoc WHERE id = ?");
$stmtLopCham->execute([$lop_cham_id]);
$lopChamInfo = $stmtLopCham->fetch();

// Lấy thông tin tuần
$tuanInfo = null;
if ($tuan_id > 0) {
    $stmtTuan = $conn->prepare("SELECT * FROM tuan_hoc WHERE id = ?");
    $stmtTuan->execute([$tuan_id]);
    $tuanInfo = $stmtTuan->fetch();
}

// Lấy danh sách tuần (5 tuần gần nhất)
$stmtDanhSachTuan = $conn->query("
    SELECT * FROM tuan_hoc
    WHERE trang_thai = 1
    ORDER BY ngay_bat_dau DESC
    LIMIT 5
");
$danhSachTuan = $stmtDanhSachTuan->fetchAll();

// ============================================================
// LẤY TIÊU CHÍ
// ============================================================
$stmtTieuChi = $conn->query("
    SELECT * FROM tieu_chi_thi_dua
    WHERE trang_thai = 1
    ORDER BY thu_tu ASC
");
$cacTieuChi = $stmtTieuChi->fetchAll();

// ============================================================
// LẤY ĐIỂM ĐÃ CHẤM (nếu có)
// ============================================================
$diemDaCham = [];
if ($tuan_id > 0) {
    $stmtDiem = $conn->prepare("
        SELECT * FROM diem_thi_dua_tuan
        WHERE lop_id = ? AND tuan_id = ? AND nguoi_cham = ?
    ");
    $stmtDiem->execute([$lop_cham_id, $tuan_id, $student['id']]);
    $diemRows = $stmtDiem->fetchAll();

    foreach ($diemRows as $row) {
        $diemDaCham[$row['tieu_chi_id']] = $row;
    }
}

// ============================================================
// HANDLE SUBMIT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']); // luu_tam, gui_duyet

    try {
        $conn->beginTransaction();

        foreach ($cacTieuChi as $tc) {
            $tieu_chi_id = $tc['id'];
            $diem = isset($_POST['diem_' . $tieu_chi_id]) ? floatval($_POST['diem_' . $tieu_chi_id]) : 0;
            $ghi_chu = isset($_POST['ghi_chu_' . $tieu_chi_id]) ? sanitize($_POST['ghi_chu_' . $tieu_chi_id]) : '';

            // Validate điểm
            if ($diem < 0 || $diem > $tc['diem_toi_da']) {
                throw new Exception("Điểm {$tc['ten_tieu_chi']} không hợp lệ (0-{$tc['diem_toi_da']})");
            }

            // Tính điểm có trọng số
            $diemCoTrongSo = round(($diem / $tc['diem_toi_da']) * $tc['trong_so'], 2);

            // Trạng thái
            $trangThai = ($action === 'gui_duyet') ? 'cho_duyet' : 'nhap';

            // INSERT or UPDATE
            if (isset($diemDaCham[$tieu_chi_id])) {
                // Update
                $stmtUpdate = $conn->prepare("
                    UPDATE diem_thi_dua_tuan
                    SET diem = ?,
                        diem_co_trong_so = ?,
                        ghi_chu = ?,
                        trang_thai = ?,
                        cham_luc = NOW(),
                        gui_tong_hop_luc = " . ($action === 'gui_duyet' ? 'NOW()' : 'NULL') . "
                    WHERE id = ?
                ");
                $stmtUpdate->execute([
                    $diem,
                    $diemCoTrongSo,
                    $ghi_chu,
                    $trangThai,
                    $diemDaCham[$tieu_chi_id]['id']
                ]);
            } else {
                // Insert
                $stmtInsert = $conn->prepare("
                    INSERT INTO diem_thi_dua_tuan
                    (
                        lop_id, tieu_chi_id, tuan_id,
                        diem, diem_co_trong_so,
                        nguoi_cham, loai_nguoi_cham,
                        ghi_chu, trang_thai,
                        cham_luc, gui_tong_hop_luc
                    )
                    VALUES (?, ?, ?, ?, ?, ?, 'hoc_sinh', ?, ?, NOW(), " . ($action === 'gui_duyet' ? 'NOW()' : 'NULL') . ")
                ");
                $stmtInsert->execute([
                    $lop_cham_id,
                    $tieu_chi_id,
                    $tuan_id,
                    $diem,
                    $diemCoTrongSo,
                    $student['id'],
                    $ghi_chu,
                    $trangThai
                ]);
            }
        }

        // Log activity
        logThiduaActivity(
            $action === 'gui_duyet' ? 'gui_duyet_diem' : 'luu_tam_diem',
            $student['id'],
            'hoc_sinh',
            "Học sinh Cờ đỏ {$student['ho_ten']} " . ($action === 'gui_duyet' ? 'gửi duyệt' : 'lưu tạm') . " điểm lớp {$lopChamInfo['ten_lop']} - Tuần {$tuan_id}",
            $lop_cham_id,
            'lop_hoc'
        );

        $conn->commit();

        if ($action === 'gui_duyet') {
            $_SESSION['success'] = 'Gửi điểm thành công! Vui lòng chờ giáo viên duyệt.';
        } else {
            $_SESSION['success'] = 'Lưu tạm thành công!';
        }

        header("Location: cham_diem.php?lop={$lop_cham_id}&tuan={$tuan_id}");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
    }
}

// Pre-calculate locked state
$hasChoduyet = false;
foreach ($diemDaCham as $d) {
    if ($d['trang_thai'] === 'cho_duyet' || $d['trang_thai'] === 'da_duyet') {
        $hasChoduyet = true;
        break;
    }
}

// Criteria metadata
$criteriaIcons = array(
    'hoc_tap' => 'fa-book',
    'ne_nep' => 'fa-user-check',
    've_sinh' => 'fa-broom',
    'hoat_dong' => 'fa-users',
    'doan_ket' => 'fa-handshake'
);
$criteriaColors = array(
    'hoc_tap' => '#4F46E5',
    'ne_nep' => '#0D9488',
    've_sinh' => '#0EA5E9',
    'hoat_dong' => '#F59E0B',
    'doan_ket' => '#EF4444'
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f4f8; color: #1F2937; min-height: 100vh; }

        .app-container { display: flex; min-height: 100vh; }

        /* Sidebar wrapper */
        .sidebar-wrapper { width: 260px; min-width: 260px; flex-shrink: 0; position: relative; z-index: 100; }
        .sidebar-wrapper .student-sidebar { min-height: 100vh; }

        .sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 999;
        }

        /* Main content */
        .main-content { flex: 1; display: flex; flex-direction: column; min-height: 100vh; overflow: hidden; }

        .content-header {
            background: #fff; border-bottom: 1px solid #e0e0e0;
            padding: 16px 28px; display: flex; align-items: center;
            justify-content: space-between; gap: 16px; flex-shrink: 0;
        }
        .header-left { display: flex; align-items: center; gap: 14px; }
        .hamburger-btn {
            display: none; width: 40px; height: 40px; border: none; border-radius: 10px;
            background: #f0f4f8; color: #4F46E5; font-size: 18px; cursor: pointer;
            align-items: center; justify-content: center;
        }
        .page-title { font-size: 1.3rem; font-weight: 700; color: #1F2937; display: flex; align-items: center; gap: 10px; }
        .page-title-icon { font-size: 1.6rem; }

        .header-filters { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .filter-select {
            padding: 8px 16px; border: 2px solid #e0e0e0; border-radius: 25px;
            background: #fff; color: #333; font-family: 'Inter', sans-serif;
            font-size: 13px; font-weight: 600; cursor: pointer; outline: none;
            transition: all 0.2s;
        }
        .filter-select:focus { border-color: #4F46E5; }

        .content-body { flex: 1; padding: 24px 28px; overflow-y: auto; }

        /* Co Do Banner */
        .co-do-banner {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: #fff; border-radius: 16px; padding: 16px 24px;
            display: flex; align-items: center; gap: 16px; margin-bottom: 20px;
        }
        .co-do-banner-icon { font-size: 2rem; }
        .co-do-banner h4 { font-size: 15px; font-weight: 700; margin-bottom: 2px; }
        .co-do-banner p { font-size: 13px; opacity: 0.9; }

        /* Flash messages */
        .flash-msg {
            padding: 14px 20px; border-radius: 12px; margin-bottom: 16px;
            font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px;
        }
        .flash-success { background: #D1FAE5; color: #065F46; }
        .flash-error { background: #FEE2E2; color: #991B1B; }

        /* Info bar */
        .info-bar {
            background: #fff; border-radius: 16px; padding: 16px 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 24px;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px;
        }
        .info-bar-item { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #555; }
        .info-bar-item i { color: #4F46E5; }

        /* Scoring grid */
        .scoring-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }

        /* Scoring card */
        .scoring-card {
            background: #fff; border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden; transition: all 0.3s ease;
        }
        .scoring-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .scoring-card-stripe { height: 5px; }
        .scoring-card-header {
            padding: 18px 20px 0; display: flex; align-items: flex-start;
            justify-content: space-between; gap: 12px;
        }
        .criterion-icon-wrap {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #fff; flex-shrink: 0;
        }
        .criterion-info { flex: 1; }
        .criterion-info h3 { font-size: 16px; font-weight: 700; margin-bottom: 2px; }
        .criterion-weight {
            display: inline-block; padding: 2px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700; background: #f0f4f8; color: #6B7280;
        }
        .criterion-desc { font-size: 12px; color: #9CA3AF; margin-top: 6px; line-height: 1.4; }

        .status-badge {
            padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;
            white-space: nowrap; flex-shrink: 0;
        }
        .status-cho-duyet { background: #FEF3C7; color: #D97706; }
        .status-da-duyet { background: #D1FAE5; color: #059669; }

        .scoring-card-body { padding: 16px 20px 20px; }

        /* Score display */
        .score-display {
            text-align: center; margin-bottom: 12px;
        }
        .score-value {
            font-size: 3rem; font-weight: 700; line-height: 1;
            transition: color 0.3s;
        }
        .score-max { font-size: 1.2rem; color: #9CA3AF; font-weight: 600; }

        /* Range slider */
        .slider-container { padding: 0 4px; margin-bottom: 16px; }
        .criterion-slider {
            -webkit-appearance: none; width: 100%; height: 10px;
            border-radius: 5px; outline: none; cursor: pointer;
            background: #e9ecef;
        }
        .criterion-slider::-webkit-slider-thumb {
            -webkit-appearance: none; width: 28px; height: 28px;
            border-radius: 50%; cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border: 3px solid #fff; transition: transform 0.2s;
        }
        .criterion-slider::-webkit-slider-thumb:hover { transform: scale(1.15); }
        .criterion-slider::-moz-range-thumb {
            width: 28px; height: 28px; border-radius: 50%;
            cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border: 3px solid #fff;
        }
        .criterion-slider:disabled { opacity: 0.5; cursor: not-allowed; }
        .criterion-slider:disabled::-webkit-slider-thumb { cursor: not-allowed; }

        /* Weighted preview */
        .weighted-preview {
            display: flex; align-items: center; gap: 12px;
        }
        .weighted-bar {
            flex: 1; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden;
        }
        .weighted-bar-fill {
            height: 100%; border-radius: 4px; transition: width 0.4s ease;
        }
        .weighted-label { font-size: 13px; font-weight: 700; color: #555; white-space: nowrap; }

        /* Note toggle */
        .note-toggle {
            margin-top: 14px; font-size: 13px; color: #6B7280; cursor: pointer;
            display: flex; align-items: center; gap: 6px; font-weight: 600;
            transition: color 0.2s;
        }
        .note-toggle:hover { color: #4F46E5; }
        .note-area { margin-top: 8px; display: none; }
        .note-area.show { display: block; }
        .note-textarea {
            width: 100%; padding: 10px 14px; border: 2px solid #e9ecef;
            border-radius: 12px; font-family: 'Inter', sans-serif;
            font-size: 13px; resize: vertical; min-height: 60px;
            outline: none; transition: border-color 0.2s;
        }
        .note-textarea:focus { border-color: #4F46E5; }
        .note-textarea:disabled { background: #f8f9fa; cursor: not-allowed; }

        /* Action bar */
        .action-bar {
            background: #fff; border-radius: 16px;
            box-shadow: 0 -2px 20px rgba(0,0,0,0.06);
            padding: 20px 24px; display: flex;
            align-items: center; justify-content: space-between;
            gap: 16px; position: sticky; bottom: 0;
        }
        .action-summary { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .total-score-display { display: flex; align-items: baseline; gap: 6px; }
        .total-label { font-size: 14px; color: #6B7280; font-weight: 600; }
        .total-value { font-size: 2rem; font-weight: 700; color: #4F46E5; }
        .total-max { font-size: 14px; color: #9CA3AF; font-weight: 600; }

        .classification-badge {
            padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 700;
        }
        .badge-xuat-sac { background: #FEF3C7; color: #D97706; }
        .badge-tot { background: #D1FAE5; color: #059669; }
        .badge-kha { background: #DBEAFE; color: #2563EB; }
        .badge-trung-binh { background: #F3F4F6; color: #6B7280; }
        .badge-can-co-gang { background: #FEE2E2; color: #DC2626; }
        .badge-none { background: #F3F4F6; color: #9CA3AF; }

        .action-buttons { display: flex; gap: 10px; flex-shrink: 0; }
        .btn-save {
            padding: 12px 24px; border: none; border-radius: 12px;
            font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 700;
            cursor: pointer; transition: all 0.3s ease;
            display: flex; align-items: center; gap: 8px;
            background: #E5E7EB; color: #374151;
        }
        .btn-save:hover { background: #D1D5DB; }
        .btn-submit {
            padding: 12px 24px; border: none; border-radius: 12px;
            font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 700;
            cursor: pointer; transition: all 0.3s ease;
            display: flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: #fff;
        }
        .btn-submit:hover { transform: scale(1.02); box-shadow: 0 4px 15px rgba(79,70,229,0.4); }
        .btn-ranking {
            padding: 12px 24px; border: none; border-radius: 12px;
            font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 700;
            cursor: pointer; transition: all 0.3s ease;
            display: flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: #fff; text-decoration: none;
        }
        .btn-ranking:hover { transform: scale(1.02); box-shadow: 0 4px 15px rgba(16,185,129,0.4); color: #fff; }

        .locked-msg { font-size: 13px; color: #9CA3AF; display: flex; align-items: center; gap: 6px; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 60px 20px; color: #9CA3AF;
        }
        .empty-state-icon { font-size: 4rem; margin-bottom: 16px; }
        .empty-state-text { font-size: 16px; font-weight: 600; }

        /* Responsive */
        @media (max-width: 1366px) {
            .sidebar-wrapper { width: 220px; min-width: 220px; }
            .content-header { padding: 14px 20px; }
            .content-body { padding: 18px 20px; }
        }
        @media (max-width: 1024px) {
            .scoring-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar-wrapper {
                position: fixed; left: 0; top: 0; width: 260px;
                height: 100vh; transform: translateX(-100%);
                transition: transform 0.3s ease; z-index: 1000;
            }
            .app-container.sidebar-open .sidebar-wrapper { transform: translateX(0); }
            .app-container.sidebar-open .sidebar-overlay { display: block; }
            .hamburger-btn { display: flex; }
            .content-header { padding: 12px 16px; flex-wrap: wrap; }
            .header-filters { width: 100%; }
            .filter-select { flex: 1; min-width: 0; font-size: 12px; }
            .content-body { padding: 14px 16px; }
            .scoring-grid { grid-template-columns: 1fr; gap: 14px; }
            .score-value { font-size: 2.2rem; }
            .action-bar { flex-direction: column; gap: 14px; text-align: center; }
            .action-summary { justify-content: center; }
            .action-buttons { width: 100%; }
            .action-buttons button, .action-buttons a { flex: 1; justify-content: center; }
            .page-title { font-size: 1.1rem; }
            .co-do-banner { padding: 12px 16px; }
        }
    </style>
</head>
<body>
    <div class="app-container" id="appContainer">
        <aside class="sidebar-wrapper">
            <?php include '../includes/sidebar.php'; ?>
        </aside>
        <div class="sidebar-overlay" onclick="closeSidebar()"></div>

        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <button class="hamburger-btn" onclick="toggleSidebar()" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">
                        <span class="page-title-icon"><i class="fas fa-flag" style="color: #EF4444;"></i></span>
                        <?php echo PAGE_TITLE; ?>
                    </h1>
                </div>
                <div class="header-filters">
                    <form method="GET" action="" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <select name="lop" class="filter-select" onchange="this.form.submit()">
                            <?php foreach ($cacLopDuocCham as $lop): ?>
                                <option value="<?php echo $lop['lop_duoc_cham_id']; ?>"
                                    <?php echo $lop_cham_id == $lop['lop_duoc_cham_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lop['ten_lop']); ?> (K<?php echo $lop['khoi']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="tuan" class="filter-select" onchange="this.form.submit()">
                            <option value="">-- Ch&#7885;n tu&#7847;n --</option>
                            <?php foreach ($danhSachTuan as $tuan): ?>
                                <option value="<?php echo $tuan['id']; ?>"
                                    <?php echo $tuan_id == $tuan['id'] ? 'selected' : ''; ?>>
                                    Tu&#7847;n <?php echo $tuan['so_tuan']; ?> (<?php echo date('d/m', strtotime($tuan['ngay_bat_dau'])); ?> - <?php echo date('d/m', strtotime($tuan['ngay_ket_thuc'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </header>

            <div class="content-body">
                <!-- Co Do Banner -->
                <div class="co-do-banner">
                    <div class="co-do-banner-icon"><i class="fas fa-flag"></i></div>
                    <div>
                        <h4>B&#7841;n l&agrave; h&#7885;c sinh C&#7901; &#273;&#7887;!</h4>
                        <p>Nhi&#7879;m v&#7909;: Ch&#7845;m &#273;i&#7875;m thi &#273;ua cho l&#7899;p &#273;&#432;&#7907;c ph&acirc;n c&ocirc;ng. <strong>KH&Ocirc;NG &#273;&#432;&#7907;c ch&#7845;m l&#7899;p m&igrave;nh!</strong></p>
                    </div>
                </div>

                <!-- Flash messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="flash-msg flash-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="flash-msg flash-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($tuanInfo): ?>
                    <!-- Info bar -->
                    <div class="info-bar">
                        <div class="info-bar-item">
                            <i class="fas fa-school"></i>
                            L&#7899;p ch&#7845;m: <strong><?php echo htmlspecialchars($lopChamInfo['ten_lop']); ?></strong>
                        </div>
                        <div class="info-bar-item">
                            <i class="far fa-calendar-check"></i>
                            Tu&#7847;n <?php echo $tuanInfo['so_tuan']; ?>:
                            <?php echo date('d/m/Y', strtotime($tuanInfo['ngay_bat_dau'])); ?> &rarr;
                            <?php echo date('d/m/Y', strtotime($tuanInfo['ngay_ket_thuc'])); ?>
                        </div>
                    </div>

                    <!-- Scoring form -->
                    <form method="POST" action="" id="formChamDiem">
                        <div class="scoring-grid">
                            <?php foreach ($cacTieuChi as $tc):
                                $tcId = $tc['id'];
                                $maTc = $tc['ma_tieu_chi'];
                                $icon = isset($criteriaIcons[$maTc]) ? $criteriaIcons[$maTc] : 'fa-star';
                                $color = isset($criteriaColors[$maTc]) ? $criteriaColors[$maTc] : '#6B7280';
                                $diemHienTai = isset($diemDaCham[$tcId]) ? floatval($diemDaCham[$tcId]['diem']) : 0;
                                $ghiChuHienTai = isset($diemDaCham[$tcId]) ? $diemDaCham[$tcId]['ghi_chu'] : '';
                                $trangThai = isset($diemDaCham[$tcId]) ? $diemDaCham[$tcId]['trang_thai'] : '';
                                $isLocked = ($trangThai === 'cho_duyet' || $trangThai === 'da_duyet');
                                $diemMax = floatval($tc['diem_toi_da']);
                                $trongSo = floatval($tc['trong_so']);
                                $weightedVal = ($diemMax > 0) ? round(($diemHienTai / $diemMax) * $trongSo, 1) : 0;
                                $pct = ($diemMax > 0) ? round(($diemHienTai / $diemMax) * 100, 0) : 0;
                            ?>
                            <div class="scoring-card" data-id="<?php echo $tcId; ?>">
                                <div class="scoring-card-stripe" style="background: <?php echo $color; ?>;"></div>
                                <div class="scoring-card-header">
                                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                                        <div class="criterion-icon-wrap" style="background: <?php echo $color; ?>;">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="criterion-info">
                                            <h3><?php echo htmlspecialchars($tc['ten_tieu_chi']); ?></h3>
                                            <span class="criterion-weight">Tr&#7885;ng s&#7889;: <?php echo intval($trongSo); ?>%</span>
                                            <?php if ($tc['mo_ta']): ?>
                                                <div class="criterion-desc"><?php echo htmlspecialchars($tc['mo_ta']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($trangThai === 'cho_duyet'): ?>
                                        <span class="status-badge status-cho-duyet"><i class="fas fa-clock"></i> Ch&#7901; duy&#7879;t</span>
                                    <?php elseif ($trangThai === 'da_duyet'): ?>
                                        <span class="status-badge status-da-duyet"><i class="fas fa-check"></i> &#272;&atilde; duy&#7879;t</span>
                                    <?php endif; ?>
                                </div>

                                <div class="scoring-card-body">
                                    <div class="score-display">
                                        <span class="score-value" id="scoreVal_<?php echo $tcId; ?>" style="color: <?php echo $color; ?>;"><?php echo number_format($diemHienTai, 1); ?></span>
                                        <span class="score-max"> / <?php echo intval($diemMax); ?></span>
                                    </div>

                                    <div class="slider-container">
                                        <input type="range"
                                               class="criterion-slider"
                                               id="slider_<?php echo $tcId; ?>"
                                               min="0"
                                               max="<?php echo $diemMax; ?>"
                                               step="0.5"
                                               value="<?php echo $diemHienTai; ?>"
                                               oninput="updateScore(<?php echo $tcId; ?>, this.value)"
                                               style="background: linear-gradient(to right, <?php echo $color; ?> <?php echo $pct; ?>%, #e9ecef <?php echo $pct; ?>%);"
                                               <?php echo $isLocked ? 'disabled' : ''; ?>>
                                    </div>

                                    <input type="hidden" name="diem_<?php echo $tcId; ?>" id="diemInput_<?php echo $tcId; ?>" value="<?php echo $diemHienTai; ?>">

                                    <div class="weighted-preview">
                                        <div class="weighted-bar">
                                            <div class="weighted-bar-fill" id="weightedBar_<?php echo $tcId; ?>"
                                                 style="width: <?php echo $pct; ?>%; background: <?php echo $color; ?>;"></div>
                                        </div>
                                        <span class="weighted-label" id="weightedVal_<?php echo $tcId; ?>"><?php echo number_format($weightedVal, 1); ?> / <?php echo intval($trongSo); ?></span>
                                    </div>

                                    <div class="note-toggle" onclick="toggleNote(<?php echo $tcId; ?>)">
                                        <i class="fas fa-comment"></i> Ghi ch&uacute;
                                        <?php if ($ghiChuHienTai): ?><i class="fas fa-circle" style="font-size:6px; color: <?php echo $color; ?>;"></i><?php endif; ?>
                                    </div>
                                    <div class="note-area <?php echo $ghiChuHienTai ? 'show' : ''; ?>" id="noteArea_<?php echo $tcId; ?>">
                                        <textarea class="note-textarea"
                                                  name="ghi_chu_<?php echo $tcId; ?>"
                                                  placeholder="Nh&#7853;n x&eacute;t v&#7873; ti&ecirc;u ch&iacute; n&agrave;y..."
                                                  <?php echo $isLocked ? 'disabled' : ''; ?>><?php echo htmlspecialchars($ghiChuHienTai); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Action bar -->
                        <div class="action-bar">
                            <div class="action-summary">
                                <div class="total-score-display">
                                    <span class="total-label">T&#7893;ng &#273;i&#7875;m:</span>
                                    <span class="total-value" id="totalScore">0.0</span>
                                    <span class="total-max">/ 100</span>
                                </div>
                                <div class="classification-badge badge-none" id="classificationBadge">--</div>
                            </div>
                            <div class="action-buttons">
                                <?php if (!$hasChoduyet): ?>
                                    <button type="submit" name="action" value="luu_tam" class="btn-save">
                                        <i class="fas fa-save"></i> L&#432;u t&#7841;m
                                    </button>
                                    <button type="button" class="btn-submit" onclick="confirmSubmit()">
                                        <i class="fas fa-paper-plane"></i> G&#7917;i duy&#7879;t
                                    </button>
                                <?php else: ?>
                                    <span class="locked-msg"><i class="fas fa-lock"></i> &#272;i&#7875;m &#273;&atilde; g&#7917;i duy&#7879;t</span>
                                    <a href="xep_hang.php" class="btn-ranking">
                                        <i class="fas fa-trophy"></i> Xem x&#7871;p h&#7841;ng
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="empty-state-text">Vui l&ograve;ng ch&#7885;n l&#7899;p v&agrave; tu&#7847;n &#273;&#7875; ch&#7845;m &#273;i&#7875;m</div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    // Criteria data for JS calculations
    var CRITERIA = <?php echo json_encode(array_map(function($tc) {
        return array(
            'id' => $tc['id'],
            'ma' => $tc['ma_tieu_chi'],
            'diem_toi_da' => floatval($tc['diem_toi_da']),
            'trong_so' => floatval($tc['trong_so'])
        );
    }, $cacTieuChi)); ?>;

    var CRITERIA_COLORS = {
        'hoc_tap': '#4F46E5', 'ne_nep': '#0D9488', 've_sinh': '#0EA5E9',
        'hoat_dong': '#F59E0B', 'doan_ket': '#EF4444'
    };

    // Build lookup
    var CRITERIA_MAP = {};
    for (var i = 0; i < CRITERIA.length; i++) {
        CRITERIA_MAP[CRITERIA[i].id] = CRITERIA[i];
    }

    function updateScore(tcId, value) {
        var val = parseFloat(value) || 0;
        var tc = CRITERIA_MAP[tcId];
        if (!tc) return;

        var max = tc.diem_toi_da;
        var weight = tc.trong_so;
        var pct = (max > 0) ? (val / max) * 100 : 0;
        var weighted = (max > 0) ? (val / max) * weight : 0;
        var color = CRITERIA_COLORS[tc.ma] || '#6B7280';

        // Update display
        document.getElementById('scoreVal_' + tcId).textContent = val.toFixed(1);
        document.getElementById('diemInput_' + tcId).value = val;
        document.getElementById('weightedVal_' + tcId).textContent = weighted.toFixed(1) + ' / ' + Math.round(weight);
        document.getElementById('weightedBar_' + tcId).style.width = pct + '%';

        // Update slider track color
        var slider = document.getElementById('slider_' + tcId);
        slider.style.background = 'linear-gradient(to right, ' + color + ' ' + pct + '%, #e9ecef ' + pct + '%)';

        recalculateTotal();
    }

    function recalculateTotal() {
        var total = 0;
        for (var i = 0; i < CRITERIA.length; i++) {
            var tc = CRITERIA[i];
            var input = document.getElementById('diemInput_' + tc.id);
            if (input) {
                var val = parseFloat(input.value) || 0;
                var weighted = (tc.diem_toi_da > 0) ? (val / tc.diem_toi_da) * tc.trong_so : 0;
                total += weighted;
            }
        }
        document.getElementById('totalScore').textContent = total.toFixed(1);
        updateClassificationBadge(total);
    }

    function updateClassificationBadge(score) {
        var badge = document.getElementById('classificationBadge');
        var text, cls;
        if (score >= 90) { text = 'Xu\u1EA5t s\u1EAFc'; cls = 'badge-xuat-sac'; }
        else if (score >= 80) { text = 'T\u1ED1t'; cls = 'badge-tot'; }
        else if (score >= 70) { text = 'Kh\u00E1'; cls = 'badge-kha'; }
        else if (score >= 50) { text = 'Trung b\u00ECnh'; cls = 'badge-trung-binh'; }
        else if (score > 0) { text = 'C\u1EA7n c\u1ED1 g\u1EAFng'; cls = 'badge-can-co-gang'; }
        else { text = '--'; cls = 'badge-none'; }
        badge.textContent = text;
        badge.className = 'classification-badge ' + cls;
    }

    function toggleNote(tcId) {
        var area = document.getElementById('noteArea_' + tcId);
        area.classList.toggle('show');
    }

    function toggleSidebar() {
        document.getElementById('appContainer').classList.toggle('sidebar-open');
    }
    function closeSidebar() {
        document.getElementById('appContainer').classList.remove('sidebar-open');
    }

    function confirmSubmit() {
        Swal.fire({
            title: 'X\u00E1c nh\u1EADn g\u1EEDi duy\u1EC7t',
            html: '<p>Sau khi g\u1EEDi, b\u1EA1n <strong>kh\u00F4ng th\u1EC3 ch\u1EC9nh s\u1EEDa</strong> n\u1EEFa!</p><p>Vui l\u00F2ng ki\u1EC3m tra k\u1EF9 \u0111i\u1EC3m tr\u01B0\u1EDBc khi g\u1EEDi.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4F46E5',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-paper-plane"></i> G\u1EEDi ngay',
            cancelButtonText: 'H\u1EE7y'
        }).then(function(result) {
            if (result.isConfirmed) {
                var form = document.getElementById('formChamDiem');
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'action';
                input.value = 'gui_duyet';
                form.appendChild(input);
                form.submit();
            }
        });
    }

    // Auto-dismiss flash messages
    setTimeout(function() {
        var msgs = document.querySelectorAll('.flash-msg');
        for (var i = 0; i < msgs.length; i++) {
            msgs[i].style.transition = 'opacity 0.5s';
            msgs[i].style.opacity = '0';
            (function(el) {
                setTimeout(function() { el.style.display = 'none'; }, 500);
            })(msgs[i]);
        }
    }, 5000);

    // Init total on page load
    recalculateTotal();
    </script>
</body>
</html>
