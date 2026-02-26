<?php
/**
 * ==============================================
 * DASHBOARD HỌC SINH - FULLSCREEN DESKTOP APP
 * Giao diện không scroll - phân trang thông minh
 * Thiết kế cho học sinh tiểu học
 * ==============================================
 */

require_once '../includes/config.php';
require_once '../includes/device.php';
require_once '../includes/week_helper.php';

// Redirect sang mobile nếu là thiết bị di động
redirectIfMobile(BASE_URL . '/student/mobile/index.php');

if (!isStudentLoggedIn()) {
    redirect('login.php');
}

$student = getCurrentStudent();
if (!$student) {
    redirect('logout.php');
}

$conn = getDBConnection();

// Lấy điểm tích lũy
$stmtDTL = $conn->prepare("SELECT * FROM diem_tich_luy WHERE hoc_sinh_id = ?");
$stmtDTL->execute(array($student['id']));
$diemTichLuy = $stmtDTL->fetch();

// Lấy tuần hiện tại
$currentWeek = getCurrentWeek();

// Lấy TẤT CẢ đề thi theo lớp (để phân trang JS)
// Sắp xếp theo thu_tu (admin tùy chỉnh), ưu tiên đề chính thức
$stmtDT = $conn->prepare("
    SELECT dt.*, mh.ten_mon, mh.mau_sac
    FROM de_thi dt
    JOIN mon_hoc mh ON dt.mon_hoc_id = mh.id
    WHERE dt.lop_id = ? AND dt.trang_thai = 1
    ORDER BY dt.is_chinh_thuc DESC, dt.thu_tu ASC, dt.created_at DESC
");
$stmtDT->execute(array($student['lop_id']));
$deThiList = $stmtDT->fetchAll();

// Lấy số lần thi trong tuần cho mỗi đề (cả luyện tập và chính thức)
$examAttempts = array();
$officialAttempts = array(); // Số lần thi chính thức
if ($currentWeek) {
    // Lấy tổng số lần thi
    $stmtAttempts = $conn->prepare("
        SELECT de_thi_id, so_lan_thi, diem_cao_nhat
        FROM ket_qua_tuan
        WHERE hoc_sinh_id = ? AND tuan_id = ?
    ");
    $stmtAttempts->execute(array($student['id'], $currentWeek['id']));
    $attemptsList = $stmtAttempts->fetchAll();
    foreach ($attemptsList as $att) {
        $examAttempts[$att['de_thi_id']] = array(
            'so_lan_thi' => $att['so_lan_thi'],
            'diem_cao_nhat' => $att['diem_cao_nhat']
        );
    }

    // Lấy số lần thi chính thức (is_chinh_thuc = 1)
    $stmtOfficial = $conn->prepare("
        SELECT de_thi_id, COUNT(*) as so_lan_chinh_thuc, MAX(diem) as diem_cao_nhat_chinh_thuc
        FROM bai_lam bl
        JOIN de_thi dt ON bl.de_thi_id = dt.id
        WHERE bl.hoc_sinh_id = ?
        AND bl.tuan_id = ?
        AND bl.is_chinh_thuc = 1
        AND bl.trang_thai = 'hoan_thanh'
        GROUP BY de_thi_id
    ");
    $stmtOfficial->execute(array($student['id'], $currentWeek['id']));
    $officialList = $stmtOfficial->fetchAll();
    foreach ($officialList as $off) {
        $officialAttempts[$off['de_thi_id']] = array(
            'so_lan' => $off['so_lan_chinh_thuc'],
            'diem_cao_nhat' => $off['diem_cao_nhat_chinh_thuc']
        );
    }
}

// Thêm thông tin số lần thi và kiểm tra thời gian mở vào mỗi đề
foreach ($deThiList as $key => $dt) {
    // Số lần thi trong tuần (luyện tập)
    if (isset($examAttempts[$dt['id']])) {
        $deThiList[$key]['so_lan_thi_tuan'] = $examAttempts[$dt['id']]['so_lan_thi'];
        $deThiList[$key]['diem_cao_nhat_tuan'] = $examAttempts[$dt['id']]['diem_cao_nhat'];
    } else {
        $deThiList[$key]['so_lan_thi_tuan'] = 0;
        $deThiList[$key]['diem_cao_nhat_tuan'] = null;
    }

    // Số lần thi chính thức
    if (isset($officialAttempts[$dt['id']])) {
        $deThiList[$key]['so_lan_thi_chinh_thuc'] = $officialAttempts[$dt['id']]['so_lan'];
        $deThiList[$key]['diem_cao_nhat_chinh_thuc'] = $officialAttempts[$dt['id']]['diem_cao_nhat'];
    } else {
        $deThiList[$key]['so_lan_thi_chinh_thuc'] = 0;
        $deThiList[$key]['diem_cao_nhat_chinh_thuc'] = null;
    }

    // Kiểm tra thời gian mở
    $deThiList[$key]['is_open'] = isExamOpen($dt);

    // Thêm thông tin is_chinh_thuc và so_lan_thi_toi_da_tuan
    $deThiList[$key]['is_chinh_thuc'] = isset($dt['is_chinh_thuc']) ? (int)$dt['is_chinh_thuc'] : 0;
    $deThiList[$key]['so_lan_thi_toi_da_tuan'] = isset($dt['so_lan_thi_toi_da_tuan']) ? (int)$dt['so_lan_thi_toi_da_tuan'] : 3;
}

// Lấy TẤT CẢ tài liệu
$stmtTL = $conn->prepare("
    SELECT tl.*, mh.ten_mon, mh.mau_sac
    FROM tai_lieu tl
    JOIN mon_hoc mh ON tl.mon_hoc_id = mh.id
    WHERE (tl.lop_id = ? OR tl.lop_id IS NULL) AND tl.trang_thai = 1
    ORDER BY tl.created_at DESC
");
$stmtTL->execute(array($student['lop_id']));
$taiLieuList = $stmtTL->fetchAll();

// Lấy TẤT CẢ lịch sử thi
$stmtLS = $conn->prepare("
    SELECT bl.*, dt.ten_de, mh.ten_mon
    FROM bai_lam bl
    JOIN de_thi dt ON bl.de_thi_id = dt.id
    JOIN mon_hoc mh ON dt.mon_hoc_id = mh.id
    WHERE bl.hoc_sinh_id = ? AND bl.trang_thai = 'hoan_thanh'
    ORDER BY bl.thoi_gian_ket_thuc DESC
");
$stmtLS->execute(array($student['id']));
$lichSuThi = $stmtLS->fetchAll();

// Chuyển data sang JSON cho JS
$jsData = array(
    'student' => array(
        'id' => $student['id'],
        'ho_ten' => $student['ho_ten'],
        'ma_hs' => $student['ma_hs'],
        'ten_lop' => $student['ten_lop'],
        'khoi' => $student['khoi'],
        'chuoi_ngay_hoc' => $student['chuoi_ngay_hoc'],
        'initial' => mb_substr($student['ho_ten'], 0, 1, 'UTF-8')
    ),
    'stats' => array(
        'diem_tb' => $diemTichLuy ? number_format($diemTichLuy['diem_trung_binh'], 1) : '0.0',
        'so_bai' => $diemTichLuy ? $diemTichLuy['tong_lan_thi'] : 0,
        'diem_xh' => $diemTichLuy ? round($diemTichLuy['diem_xep_hang']) : 0
    ),
    'exams' => $deThiList,
    'documents' => $taiLieuList,
    'history' => $lichSuThi,
    'baseUrl' => BASE_URL
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#4F46E5">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            background: #F0F2F5;
        }

        /* ========== LAYOUT CHÍNH ========== */
        .app-container {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* ========== SIDEBAR CỐ ĐỊNH ========== */
        .sidebar {
            width: 260px;
            min-width: 260px;
            background: linear-gradient(180deg, #4F46E5 0%, #7C3AED 100%);
            display: flex;
            flex-direction: column;
            color: white;
            height: 100vh;
            position: relative;
        }

        .sidebar-header {
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }

        .user-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255,255,255,0.25);
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            border: 3px solid rgba(255,255,255,0.4);
        }

        .user-name {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .user-class {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .streak-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 15px;
            margin-top: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Menu Navigation */
        .sidebar-nav {
            flex: 1;
            padding: 15px 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
            font-weight: 600;
            color: rgba(255,255,255,0.85);
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
        }

        .nav-btn:hover {
            background: rgba(255,255,255,0.15);
            color: white;
        }

        .nav-btn.active {
            background: rgba(255,255,255,0.25);
            color: white;
        }

        .nav-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .nav-btn.active .nav-icon {
            background: white;
        }

        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid rgba(255,255,255,0.15);
        }

        .logout-btn {
            background: rgba(239, 68, 68, 0.25) !important;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.4) !important;
        }

        .author-credit {
            padding: 2px 12px;
            text-align: center;
            font-size: 0.65rem;
            color: rgba(255,255,255,0.45);
            line-height: 1.4;
        }

        .author-credit strong {
            color: rgba(255,255,255,0.6);
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        /* Header */
        .content-header {
            background: white;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            flex-shrink: 0;
        }

        .page-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1F2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title-icon {
            font-size: 1.6rem;
        }

        .header-date {
            color: #6B7280;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .week-badge {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            animation: weekPulse 2s ease-in-out infinite;
        }

        @keyframes weekPulse {
            0%, 100% { box-shadow: 0 2px 10px rgba(79, 70, 229, 0.3); }
            50% { box-shadow: 0 4px 20px rgba(79, 70, 229, 0.5); }
        }

        /* Content Area */
        .content-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px 24px;
            overflow: hidden;
        }

        /* ========== PAGINATION BAR ========== */
        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            padding: 12px 20px;
            border-radius: 14px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            flex-shrink: 0;
        }

        .page-info {
            font-weight: 600;
            color: #4B5563;
        }

        .page-info strong {
            color: #4F46E5;
        }

        .pagination-btns {
            display: flex;
            gap: 10px;
        }

        .page-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .page-btn.prev {
            background: #E5E7EB;
            color: #374151;
        }

        .page-btn.prev:hover:not(:disabled) {
            background: #D1D5DB;
        }

        .page-btn.next {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
        }

        .page-btn.next:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }

        .page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none !important;
        }

        /* ========== CONTENT GRID ========== */
        .content-grid {
            flex: 1;
            display: grid;
            gap: 16px;
            overflow: hidden;
        }

        /* ========== TRANG CHỦ ========== */
        .home-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-label {
            color: #6B7280;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .home-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            flex: 1;
        }

        .action-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            cursor: pointer;
            transition: all 0.25s;
            border: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #4F46E5;
        }

        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .action-info h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 4px;
        }

        .action-info p {
            color: #6B7280;
            font-size: 0.9rem;
        }

        /* ========== CARDS CHUNG ========== */
        .item-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.25s;
            border: 3px solid transparent;
            display: flex;
            flex-direction: column;
            max-height: 25vh;
        }

        .item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #4F46E5;
        }

        .item-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .item-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .item-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1F2937;
            line-height: 1.3;
        }

        .item-subtitle {
            font-size: 0.85rem;
            color: #6B7280;
            margin-top: 2px;
        }

        .item-meta {
            display: flex;
            gap: 12px;
            padding: 10px;
            background: #F9FAFB;
            border-radius: 10px;
            margin-bottom: 12px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            color: #4B5563;
            font-weight: 600;
        }

        .item-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
            font-family: inherit;
            margin-top: auto;
        }

        .item-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }

        .item-btn.doc-btn {
            background: linear-gradient(135deg, #0D9488 0%, #0F766E 100%);
        }

        .item-btn.doc-btn:hover {
            box-shadow: 0 4px 15px rgba(78, 205, 196, 0.4);
        }

        /* ========== LỊCH SỬ ========== */
        .history-card {
            background: white;
            border-radius: 16px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .history-score {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }

        .history-info {
            flex: 1;
        }

        .history-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 3px;
        }

        .history-meta {
            color: #6B7280;
            font-size: 0.85rem;
        }

        .history-result {
            text-align: right;
        }

        .history-correct {
            font-size: 1.1rem;
            font-weight: 700;
            color: #10B981;
        }

        .history-date {
            font-size: 0.8rem;
            color: #9CA3AF;
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #9CA3AF;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 16px;
        }

        .empty-text {
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* ========== DOCUMENT VIEWER MODAL ========== */
        .doc-viewer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
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

        .doc-viewer-title-icon {
            font-size: 1.4rem;
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

        .doc-viewer-btn.download-btn {
            background: linear-gradient(135deg, #0D9488 0%, #0F766E 100%);
            color: white;
        }

        .doc-viewer-btn.download-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(78, 205, 196, 0.4);
        }

        .doc-viewer-btn.close-btn {
            background: rgba(255,255,255,0.1);
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
            border: 4px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
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

        .doc-viewer-error-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .doc-viewer-error-btn:hover {
            transform: scale(1.05);
        }

        /* ========== BADGE ========== */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* ========== LOADING ========== */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .hidden { display: none !important; }

        /* ========== OFFICIAL EXAM HIGHLIGHT ========== */
        .official-exam {
            position: relative;
            border: 3px solid #FFD700 !important;
            background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%) !important;
            animation: officialGlow 2s ease-in-out infinite;
        }

        .official-exam::before {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            border-radius: 18px;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700, #FFA500);
            background-size: 400% 400%;
            animation: gradientBorder 3s ease infinite;
            z-index: -1;
        }

        @keyframes officialGlow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(255, 215, 0, 0.4), 0 0 40px rgba(255, 165, 0, 0.2);
            }
            50% {
                box-shadow: 0 0 30px rgba(255, 215, 0, 0.6), 0 0 60px rgba(255, 165, 0, 0.4);
            }
        }

        @keyframes gradientBorder {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .official-badge {
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #7C2D12;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 165, 0, 0.4);
            animation: badgePulse 1.5s ease-in-out infinite;
            z-index: 10;
            white-space: nowrap;
        }

        @keyframes badgePulse {
            0%, 100% { transform: translateX(-50%) scale(1); }
            50% { transform: translateX(-50%) scale(1.05); }
        }

        .practice-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
            z-index: 10;
            white-space: nowrap;
        }

        .attempt-counter {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 12px;
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: white;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .attempt-counter.available {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        }

        .attempt-counter .count {
            font-size: 1.1rem;
        }

        .official-score {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #7C2D12;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-top: 8px;
            text-align: center;
        }

        /* Avatar Dropdown Menu */
        .avatar-wrapper {
            position: relative;
            cursor: pointer;
        }
        .avatar-dropdown {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            padding: 8px;
            min-width: 180px;
            display: none;
            z-index: 1000;
            margin-top: 10px;
        }
        .avatar-dropdown.show {
            display: block;
        }
        .avatar-dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .avatar-dropdown-item:hover {
            background: #F3F4F6;
        }
        .avatar-dropdown-item.danger {
            color: #EF4444;
        }

        /* Change Password Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        .modal-overlay.show {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 20px;
            text-align: center;
        }
        .modal-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            margin-bottom: 16px;
            transition: border-color 0.2s;
        }
        .modal-input:focus {
            outline: none;
            border-color: #4F46E5;
        }
        .modal-btns {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .modal-btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
        }
        .modal-btn.primary {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
        }
        .modal-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }
        .modal-btn.secondary {
            background: #E5E7EB;
            color: #374151;
        }
        .modal-btn.secondary:hover {
            background: #D1D5DB;
        }
        .modal-message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-weight: 600;
            text-align: center;
        }
        .modal-message.success {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }
        .modal-message.error {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }

        /* ========== RESPONSIVE CHO MÀN HÌNH NHỎ ========== */
        /* Laptop nhỏ (1366px và thấp hơn) */
        @media (max-width: 1366px) {
            .sidebar {
                width: 220px;
                min-width: 220px;
            }
            .sidebar-header {
                padding: 6px;
            }
            .user-avatar {
                width: 55px;
                height: 55px;
                font-size: 1.5rem;
            }
            .user-name {
                font-size: 0.9rem;
            }
            .user-class {
                font-size: 0.75rem;
            }
            .streak-badge {
                padding: 4px 10px;
                font-size: 0.75rem;
            }
            .sidebar-nav {
                padding: 10px 8px;
                gap: 4px;
            }
            .nav-btn {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
            .nav-icon {
                width: 36px;
                height: 36px;
                font-size: 1.1rem;
            }
            .content-header {
                padding: 12px 16px;
            }
            .page-title {
                font-size: 1.2rem;
            }
            .content-body {
                padding: 14px 16px;
            }
            .home-stats {
                gap: 12px;
                margin-bottom: 14px;
            }
            .stat-card {
                padding: 14px;
            }
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            .stat-value {
                font-size: 1.6rem;
            }
            .stat-label {
                font-size: 0.8rem;
            }
            .action-card {
                padding: 16px;
                gap: 12px;
            }
            .action-icon {
                width: 48px;
                height: 48px;
                font-size: 1.4rem;
            }
            .action-info h3 {
                font-size: 1rem;
            }
            .action-info p {
                font-size: 0.8rem;
            }
            .item-card {
                padding: 14px;
            }
            .item-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            .item-title {
                font-size: 0.9rem;
            }
            .item-subtitle {
                font-size: 0.75rem;
            }
            .item-meta {
                padding: 8px;
                gap: 10px;
            }
            .meta-item {
                font-size: 0.75rem;
            }
            .item-btn {
                padding: 10px;
                font-size: 0.85rem;
            }
            .pagination-bar {
                padding: 10px 14px;
            }
            .page-btn {
                padding: 10px 18px;
                font-size: 0.9rem;
            }
        }

        /* Màn hình rất nhỏ (1280px và thấp hơn) */
        @media (max-width: 1280px) {
            .sidebar {
                width: 200px;
                min-width: 200px;
            }
            .user-avatar {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
            .nav-btn {
                padding: 5px 10px;
                font-size: 0.85rem;
            }
            .nav-icon {
                width: 32px;
                height: 32px;
                font-size: 1rem;
            }
            .content-body {
                padding: 12px 14px;
            }
            .stat-value {
                font-size: 1.4rem;
            }
            .action-icon {
                width: 42px;
                height: 42px;
                font-size: 1.2rem;
            }
            .action-info h3 {
                font-size: 0.95rem;
            }
        }

        /* Màn hình chiều cao thấp (laptop) */
        @media (max-height: 768px) {
            .sidebar-header {
                padding: 5px;
            }
            .user-avatar {
                width: 50px;
                height: 50px;
                margin-bottom: 6px;
            }
            .streak-badge {
                margin-top: 5px;
                padding: 3px 8px;
            }
            .sidebar-nav {
                padding: 8px;
                gap: 3px;
            }
            .nav-btn {
                padding: 5px 10px;
            }
            .nav-icon {
                width: 32px;
                height: 32px;
            }
            .sidebar-footer {
                padding: 8px;
            }
            .author-credit {
                font-size: 0.6rem;
                padding: 2px 8px;
            }
            .content-header {
                padding: 10px 14px;
            }
            .page-title {
                font-size: 1.1rem;
            }
            .content-body {
                padding: 10px 14px;
            }
            .home-stats {
                gap: 10px;
                margin-bottom: 10px;
            }
            .stat-card {
                padding: 10px;
            }
            .stat-icon {
                width: 35px;
                height: 35px;
                margin-bottom: 6px;
            }
            .stat-value {
                font-size: 1.3rem;
            }
            .home-actions {
                gap: 10px;
            }
            .action-card {
                padding: 12px;
            }
            .item-card {
                padding: 10px;
                max-height: 22vh;
            }
            .item-header {
                margin-bottom: 8px;
            }
            .item-meta {
                padding: 6px;
                margin-bottom: 8px;
            }
            .item-btn {
                padding: 8px;
                font-size: 0.8rem;
            }
            .pagination-bar {
                padding: 8px 12px;
                margin-bottom: 10px;
            }
            .page-btn {
                padding: 8px 14px;
                font-size: 0.85rem;
            }
            .history-card {
                padding: 12px 14px;
            }
            .history-score {
                width: 50px;
                height: 50px;
                font-size: 1.1rem;
            }
        }

        /* Màn hình chiều cao rất thấp */
        @media (max-height: 680px) {
            .user-avatar {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
            .user-name {
                font-size: 0.85rem;
            }
            .nav-icon {
                width: 28px;
                height: 28px;
                font-size: 0.9rem;
            }
            .nav-btn {
                padding: 4px 8px;
                font-size: 0.8rem;
                gap: 8px;
            }
            .stat-value {
                font-size: 1.2rem;
            }
            .stat-label {
                font-size: 0.7rem;
            }
            .action-icon {
                width: 36px;
                height: 36px;
                font-size: 1.1rem;
            }
            .action-info h3 {
                font-size: 0.9rem;
            }
            .action-info p {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-spinner"></div>
        <div class="loading-text">Đang tải...</div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal-overlay" id="changePasswordModal">
        <div class="modal-content">
            <h3 class="modal-title">🔐 Đổi mật khẩu</h3>
            <div id="passwordMessage" class="modal-message" style="display: none;"></div>
            <input type="password" class="modal-input" id="currentPassword" placeholder="Mật khẩu hiện tại">
            <input type="password" class="modal-input" id="newPassword" placeholder="Mật khẩu mới">
            <input type="password" class="modal-input" id="confirmPassword" placeholder="Nhập lại mật khẩu mới">
            <div class="modal-btns">
                <button class="modal-btn secondary" onclick="closeChangePasswordModal()">Hủy</button>
                <button class="modal-btn primary" onclick="changePassword()">Đổi mật khẩu</button>
            </div>
        </div>
    </div>

    <!-- Document Viewer Modal -->
    <div class="doc-viewer-overlay" id="docViewerOverlay">
        <div class="doc-viewer-header">
            <div class="doc-viewer-title">
                <span class="doc-viewer-title-icon" id="docViewerIcon">📄</span>
                <span id="docViewerTitle">Tài liệu</span>
            </div>
            <div class="doc-viewer-actions">
                <button class="doc-viewer-btn download-btn" id="docDownloadBtn" onclick="downloadDocument()">
                    <span>⬇️</span> Tải xuống
                </button>
                <button class="doc-viewer-btn close-btn" onclick="closeDocViewer()">
                    <span>✕</span> Đóng
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
        <!-- SIDEBAR CỐ ĐỊNH -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="avatar-wrapper" onclick="toggleAvatarDropdown(event)">
                    <div class="user-avatar" id="userAvatar"></div>
                    <div class="avatar-dropdown" id="avatarDropdown">
                        <div class="avatar-dropdown-item" onclick="showChangePasswordModal(event)">
                            <span>🔐</span> Đổi mật khẩu
                        </div>
                        <div class="avatar-dropdown-item danger" onclick="window.location.href='<?php echo BASE_URL; ?>/logout.php'">
                            <span>🚪</span> Đăng xuất
                        </div>
                    </div>
                </div>
                <div class="user-name" id="userName"></div>
                <div class="user-class" id="userClass"></div>
                <div class="streak-badge">
                    <span>🔥</span>
                    <span id="userStreak">0</span> ngày liên tục
                </div>
            </div>

            <nav class="sidebar-nav">
                <button class="nav-btn active" data-page="home">
                    <div class="nav-icon">🏠</div>
                    <span>Trang chủ</span>
                </button>
                <button class="nav-btn" data-page="exams">
                    <div class="nav-icon">📝</div>
                    <span>Làm bài thi</span>
                </button>
                <button class="nav-btn" data-page="documents">
                    <div class="nav-icon">📚</div>
                    <span>Tài liệu</span>
                </button>
                <button class="nav-btn" data-page="history">
                    <div class="nav-icon">📊</div>
                    <span>Lịch sử thi</span>
                </button>
                <button class="nav-btn" onclick="window.location.href='<?php echo BASE_URL; ?>/student/ranking.php'">
                    <div class="nav-icon">🏆</div>
                    <span>Xếp hạng</span>
                </button>
                <button class="nav-btn" onclick="window.location.href='<?php echo BASE_URL; ?>/student/thidua/xep_hang.php'">
                    <div class="nav-icon">🏅</div>
                    <span>Thi đua</span>
                </button>
                <?php if (isset($student['la_co_do']) && $student['la_co_do'] == 1): ?>
                <button class="nav-btn" onclick="window.location.href='<?php echo BASE_URL; ?>/student/thidua/cham_diem.php'" style="background: rgba(220,38,38,0.15);">
                    <div class="nav-icon" style="color:#dc2626;">🚩</div>
                    <span style="color:#dc2626;">Chấm điểm</span>
                </button>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <button class="nav-btn logout-btn" onclick="window.location.href='<?php echo BASE_URL; ?>/logout.php'">
                    <div class="nav-icon">🚪</div>
                    <span>Đăng xuất</span>
                </button>
            </div>

            <div class="author-credit">
                <strong>Tác giả:</strong><br>
                GV Đoàn Thị Ngọc Lĩnh
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="content-header">
                <h1 class="page-title">
                    <span class="page-title-icon" id="pageIcon">🏠</span>
                    <span id="pageTitle">Trang chủ</span>
                </h1>
                <div class="header-date">
                    <?php
                    $days = array('Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7');
                    echo $days[date('w')] . ', ' . date('d/m/Y');
                    if ($currentWeek) {
                        echo ' <span class="week-badge">📅 ' . htmlspecialchars($currentWeek['ten_tuan']) . '</span>';
                    }
                    ?>
                </div>
            </header>

            <div class="content-body" id="contentBody">
                <!-- Content được render bằng JS -->
            </div>
        </main>
    </div>

    <script>
    // ========== DATA TỪ PHP ==========
    var APP = <?php echo json_encode($jsData, JSON_UNESCAPED_UNICODE); ?>;

    // ========== SCREEN CONFIG ==========
    var SCREEN = {
        width: 0,
        height: 0,
        contentHeight: 0,
        contentWidth: 0,
        itemsPerPage: 4,
        columns: 2,
        rows: 2
    };

    // ========== PAGINATION STATE ==========
    var PAGE_STATE = {
        exams: { current: 1, total: 1 },
        documents: { current: 1, total: 1 },
        history: { current: 1, total: 1 }
    };

    var CURRENT_PAGE = 'home';

    // ========== FILE ICONS ==========
    var FILE_ICONS = {
        'pdf': '📄',
        'word': '📝',
        'ppt': '📊',
        'video': '🎬',
        'image': '🖼️'
    };

    // ========== KHỞI TẠO ==========
    document.addEventListener('DOMContentLoaded', function() {
        // Tính toán màn hình
        calculateScreen();

        // Hiển thị user info
        document.getElementById('userAvatar').textContent = APP.student.initial;
        document.getElementById('userName').textContent = APP.student.ho_ten;
        document.getElementById('userClass').textContent = APP.student.ten_lop + ' • ' + APP.student.ma_hs;
        document.getElementById('userStreak').textContent = APP.student.chuoi_ngay_hoc;

        // Bind menu events
        var navBtns = document.querySelectorAll('.nav-btn[data-page]');
        for (var i = 0; i < navBtns.length; i++) {
            navBtns[i].addEventListener('click', function() {
                var page = this.getAttribute('data-page');
                if (page) navigateTo(page);
            });
        }

        // Render trang chủ
        navigateTo('home');

        // Ẩn loading
        setTimeout(function() {
            document.getElementById('loadingScreen').classList.add('hidden');
        }, 300);

        // Resize listener
        window.addEventListener('resize', function() {
            calculateScreen();
            renderCurrentPage();
        });
    });

    // ========== TÍNH TOÁN MÀN HÌNH ==========
    function calculateScreen() {
        SCREEN.width = window.innerWidth;
        SCREEN.height = window.innerHeight;

        // Tính kích thước content area
        var sidebarWidth = 260;
        var headerHeight = 70;
        var padding = 40; // padding của content-body
        var paginationHeight = 60; // height của pagination bar

        SCREEN.contentWidth = SCREEN.width - sidebarWidth - padding;
        SCREEN.contentHeight = SCREEN.height - headerHeight - padding - paginationHeight;

        // Tính số item hiển thị được
        // Card height khoảng 160px cho exam/doc, 80px cho history
        var cardHeight = 160;
        var cardWidth = 300;
        var gap = 16;

        // Số cột dựa trên chiều rộng
        SCREEN.columns = Math.floor((SCREEN.contentWidth + gap) / (cardWidth + gap));
        if (SCREEN.columns < 1) SCREEN.columns = 1;
        if (SCREEN.columns > 4) SCREEN.columns = 4;

        // Số hàng dựa trên chiều cao (tối đa 2 hàng = 6 items với 3 cột)
        SCREEN.rows = Math.floor((SCREEN.contentHeight + gap) / (cardHeight + gap));
        if (SCREEN.rows < 1) SCREEN.rows = 1;
        if (SCREEN.rows > 2) SCREEN.rows = 2;

        // Giới hạn tối đa 6 items mỗi trang
        SCREEN.itemsPerPage = SCREEN.columns * SCREEN.rows;
        if (SCREEN.itemsPerPage > 6) SCREEN.itemsPerPage = 6;

        // Cập nhật tổng số trang
        updateTotalPages();
    }

    // ========== CẬP NHẬT TỔNG SỐ TRANG ==========
    function updateTotalPages() {
        PAGE_STATE.exams.total = Math.ceil(APP.exams.length / SCREEN.itemsPerPage) || 1;
        PAGE_STATE.documents.total = Math.ceil(APP.documents.length / SCREEN.itemsPerPage) || 1;

        // History dùng layout khác (1 cột)
        var historyPerPage = SCREEN.rows * 2; // 2 item per row for history
        PAGE_STATE.history.total = Math.ceil(APP.history.length / historyPerPage) || 1;

        // Reset về trang 1 nếu current > total
        if (PAGE_STATE.exams.current > PAGE_STATE.exams.total) PAGE_STATE.exams.current = 1;
        if (PAGE_STATE.documents.current > PAGE_STATE.documents.total) PAGE_STATE.documents.current = 1;
        if (PAGE_STATE.history.current > PAGE_STATE.history.total) PAGE_STATE.history.current = 1;
    }

    // ========== CHUYỂN TRANG ==========
    function navigateTo(page) {
        CURRENT_PAGE = page;

        // Update active menu
        var navBtns = document.querySelectorAll('.nav-btn');
        for (var i = 0; i < navBtns.length; i++) {
            navBtns[i].classList.remove('active');
            if (navBtns[i].getAttribute('data-page') === page) {
                navBtns[i].classList.add('active');
            }
        }

        // Update header
        var titles = {
            'home': { icon: '🏠', text: 'Trang chủ' },
            'exams': { icon: '📝', text: 'Làm bài thi' },
            'documents': { icon: '📚', text: 'Tài liệu học tập' },
            'history': { icon: '📊', text: 'Lịch sử làm bài' }
        };

        if (titles[page]) {
            document.getElementById('pageIcon').textContent = titles[page].icon;
            document.getElementById('pageTitle').textContent = titles[page].text;
        }

        renderCurrentPage();
    }

    // ========== RENDER TRANG HIỆN TẠI ==========
    function renderCurrentPage() {
        var contentBody = document.getElementById('contentBody');

        switch(CURRENT_PAGE) {
            case 'home':
                contentBody.innerHTML = renderHomePage();
                break;
            case 'exams':
                contentBody.innerHTML = renderExamsPage();
                break;
            case 'documents':
                contentBody.innerHTML = renderDocumentsPage();
                break;
            case 'history':
                contentBody.innerHTML = renderHistoryPage();
                break;
        }
    }

    // ========== RENDER TRANG CHỦ ==========
    function renderHomePage() {
        return '<div class="home-stats">' +
            '<div class="stat-card">' +
                '<div class="stat-icon" style="background: rgba(79, 70, 229, 0.15);">⭐</div>' +
                '<div class="stat-value" style="color: #4F46E5;">' + APP.stats.diem_tb + '</div>' +
                '<div class="stat-label">Điểm trung bình</div>' +
            '</div>' +
            '<div class="stat-card">' +
                '<div class="stat-icon" style="background: rgba(78, 205, 196, 0.15);">✅</div>' +
                '<div class="stat-value" style="color: #0D9488;">' + APP.stats.so_bai + '</div>' +
                '<div class="stat-label">Bài đã làm</div>' +
            '</div>' +
            '<div class="stat-card">' +
                '<div class="stat-icon" style="background: rgba(167, 139, 250, 0.15);">🏆</div>' +
                '<div class="stat-value" style="color: #A78BFA;">' + APP.stats.diem_xh + '</div>' +
                '<div class="stat-label">Điểm xếp hạng</div>' +
            '</div>' +
        '</div>' +
        '<div class="home-actions">' +
            '<div class="action-card" onclick="navigateTo(\'exams\')">' +
                '<div class="action-icon" style="background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);">📝</div>' +
                '<div class="action-info">' +
                    '<h3>Làm bài thi ngay!</h3>' +
                    '<p>' + APP.exams.length + ' đề thi đang chờ bạn</p>' +
                '</div>' +
            '</div>' +
            '<div class="action-card" onclick="navigateTo(\'documents\')">' +
                '<div class="action-icon" style="background: linear-gradient(135deg, #0D9488 0%, #0F766E 100%);">📚</div>' +
                '<div class="action-info">' +
                    '<h3>Xem tài liệu</h3>' +
                    '<p>' + APP.documents.length + ' tài liệu học tập</p>' +
                '</div>' +
            '</div>' +
            '<div class="action-card" onclick="window.location.href=\'' + APP.baseUrl + '/student/ranking.php\'">' +
                '<div class="action-icon" style="background: linear-gradient(135deg, #A78BFA 0%, #8B5CF6 100%);">🏆</div>' +
                '<div class="action-info">' +
                    '<h3>Bảng xếp hạng</h3>' +
                    '<p>Xem thứ hạng của bạn</p>' +
                '</div>' +
            '</div>' +
            '<div class="action-card" onclick="navigateTo(\'history\')">' +
                '<div class="action-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">📊</div>' +
                '<div class="action-info">' +
                    '<h3>Lịch sử làm bài</h3>' +
                    '<p>Xem kết quả các bài thi</p>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    // ========== RENDER TRANG ĐỀ THI ==========
    function renderExamsPage() {
        if (APP.exams.length === 0) {
            return '<div class="empty-state">' +
                '<div class="empty-icon">📝</div>' +
                '<div class="empty-text">Chưa có đề thi nào cho lớp của bạn</div>' +
            '</div>';
        }

        var state = PAGE_STATE.exams;
        var start = (state.current - 1) * SCREEN.itemsPerPage;
        var end = start + SCREEN.itemsPerPage;
        var items = APP.exams.slice(start, end);

        var html = renderPaginationBar('exams', state.current, state.total, APP.exams.length);

        html += '<div class="content-grid" style="grid-template-columns: repeat(' + SCREEN.columns + ', 1fr);">';

        for (var i = 0; i < items.length; i++) {
            var exam = items[i];
            var soLanThi = exam.so_lan_thi_tuan || 0;
            var diemCaoNhat = exam.diem_cao_nhat_tuan;
            var isOpen = exam.is_open;
            var isChinhThuc = exam.is_chinh_thuc || 0;
            var soLanChinhThuc = exam.so_lan_thi_chinh_thuc || 0;
            var soLanToiDa = exam.so_lan_thi_toi_da_tuan || 3;
            var diemChinhThuc = exam.diem_cao_nhat_chinh_thuc;

            // Card class - thêm hiệu ứng nếu là đề chính thức
            var cardClass = 'item-card';
            if (isChinhThuc) {
                cardClass += ' official-exam';
            }

            // Badge đề chính thức hoặc luyện thi
            var officialBadge = '';
            if (isChinhThuc) {
                officialBadge = '<div class="official-badge">⭐ BÀI THI CHÍNH THỨC ⭐</div>';
            } else {
                officialBadge = '<div class="practice-badge">📝 LUYỆN THI</div>';
            }

            // Badge số lần thi (cho đề luyện tập)
            var attemptBadge = '';
            if (!isChinhThuc && soLanThi > 0) {
                var scoreInfo = diemCaoNhat !== null ? ' - Cao nhất: ' + parseFloat(diemCaoNhat).toFixed(1) : '';
                attemptBadge = '<div style="position: absolute; top: 36px; right: 8px; background: rgba(16, 185, 129, 0.9); color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: 600;">' +
                    '🔄 Lần ' + soLanThi + scoreInfo +
                '</div>';
            }

            // Bộ đếm số lần thi còn lại (cho đề chính thức)
            var attemptCounter = '';
            if (isChinhThuc) {
                var remaining = soLanToiDa - soLanChinhThuc;
                var counterClass = remaining > 0 ? 'attempt-counter available' : 'attempt-counter';
                attemptCounter = '<div class="' + counterClass + '">' +
                    '<span>🎯</span> Số lượt thi còn lại: <span class="count">' + remaining + '/' + soLanToiDa + '</span>' +
                '</div>';

                // Hiển thị điểm cao nhất nếu đã thi
                if (soLanChinhThuc > 0 && diemChinhThuc !== null) {
                    attemptCounter += '<div class="official-score">🏆 Điểm chính thức: ' + parseFloat(diemChinhThuc).toFixed(1) + '</div>';
                }
            }

            // Nút bắt đầu hoặc thông báo đóng
            var actionBtn = '';
            if (isChinhThuc) {
                // Đề chính thức - kiểm tra còn lượt thi không
                var remaining = soLanToiDa - soLanChinhThuc;
                if (remaining <= 0) {
                    actionBtn = '<div style="width: 100%; padding: 12px; background: #FEE2E2; color: #DC2626; border-radius: 10px; text-align: center; font-weight: 700; font-size: 0.9rem; margin-top: auto;">' +
                        '❌ Đã hết lượt thi' +
                    '</div>';
                } else if (isOpen) {
                    actionBtn = '<button class="item-btn" style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);" onclick="event.stopPropagation(); startExam(' + exam.id + ')">' +
                        '<span>🎯</span> ' + (soLanChinhThuc > 0 ? 'Thi lại (Lần ' + (soLanChinhThuc + 1) + ')' : 'BẮT ĐẦU THI') +
                    '</button>';
                } else {
                    actionBtn = '<div style="width: 100%; padding: 12px; background: #F3F4F6; color: #6B7280; border-radius: 10px; text-align: center; font-weight: 700; font-size: 0.9rem; margin-top: auto;">' +
                        '🔒 Chỉ mở vào T7-CN' +
                    '</div>';
                }
            } else {
                // Đề luyện tập - không giới hạn
                if (isOpen) {
                    actionBtn = '<button class="item-btn" onclick="event.stopPropagation(); startExam(' + exam.id + ')">' +
                        '<span>▶️</span> ' + (soLanThi > 0 ? 'Làm lại' : 'Bắt đầu làm bài') +
                    '</button>';
                } else {
                    actionBtn = '<div style="width: 100%; padding: 12px; background: #F3F4F6; color: #6B7280; border-radius: 10px; text-align: center; font-weight: 700; font-size: 0.9rem; margin-top: auto;">' +
                        '🔒 Chỉ mở vào T7-CN' +
                    '</div>';
                }
            }

            // Kiểm tra xem có thể click không
            var canClick = isOpen;
            if (isChinhThuc) {
                var remaining = soLanToiDa - soLanChinhThuc;
                canClick = isOpen && remaining > 0;
            }

            html += '<div class="' + cardClass + '" style="position: relative; ' + (canClick ? 'cursor: pointer;' : 'opacity: 0.9;') + '" ' + (canClick ? 'onclick="startExam(' + exam.id + ')"' : '') + '>' +
                officialBadge +
                attemptBadge +
                '<div class="item-header" style="margin-top: 8px;">' +
                    '<div class="item-icon" style="background: ' + (isChinhThuc ? 'linear-gradient(135deg, #FFD700 0%, #FFA500 100%);' : exam.mau_sac + '20;') + '">' + (isChinhThuc ? '🏆' : '📖') + '</div>' +
                    '<div>' +
                        '<div class="item-title">' + escapeHtml(exam.ten_de) + '</div>' +
                        '<div class="item-subtitle">' + escapeHtml(exam.ten_mon) + (isChinhThuc ? ' • <span style="color: #D97706; font-weight: 700;">Thi chính thức</span>' : ' • <span style="color: #4F46E5;">Luyện tập</span>') + '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="item-meta">' +
                    '<div class="meta-item"><span>❓</span> ' + exam.so_cau + ' câu</div>' +
                    '<div class="meta-item"><span>⏱️</span> ' + exam.thoi_gian_cau + 's/câu</div>' +
                '</div>' +
                attemptCounter +
                actionBtn +
            '</div>';
        }

        html += '</div>';
        return html;
    }

    // ========== RENDER TRANG TÀI LIỆU ==========
    function renderDocumentsPage() {
        if (APP.documents.length === 0) {
            return '<div class="empty-state">' +
                '<div class="empty-icon">📚</div>' +
                '<div class="empty-text">Chưa có tài liệu nào</div>' +
            '</div>';
        }

        var state = PAGE_STATE.documents;
        var start = (state.current - 1) * SCREEN.itemsPerPage;
        var end = start + SCREEN.itemsPerPage;
        var items = APP.documents.slice(start, end);

        var html = renderPaginationBar('documents', state.current, state.total, APP.documents.length);

        html += '<div class="content-grid" style="grid-template-columns: repeat(' + SCREEN.columns + ', 1fr);">';

        for (var i = 0; i < items.length; i++) {
            var doc = items[i];
            var icon = FILE_ICONS[doc.loai_file] || '📁';
            var localFile = doc.local_file || '';
            var gdriveId = doc.google_drive_id || '';

            html += '<div class="item-card" onclick="viewDocument(\'' + gdriveId + '\', \'' + doc.loai_file + '\', \'' + escapeHtml(doc.tieu_de).replace(/'/g, "\\'") + '\', \'' + localFile + '\')">' +
                '<div class="item-header">' +
                    '<div class="item-icon" style="background: ' + doc.mau_sac + '20;">' + icon + '</div>' +
                    '<div>' +
                        '<div class="item-title">' + escapeHtml(doc.tieu_de) + '</div>' +
                        '<div class="item-subtitle">' + escapeHtml(doc.ten_mon) + '</div>' +
                    '</div>' +
                '</div>' +
                '<button class="item-btn doc-btn" onclick="event.stopPropagation(); viewDocument(\'' + gdriveId + '\', \'' + doc.loai_file + '\', \'' + escapeHtml(doc.tieu_de).replace(/'/g, "\\'") + '\', \'' + localFile + '\')">' +
                    '<span>👁️</span> Xem tài liệu' +
                '</button>' +
            '</div>';
        }

        html += '</div>';
        return html;
    }

    // ========== RENDER TRANG LỊCH SỬ ==========
    function renderHistoryPage() {
        if (APP.history.length === 0) {
            return '<div class="empty-state">' +
                '<div class="empty-icon">📊</div>' +
                '<div class="empty-text">Bạn chưa làm bài thi nào</div>' +
            '</div>';
        }

        var historyPerPage = SCREEN.rows * 2;
        var state = PAGE_STATE.history;
        state.total = Math.ceil(APP.history.length / historyPerPage) || 1;

        var start = (state.current - 1) * historyPerPage;
        var end = start + historyPerPage;
        var items = APP.history.slice(start, end);

        var html = renderPaginationBar('history', state.current, state.total, APP.history.length);

        html += '<div class="content-grid" style="grid-template-columns: repeat(2, 1fr);">';

        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var scoreColor = item.diem >= 8 ? '#10B981' : (item.diem >= 5 ? '#F59E0B' : '#EF4444');
            var date = new Date(item.thoi_gian_ket_thuc);
            var dateStr = date.toLocaleDateString('vi-VN');

            html += '<div class="history-card">' +
                '<div class="history-score" style="background: ' + scoreColor + ';">' +
                    parseFloat(item.diem).toFixed(1) +
                '</div>' +
                '<div class="history-info">' +
                    '<div class="history-title">' + escapeHtml(item.ten_de) + '</div>' +
                    '<div class="history-meta">' + escapeHtml(item.ten_mon) + '</div>' +
                '</div>' +
                '<div class="history-result">' +
                    '<div class="history-correct">' + item.so_cau_dung + '/' + item.tong_cau + ' câu đúng</div>' +
                    '<div class="history-date">' + dateStr + '</div>' +
                '</div>' +
            '</div>';
        }

        html += '</div>';
        return html;
    }

    // ========== RENDER PAGINATION BAR ==========
    function renderPaginationBar(type, current, total, totalItems) {
        var prevDisabled = current <= 1 ? ' disabled' : '';
        var nextDisabled = current >= total ? ' disabled' : '';

        return '<div class="pagination-bar">' +
            '<div class="page-info">' +
                'Trang <strong>' + current + '</strong> / ' + total + ' • Tổng cộng <strong>' + totalItems + '</strong> mục' +
            '</div>' +
            '<div class="pagination-btns">' +
                '<button class="page-btn prev"' + prevDisabled + ' onclick="goPage(\'' + type + '\', ' + (current - 1) + ')">' +
                    '<span>◀️</span> Trước' +
                '</button>' +
                '<button class="page-btn next"' + nextDisabled + ' onclick="goPage(\'' + type + '\', ' + (current + 1) + ')">' +
                    'Tiếp <span>▶️</span>' +
                '</button>' +
            '</div>' +
        '</div>';
    }

    // ========== CHUYỂN TRANG PAGINATION ==========
    function goPage(type, page) {
        if (page < 1) page = 1;
        if (page > PAGE_STATE[type].total) page = PAGE_STATE[type].total;

        PAGE_STATE[type].current = page;
        renderCurrentPage();
    }

    // ========== ACTIONS ==========
    function startExam(examId) {
        window.location.href = APP.baseUrl + '/student/exam.php?id=' + examId;
    }

    // ========== DOCUMENT VIEWER ==========
    var CURRENT_DOC = {
        gdriveId: '',
        fileType: '',
        title: '',
        localFile: '',
        downloadUrl: ''
    };

    function viewDocument(gdriveId, fileType, title, localFile) {
        if (!localFile && !gdriveId) {
            alert('Tài liệu chưa có file đính kèm');
            return;
        }

        // Lưu thông tin doc hiện tại
        CURRENT_DOC = {
            gdriveId: gdriveId,
            fileType: fileType,
            title: title,
            localFile: localFile,
            downloadUrl: ''
        };

        // Hiển thị modal
        var overlay = document.getElementById('docViewerOverlay');
        var iframe = document.getElementById('docViewerIframe');
        var loading = document.getElementById('docViewerLoading');

        overlay.classList.add('show');
        iframe.style.display = 'none';
        loading.style.display = 'block';

        // Set title và icon
        var icon = FILE_ICONS[fileType] || '📄';
        document.getElementById('docViewerIcon').textContent = icon;
        document.getElementById('docViewerTitle').textContent = title;

        // Xác định URL để xem
        var viewUrl = '';
        var downloadUrl = '';

        if (localFile) {
            // File local
            var fileUrl = APP.baseUrl + '/uploads/documents/' + localFile;
            downloadUrl = fileUrl;

            if (fileType === 'pdf') {
                // PDF có thể xem trực tiếp
                viewUrl = fileUrl;
            } else if (fileType === 'word' || fileType === 'ppt') {
                // Dùng Google Docs Viewer cho Word/PPT
                viewUrl = 'https://docs.google.com/gview?url=' + encodeURIComponent(fileUrl) + '&embedded=true';
            } else if (fileType === 'image') {
                // Image hiển thị trực tiếp
                showImageViewer(fileUrl);
                return;
            } else if (fileType === 'video') {
                // Video hiển thị trực tiếp
                showVideoViewer(fileUrl);
                return;
            } else {
                viewUrl = fileUrl;
            }
        } else if (gdriveId) {
            // File từ Google Drive
            downloadUrl = 'https://drive.google.com/uc?export=download&id=' + gdriveId;

            if (fileType === 'pdf' || fileType === 'word' || fileType === 'ppt') {
                // Dùng Google Drive preview
                viewUrl = 'https://drive.google.com/file/d/' + gdriveId + '/preview';
            } else if (fileType === 'image') {
                // Image từ Drive
                var imgUrl = 'https://drive.google.com/uc?export=view&id=' + gdriveId;
                showImageViewer(imgUrl);
                return;
            } else if (fileType === 'video') {
                // Video từ Drive
                viewUrl = 'https://drive.google.com/file/d/' + gdriveId + '/preview';
            } else {
                viewUrl = 'https://drive.google.com/file/d/' + gdriveId + '/preview';
            }
        }

        CURRENT_DOC.downloadUrl = downloadUrl;

        // Load iframe
        iframe.onload = function() {
            loading.style.display = 'none';
            iframe.style.display = 'block';
        };

        iframe.onerror = function() {
            showViewerError();
        };

        iframe.src = viewUrl;

        // Timeout fallback
        setTimeout(function() {
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
        CURRENT_DOC.downloadUrl = imageUrl;
    }

    function showVideoViewer(videoUrl) {
        var body = document.getElementById('docViewerBody');
        document.getElementById('docViewerLoading').style.display = 'none';
        document.getElementById('docViewerIframe').style.display = 'none';

        body.innerHTML = '<video controls autoplay style="max-width: 100%; max-height: 100%; border-radius: 12px;"><source src="' + videoUrl + '">Trình duyệt không hỗ trợ video.</video>';
        CURRENT_DOC.downloadUrl = videoUrl;
    }

    function showViewerError() {
        var body = document.getElementById('docViewerBody');
        body.innerHTML = '<div class="doc-viewer-error">' +
            '<div class="doc-viewer-error-icon">😕</div>' +
            '<div class="doc-viewer-error-text">Không thể xem trực tiếp tài liệu này</div>' +
            '<button class="doc-viewer-error-btn" onclick="downloadDocument()">' +
                '<span>⬇️</span> Tải xuống để xem' +
            '</button>' +
        '</div>';
    }

    function closeDocViewer() {
        var overlay = document.getElementById('docViewerOverlay');
        var iframe = document.getElementById('docViewerIframe');

        overlay.classList.remove('show');
        iframe.src = '';

        // Reset body content
        document.getElementById('docViewerBody').innerHTML =
            '<div class="doc-viewer-loading" id="docViewerLoading">' +
                '<div class="loading-spinner"></div>' +
                '<div>Đang tải tài liệu...</div>' +
            '</div>' +
            '<iframe class="doc-viewer-iframe" id="docViewerIframe" style="display: none;"></iframe>';
    }

    function downloadDocument() {
        if (CURRENT_DOC.downloadUrl) {
            window.open(CURRENT_DOC.downloadUrl, '_blank');
        } else if (CURRENT_DOC.gdriveId) {
            window.open('https://drive.google.com/uc?export=download&id=' + CURRENT_DOC.gdriveId, '_blank');
        }
    }

    // Đóng modal khi nhấn ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDocViewer();
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ========== AVATAR DROPDOWN & CHANGE PASSWORD ==========
    function toggleAvatarDropdown(e) {
        e.stopPropagation();
        var dropdown = document.getElementById('avatarDropdown');
        dropdown.classList.toggle('show');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        var dropdown = document.getElementById('avatarDropdown');
        if (dropdown && !e.target.closest('.avatar-wrapper')) {
            dropdown.classList.remove('show');
        }
    });

    function showChangePasswordModal(e) {
        e.stopPropagation();
        document.getElementById('avatarDropdown').classList.remove('show');
        document.getElementById('changePasswordModal').classList.add('show');
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
        document.getElementById('passwordMessage').style.display = 'none';
    }

    function closeChangePasswordModal() {
        document.getElementById('changePasswordModal').classList.remove('show');
    }

    function changePassword() {
        var currentPwd = document.getElementById('currentPassword').value;
        var newPwd = document.getElementById('newPassword').value;
        var confirmPwd = document.getElementById('confirmPassword').value;
        var messageDiv = document.getElementById('passwordMessage');

        // Validate
        if (!currentPwd || !newPwd || !confirmPwd) {
            messageDiv.textContent = 'Vui lòng điền đầy đủ thông tin!';
            messageDiv.className = 'modal-message error';
            messageDiv.style.display = 'block';
            return;
        }

        if (newPwd.length < 6) {
            messageDiv.textContent = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
            messageDiv.className = 'modal-message error';
            messageDiv.style.display = 'block';
            return;
        }

        if (newPwd !== confirmPwd) {
            messageDiv.textContent = 'Mật khẩu mới không khớp!';
            messageDiv.className = 'modal-message error';
            messageDiv.style.display = 'block';
            return;
        }

        // Send request
        fetch(APP.baseUrl + '/api/change-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                current_password: currentPwd,
                new_password: newPwd
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                messageDiv.textContent = 'Đổi mật khẩu thành công!';
                messageDiv.className = 'modal-message success';
                messageDiv.style.display = 'block';
                setTimeout(function() {
                    closeChangePasswordModal();
                }, 1500);
            } else {
                messageDiv.textContent = data.message || 'Có lỗi xảy ra!';
                messageDiv.className = 'modal-message error';
                messageDiv.style.display = 'block';
            }
        })
        .catch(function(error) {
            messageDiv.textContent = 'Có lỗi xảy ra!';
            messageDiv.className = 'modal-message error';
            messageDiv.style.display = 'block';
        });
    }
    </script>
</body>
</html>
