<?php
/**
 * ==============================================
 * TRANG CHU - PUBLIC + STUDENT
 * Web App Hoc tap & Thi dua Truc tuyen THCS
 * Truong THCS Le Tri Vien
 * Style: Fullscreen Desktop App
 * ==============================================
 */

require_once 'includes/config.php';

// Admin da dang nhap -> redirect
if (isAdminLoggedIn()) {
    redirect('admin/dashboard.php');
}

// Student da login: KHONG redirect, van hien thi trang chu
$isLoggedIn = isStudentLoggedIn();
$currentStudent = $isLoggedIn ? getCurrentStudent() : null;

// Lay danh sach lop
$conn = getDBConnection();
$stmtLop = $conn->query("SELECT * FROM lop_hoc WHERE trang_thai = 1 ORDER BY thu_tu ASC");
$danhSachLop = $stmtLop->fetchAll();

// Lay danh sach mon hoc
$stmtMon = $conn->query("SELECT * FROM mon_hoc WHERE trang_thai = 1 ORDER BY thu_tu ASC");
$danhSachMon = $stmtMon->fetchAll();

// Lay tai lieu cong khai
$stmtTL = $conn->prepare("
    SELECT tl.*, mh.ten_mon, mh.icon, mh.mau_sac, lh.ten_lop
    FROM tai_lieu tl
    JOIN mon_hoc mh ON tl.mon_hoc_id = mh.id
    LEFT JOIN lop_hoc lh ON tl.lop_id = lh.id
    WHERE tl.is_public = 1 AND tl.trang_thai = 1
    ORDER BY tl.created_at DESC
");
$stmtTL->execute();
$taiLieuList = $stmtTL->fetchAll();

// Lay top hoc sinh theo diem xep hang
$stmtXH = $conn->prepare("
    SELECT hs.ho_ten, hs.avatar, lh.ten_lop, lh.khoi, dtl.diem_xep_hang
    FROM hoc_sinh hs
    JOIN lop_hoc lh ON hs.lop_id = lh.id
    LEFT JOIN diem_tich_luy dtl ON hs.id = dtl.hoc_sinh_id
    WHERE lh.trang_thai = 1 AND hs.trang_thai = 1
    ORDER BY dtl.diem_xep_hang DESC
    LIMIT 50
");
$stmtXH->execute();
$topHocSinh = $stmtXH->fetchAll();

// ============================================================
// THI DUA DATA
// ============================================================

// Lấy cấu hình hiển thị
$homeMode = 'auto';
$manualHomeWeekId = 0;
$stmtCfg = $conn->query("SELECT ma_cau_hinh, gia_tri FROM cau_hinh WHERE ma_cau_hinh IN ('home_week_display_mode', 'home_week_id')");
while ($row = $stmtCfg->fetch()) {
    if ($row['ma_cau_hinh'] == 'home_week_display_mode')
        $homeMode = $row['gia_tri'];
    if ($row['ma_cau_hinh'] == 'home_week_id')
        $manualHomeWeekId = $row['gia_tri'];
}

// Xác định tuần cần hiển thị
$displayWeekId = 0;
if ($homeMode === 'manual' && $manualHomeWeekId > 0) {
    $displayWeekId = $manualHomeWeekId;
} else {
    // Chế độ AUTO: Lấy tuần vừa kết thúc gần nhất (ngay_ket_thuc < Today)
    $stmtTuanAuto = $conn->query("
        SELECT id FROM tuan_hoc 
        WHERE ngay_ket_thuc < CURDATE() 
        ORDER BY ngay_ket_thuc DESC 
        LIMIT 1
    ");
    $autoWeek = $stmtTuanAuto->fetch();
    if ($autoWeek) {
        $displayWeekId = $autoWeek['id'];
    } else {
        // Nếu không có tuần nào đã kết thúc, lấy tuần hiện tại
        $stmtTuanCurrent = $conn->query("
            SELECT id FROM tuan_hoc 
            WHERE CURDATE() BETWEEN ngay_bat_dau AND ngay_ket_thuc 
            LIMIT 1
        ");
        $currentW = $stmtTuanCurrent->fetch();
        $displayWeekId = $currentW ? $currentW['id'] : 0;
    }
}

// Lấy danh sách 10 tuần gần nhất có dữ liệu (để hiển thị ở menu/pills)
$stmtTuanMenu = $conn->query("
    SELECT DISTINCT th.id, th.so_tuan, th.ngay_bat_dau, th.ngay_ket_thuc
    FROM xep_hang_lop_tuan xh
    JOIN tuan_hoc th ON xh.tuan_id = th.id
    ORDER BY th.ngay_bat_dau DESC
    LIMIT 10
");
$tuanCoXepHang = $stmtTuanMenu->fetchAll();

// Nếu tuần hiển thị mặc định chưa có trong danh sách top 10, hãy lấy thêm nó
$found = false;
foreach ($tuanCoXepHang as $tw) {
    if ($tw['id'] == $displayWeekId) {
        $found = true;
        break;
    }
}

if (!$found && $displayWeekId > 0) {
    $stmtTuanSpecific = $conn->prepare("SELECT id, so_tuan, ngay_bat_dau, ngay_ket_thuc FROM tuan_hoc WHERE id = ?");
    $stmtTuanSpecific->execute([$displayWeekId]);
    $specWeek = $stmtTuanSpecific->fetch();
    if ($specWeek) {
        array_unshift($tuanCoXepHang, $specWeek); // Đưa lên đầu
    }
}

// Lay xep hang của tuần được chọn
$thiDuaData = [];
if ($displayWeekId > 0) {
    $stmtThiDua = $conn->prepare("
        SELECT xh.*, lh.ten_lop, lh.khoi, th.so_tuan
        FROM xep_hang_lop_tuan xh
        JOIN lop_hoc lh ON xh.lop_id = lh.id
        JOIN tuan_hoc th ON xh.tuan_id = th.id
        WHERE xh.tuan_id = ?
        ORDER BY xh.thu_hang_toan_truong ASC
    ");
    $stmtThiDua->execute([$displayWeekId]);
    $thiDuaData = $stmtThiDua->fetchAll();
}

// Lay tieu chi thi dua
$stmtTC = $conn->query("
    SELECT * FROM tieu_chi_thi_dua
    WHERE trang_thai = 1
    ORDER BY thu_tu
");
$tieuChiList = $stmtTC->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - <?php echo SITE_NAME; ?></title>
    <?php
    require_once 'includes/seo.php';
    echo getSeoMetaTags('Trang chủ');
    echo getSeoJsonLd();
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        }

        .app-container {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
            width: 280px;
            min-width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            display: flex;
            flex-direction: column;
            color: white;
            position: relative;
        }

        .sidebar-header {
            padding: 10px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .school-logo {
            font-size: 50px;
            margin-bottom: 10px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .school-name {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
        }

        .school-desc {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 5px;
        }

        /* Menu */
        .sidebar-menu {
            flex: 1;
            padding: 20px 15px;
            overflow-y: auto;
        }

        .menu-section-title {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            padding-left: 10px;
        }

        .menu-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 8px 18px;
            margin-bottom: 8px;
            border: none;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
        }

        .menu-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .menu-btn.active {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }

        .menu-btn-icon {
            font-size: 22px;
            width: 30px;
            text-align: center;
        }

        .menu-btn-count {
            margin-left: auto;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }

        .menu-btn.active .menu-btn-count {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Login button */
        .sidebar-footer {
            padding: 12px 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .login-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 12px 16px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .login-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.5);
        }

        .login-btn.dashboard-btn {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        }

        .login-btn.dashboard-btn:hover {
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
        }

        .author-credit {
            padding: 12px 15px;
            text-align: center;
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.5;
        }

        .author-credit strong {
            color: rgba(255, 255, 255, 0.7);
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f0f4f8;
            overflow: hidden;
        }

        /* Header */
        .content-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 30px;
            background: white;
            border-bottom: 1px solid #e0e0e0;
            min-height: 80px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title-icon {
            font-size: 32px;
        }

        /* Filter tabs */
        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-tab {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            background: white;
            color: #666;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-tab:hover {
            border-color: #4F46E5;
            color: #4F46E5;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            border-color: transparent;
            color: white;
        }

        .week-select {
            padding: 8px 14px;
            border: 2px solid #4F46E5;
            border-radius: 25px;
            background: white;
            color: #4F46E5;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            outline: none;
        }

        /* Content Body */
        .content-body {
            flex: 1;
            padding: 25px 30px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* Documents Grid */
        .docs-grid {
            flex: 1;
            display: grid;
            gap: 20px;
            overflow: hidden;
        }

        .doc-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .doc-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #4F46E5;
        }

        .doc-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .doc-title {
            font-size: 15px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .doc-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .doc-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        /* Rankings Grid */
        .rankings-grid {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
            overflow: hidden;
        }

        .podium-section {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 20px;
            padding: 5px;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            border-radius: 20px;
            min-height: 200px;
        }

        .podium-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: white;
        }

        .podium-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: #4F46E5;
            margin-bottom: 8px;
            border: 3px solid;
        }

        .podium-item.rank-1 .podium-avatar {
            width: 80px;
            height: 80px;
            font-size: 32px;
            border-color: #FFD700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }

        .podium-item.rank-2 .podium-avatar {
            border-color: #C0C0C0;
        }

        .podium-item.rank-3 .podium-avatar {
            border-color: #CD7F32;
        }

        .podium-medal {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .podium-item.rank-1 .podium-medal {
            font-size: 42px;
        }

        .podium-name {
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            max-width: 100px;
        }

        .podium-class {
            font-size: 11px;
            opacity: 0.8;
        }

        .podium-score {
            font-size: 16px;
            font-weight: 700;
            margin-top: 5px;
            background: rgba(255, 255, 255, 0.2);
            padding: 3px 12px;
            border-radius: 15px;
        }

        .podium-stand {
            width: 90px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding-bottom: 10px;
            border-radius: 8px 8px 0 0;
        }

        .podium-item.rank-1 .podium-stand {
            height: 100px;
            background: linear-gradient(180deg, #FFD700 0%, #FFA500 100%);
        }

        .podium-item.rank-2 .podium-stand {
            height: 70px;
            background: linear-gradient(180deg, #C0C0C0 0%, #A0A0A0 100%);
        }

        .podium-item.rank-3 .podium-stand {
            height: 50px;
            background: linear-gradient(180deg, #CD7F32 0%, #8B4513 100%);
        }

        .rank-number {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Rank list */
        .rank-list {
            flex: 1;
            display: grid;
            gap: 12px;
            overflow: hidden;
        }

        .rank-item {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 15px 20px;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .rank-position {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
        }

        .rank-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
        }

        .rank-info {
            flex: 1;
        }

        .rank-name {
            font-size: 15px;
            font-weight: 700;
            color: #333;
        }

        .rank-class {
            font-size: 12px;
            color: #888;
        }

        .rank-score {
            font-size: 18px;
            font-weight: 700;
            color: #4F46E5;
        }

        /* Pagination Bar */
        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 20px;
            background: white;
            border-radius: 16px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .pagination-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }

        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .pagination-info {
            font-size: 15px;
            font-weight: 600;
            color: #666;
        }

        .pagination-info span {
            color: #4F46E5;
            font-weight: 700;
        }

        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #888;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .empty-text {
            font-size: 18px;
            font-weight: 600;
        }

        /* Class filter buttons in sidebar */
        .class-filter-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }

        .class-filter-btn {
            padding: 10px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.7);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .class-filter-btn:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .class-filter-btn.active {
            background: rgba(255, 255, 255, 0.2);
            border-color: #4CAF50;
            color: #4CAF50;
        }

        /* ========== THI DUA STYLES ========== */
        .thidua-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .thidua-week-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .thidua-week-pill {
            padding: 6px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            background: white;
            color: #666;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .thidua-week-pill:hover {
            border-color: #4F46E5;
            color: #4F46E5;
        }

        .thidua-week-pill.active {
            background: #4F46E5;
            border-color: #4F46E5;
            color: white;
        }

        /* Podium cho thi dua lop */
        .thidua-podium {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 16px;
            padding: 20px 10px 0;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            border-radius: 20px;
            min-height: 260px;
            position: relative;
            overflow: hidden;
        }

        .thidua-podium::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 0%, rgba(255, 255, 255, 0.15), transparent 70%);
        }

        .td-podium-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: white;
            z-index: 1;
            min-width: 120px;
        }

        .td-podium-medal {
            font-size: 36px;
            margin-bottom: 4px;
        }

        .td-podium-item.td-rank-1 .td-podium-medal {
            font-size: 48px;
        }

        .td-podium-class-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .td-podium-item.td-rank-1 .td-podium-class-name {
            font-size: 22px;
        }

        .td-podium-score-val {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
        }

        .td-podium-item.td-rank-1 .td-podium-score-val {
            font-size: 36px;
        }

        .td-podium-score-label {
            font-size: 11px;
            opacity: 0.7;
            margin-bottom: 4px;
        }

        .td-podium-badge {
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .td-badge-xuat_sac {
            background: rgba(255, 215, 0, 0.3);
            color: #FFD700;
        }

        .td-badge-tot {
            background: rgba(76, 175, 80, 0.3);
            color: #81C784;
        }

        .td-badge-kha {
            background: rgba(255, 152, 0, 0.3);
            color: #FFB74D;
        }

        .td-badge-trung_binh {
            background: rgba(158, 158, 158, 0.3);
            color: #BDBDBD;
        }

        .td-badge-can_co_gang {
            background: rgba(244, 67, 54, 0.3);
            color: #EF5350;
        }

        .td-podium-stand {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px 12px 0 0;
            font-size: 28px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .td-podium-item.td-rank-1 .td-podium-stand {
            height: 80px;
            background: linear-gradient(180deg, #FFD700 0%, #FFA500 100%);
        }

        .td-podium-item.td-rank-2 .td-podium-stand {
            height: 56px;
            background: linear-gradient(180deg, #C0C0C0 0%, #A0A0A0 100%);
        }

        .td-podium-item.td-rank-3 .td-podium-stand {
            height: 40px;
            background: linear-gradient(180deg, #CD7F32 0%, #8B4513 100%);
        }

        /* Bang chi tiet thi dua */
        .thidua-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        .thidua-table thead th {
            background: #f8f9fa;
            padding: 14px 12px;
            font-size: 12px;
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e9ecef;
            text-align: center;
            white-space: nowrap;
        }

        .thidua-table thead th:first-child {
            text-align: center;
            width: 60px;
        }

        .thidua-table thead th:nth-child(2) {
            text-align: left;
            min-width: 100px;
        }

        .thidua-table tbody td {
            padding: 14px 12px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .thidua-table tbody td:nth-child(2) {
            text-align: left;
            font-weight: 600;
        }

        .thidua-table tbody tr:last-child td {
            border-bottom: none;
        }

        .thidua-table tbody tr:hover {
            background: #f8f9ff;
        }

        .thidua-table tbody tr.td-row-1 {
            background: rgba(255, 215, 0, 0.08);
        }

        .thidua-table tbody tr.td-row-2 {
            background: rgba(192, 192, 192, 0.08);
        }

        .thidua-table tbody tr.td-row-3 {
            background: rgba(205, 127, 50, 0.08);
        }

        .td-rank-medal {
            font-size: 22px;
            line-height: 1;
        }

        .td-rank-num {
            display: inline-flex;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            background: #e9ecef;
            font-size: 13px;
            font-weight: 700;
            color: #555;
        }

        .td-criterion-cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .td-criterion-score {
            font-size: 13px;
            font-weight: 700;
            color: #333;
        }

        .td-criterion-bar {
            width: 60px;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }

        .td-criterion-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.6s ease;
        }

        .td-bar-hoc_tap {
            background: #4F46E5;
        }

        .td-bar-ne_nep {
            background: #0D9488;
        }

        .td-bar-ve_sinh {
            background: #0EA5E9;
        }

        .td-bar-hoat_dong {
            background: #F59E0B;
        }

        .td-bar-doan_ket {
            background: #EF4444;
        }

        .td-total-score {
            font-size: 18px;
            font-weight: 700;
            color: #4F46E5;
        }

        .td-xep-loai-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .td-xl-xuat_sac {
            background: #FEF3C7;
            color: #D97706;
        }

        .td-xl-tot {
            background: #D1FAE5;
            color: #059669;
        }

        .td-xl-kha {
            background: #DBEAFE;
            color: #2563EB;
        }

        .td-xl-trung_binh {
            background: #F3F4F6;
            color: #6B7280;
        }

        .td-xl-can_co_gang {
            background: #FEE2E2;
            color: #DC2626;
        }

        /* ========== DOCUMENT VIEWER MODAL ========== */
        .doc-viewer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            display: none;
            flex-direction: column;
        }

        .doc-viewer-overlay.show {
            display: flex;
        }

        .doc-viewer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 25px;
            background: #1a1a2e;
            color: white;
            flex-shrink: 0;
        }

        .doc-viewer-title {
            font-size: 1.1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .doc-viewer-actions {
            display: flex;
            gap: 10px;
        }

        .doc-viewer-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .doc-viewer-btn.login-prompt-btn {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            text-decoration: none;
        }

        .doc-viewer-btn.login-prompt-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }

        .doc-viewer-btn.close-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .doc-viewer-btn.close-btn:hover {
            background: rgba(239, 68, 68, 0.8);
        }

        .doc-viewer-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 20px;
        }

        .doc-viewer-iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 12px;
            background: white;
        }

        .doc-viewer-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
        }

        .doc-viewer-loading .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .doc-viewer-error {
            text-align: center;
            color: white;
            padding: 40px;
        }

        .doc-viewer-error-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .doc-viewer-error-text {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <!-- Document Viewer Modal -->
    <div class="doc-viewer-overlay" id="docViewerOverlay">
        <div class="doc-viewer-header">
            <div class="doc-viewer-title">
                <span id="docViewerIcon">&#128196;</span>
                <span id="docViewerTitle">Tài liệu</span>
            </div>
            <div class="doc-viewer-actions">
                <?php if (!$isLoggedIn): ?>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="doc-viewer-btn login-prompt-btn">
                        <span>&#128273;</span> Đăng nhập để tải xuống
                    </a>
                <?php endif; ?>
                <button class="doc-viewer-btn close-btn" onclick="closeDocViewer()">
                    <span>&#10005;</span> Đóng
                </button>
            </div>
        </div>
        <div class="doc-viewer-body" id="docViewerBody">
            <div class="doc-viewer-loading" id="docViewerLoading">
                <div class="loading-spinner"></div>
                <div>Đang tải tài liệu...</div>
            </div>
            <iframe class="doc-viewer-iframe" id="docViewerIframe" style="display: none;"></iframe>
        </div>
    </div>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="school-logo">&#128218;</div>
                <div class="school-name"><?php echo SITE_NAME; ?></div>
                <div class="school-desc"><?php echo SITE_DESCRIPTION; ?></div>
            </div>

            <nav class="sidebar-menu">
                <div class="menu-section-title">Khám phá</div>

                <button class="menu-btn active" onclick="navigateTo('thidua')">
                    <span class="menu-btn-icon">&#127941;</span>
                    <span>Thi Đua Học Thức</span>
                </button>

                <button class="menu-btn" onclick="navigateTo('documents')">
                    <span class="menu-btn-icon">&#128214;</span>
                    <span>Kho tài liệu</span>
                    <span class="menu-btn-count"><?php echo count($taiLieuList); ?></span>
                </button>

                <button class="menu-btn" onclick="navigateTo('rankings')">
                    <span class="menu-btn-icon">&#127942;</span>
                    <span>Bảng xếp hạng</span>
                </button>

                <div class="menu-section-title" style="margin-top: 25px;">Lọc theo lớp</div>

                <div class="class-filter-group">
                    <button class="class-filter-btn active" onclick="filterClass('all', this)">Tất cả</button>
                    <?php foreach ($danhSachLop as $lop): ?>
                        <button class="class-filter-btn" onclick="filterClass(<?php echo $lop['id']; ?>, this)">
                            <?php echo htmlspecialchars($lop['ten_lop']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </nav>

            <div class="sidebar-footer">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo BASE_URL; ?>/student/dashboard.php" class="login-btn dashboard-btn">
                        <span>&#128218;</span>
                        <span>Vào lớp học</span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="login-btn">
                        <span>&#128273;</span>
                        <span>Đăng nhập</span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="author-credit">
                <strong>Tác giả:</strong><br>
                Trần Văn Phi Hoàng, Lê Quang Nguyên. GVHD: Đoàn Thị Ngọc Lĩnh
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1 class="page-title">
                    <span class="page-title-icon" id="page-icon">&#127941;</span>
                    <span id="page-title-text">Thi Đua Học Thức</span>
                </h1>

                <div class="filter-tabs" id="filter-tabs">
                    <button class="filter-tab active" id="subtab-tuan"
                        onclick="switchThiduaTab('tuan', this)">Tuần</button>
                    <button class="filter-tab" id="subtab-thang" onclick="switchThiduaTab('thang', this)">Tháng</button>
                    <button class="filter-tab" id="subtab-hocky" onclick="switchThiduaTab('hocky', this)">Học
                        kỳ</button>
                </div>
            </header>

            <div class="content-body" id="contentBody">
                <div id="content-area"></div>
            </div>
        </main>
    </div>

    <script>
        // Data from PHP
        var DATA = {
            documents: <?php echo json_encode($taiLieuList); ?>,
            rankings: <?php echo json_encode($topHocSinh); ?>,
            subjects: <?php echo json_encode($danhSachMon); ?>,
            classes: <?php echo json_encode($danhSachLop); ?>,
            thidua: <?php echo json_encode($thiDuaData); ?>,
            tuanCoXepHang: <?php echo json_encode($tuanCoXepHang); ?>,
            tieuChi: <?php echo json_encode($tieuChiList); ?>
        };

        var SCREEN = {
            width: 0,
            height: 0,
            contentHeight: 0,
            contentWidth: 0,
            columns: 3,
            rows: 2,
            itemsPerPage: 6
        };

        var STATE = {
            currentPage: 'thidua',
            thidua: {
                subTab: 'tuan',
                selectedWeek: <?php echo $displayWeekId ? $displayWeekId : (count($tuanCoXepHang) > 0 ? $tuanCoXepHang[0]['id'] : 'null'); ?>
            },
            documents: { page: 1, total: 1, filterSubject: 'all', filterClass: 'all' },
            rankings: { page: 1, total: 1, filterClass: 'all' }
        };

        var XEP_LOAI_LABELS = {
            'xuat_sac': 'Xuất sắc',
            'tot': 'Tốt',
            'kha': 'Khá',
            'trung_binh': 'Trung bình',
            'can_co_gang': 'Cần cố gắng'
        };

        var TIEU_CHI_COLORS = {
            'hoc_tap': '#4F46E5',
            'ne_nep': '#0D9488',
            've_sinh': '#0EA5E9',
            'hoat_dong': '#F59E0B',
            'doan_ket': '#EF4444',
            'HT': '#4F46E5',
            'NN': '#0D9488',
            'VS': '#0EA5E9',
            'HD': '#F59E0B',
            'DK': '#EF4444'
        };

        // Calculate screen dimensions
        function calculateScreen() {
            SCREEN.width = window.innerWidth;
            SCREEN.height = window.innerHeight;
            SCREEN.contentWidth = SCREEN.width - 280 - 60;
            SCREEN.contentHeight = SCREEN.height - 80 - 50 - 100;

            if (STATE.currentPage === 'documents') {
                var cardWidth = 200;
                var cardHeight = 160;
                var gap = 20;
                SCREEN.columns = Math.max(2, Math.floor((SCREEN.contentWidth + gap) / (cardWidth + gap)));
                SCREEN.rows = Math.max(1, Math.floor((SCREEN.contentHeight + gap) / (cardHeight + gap)));
                SCREEN.itemsPerPage = SCREEN.columns * SCREEN.rows;
            } else if (STATE.currentPage === 'rankings') {
                var itemHeight = 70;
                var podiumHeight = 220;
                var availableHeight = SCREEN.contentHeight - podiumHeight - 40;
                SCREEN.itemsPerPage = Math.max(2, Math.floor(availableHeight / itemHeight));
            }
        }

        // Navigate to page
        function navigateTo(page) {
            STATE.currentPage = page;

            // Update menu buttons
            var menuBtns = document.querySelectorAll('.menu-btn');
            for (var i = 0; i < menuBtns.length; i++) {
                menuBtns[i].className = 'menu-btn';
            }

            var filterTabs = document.getElementById('filter-tabs');

            if (page === 'thidua') {
                menuBtns[0].className = 'menu-btn active';
                document.getElementById('page-icon').textContent = '\uD83C\uDFC5';
                document.getElementById('page-title-text').textContent = 'Thi Đua Học Thức';
                // Show thidua sub-tabs
                filterTabs.innerHTML = '';
                filterTabs.innerHTML =
                    '<button class="filter-tab' + (STATE.thidua.subTab === 'tuan' ? ' active' : '') + '" onclick="switchThiduaTab(\'tuan\', this)">Tuần</button>' +
                    '<button class="filter-tab' + (STATE.thidua.subTab === 'thang' ? ' active' : '') + '" onclick="switchThiduaTab(\'thang\', this)">Tháng</button>' +
                    '<button class="filter-tab' + (STATE.thidua.subTab === 'hocky' ? ' active' : '') + '" onclick="switchThiduaTab(\'hocky\', this)">Học kỳ</button>';
                filterTabs.style.display = 'flex';
            } else if (page === 'documents') {
                menuBtns[1].className = 'menu-btn active';
                document.getElementById('page-icon').textContent = '\uD83D\uDCD6';
                document.getElementById('page-title-text').textContent = 'Kho tài liệu học tập';
                // Show subject filter tabs
                var tabsHtml = '<button class="filter-tab active" onclick="filterSubject(\'all\', this)">Tất cả</button>';
                for (var s = 0; s < DATA.subjects.length; s++) {
                    tabsHtml += '<button class="filter-tab" onclick="filterSubject(' + DATA.subjects[s].id + ', this)">' + escapeHtml(DATA.subjects[s].ten_mon) + '</button>';
                }
                filterTabs.innerHTML = tabsHtml;
                filterTabs.style.display = 'flex';
            } else {
                menuBtns[2].className = 'menu-btn active';
                document.getElementById('page-icon').textContent = '\uD83C\uDFC6';
                document.getElementById('page-title-text').textContent = 'Bảng xếp hạng';
                filterTabs.style.display = 'none';
            }

            calculateScreen();
            render();
        }

        // Switch thidua sub-tab
        function switchThiduaTab(tab, btn) {
            STATE.thidua.subTab = tab;
            var tabs = document.querySelectorAll('#filter-tabs .filter-tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].className = 'filter-tab';
            }
            if (btn) btn.className = 'filter-tab active';
            render();
        }

        // Select thidua week
        function selectThiduaWeek(weekId, btn) {
            STATE.thidua.selectedWeek = weekId;
            var pills = document.querySelectorAll('.thidua-week-pill');
            for (var i = 0; i < pills.length; i++) {
                pills[i].className = 'thidua-week-pill';
            }
            if (btn) btn.className = 'thidua-week-pill active';
            renderThiduaContent();
        }

        // Filter by subject
        function filterSubject(subjectId, btn) {
            STATE.documents.filterSubject = subjectId;
            STATE.documents.page = 1;
            var tabs = document.querySelectorAll('#filter-tabs .filter-tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].className = 'filter-tab';
            }
            if (btn) btn.className = 'filter-tab active';
            render();
        }

        // Filter by class
        function filterClass(classId, btn) {
            STATE.documents.filterClass = classId;
            STATE.rankings.filterClass = classId;
            STATE.documents.page = 1;
            STATE.rankings.page = 1;
            var btns = document.querySelectorAll('.class-filter-btn');
            for (var i = 0; i < btns.length; i++) {
                btns[i].className = 'class-filter-btn';
            }
            if (btn) btn.className = 'class-filter-btn active';
            render();
        }

        // Get filtered data
        function getFilteredDocuments() {
            var docs = DATA.documents;
            var filtered = [];
            for (var i = 0; i < docs.length; i++) {
                var doc = docs[i];
                var matchSubject = STATE.documents.filterSubject === 'all' || doc.mon_hoc_id == STATE.documents.filterSubject;
                var matchClass = STATE.documents.filterClass === 'all' || doc.lop_id == STATE.documents.filterClass || doc.lop_id === null;
                if (matchSubject && matchClass) {
                    filtered.push(doc);
                }
            }
            return filtered;
        }

        function getFilteredRankings() {
            var rankings = DATA.rankings;
            if (STATE.rankings.filterClass === 'all') return rankings;
            var filtered = [];
            for (var i = 0; i < rankings.length; i++) {
                var classInfo = null;
                for (var j = 0; j < DATA.classes.length; j++) {
                    if (DATA.classes[j].id == STATE.rankings.filterClass) {
                        classInfo = DATA.classes[j];
                        break;
                    }
                }
                if (classInfo && rankings[i].khoi == classInfo.khoi) {
                    filtered.push(rankings[i]);
                }
            }
            return filtered;
        }

        // Render content
        function render() {
            calculateScreen();
            if (STATE.currentPage === 'thidua') {
                renderThidua();
            } else if (STATE.currentPage === 'documents') {
                renderDocuments();
            } else {
                renderRankings();
            }
        }

        // ========== RENDER THI DUA ==========
        function renderThidua() {
            if (STATE.thidua.subTab === 'tuan') {
                var html = '<div class="thidua-container">';

                // Week pills
                if (DATA.tuanCoXepHang.length > 0) {
                    html += '<div class="thidua-week-pills">';
                    for (var w = 0; w < DATA.tuanCoXepHang.length; w++) {
                        var wk = DATA.tuanCoXepHang[w];
                        var isActive = (STATE.thidua.selectedWeek == wk.id) ? ' active' : '';
                        var startDate = formatDate(wk.ngay_bat_dau);
                        var endDate = formatDate(wk.ngay_ket_thuc);
                        html += '<button class="thidua-week-pill' + isActive + '" onclick="selectThiduaWeek(' + wk.id + ', this)">';
                        html += 'Tuan ' + wk.so_tuan + ' (' + startDate + ' - ' + endDate + ')';
                        html += '</button>';
                    }
                    html += '</div>';
                }

                html += '<div id="thidua-week-content"></div>';
                html += '</div>';

                document.getElementById('content-area').innerHTML = html;
                renderThiduaContent();
            } else {
                // Thang / Hoc ky
                var tabName = STATE.thidua.subTab === 'thang' ? 'tháng' : 'học kỳ';
                var html = '<div class="empty-state">';
                html += '<div class="empty-icon">&#128202;</div>';
                html += '<div class="empty-text">Chưa có dữ liệu xếp hạng theo ' + tabName + '</div>';
                html += '<p style="color:#aaa; margin-top:10px;">Dữ liệu sẽ được cập nhật khi có tổng kết ' + tabName + '.</p>';
                html += '</div>';
                document.getElementById('content-area').innerHTML = html;
            }
        }

        function renderThiduaContent() {
            var weekId = STATE.thidua.selectedWeek;
            if (!weekId) {
                document.getElementById('thidua-week-content').innerHTML =
                    '<div class="empty-state"><div class="empty-icon">&#127941;</div><div class="empty-text">Chưa có dữ liệu thi đua</div></div>';
                return;
            }

            // Filter data for this week
            var weekData = [];
            for (var i = 0; i < DATA.thidua.length; i++) {
                if (DATA.thidua[i].tuan_id == weekId) {
                    weekData.push(DATA.thidua[i]);
                }
            }

            if (weekData.length === 0) {
                document.getElementById('thidua-week-content').innerHTML =
                    '<div class="empty-state"><div class="empty-icon">&#128202;</div><div class="empty-text">Chưa có dữ liệu cho tuần này</div></div>';
                return;
            }

            var html = '';

            // ---- PODIUM TOP 3 ----
            if (weekData.length >= 3) {
                var medals = ['\uD83E\uDD47', '\uD83E\uDD48', '\uD83E\uDD49'];
                html += '<div class="thidua-podium">';

                // Order: 2, 1, 3
                var order = [1, 0, 2];
                for (var oi = 0; oi < order.length; oi++) {
                    var idx = order[oi];
                    var r = weekData[idx];
                    var rank = idx + 1;
                    html += '<div class="td-podium-item td-rank-' + rank + '">';
                    html += '<div class="td-podium-medal">' + medals[idx] + '</div>';
                    html += '<div class="td-podium-class-name">' + escapeHtml(r.ten_lop) + '</div>';
                    html += '<div class="td-podium-score-val">' + parseFloat(r.tong_diem_co_trong_so).toFixed(1) + '</div>';
                    html += '<div class="td-podium-score-label">/ 100 điểm</div>';
                    html += '<div class="td-podium-badge td-badge-' + (r.xep_loai || '') + '">' + (XEP_LOAI_LABELS[r.xep_loai] || '') + '</div>';
                    html += '<div class="td-podium-stand">' + rank + '</div>';
                    html += '</div>';
                }
                html += '</div>';
            } else if (weekData.length > 0) {
                // Fewer than 3: simple display
                html += '<div class="thidua-podium" style="min-height:160px; justify-content:center; align-items:center;">';
                for (var si = 0; si < weekData.length; si++) {
                    var sr = weekData[si];
                    html += '<div class="td-podium-item td-rank-' + (si + 1) + '" style="margin:0 20px;">';
                    html += '<div class="td-podium-medal">' + (si === 0 ? '\uD83E\uDD47' : '\uD83E\uDD48') + '</div>';
                    html += '<div class="td-podium-class-name">' + escapeHtml(sr.ten_lop) + '</div>';
                    html += '<div class="td-podium-score-val">' + parseFloat(sr.tong_diem_co_trong_so).toFixed(1) + '</div>';
                    html += '<div class="td-podium-score-label">/ 100 điểm</div>';
                    html += '</div>';
                }
                html += '</div>';
            }

            // ---- DETAIL TABLE ----
            html += '<table class="thidua-table">';
            html += '<thead><tr>';
            html += '<th>Hạng</th>';
            html += '<th>Lớp</th>';

            // Tieu chi columns
            var colFields = ['diem_hoc_tap', 'diem_ne_nep', 'diem_ve_sinh', 'diem_hoat_dong', 'diem_doan_ket'];
            var colLabels = ['Học tập', 'Nề nếp', 'Vệ sinh', 'Hoạt động', 'Đoàn kết'];
            var colMaxes = [40, 25, 15, 15, 5];
            var colCssKeys = ['hoc_tap', 'ne_nep', 've_sinh', 'hoat_dong', 'doan_ket'];

            for (var c = 0; c < colLabels.length; c++) {
                html += '<th>' + colLabels[c] + '<br><small style="font-weight:500;opacity:0.6;">/' + colMaxes[c] + '</small></th>';
            }
            html += '<th>Tổng</th>';
            html += '<th>Xếp loại</th>';
            html += '</tr></thead>';
            html += '<tbody>';

            for (var ri = 0; ri < weekData.length; ri++) {
                var item = weekData[ri];
                var rankNum = ri + 1;
                var rowClass = (rankNum <= 3) ? ' class="td-row-' + rankNum + '"' : '';

                html += '<tr' + rowClass + '>';

                // Rank
                html += '<td>';
                if (rankNum === 1) html += '<span class="td-rank-medal">\uD83E\uDD47</span>';
                else if (rankNum === 2) html += '<span class="td-rank-medal">\uD83E\uDD48</span>';
                else if (rankNum === 3) html += '<span class="td-rank-medal">\uD83E\uDD49</span>';
                else html += '<span class="td-rank-num">' + rankNum + '</span>';
                html += '</td>';

                // Class name
                html += '<td><strong>' + escapeHtml(item.ten_lop) + '</strong><br><small style="color:#888;">Khối ' + item.khoi + '</small></td>';

                // Criteria scores with progress bars
                for (var ci = 0; ci < colFields.length; ci++) {
                    var val = parseFloat(item[colFields[ci]] || 0);
                    var max = colMaxes[ci];
                    var pct = Math.min(100, (val / max) * 100);
                    var barKey = colCssKeys[ci];

                    html += '<td>';
                    html += '<div class="td-criterion-cell">';
                    html += '<div class="td-criterion-score">' + val.toFixed(1) + '</div>';
                    html += '<div class="td-criterion-bar"><div class="td-criterion-bar-fill td-bar-' + barKey + '" style="width:' + pct + '%"></div></div>';
                    html += '</div>';
                    html += '</td>';
                }

                // Total
                html += '<td><div class="td-total-score">' + parseFloat(item.tong_diem_co_trong_so).toFixed(1) + '</div></td>';

                // Xep loai badge
                var xlKey = item.xep_loai || 'trung_binh';
                html += '<td><span class="td-xep-loai-badge td-xl-' + xlKey + '">' + (XEP_LOAI_LABELS[xlKey] || xlKey) + '</span></td>';

                html += '</tr>';
            }

            html += '</tbody></table>';

            document.getElementById('thidua-week-content').innerHTML = html;
        }

        // ========== RENDER DOCUMENTS ==========
        function renderDocuments() {
            var docs = getFilteredDocuments();
            var total = docs.length;
            var totalPages = Math.max(1, Math.ceil(total / SCREEN.itemsPerPage));

            STATE.documents.total = totalPages;
            if (STATE.documents.page > totalPages) STATE.documents.page = totalPages;

            var start = (STATE.documents.page - 1) * SCREEN.itemsPerPage;
            var end = Math.min(start + SCREEN.itemsPerPage, total);
            var pageDocs = docs.slice(start, end);

            var html = '<div class="docs-grid" style="grid-template-columns: repeat(' + SCREEN.columns + ', 1fr); grid-template-rows: repeat(' + SCREEN.rows + ', 1fr);">';

            if (pageDocs.length === 0) {
                html += '<div class="empty-state" style="grid-column: 1 / -1; grid-row: 1 / -1;">';
                html += '<div class="empty-icon">&#128218;</div>';
                html += '<div class="empty-text">Chưa có tài liệu nào</div>';
                html += '</div>';
            } else {
                var icons = { pdf: '\uD83D\uDCC4', word: '\uD83D\uDCDD', ppt: '\uD83D\uDCCA', video: '\uD83C\uDFAC', image: '\uD83D\uDDBC\uFE0F' };
                for (var i = 0; i < pageDocs.length; i++) {
                    var doc = pageDocs[i];
                    var icon = icons[doc.loai_file] || '\uD83D\uDCC1';
                    html += '<div class="doc-card" onclick="viewDocument(' + doc.id + ')">';
                    html += '<div class="doc-icon">' + icon + '</div>';
                    html += '<div class="doc-title">' + escapeHtml(doc.tieu_de) + '</div>';
                    html += '<div class="doc-meta">';
                    html += '<span class="doc-badge" style="background: ' + doc.mau_sac + '20; color: ' + doc.mau_sac + '">' + escapeHtml(doc.ten_mon) + '</span>';
                    html += '<span class="doc-badge" style="background: #66666620; color: #666">' + (doc.ten_lop || 'Chung') + '</span>';
                    html += '</div></div>';
                }
            }
            html += '</div>';
            html += renderPagination('documents', STATE.documents.page, totalPages, total);
            document.getElementById('content-area').innerHTML = html;
        }

        // ========== RENDER RANKINGS ==========
        function renderRankings() {
            var rankings = getFilteredRankings();
            var total = rankings.length;
            var html = '<div class="rankings-grid">';

            // Podium for top 3
            if (rankings.length >= 3) {
                html += '<div class="podium-section">';
                var r2 = rankings[1];
                var initial2 = r2.ho_ten.charAt(0).toUpperCase();
                html += '<div class="podium-item rank-2">';
                html += '<div class="podium-medal">\uD83E\uDD48</div>';
                html += '<div class="podium-avatar">' + initial2 + '</div>';
                html += '<div class="podium-name">' + escapeHtml(r2.ho_ten) + '</div>';
                html += '<div class="podium-class">' + escapeHtml(r2.ten_lop) + '</div>';
                html += '<div class="podium-stand"><span class="rank-number">2</span></div>';
                html += '</div>';

                var r1 = rankings[0];
                var initial1 = r1.ho_ten.charAt(0).toUpperCase();
                html += '<div class="podium-item rank-1">';
                html += '<div class="podium-medal">\uD83E\uDD47</div>';
                html += '<div class="podium-avatar">' + initial1 + '</div>';
                html += '<div class="podium-name">' + escapeHtml(r1.ho_ten) + '</div>';
                html += '<div class="podium-class">' + escapeHtml(r1.ten_lop) + '</div>';
                html += '<div class="podium-score">' + Math.round(r1.diem_xep_hang || 0) + ' điểm</div>';
                html += '<div class="podium-stand"><span class="rank-number">1</span></div>';
                html += '</div>';

                var r3 = rankings[2];
                var initial3 = r3.ho_ten.charAt(0).toUpperCase();
                html += '<div class="podium-item rank-3">';
                html += '<div class="podium-medal">\uD83E\uDD49</div>';
                html += '<div class="podium-avatar">' + initial3 + '</div>';
                html += '<div class="podium-name">' + escapeHtml(r3.ho_ten) + '</div>';
                html += '<div class="podium-class">' + escapeHtml(r3.ten_lop) + '</div>';
                html += '<div class="podium-stand"><span class="rank-number">3</span></div>';
                html += '</div>';
                html += '</div>';
            }

            // List from rank 4
            var listRankings = rankings.slice(3);
            var listTotal = listRankings.length;
            var totalPages = Math.max(1, Math.ceil(listTotal / SCREEN.itemsPerPage));

            STATE.rankings.total = totalPages;
            if (STATE.rankings.page > totalPages) STATE.rankings.page = totalPages;

            var start = (STATE.rankings.page - 1) * SCREEN.itemsPerPage;
            var end = Math.min(start + SCREEN.itemsPerPage, listTotal);
            var pageRankings = listRankings.slice(start, end);

            if (pageRankings.length > 0) {
                html += '<div class="rank-list" style="grid-template-rows: repeat(' + SCREEN.itemsPerPage + ', 1fr);">';
                for (var i = 0; i < pageRankings.length; i++) {
                    var r = pageRankings[i];
                    var rank = start + i + 4;
                    var initial = r.ho_ten.charAt(0).toUpperCase();
                    html += '<div class="rank-item">';
                    html += '<div class="rank-position">' + rank + '</div>';
                    html += '<div class="rank-avatar">' + initial + '</div>';
                    html += '<div class="rank-info">';
                    html += '<div class="rank-name">' + escapeHtml(r.ho_ten) + '</div>';
                    html += '<div class="rank-class">' + escapeHtml(r.ten_lop) + '</div>';
                    html += '</div>';
                    html += '<div class="rank-score">' + Math.round(r.diem_xep_hang || 0) + '</div>';
                    html += '</div>';
                }
                html += '</div>';
            } else if (rankings.length < 3) {
                html += '<div class="empty-state">';
                html += '<div class="empty-icon">&#127942;</div>';
                html += '<div class="empty-text">Chưa có đủ dữ liệu xếp hạng</div>';
                html += '</div>';
            }
            html += '</div>';

            if (listTotal > 0) {
                html += renderPagination('rankings', STATE.rankings.page, totalPages, listTotal);
            }

            document.getElementById('content-area').innerHTML = html;
        }

        // ========== PAGINATION ==========
        function renderPagination(type, current, total, totalItems) {
            var html = '<div class="pagination-bar">';
            html += '<button class="pagination-btn" onclick="goPage(\'' + type + '\', -1)" ' + (current <= 1 ? 'disabled' : '') + '>';
            html += '\u25C0 Trước';
            html += '</button>';
            html += '<div class="pagination-info">';
            html += 'Trang <span>' + current + '</span> / <span>' + total + '</span>';
            html += ' (' + totalItems + ' mục)';
            html += '</div>';
            html += '<button class="pagination-btn" onclick="goPage(\'' + type + '\', 1)" ' + (current >= total ? 'disabled' : '') + '>';
            html += 'Sau \u25B6';
            html += '</button>';
            html += '</div>';
            return html;
        }

        function goPage(type, delta) {
            STATE[type].page += delta;
            render();
        }

        // ========== UTILITIES ==========
        function formatDate(dateStr) {
            if (!dateStr) return '';
            var parts = dateStr.split('-');
            if (parts.length === 3) return parts[2] + '/' + parts[1];
            return dateStr;
        }

        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }

        // ========== FILE ICONS ==========
        var FILE_ICONS = { pdf: '\uD83D\uDCC4', word: '\uD83D\uDCDD', ppt: '\uD83D\uDCCA', video: '\uD83C\uDFAC', image: '\uD83D\uDDBC\uFE0F' };
        var BASE_URL = '<?php echo BASE_URL; ?>';

        // ========== DOCUMENT VIEWER ==========
        var CURRENT_DOC = null;

        function viewDocument(docId) {
            var doc = null;
            for (var i = 0; i < DATA.documents.length; i++) {
                if (DATA.documents[i].id == docId) {
                    doc = DATA.documents[i];
                    break;
                }
            }
            if (!doc) return;

            CURRENT_DOC = doc;
            var gdriveId = doc.google_drive_id || '';
            var localFile = doc.local_file || '';
            var fileType = doc.loai_file || '';

            if (!localFile && !gdriveId) {
                alert('Tài liệu chưa có file đính kèm');
                return;
            }

            var overlay = document.getElementById('docViewerOverlay');
            var iframe = document.getElementById('docViewerIframe');
            var loading = document.getElementById('docViewerLoading');

            overlay.classList.add('show');
            iframe.style.display = 'none';
            loading.style.display = 'block';

            var icon = FILE_ICONS[fileType] || '\uD83D\uDCC4';
            document.getElementById('docViewerIcon').textContent = icon;
            document.getElementById('docViewerTitle').textContent = doc.tieu_de;

            var viewUrl = '';
            if (localFile) {
                var fileUrl = BASE_URL + '/uploads/documents/' + localFile;
                if (fileType === 'pdf') {
                    viewUrl = fileUrl;
                } else if (fileType === 'word' || fileType === 'ppt') {
                    viewUrl = 'https://docs.google.com/gview?url=' + encodeURIComponent(fileUrl) + '&embedded=true';
                } else if (fileType === 'image') {
                    showImageViewer(fileUrl);
                    return;
                } else if (fileType === 'video') {
                    showVideoViewer(fileUrl);
                    return;
                } else {
                    viewUrl = fileUrl;
                }
            } else if (gdriveId) {
                if (fileType === 'image') {
                    var imgUrl = 'https://drive.google.com/uc?export=view&id=' + gdriveId;
                    showImageViewer(imgUrl);
                    return;
                } else {
                    viewUrl = 'https://drive.google.com/file/d/' + gdriveId + '/preview';
                }
            }

            iframe.onload = function () {
                loading.style.display = 'none';
                iframe.style.display = 'block';
            };
            iframe.onerror = function () { showViewerError(); };
            iframe.src = viewUrl;

            setTimeout(function () {
                if (loading.style.display !== 'none') {
                    loading.style.display = 'none';
                    iframe.style.display = 'block';
                }
            }, 5000);
        }

        function showImageViewer(imageUrl) {
            var body = document.getElementById('docViewerBody');
            document.getElementById('docViewerLoading').style.display = 'none';
            document.getElementById('docViewerIframe').style.display = 'none';
            body.innerHTML = '<img src="' + imageUrl + '" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 12px;" onerror="showViewerError()">';
        }

        function showVideoViewer(videoUrl) {
            var body = document.getElementById('docViewerBody');
            document.getElementById('docViewerLoading').style.display = 'none';
            document.getElementById('docViewerIframe').style.display = 'none';
            body.innerHTML = '<video controls autoplay style="max-width: 100%; max-height: 100%; border-radius: 12px;"><source src="' + videoUrl + '">Trình duyệt không hỗ trợ video.</video>';
        }

        function showViewerError() {
            var body = document.getElementById('docViewerBody');
            body.innerHTML = '<div class="doc-viewer-error"><div class="doc-viewer-error-icon">\uD83D\uDE15</div><div class="doc-viewer-error-text">Không thể xem trực tiếp tài liệu này.<br>Vui lòng đăng nhập để tải xuống.</div></div>';
        }

        function closeDocViewer() {
            var overlay = document.getElementById('docViewerOverlay');
            var iframe = document.getElementById('docViewerIframe');
            overlay.classList.remove('show');
            iframe.src = '';
            document.getElementById('docViewerBody').innerHTML =
                '<div class="doc-viewer-loading" id="docViewerLoading"><div class="loading-spinner"></div><div>Đang tải tài liệu...</div></div>' +
                '<iframe class="doc-viewer-iframe" id="docViewerIframe" style="display: none;"></iframe>';
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDocViewer();
        });

        // ========== INIT ==========
        window.onload = function () {
            calculateScreen();
            render();
        };

        window.onresize = function () {
            calculateScreen();
            render();
        };
    </script>
</body>

</html>