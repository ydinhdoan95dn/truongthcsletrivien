<?php
/**
 * ==============================================
 * XEP HANG LOP (Student View)
 * Module: Student - He thong Thi dua
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/permission_helper.php';

requireStudent();

define('PAGE_TITLE', 'Xep hang lop');

$conn = getDBConnection();
$student = getCurrentStudent();
$lop_id = $student['lop_id'];

// ============================================================
// GET LOP INFO
// ============================================================
$stmtLop = $conn->prepare("SELECT * FROM lop_hoc WHERE id = ?");
$stmtLop->execute([$lop_id]);
$lopInfo = $stmtLop->fetch();

// ============================================================
// FILTER
// ============================================================
$tuan_id = isset($_GET['tuan']) ? intval($_GET['tuan']) : 0;
$view_mode = isset($_GET['view']) ? sanitize($_GET['view']) : 'my_class'; // my_class, all_classes

// Lay tuan hien tai neu khong chon
if ($tuan_id == 0) {
    $stmtCurrentTuan = $conn->query("
        SELECT id FROM tuan_hoc
        WHERE CURDATE() BETWEEN ngay_bat_dau AND ngay_ket_thuc
        LIMIT 1
    ");
    $currentTuan = $stmtCurrentTuan->fetch();
    $tuan_id = $currentTuan ? $currentTuan['id'] : 0;
}

// Lay thong tin tuan
$tuanInfo = null;
if ($tuan_id > 0) {
    $stmtTuan = $conn->prepare("SELECT * FROM tuan_hoc WHERE id = ?");
    $stmtTuan->execute([$tuan_id]);
    $tuanInfo = $stmtTuan->fetch();
}

// Lay danh sach tuan (10 tuan gan nhat)
$stmtDanhSachTuan = $conn->query("
    SELECT * FROM tuan_hoc
    WHERE trang_thai = 1
    ORDER BY ngay_bat_dau DESC
    LIMIT 10
");
$danhSachTuan = $stmtDanhSachTuan->fetchAll();

// ============================================================
// GET XEP HANG LOP MINH
// ============================================================
$xepHangLopMinh = null;
if ($tuan_id > 0) {
    $stmtMyRank = $conn->prepare("
        SELECT
            xh.*,
            lh.ten_lop,
            lh.khoi,
            lh.khoi_label
        FROM xep_hang_lop_tuan xh
        JOIN lop_hoc lh ON xh.lop_id = lh.id
        WHERE xh.lop_id = ? AND xh.tuan_id = ?
    ");
    $stmtMyRank->execute([$lop_id, $tuan_id]);
    $xepHangLopMinh = $stmtMyRank->fetch();
}

// ============================================================
// GET XEP HANG TAT CA (neu che do xem all)
// ============================================================
$allRankings = [];
if ($view_mode === 'all_classes' && $tuan_id > 0) {
    // Lay xep hang cung khoi
    $stmtAll = $conn->prepare("
        SELECT
            xh.*,
            lh.ten_lop,
            lh.khoi,
            lh.khoi_label
        FROM xep_hang_lop_tuan xh
        JOIN lop_hoc lh ON xh.lop_id = lh.id
        WHERE xh.tuan_id = ? AND lh.khoi = ?
        ORDER BY xh.thu_hang_cung_khoi ASC
    ");
    $stmtAll->execute([$tuan_id, $lopInfo['khoi']]);
    $allRankings = $stmtAll->fetchAll();
}

// ============================================================
// DIEM CHI TIET
// ============================================================
$chiTietDiem = [];
if ($tuan_id > 0) {
    $stmtChiTiet = $conn->prepare("
        SELECT
            dtd.*,
            tc.ten_tieu_chi,
            tc.ma_tieu_chi,
            tc.diem_toi_da,
            tc.trong_so
        FROM diem_thi_dua_tuan dtd
        JOIN tieu_chi_thi_dua tc ON dtd.tieu_chi_id = tc.id
        WHERE dtd.lop_id = ? AND dtd.tuan_id = ?
          AND dtd.trang_thai = 'da_duyet'
        ORDER BY tc.thu_tu
    ");
    $stmtChiTiet->execute([$lop_id, $tuan_id]);
    $chiTietDiem = $stmtChiTiet->fetchAll();
}

// ============================================================
// HELPER DATA
// ============================================================
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
$xepLoaiMap = array(
    'xuat_sac' => array('text' => 'Xuat sac', 'icon' => '&#11088;&#11088;&#11088;'),
    'tot' => array('text' => 'Tot', 'icon' => '&#11088;&#11088;'),
    'kha' => array('text' => 'Kha', 'icon' => '&#11088;'),
    'trung_binh' => array('text' => 'Trung binh', 'icon' => '&#128202;'),
    'can_co_gang' => array('text' => 'Can co gang', 'icon' => '&#128170;')
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?> - Student</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ============================================= */
        /* BASE RESET & LAYOUT                           */
        /* ============================================= */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f4f8;
            color: #1e293b;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* ============================================= */
        /* SIDEBAR                                        */
        /* ============================================= */
        .sidebar-wrapper {
            width: 260px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 100;
        }

        .sidebar-wrapper .student-sidebar {
            min-height: 100vh;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        /* ============================================= */
        /* MAIN CONTENT                                   */
        /* ============================================= */
        .main-content {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .content-header {
            background: white;
            padding: 16px 28px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .hamburger-btn {
            display: none;
            background: none;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            cursor: pointer;
            color: #64748b;
            font-size: 18px;
        }

        .header-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-title i { color: #F59E0B; }

        .header-filters {
            margin-left: auto;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 8px 32px 8px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            color: #334155;
            background: white;
            cursor: pointer;
            -webkit-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        .filter-select:focus {
            outline: none;
            border-color: #4F46E5;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }

        /* View toggle pills */
        .view-toggle {
            display: flex;
            background: #f1f5f9;
            border-radius: 10px;
            padding: 3px;
        }

        .view-toggle a {
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .view-toggle a.active {
            background: white;
            color: #4F46E5;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .view-toggle a:hover:not(.active) {
            color: #334155;
        }

        .content-body {
            padding: 24px 28px;
            flex: 1;
        }

        /* ============================================= */
        /* HERO CARD - MY CLASS OVERVIEW                  */
        /* ============================================= */
        .hero-card {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            border-radius: 20px;
            padding: 0;
            margin-bottom: 24px;
            box-shadow: 0 10px 40px rgba(79,70,229,0.3);
            position: relative;
            overflow: hidden;
            color: white;
        }

        .hero-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.12), transparent 60%);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            position: relative;
            z-index: 1;
        }

        .hero-section {
            padding: 28px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .hero-section-mid {
            border-left: 1px solid rgba(255,255,255,0.15);
            border-right: 1px solid rgba(255,255,255,0.15);
        }

        /* Rank display */
        .hero-medal { font-size: 40px; margin-bottom: 4px; line-height: 1; }
        .hero-rank-num {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1;
            text-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .hero-rank-label {
            font-size: 0.82rem;
            opacity: 0.8;
            margin-top: 4px;
        }

        /* Score circle SVG */
        .score-circle-wrap {
            position: relative;
            width: 120px;
            height: 120px;
            margin-bottom: 8px;
        }

        .score-circle-wrap svg {
            transform: rotate(-90deg);
            width: 120px;
            height: 120px;
        }

        .score-circle-bg {
            fill: none;
            stroke: rgba(255,255,255,0.15);
            stroke-width: 8;
        }

        .score-circle-fill {
            fill: none;
            stroke: white;
            stroke-width: 8;
            stroke-linecap: round;
            stroke-dasharray: 339.292;
            stroke-dashoffset: 339.292;
            transition: stroke-dashoffset 1.2s ease;
        }

        .score-circle-text {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .score-circle-val {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1;
        }

        .score-circle-max {
            font-size: 0.72rem;
            opacity: 0.65;
            margin-top: 2px;
        }

        /* Classification in hero */
        .hero-class-name {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .hero-xep-loai-badge {
            padding: 4px 16px;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 700;
            margin-top: 8px;
        }

        .hero-badge-xuat_sac { background: rgba(255,215,0,0.3); color: #FFD700; }
        .hero-badge-tot { background: rgba(76,175,80,0.3); color: #81C784; }
        .hero-badge-kha { background: rgba(255,152,0,0.3); color: #FFB74D; }
        .hero-badge-trung_binh { background: rgba(158,158,158,0.3); color: #BDBDBD; }
        .hero-badge-can_co_gang { background: rgba(244,67,54,0.3); color: #EF5350; }

        .hero-week-label {
            font-size: 0.78rem;
            opacity: 0.7;
            margin-top: 4px;
        }

        /* ============================================= */
        /* SCORE BREAKDOWN GRID                           */
        /* ============================================= */
        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .breakdown-card {
            background: white;
            border-radius: 14px;
            padding: 18px 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .breakdown-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .breakdown-card[data-tc="hoc_tap"]::before { background: #4F46E5; }
        .breakdown-card[data-tc="ne_nep"]::before { background: #0D9488; }
        .breakdown-card[data-tc="ve_sinh"]::before { background: #0EA5E9; }
        .breakdown-card[data-tc="hoat_dong"]::before { background: #F59E0B; }
        .breakdown-card[data-tc="doan_ket"]::before { background: #EF4444; }

        .breakdown-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
            margin-bottom: 10px;
        }

        .breakdown-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 2px;
        }

        .breakdown-weight {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-bottom: 10px;
        }

        .breakdown-score {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 2px;
        }

        .breakdown-score-max {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-bottom: 10px;
        }

        .breakdown-bar {
            width: 100%;
            height: 6px;
            background: #f1f5f9;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .breakdown-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.8s ease;
        }

        .breakdown-weighted {
            font-size: 0.75rem;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }

        .breakdown-weighted strong {
            font-weight: 700;
        }

        .breakdown-note {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #f1f5f9;
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .breakdown-note i { margin-right: 4px; }

        /* ============================================= */
        /* PODIUM (from index.php)                        */
        /* ============================================= */
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i { color: #4F46E5; }

        .thidua-podium {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 16px;
            padding: 24px 10px 0;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            border-radius: 20px;
            min-height: 260px;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .thidua-podium::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 50% 0%, rgba(255,255,255,0.15), transparent 70%);
        }

        .td-podium-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: white;
            z-index: 1;
            min-width: 110px;
        }

        .td-podium-medal { font-size: 36px; margin-bottom: 4px; }
        .td-podium-item.td-rank-1 .td-podium-medal { font-size: 48px; }

        .td-podium-class-name { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
        .td-podium-item.td-rank-1 .td-podium-class-name { font-size: 22px; }

        .td-podium-score-val { font-size: 28px; font-weight: 700; line-height: 1; }
        .td-podium-item.td-rank-1 .td-podium-score-val { font-size: 36px; }

        .td-podium-score-label { font-size: 11px; opacity: 0.7; margin-bottom: 4px; }

        .td-podium-badge {
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .td-badge-xuat_sac { background: rgba(255,215,0,0.3); color: #FFD700; }
        .td-badge-tot { background: rgba(76,175,80,0.3); color: #81C784; }
        .td-badge-kha { background: rgba(255,152,0,0.3); color: #FFB74D; }
        .td-badge-trung_binh { background: rgba(158,158,158,0.3); color: #BDBDBD; }
        .td-badge-can_co_gang { background: rgba(244,67,54,0.3); color: #EF5350; }

        .td-podium-stand {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px 12px 0 0;
            font-size: 28px;
            font-weight: 700;
            color: rgba(255,255,255,0.9);
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
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

        /* ============================================= */
        /* RANKING TABLE (from index.php)                 */
        /* ============================================= */
        .table-wrapper {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .thidua-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
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

        .thidua-table thead th:first-child { text-align: center; width: 60px; }
        .thidua-table thead th:nth-child(2) { text-align: left; min-width: 100px; }

        .thidua-table tbody td {
            padding: 14px 12px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .thidua-table tbody td:nth-child(2) { text-align: left; font-weight: 600; }
        .thidua-table tbody tr:last-child td { border-bottom: none; }
        .thidua-table tbody tr:hover { background: #f8f9ff; }

        .thidua-table tbody tr.td-row-1 { background: rgba(255,215,0,0.08); }
        .thidua-table tbody tr.td-row-2 { background: rgba(192,192,192,0.08); }
        .thidua-table tbody tr.td-row-3 { background: rgba(205,127,50,0.08); }

        .thidua-table tbody tr.td-my-class {
            background: rgba(79,70,229,0.08) !important;
            border-left: 3px solid #4F46E5;
        }

        .td-rank-medal { font-size: 22px; line-height: 1; }

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

        .td-criterion-score { font-size: 13px; font-weight: 700; color: #333; }

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

        .td-bar-hoc_tap { background: #4F46E5; }
        .td-bar-ne_nep { background: #0D9488; }
        .td-bar-ve_sinh { background: #0EA5E9; }
        .td-bar-hoat_dong { background: #F59E0B; }
        .td-bar-doan_ket { background: #EF4444; }

        .td-total-score { font-size: 18px; font-weight: 700; color: #4F46E5; }

        .td-xep-loai-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .td-xl-xuat_sac { background: #FEF3C7; color: #D97706; }
        .td-xl-tot { background: #D1FAE5; color: #059669; }
        .td-xl-kha { background: #DBEAFE; color: #2563EB; }
        .td-xl-trung_binh { background: #F3F4F6; color: #6B7280; }
        .td-xl-can_co_gang { background: #FEE2E2; color: #DC2626; }

        .td-my-badge {
            display: inline-block;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 8px;
            margin-left: 6px;
        }

        /* ============================================= */
        /* ALERTS                                         */
        /* ============================================= */
        .alert-card {
            background: white;
            border-radius: 14px;
            padding: 32px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .alert-card i {
            font-size: 2.5rem;
            margin-bottom: 12px;
            display: block;
        }

        .alert-card.alert-warning i { color: #F59E0B; }
        .alert-card.alert-info i { color: #4F46E5; }

        .alert-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: #1e293b;
        }

        .alert-card p {
            font-size: 0.9rem;
            color: #64748b;
            margin: 0;
        }

        /* ============================================= */
        /* RESPONSIVE                                     */
        /* ============================================= */
        @media (max-width: 1366px) {
            .sidebar-wrapper { width: 220px; }
        }

        @media (max-width: 1280px) {
            .sidebar-wrapper { width: 200px; }
            .breakdown-grid { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 1024px) {
            .breakdown-grid { grid-template-columns: repeat(2, 1fr); }
            .thidua-table thead th:nth-child(n+3):nth-child(-n+7) { display: none; }
            .thidua-table tbody td:nth-child(n+3):nth-child(-n+7) { display: none; }
        }

        @media (max-width: 768px) {
            .sidebar-wrapper {
                position: fixed;
                left: -280px;
                top: 0;
                width: 260px;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
            }

            .sidebar-wrapper.open { left: 0; }

            .sidebar-overlay.show {
                display: block;
                z-index: 999;
            }

            .hamburger-btn { display: block; }

            .content-header {
                padding: 12px 16px;
                gap: 10px;
            }

            .header-filters {
                margin-left: 0;
                width: 100%;
            }

            .content-body { padding: 16px; }

            .hero-grid {
                grid-template-columns: 1fr;
            }

            .hero-section-mid {
                border-left: none;
                border-right: none;
                border-top: 1px solid rgba(255,255,255,0.15);
                border-bottom: 1px solid rgba(255,255,255,0.15);
            }

            .hero-section { padding: 20px 16px; }

            .breakdown-grid {
                grid-template-columns: 1fr;
            }

            .thidua-podium {
                gap: 8px;
                min-height: 220px;
                padding: 16px 6px 0;
            }

            .td-podium-item { min-width: 90px; }
            .td-podium-class-name { font-size: 15px; }
            .td-podium-item.td-rank-1 .td-podium-class-name { font-size: 18px; }
            .td-podium-score-val { font-size: 22px; }
            .td-podium-item.td-rank-1 .td-podium-score-val { font-size: 28px; }
        }

        @media (max-width: 480px) {
            .hero-rank-num { font-size: 2.4rem; }
            .score-circle-wrap { width: 100px; height: 100px; }
            .score-circle-wrap svg { width: 100px; height: 100px; }
            .score-circle-val { font-size: 1.4rem; }

            .filter-select { font-size: 0.8rem; padding: 6px 28px 6px 10px; }
            .view-toggle a { font-size: 0.78rem; padding: 5px 12px; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <aside class="sidebar-wrapper" id="sidebarWrapper">
            <?php include '../includes/sidebar.php'; ?>
        </aside>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <!-- HEADER -->
            <header class="content-header">
                <button class="hamburger-btn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="header-title">
                    <i class="fas fa-trophy"></i>
                    <?php echo PAGE_TITLE; ?>
                </div>

                <div class="header-filters">
                    <form method="GET" action="" id="filterForm" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <select name="tuan" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">-- Chon tuan --</option>
                            <?php foreach ($danhSachTuan as $tuan): ?>
                                <option value="<?php echo $tuan['id']; ?>"
                                    <?php echo $tuan_id == $tuan['id'] ? 'selected' : ''; ?>>
                                    Tuan <?php echo $tuan['so_tuan']; ?> (<?php echo date('d/m', strtotime($tuan['ngay_bat_dau'])); ?> - <?php echo date('d/m', strtotime($tuan['ngay_ket_thuc'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                    </form>

                    <div class="view-toggle">
                        <a href="?tuan=<?php echo $tuan_id; ?>&view=my_class"
                           class="<?php echo $view_mode === 'my_class' ? 'active' : ''; ?>">
                            <i class="fas fa-home" style="margin-right:4px;"></i>Lop minh
                        </a>
                        <a href="?tuan=<?php echo $tuan_id; ?>&view=all_classes"
                           class="<?php echo $view_mode === 'all_classes' ? 'active' : ''; ?>">
                            <i class="fas fa-globe" style="margin-right:4px;"></i>Toan khoi
                        </a>
                    </div>
                </div>
            </header>

            <!-- CONTENT BODY -->
            <div class="content-body">

                <?php if ($tuanInfo && $xepHangLopMinh): ?>
                    <?php
                        // Xep loai
                        $xepLoaiKey = $xepHangLopMinh['xep_loai'] ? $xepHangLopMinh['xep_loai'] : 'trung_binh';
                        $xepLoaiText = isset($xepLoaiMap[$xepLoaiKey]) ? $xepLoaiMap[$xepLoaiKey]['text'] : '';
                        $xepLoaiIcon = isset($xepLoaiMap[$xepLoaiKey]) ? $xepLoaiMap[$xepLoaiKey]['icon'] : '';

                        // Medal
                        $medal = '';
                        $rank = intval($xepHangLopMinh['thu_hang_toan_truong']);
                        if ($rank == 1) $medal = '&#129351;';
                        elseif ($rank == 2) $medal = '&#129352;';
                        elseif ($rank == 3) $medal = '&#129353;';

                        $totalScore = floatval($xepHangLopMinh['tong_diem_co_trong_so']);
                        $scorePercent = min($totalScore, 100);
                    ?>

                    <!-- ============ HERO CARD ============ -->
                    <div class="hero-card">
                        <div class="hero-grid">
                            <!-- Rank -->
                            <div class="hero-section">
                                <?php if ($medal): ?>
                                    <div class="hero-medal"><?php echo $medal; ?></div>
                                <?php endif; ?>
                                <div class="hero-rank-num">#<?php echo $rank; ?></div>
                                <div class="hero-rank-label">Hang toan truong</div>
                                <?php if ($xepHangLopMinh['thu_hang_cung_khoi']): ?>
                                    <div class="hero-rank-label" style="opacity:0.6;">
                                        #<?php echo intval($xepHangLopMinh['thu_hang_cung_khoi']); ?> cung khoi
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Score Circle -->
                            <div class="hero-section hero-section-mid">
                                <div class="score-circle-wrap">
                                    <svg viewBox="0 0 120 120">
                                        <circle class="score-circle-bg" cx="60" cy="60" r="54" />
                                        <circle class="score-circle-fill" id="scoreCircle" cx="60" cy="60" r="54"
                                                data-percent="<?php echo $scorePercent; ?>" />
                                    </svg>
                                    <div class="score-circle-text">
                                        <div class="score-circle-val"><?php echo number_format($totalScore, 1); ?></div>
                                        <div class="score-circle-max">/ 100</div>
                                    </div>
                                </div>
                                <div style="font-size:0.82rem;opacity:0.8;">Tong diem tuan</div>
                            </div>

                            <!-- Classification -->
                            <div class="hero-section">
                                <div class="hero-class-name"><?php echo htmlspecialchars($lopInfo['ten_lop']); ?></div>
                                <div style="font-size:1.3rem;"><?php echo $xepLoaiIcon; ?></div>
                                <div class="hero-xep-loai-badge hero-badge-<?php echo $xepLoaiKey; ?>">
                                    <?php echo $xepLoaiText; ?>
                                </div>
                                <div class="hero-week-label">
                                    Tuan <?php echo $tuanInfo['so_tuan']; ?>
                                    (<?php echo date('d/m', strtotime($tuanInfo['ngay_bat_dau'])); ?> - <?php echo date('d/m', strtotime($tuanInfo['ngay_ket_thuc'])); ?>)
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ SCORE BREAKDOWN ============ -->
                    <?php if (count($chiTietDiem) > 0): ?>
                        <div class="section-title">
                            <i class="fas fa-chart-bar"></i>
                            Chi tiet diem theo tieu chi
                        </div>

                        <div class="breakdown-grid">
                            <?php foreach ($chiTietDiem as $diem):
                                $maTc = $diem['ma_tieu_chi'];
                                $icon = isset($criteriaIcons[$maTc]) ? $criteriaIcons[$maTc] : 'fa-star';
                                $color = isset($criteriaColors[$maTc]) ? $criteriaColors[$maTc] : '#4F46E5';
                                $diemTho = floatval($diem['diem']);
                                $diemMax = floatval($diem['diem_toi_da']);
                                $trongSo = intval($diem['trong_so']);
                                $diemCoTrongSo = round(($diemTho / $diemMax) * $trongSo, 2);
                                $phanTram = ($diemMax > 0) ? ($diemTho / $diemMax) * 100 : 0;
                            ?>
                                <div class="breakdown-card" data-tc="<?php echo $maTc; ?>">
                                    <div class="breakdown-icon" style="background: <?php echo $color; ?>;">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="breakdown-name"><?php echo htmlspecialchars($diem['ten_tieu_chi']); ?></div>
                                    <div class="breakdown-weight">Trong so: <?php echo $trongSo; ?>%</div>
                                    <div class="breakdown-score" style="color: <?php echo $color; ?>;">
                                        <?php echo number_format($diemTho, 1); ?>
                                    </div>
                                    <div class="breakdown-score-max">/ <?php echo number_format($diemMax, 0); ?> diem</div>
                                    <div class="breakdown-bar">
                                        <div class="breakdown-bar-fill" style="width:<?php echo $phanTram; ?>%; background:<?php echo $color; ?>;"></div>
                                    </div>
                                    <div class="breakdown-weighted">
                                        <span>Co trong so:</span>
                                        <strong style="color:<?php echo $color; ?>;"><?php echo number_format($diemCoTrongSo, 1); ?> / <?php echo $trongSo; ?></strong>
                                    </div>
                                    <?php if (!empty($diem['ghi_chu'])): ?>
                                        <div class="breakdown-note">
                                            <i class="fas fa-comment-dots"></i>
                                            <?php echo nl2br(htmlspecialchars($diem['ghi_chu'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- ============ ALL CLASSES VIEW ============ -->
                    <?php if ($view_mode === 'all_classes' && count($allRankings) > 0): ?>

                        <?php
                            // Prepare podium data (top 3)
                            $podiumData = array();
                            foreach ($allRankings as $r) {
                                $rk = intval($r['thu_hang_cung_khoi']);
                                if ($rk >= 1 && $rk <= 3) {
                                    $podiumData[$rk] = $r;
                                }
                            }
                        ?>

                        <?php if (count($podiumData) >= 2): ?>
                            <!-- PODIUM -->
                            <div class="section-title">
                                <i class="fas fa-medal"></i>
                                Top 3 Khoi <?php echo htmlspecialchars($lopInfo['khoi']); ?>
                            </div>

                            <div class="thidua-podium">
                                <?php
                                // Display order: rank 2, rank 1, rank 3
                                $podiumOrder = array(2, 1, 3);
                                $medals = array(1 => '&#129351;', 2 => '&#129352;', 3 => '&#129353;');
                                foreach ($podiumOrder as $podRank):
                                    if (!isset($podiumData[$podRank])) continue;
                                    $pd = $podiumData[$podRank];
                                    $pdXl = $pd['xep_loai'] ? $pd['xep_loai'] : 'trung_binh';
                                    $pdXlText = isset($xepLoaiMap[$pdXl]) ? $xepLoaiMap[$pdXl]['text'] : '';
                                ?>
                                    <div class="td-podium-item td-rank-<?php echo $podRank; ?>">
                                        <div class="td-podium-medal"><?php echo $medals[$podRank]; ?></div>
                                        <div class="td-podium-class-name"><?php echo htmlspecialchars($pd['ten_lop']); ?></div>
                                        <div class="td-podium-score-val"><?php echo number_format(floatval($pd['tong_diem_co_trong_so']), 1); ?></div>
                                        <div class="td-podium-score-label">/ 100 diem</div>
                                        <div class="td-podium-badge td-badge-<?php echo $pdXl; ?>"><?php echo $pdXlText; ?></div>
                                        <div class="td-podium-stand"><?php echo $podRank; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- RANKING TABLE -->
                        <div class="section-title">
                            <i class="fas fa-list-ol"></i>
                            Bang xep hang Khoi <?php echo htmlspecialchars($lopInfo['khoi']); ?>
                        </div>

                        <div class="table-wrapper">
                            <div style="overflow-x:auto;">
                                <table class="thidua-table">
                                    <thead>
                                        <tr>
                                            <th>Hang</th>
                                            <th>Lop</th>
                                            <th>Hoc tap<br><small style="font-weight:500;opacity:0.6;">/ 40</small></th>
                                            <th>Ne nep<br><small style="font-weight:500;opacity:0.6;">/ 25</small></th>
                                            <th>Ve sinh<br><small style="font-weight:500;opacity:0.6;">/ 15</small></th>
                                            <th>Hoat dong<br><small style="font-weight:500;opacity:0.6;">/ 15</small></th>
                                            <th>Doan ket<br><small style="font-weight:500;opacity:0.6;">/ 5</small></th>
                                            <th>Tong</th>
                                            <th>Xep loai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allRankings as $item):
                                            $rk = intval($item['thu_hang_cung_khoi']);
                                            $isMyClass = ($item['lop_id'] == $lop_id);
                                            $rowClass = '';
                                            if ($isMyClass) $rowClass = 'td-my-class';
                                            elseif ($rk == 1) $rowClass = 'td-row-1';
                                            elseif ($rk == 2) $rowClass = 'td-row-2';
                                            elseif ($rk == 3) $rowClass = 'td-row-3';

                                            $itemXl = $item['xep_loai'] ? $item['xep_loai'] : 'trung_binh';
                                            $itemXlText = isset($xepLoaiMap[$itemXl]) ? $xepLoaiMap[$itemXl]['text'] : '';

                                            // Criterion scores
                                            $dHocTap = floatval($item['diem_hoc_tap']);
                                            $dNeNep = floatval($item['diem_ne_nep']);
                                            $dVeSinh = floatval($item['diem_ve_sinh']);
                                            $dHoatDong = floatval($item['diem_hoat_dong']);
                                            $dDoanKet = floatval($item['diem_doan_ket']);
                                        ?>
                                            <tr class="<?php echo $rowClass; ?>">
                                                <td>
                                                    <?php if ($rk == 1): ?>
                                                        <span class="td-rank-medal">&#129351;</span>
                                                    <?php elseif ($rk == 2): ?>
                                                        <span class="td-rank-medal">&#129352;</span>
                                                    <?php elseif ($rk == 3): ?>
                                                        <span class="td-rank-medal">&#129353;</span>
                                                    <?php else: ?>
                                                        <span class="td-rank-num"><?php echo $rk; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($item['ten_lop']); ?></strong>
                                                    <?php if ($isMyClass): ?>
                                                        <span class="td-my-badge">Lop ban</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="td-criterion-cell">
                                                        <div class="td-criterion-score"><?php echo number_format($dHocTap, 1); ?></div>
                                                        <div class="td-criterion-bar">
                                                            <div class="td-criterion-bar-fill td-bar-hoc_tap" style="width:<?php echo min(($dHocTap/40)*100, 100); ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="td-criterion-cell">
                                                        <div class="td-criterion-score"><?php echo number_format($dNeNep, 1); ?></div>
                                                        <div class="td-criterion-bar">
                                                            <div class="td-criterion-bar-fill td-bar-ne_nep" style="width:<?php echo min(($dNeNep/25)*100, 100); ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="td-criterion-cell">
                                                        <div class="td-criterion-score"><?php echo number_format($dVeSinh, 1); ?></div>
                                                        <div class="td-criterion-bar">
                                                            <div class="td-criterion-bar-fill td-bar-ve_sinh" style="width:<?php echo min(($dVeSinh/15)*100, 100); ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="td-criterion-cell">
                                                        <div class="td-criterion-score"><?php echo number_format($dHoatDong, 1); ?></div>
                                                        <div class="td-criterion-bar">
                                                            <div class="td-criterion-bar-fill td-bar-hoat_dong" style="width:<?php echo min(($dHoatDong/15)*100, 100); ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="td-criterion-cell">
                                                        <div class="td-criterion-score"><?php echo number_format($dDoanKet, 1); ?></div>
                                                        <div class="td-criterion-bar">
                                                            <div class="td-criterion-bar-fill td-bar-doan_ket" style="width:<?php echo min(($dDoanKet/5)*100, 100); ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="td-total-score"><?php echo number_format(floatval($item['tong_diem_co_trong_so']), 1); ?></div>
                                                </td>
                                                <td>
                                                    <span class="td-xep-loai-badge td-xl-<?php echo $itemXl; ?>">
                                                        <?php echo $itemXlText; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php endif; ?>

                <?php elseif ($tuanInfo): ?>
                    <!-- No ranking data yet -->
                    <div class="alert-card alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Chua co ket qua</h3>
                        <p>Chua co ket qua xep hang cho tuan nay. Vui long cho giao vien duyet diem.</p>
                    </div>

                <?php else: ?>
                    <!-- No week selected -->
                    <div class="alert-card alert-info">
                        <i class="fas fa-calendar-check"></i>
                        <h3>Chon tuan</h3>
                        <p>Vui long chon tuan de xem ket qua xep hang lop.</p>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <script>
    // ============================================
    // SIDEBAR TOGGLE (Mobile)
    // ============================================
    function toggleSidebar() {
        document.getElementById('sidebarWrapper').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }

    function closeSidebar() {
        document.getElementById('sidebarWrapper').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
    }

    // ============================================
    // SCORE CIRCLE ANIMATION
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        var circle = document.getElementById('scoreCircle');
        if (circle) {
            var pct = parseFloat(circle.getAttribute('data-percent')) || 0;
            var circumference = 2 * Math.PI * 54; // r=54
            var offset = circumference - (pct / 100) * circumference;
            setTimeout(function() {
                circle.style.strokeDashoffset = offset;
            }, 100);
        }
    });
    </script>
</body>
</html>
