<?php
/**
 * ==============================================
 * MOBILE - LÀM BÀI THI
 * Giao diện 1 câu hỏi/màn hình, tối ưu cho mobile
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/device.php';

redirectIfDesktop(BASE_URL . '/student/exam.php' . (isset($_GET['id']) ? '?id=' . $_GET['id'] : ''));

if (!isStudentLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$student = getCurrentStudent();
$conn = getDBConnection();

// Lấy thông tin đề thi
$deThiId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sessionToken = isset($_GET['session']) ? $_GET['session'] : '';

if ($deThiId <= 0) {
    redirect(BASE_URL . '/student/mobile/exams.php');
}

$stmtDeThi = $conn->prepare("
    SELECT dt.*, mh.ten_mon, mh.icon
    FROM de_thi dt
    JOIN mon_hoc mh ON dt.mon_hoc_id = mh.id
    WHERE dt.id = ? AND dt.trang_thai = 1
");
$stmtDeThi->execute(array($deThiId));
$deThi = $stmtDeThi->fetch();

if (!$deThi) {
    redirect(BASE_URL . '/student/mobile/exams.php');
}

// Lấy tuần hiện tại
require_once '../../includes/week_helper.php';
$currentWeek = getCurrentWeek();

// Kiểm tra nếu là đề thi chính thức - cần kiểm tra số lần thi còn lại và chế độ mở
$isChinhThuc = isset($deThi['is_chinh_thuc']) ? (int)$deThi['is_chinh_thuc'] : 0;
$soLanToiDa = isset($deThi['so_lan_thi_toi_da_tuan']) ? (int)$deThi['so_lan_thi_toi_da_tuan'] : 3;
$soLanDaThi = 0;
$hetLuotThi = false;

// Ưu tiên 1: Lấy chế độ mở từ lịch thi (exam-schedule)
// Ưu tiên 2: Nếu không có, lấy từ cài đặt hệ thống
$cheDoMo = isset($deThi['che_do_mo']) && !empty($deThi['che_do_mo']) ? $deThi['che_do_mo'] : null;

// Nếu không có chế độ mở từ lịch thi, lấy từ cài đặt hệ thống
if ($cheDoMo === null && $isChinhThuc) {
    $stmtSetting = $conn->prepare("SELECT gia_tri FROM cau_hinh WHERE ma_cau_hinh = 'che_do_mo_mac_dinh'");
    $stmtSetting->execute();
    $settingResult = $stmtSetting->fetch();
    $cheDoMoMacDinh = $settingResult ? $settingResult['gia_tri'] : 'cuoi_tuan';

    // Chuyển đổi từ cài đặt hệ thống sang chế độ mở
    if ($cheDoMoMacDinh == 'luon_mo') {
        $cheDoMo = 'mo_ngay';
    } else {
        $cheDoMo = 'theo_lich'; // cuoi_tuan hoặc theo_gio đều dùng theo_lich
    }
}

// Mặc định nếu vẫn null
if ($cheDoMo === null) {
    $cheDoMo = 'theo_lich';
}

// Kiểm tra chế độ mở cho đề thi chính thức
if ($isChinhThuc && $cheDoMo == 'theo_lich' && empty($sessionToken)) {
    // Lấy danh sách ngày mở thi từ cài đặt (hoặc mặc định T7, CN)
    $stmtNgayMo = $conn->prepare("SELECT gia_tri FROM cau_hinh WHERE ma_cau_hinh = 'ngay_mo_thi'");
    $stmtNgayMo->execute();
    $ngayMoResult = $stmtNgayMo->fetch();
    $ngayMoThi = $ngayMoResult ? $ngayMoResult['gia_tri'] : 't7,cn';

    // Chuyển đổi ngày trong tuần
    $dayMap = array('cn' => 0, 't2' => 1, 't3' => 2, 't4' => 3, 't5' => 4, 't6' => 5, 't7' => 6);
    $allowedDays = array();
    foreach (explode(',', $ngayMoThi) as $day) {
        $day = trim(strtolower($day));
        if (isset($dayMap[$day])) {
            $allowedDays[] = $dayMap[$day];
        }
    }

    // Kiểm tra ngày hiện tại
    $dayOfWeek = (int)date('w'); // 0 = CN, 1-6 = T2-T7
    if (!in_array($dayOfWeek, $allowedDays)) {
        $dayNames = array('Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7');
        $allowedDayNames = array();
        foreach ($allowedDays as $d) {
            $allowedDayNames[] = $dayNames[$d];
        }
        $_SESSION['error_message'] = 'Đề thi chính thức này chỉ mở vào: ' . implode(', ', $allowedDayNames) . '!';
        redirect(BASE_URL . '/student/mobile/exams.php');
    }
}

if ($isChinhThuc && $currentWeek) {
    // Đếm số lần thi chính thức trong tuần này
    $stmtCount = $conn->prepare("
        SELECT COUNT(*) as count
        FROM bai_lam
        WHERE hoc_sinh_id = ?
        AND de_thi_id = ?
        AND tuan_id = ?
        AND is_chinh_thuc = 1
        AND trang_thai = 'hoan_thanh'
    ");
    $stmtCount->execute(array($student['id'], $deThiId, $currentWeek['id']));
    $countResult = $stmtCount->fetch();
    $soLanDaThi = $countResult ? (int)$countResult['count'] : 0;

    if ($soLanDaThi >= $soLanToiDa) {
        $hetLuotThi = true;
    }
}

// Nếu hết lượt thi chính thức -> redirect về exams
if ($isChinhThuc && $hetLuotThi && empty($sessionToken)) {
    $_SESSION['error_message'] = 'Bạn đã hết lượt thi chính thức cho đề thi này trong tuần!';
    redirect(BASE_URL . '/student/mobile/exams.php');
}

// Kiểm tra session hoặc tạo mới
if (!empty($sessionToken)) {
    $stmtBL = $conn->prepare("SELECT * FROM bai_lam WHERE session_token = ? AND hoc_sinh_id = ?");
    $stmtBL->execute(array($sessionToken, $student['id']));
    $baiLam = $stmtBL->fetch();

    if (!$baiLam || $baiLam['trang_thai'] !== 'dang_lam') {
        redirect(BASE_URL . '/student/mobile/exams.php');
    }
} else {
    // Tạo session mới
    $sessionToken = hash('sha256', $student['id'] . $deThiId . time() . rand());
    $tuanId = $currentWeek ? $currentWeek['id'] : null;

    $conn->prepare("
        INSERT INTO bai_lam (hoc_sinh_id, de_thi_id, session_token, thoi_gian_bat_dau, trang_thai, tuan_id, is_chinh_thuc)
        VALUES (?, ?, ?, NOW(), 'dang_lam', ?, ?)
    ")->execute(array($student['id'], $deThiId, $sessionToken, $tuanId, $isChinhThuc));

    redirect(BASE_URL . '/student/mobile/exam.php?id=' . $deThiId . '&session=' . $sessionToken);
}

// Lấy câu hỏi
$stmtCauHoi = $conn->prepare("
    SELECT ch.*, ctdt.thu_tu
    FROM chi_tiet_de_thi ctdt
    JOIN cau_hoi ch ON ctdt.cau_hoi_id = ch.id
    WHERE ctdt.de_thi_id = ?
    ORDER BY ctdt.thu_tu ASC
");
$stmtCauHoi->execute(array($deThiId));
$cauHoiList = $stmtCauHoi->fetchAll();

// Chuẩn bị JSON câu hỏi
$questionsJson = array();
foreach ($cauHoiList as $ch) {
    $questionsJson[] = array(
        'id' => $ch['id'],
        'noi_dung' => $ch['noi_dung'],
        'hinh_anh' => $ch['hinh_anh'],
        'dap_an_a' => $ch['dap_an_a'],
        'dap_an_b' => $ch['dap_an_b'],
        'dap_an_c' => $ch['dap_an_c'],
        'dap_an_d' => $ch['dap_an_d'],
        'dap_an_dung' => $ch['dap_an_dung']
    );
}

$pageTitle = $deThi['ten_de'];

$extraStyles = '<style>
    .app { padding-bottom: 0; }

    .exam-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        padding: 16px 20px;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 100;
    }

    .exam-header-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .exam-title {
        font-size: 14px;
        font-weight: 600;
        opacity: 0.9;
    }

    .exam-progress {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .question-num {
        font-size: 16px;
        font-weight: 700;
    }

    .progress-bar {
        flex: 1;
        height: 6px;
        background: rgba(255,255,255,0.3);
        margin: 0;
    }

    .progress-bar .fill {
        background: white;
    }

    .exam-body {
        padding-top: 100px;
        padding-bottom: 100px;
        min-height: 100vh;
    }

    .question-container {
        padding: 20px;
    }

    .question-text {
        font-size: 20px;
        font-weight: 700;
        line-height: 1.5;
        margin-bottom: 8px;
        text-align: center;
    }

    .question-image {
        max-width: 100%;
        border-radius: 12px;
        margin: 16px 0;
    }

    .answer-list {
        margin-top: 24px;
    }

    .exam-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        padding: 12px 20px;
        padding-bottom: calc(12px + var(--safe-bottom));
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        display: flex;
        gap: 12px;
        z-index: 100;
    }

    .nav-btn {
        flex: 1;
        padding: 16px;
        border-radius: var(--radius-sm);
        font-size: 16px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .nav-btn.prev {
        background: var(--bg);
        color: var(--text);
    }

    .nav-btn.next {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
    }

    .nav-btn:disabled {
        opacity: 0.5;
    }

    .nav-btn.submit {
        background: var(--success);
    }

    /* Question dots */
    .question-dots {
        display: flex;
        justify-content: center;
        gap: 6px;
        flex-wrap: wrap;
        padding: 12px 20px;
        background: rgba(255,255,255,0.1);
        border-radius: 12px;
        margin-top: 8px;
    }

    .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: rgba(255,255,255,0.4);
    }

    .dot.current {
        background: white;
        transform: scale(1.3);
    }

    .dot.answered {
        background: var(--success);
    }

    /* Confirm modal */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        padding: 20px;
    }

    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: white;
        border-radius: var(--radius);
        padding: 24px;
        max-width: 320px;
        width: 100%;
        text-align: center;
    }

    .modal-icon {
        font-size: 48px;
        margin-bottom: 16px;
    }

    .modal-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .modal-desc {
        color: var(--text-light);
        margin-bottom: 20px;
        font-size: 14px;
    }

    .modal-buttons {
        display: flex;
        gap: 12px;
    }

    .modal-buttons .btn {
        flex: 1;
    }

    /* Review Mode Styles */
    .review-mode .exam-header {
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    }

    .review-timer-total {
        background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
        padding: 8px 12px;
        border-radius: var(--radius-sm);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .review-timer-total.critical {
        background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        animation: pulse 0.5s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .review-nav-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        padding: 12px 20px;
        padding-bottom: calc(12px + var(--safe-bottom));
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        display: none;
        gap: 12px;
        z-index: 100;
    }

    .review-mode .review-nav-bar {
        display: flex;
    }

    .review-mode .exam-footer {
        display: none;
    }

    .review-btn {
        flex: 1;
        padding: 14px;
        border-radius: var(--radius-sm);
        font-size: 15px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .review-btn.nav {
        background: var(--bg);
        color: var(--text);
    }

    .review-btn.nav:disabled {
        opacity: 0.5;
    }

    .review-btn.submit {
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        color: white;
    }

    /* Score Modal */
    .score-modal .modal-content {
        padding: 32px 24px;
    }

    .score-display {
        font-size: 4rem;
        font-weight: 700;
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 8px;
    }

    .score-label {
        font-size: 1.1rem;
        color: var(--text-light);
        font-weight: 600;
    }

    .score-detail {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid var(--border);
    }

    .score-item {
        text-align: center;
    }

    .score-item-value {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .score-item-value.correct {
        color: var(--success);
    }

    .score-item-value.wrong {
        color: var(--danger);
    }

    .score-item-label {
        font-size: 0.85rem;
        color: var(--text-light);
    }

    /* Button Styles */
    .btn {
        padding: 14px 20px;
        border-radius: var(--radius-sm);
        font-size: 15px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
    }

    .btn-success {
        background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        color: white;
    }

    .btn-outline {
        background: white;
        color: var(--text);
        border: 2px solid var(--border);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Answer option styles */
    .answer-option {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: white;
        border: 2px solid var(--border);
        border-radius: var(--radius-sm);
        margin-bottom: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .answer-option:hover {
        border-color: var(--primary);
    }

    .answer-option.selected {
        border-color: var(--success);
        background: rgba(16, 185, 129, 0.1);
    }

    .answer-option .letter {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }

    .answer-option.selected .letter {
        background: var(--success);
    }

    .answer-option .text {
        font-weight: 600;
        color: var(--text);
        line-height: 1.4;
    }
</style>';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { font-family: 'Inter', sans-serif; background: #F3F4F6; min-height: 100vh; }
        :root {
            --primary: #4F46E5; --secondary: #7C3AED; --success: #10B981;
            --danger: #EF4444; --warning: #F59E0B; --text: #1F2937;
            --text-light: #6B7280; --bg: #F3F4F6; --card: #FFFFFF;
            --border: #E5E7EB; --radius: 16px; --radius-sm: 12px;
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
    </style>
    <?php echo $extraStyles; ?>
</head>
<body>

<!-- Exam Header -->
<div class="exam-header">
    <div class="exam-header-top">
        <div class="exam-title"><?php echo getSubjectIcon($deThi['icon']); ?> <?php echo htmlspecialchars($deThi['ten_de']); ?></div>
        <div class="timer" id="timer">⏱️ <span id="timerText">00:00</span></div>
    </div>
    <div class="exam-progress">
        <span class="question-num" id="questionNum">1/<?php echo count($cauHoiList); ?></span>
        <div class="progress-bar">
            <div class="fill" id="progressFill" style="width: 0%"></div>
        </div>
    </div>
    <div class="question-dots" id="questionDots">
        <?php for ($i = 0; $i < count($cauHoiList); $i++): ?>
        <div class="dot" data-index="<?php echo $i; ?>"></div>
        <?php endfor; ?>
    </div>
</div>

<!-- Exam Body -->
<div class="exam-body">
    <div class="question-container" id="questionContainer">
        <!-- Rendered by JS -->
    </div>
</div>

<!-- Exam Footer -->
<div class="exam-footer">
    <button class="nav-btn prev" id="btnPrev" onclick="prevQuestion()">
        ‹ Trước
    </button>
    <button class="nav-btn next" id="btnNext" onclick="nextQuestion()">
        Tiếp ›
    </button>
</div>

<!-- Review Modal - Hiển thị khi làm xong câu cuối -->
<div class="modal-overlay" id="reviewModal">
    <div class="modal-content">
        <div class="modal-icon">📋</div>
        <div class="modal-title">Hoàn thành bài thi!</div>
        <div class="modal-desc">Bạn có muốn xem lại bài trước khi nộp không?</div>
        <div style="display: flex; justify-content: center; gap: 24px; margin-bottom: 20px;">
            <div style="text-align: center;">
                <div style="font-size: 1.8rem; font-weight: 700; color: #10B981;" id="answeredCount">0</div>
                <div style="font-size: 0.85rem; color: #6B7280;">Đã trả lời</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.8rem; font-weight: 700; color: #EF4444;" id="unansweredCount">0</div>
                <div style="font-size: 0.85rem; color: #6B7280;">Chưa trả lời</div>
            </div>
        </div>
        <div class="modal-buttons">
            <button class="btn btn-primary" onclick="enterReviewMode()">👁️ Xem lại</button>
            <button class="btn btn-success" onclick="submitExamNow()">✓ Nộp bài</button>
        </div>
    </div>
</div>

<!-- Score Modal - Hiển thị điểm sau khi nộp -->
<div class="modal-overlay score-modal" id="scoreModal">
    <div class="modal-content">
        <div class="modal-icon" id="scoreEmoji">🌟</div>
        <div class="modal-title" id="scoreTitle">Xuất sắc!</div>
        <div class="score-display" id="scoreValue">10</div>
        <div class="score-label">điểm</div>
        <div class="score-detail">
            <div class="score-item">
                <div class="score-item-value correct" id="correctCount">0</div>
                <div class="score-item-label">Đúng ✓</div>
            </div>
            <div class="score-item">
                <div class="score-item-value wrong" id="wrongCount">0</div>
                <div class="score-item-label">Sai ✗</div>
            </div>
        </div>
        <div class="modal-buttons" style="margin-top: 20px;">
            <button class="btn btn-success" onclick="goToResult()">📊 Xem chi tiết</button>
        </div>
    </div>
</div>

<!-- Submit Modal (giữ lại cho compatibility) -->
<div class="modal-overlay" id="submitModal">
    <div class="modal-content">
        <div class="modal-icon">📝</div>
        <div class="modal-title">Nộp bài?</div>
        <div class="modal-desc" id="submitDesc">
            Bạn đã trả lời <strong>0/<?php echo count($cauHoiList); ?></strong> câu.
        </div>
        <div class="modal-buttons">
            <button class="btn btn-outline" onclick="hideSubmitModal()">Quay lại</button>
            <button class="btn btn-success" onclick="submitExamNow()">Nộp bài</button>
        </div>
    </div>
</div>

<!-- Review Navigation Bar -->
<div class="review-nav-bar" id="reviewNavBar">
    <button class="review-btn nav" id="btnPrevReview" onclick="reviewPrev()">
        ← Trước
    </button>
    <div class="review-timer-total" id="reviewTimerTotal">
        ⏱️ <span id="reviewTimerDisplay">00:00</span>
    </div>
    <button class="review-btn nav" id="btnNextReview" onclick="reviewNext()">
        Sau →
    </button>
    <button class="review-btn submit" onclick="showSubmitConfirm()">
        ✓ Nộp
    </button>
</div>

<!-- Loading -->
<div id="loading" class="loading-overlay">
    <div class="spinner" style="width:48px;height:48px;border:4px solid #E5E7EB;border-top-color:#4F46E5;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
    <div style="margin-top:16px;font-weight:600;color:#6B7280;">Đang nộp bài...</div>
</div>
<style>
    .loading-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.95);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:2000;opacity:0;visibility:hidden;transition:all 0.3s;}
    .loading-overlay.show{opacity:1;visibility:visible;}
    @keyframes spin{to{transform:rotate(360deg);}}
</style>

<script>
var BASE_URL = '<?php echo BASE_URL; ?>';
var EXAM = {
    id: <?php echo $deThiId; ?>,
    session: '<?php echo $sessionToken; ?>',
    questions: <?php echo json_encode($questionsJson); ?>,
    timePerQ: <?php echo $deThi['thoi_gian_cau']; ?>
};

var currentIndex = 0;
var answers = {};
var timer = null;
var timeLeft = EXAM.timePerQ;
var examFinished = false;

// Review mode variables
var isReviewMode = false;
var totalTimeUsed = 0; // Tổng thời gian đã sử dụng
var reviewTimer = null;

// Format time
function formatTime(sec) {
    var m = Math.floor(sec / 60);
    var s = sec % 60;
    return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Render question
function renderQuestion() {
    var q = EXAM.questions[currentIndex];
    var html = '<div class="question-text">' + escapeHtml(q.noi_dung) + '</div>';

    if (q.hinh_anh) {
        html += '<img src="' + BASE_URL + '/uploads/questions/' + q.hinh_anh + '" class="question-image" alt="Hình minh họa">';
    }

    html += '<div class="answer-list">';
    var options = ['A', 'B', 'C', 'D'];
    var fields = ['dap_an_a', 'dap_an_b', 'dap_an_c', 'dap_an_d'];

    for (var i = 0; i < 4; i++) {
        var selected = answers[q.id] === options[i] ? 'selected' : '';
        html += '<div class="answer-option ' + selected + '" onclick="selectAnswer(\'' + options[i] + '\')">';
        html += '<div class="letter">' + options[i] + '</div>';
        html += '<div class="text">' + escapeHtml(q[fields[i]]) + '</div>';
        html += '</div>';
    }
    html += '</div>';

    document.getElementById('questionContainer').innerHTML = html;

    // Update progress
    document.getElementById('questionNum').textContent = (currentIndex + 1) + '/' + EXAM.questions.length;
    document.getElementById('progressFill').style.width = ((currentIndex + 1) / EXAM.questions.length * 100) + '%';

    // Update dots
    var dots = document.querySelectorAll('.dot');
    dots.forEach(function(dot, idx) {
        dot.classList.remove('current');
        if (idx === currentIndex) dot.classList.add('current');
        // Update answered status
        if (answers[EXAM.questions[idx].id]) {
            dot.classList.add('answered');
        }
    });

    // Update buttons (only in normal mode)
    if (!isReviewMode) {
        document.getElementById('btnPrev').disabled = currentIndex === 0;
        var btnNext = document.getElementById('btnNext');
        if (currentIndex === EXAM.questions.length - 1) {
            btnNext.innerHTML = 'Nộp bài ✓';
            btnNext.classList.add('submit');
            btnNext.classList.remove('next');
        } else {
            btnNext.innerHTML = 'Tiếp ›';
            btnNext.classList.remove('submit');
            btnNext.classList.add('next');
        }
    } else {
        // Update review nav buttons
        document.getElementById('btnPrevReview').disabled = currentIndex === 0;
        document.getElementById('btnNextReview').disabled = currentIndex === EXAM.questions.length - 1;
    }
}

// Select answer
function selectAnswer(option) {
    var q = EXAM.questions[currentIndex];
    answers[q.id] = option;

    // Update UI
    document.querySelectorAll('.answer-option').forEach(function(el) {
        el.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');

    // Update dot
    var dotEl = document.querySelector('.dot[data-index="' + currentIndex + '"]');
    if (dotEl) dotEl.classList.add('answered');

    // Save to server
    saveAnswer(q.id, option);

    // Auto next after 400ms (only in normal mode)
    if (!isReviewMode) {
        setTimeout(function() {
            if (currentIndex < EXAM.questions.length - 1) {
                nextQuestion();
            }
        }, 400);
    }
}

// Save answer to server
function saveAnswer(questionId, answer) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', BASE_URL + '/api/submit_answer.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify({
        session_token: EXAM.session,
        question_id: questionId,
        answer: answer,
        time_spent: EXAM.timePerQ - timeLeft
    }));
}

// Navigation
function prevQuestion() {
    if (currentIndex > 0) {
        currentIndex--;
        if (!isReviewMode) resetTimer();
        renderQuestion();
    }
}

function nextQuestion() {
    if (currentIndex < EXAM.questions.length - 1) {
        currentIndex++;
        if (!isReviewMode) resetTimer();
        renderQuestion();
    } else {
        // Khi đến câu cuối, hiển thị modal review thay vì submit modal
        showReviewModal();
    }
}

// Timer
function startTimer() {
    timeLeft = EXAM.timePerQ;
    updateTimerDisplay();

    timer = setInterval(function() {
        timeLeft--;
        totalTimeUsed++; // Cộng thời gian đã sử dụng
        updateTimerDisplay();

        if (timeLeft <= 0) {
            clearInterval(timer);
            autoNext();
        }
    }, 1000);
}

function resetTimer() {
    clearInterval(timer);
    startTimer();
}

function updateTimerDisplay() {
    var timerEl = document.getElementById('timer');
    var timerText = document.getElementById('timerText');

    timerText.textContent = formatTime(timeLeft);

    timerEl.classList.remove('warning', 'danger');
    if (timeLeft <= 5) {
        timerEl.classList.add('danger');
    } else if (timeLeft <= 10) {
        timerEl.classList.add('warning');
    }
}

function autoNext() {
    if (currentIndex < EXAM.questions.length - 1) {
        currentIndex++;
        startTimer();
        renderQuestion();
    } else {
        showReviewModal();
    }
}

// ============ REVIEW MODE FUNCTIONS ============

function showReviewModal() {
    clearInterval(timer);

    // Đếm số câu đã trả lời
    var answered = 0;
    for (var i = 0; i < EXAM.questions.length; i++) {
        if (answers[EXAM.questions[i].id]) {
            answered++;
        }
    }
    var unanswered = EXAM.questions.length - answered;

    document.getElementById('answeredCount').textContent = answered;
    document.getElementById('unansweredCount').textContent = unanswered;
    document.getElementById('reviewModal').classList.add('show');
}

function hideReviewModal() {
    document.getElementById('reviewModal').classList.remove('show');
}

function enterReviewMode() {
    hideReviewModal();
    isReviewMode = true;
    currentIndex = 0;

    // Add review-mode class
    document.body.classList.add('review-mode');

    // Ẩn timer per-question
    document.getElementById('timer').style.display = 'none';

    // Start review timer
    startReviewTimer();

    // Render câu hỏi đầu tiên
    renderQuestion();
}

function startReviewTimer() {
    // Tính thời gian còn lại = Tổng thời gian - Thời gian đã dùng
    var totalTime = EXAM.questions.length * EXAM.timePerQ;
    var reviewTimeLeft = totalTime - totalTimeUsed;
    if (reviewTimeLeft < 0) reviewTimeLeft = 0;

    updateReviewTimerDisplay(reviewTimeLeft);

    reviewTimer = setInterval(function() {
        reviewTimeLeft--;
        updateReviewTimerDisplay(reviewTimeLeft);

        if (reviewTimeLeft <= 30) {
            document.getElementById('reviewTimerTotal').classList.add('critical');
        }

        if (reviewTimeLeft <= 0) {
            clearInterval(reviewTimer);
            alert('Hết thời gian! Bài thi đang được nộp...');
            submitExamNow();
        }
    }, 1000);
}

function updateReviewTimerDisplay(timeLeft) {
    document.getElementById('reviewTimerDisplay').textContent = formatTime(timeLeft);
}

function reviewPrev() {
    if (currentIndex > 0) {
        currentIndex--;
        renderQuestion();
    }
}

function reviewNext() {
    if (currentIndex < EXAM.questions.length - 1) {
        currentIndex++;
        renderQuestion();
    }
}

function showSubmitConfirm() {
    var answered = 0;
    for (var i = 0; i < EXAM.questions.length; i++) {
        if (answers[EXAM.questions[i].id]) {
            answered++;
        }
    }
    var unanswered = EXAM.questions.length - answered;

    if (unanswered > 0) {
        if (!confirm('Bạn còn ' + unanswered + ' câu chưa trả lời. Bạn có chắc muốn nộp bài?')) {
            return;
        }
    }
    submitExamNow();
}

function submitExamNow() {
    hideReviewModal();
    hideSubmitModal();
    clearInterval(reviewTimer);
    clearInterval(timer);
    document.getElementById('loading').classList.add('show');
    examFinished = true;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', BASE_URL + '/api/finish_exam.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            document.getElementById('loading').classList.remove('show');
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    showScoreModal(response);
                } catch (e) {
                    window.location.href = BASE_URL + '/student/mobile/result.php?session=' + EXAM.session;
                }
            } else {
                examFinished = false;
                alert('Có lỗi xảy ra!');
            }
        }
    };
    xhr.send(JSON.stringify({session_token: EXAM.session}));
}

function showScoreModal(response) {
    var score = response.score || 0;
    var correct = response.correct || 0;
    var total = response.total || EXAM.questions.length;
    var wrong = total - correct;

    // Xác định emoji và title dựa trên điểm
    var emoji, title;
    if (score >= 9) {
        emoji = '🌟';
        title = 'Xuất sắc!';
    } else if (score >= 7) {
        emoji = '👏';
        title = 'Tốt lắm!';
    } else if (score >= 5) {
        emoji = '👍';
        title = 'Khá tốt!';
    } else {
        emoji = '💪';
        title = 'Cố gắng hơn nhé!';
    }

    document.getElementById('scoreEmoji').textContent = emoji;
    document.getElementById('scoreTitle').textContent = title;
    document.getElementById('scoreValue').textContent = score;
    document.getElementById('correctCount').textContent = correct;
    document.getElementById('wrongCount').textContent = wrong;

    document.getElementById('scoreModal').classList.add('show');
}

function goToResult() {
    window.location.href = BASE_URL + '/student/mobile/result.php?session=' + EXAM.session;
}

// Legacy submit functions
function showSubmitModal() {
    var answered = Object.keys(answers).length;
    document.getElementById('submitDesc').innerHTML =
        'Bạn đã trả lời <strong>' + answered + '/' + EXAM.questions.length + '</strong> câu.';
    document.getElementById('submitModal').classList.add('show');
}

function hideSubmitModal() {
    document.getElementById('submitModal').classList.remove('show');
}

function submitExam() {
    submitExamNow();
}

// Prevent reload
window.addEventListener('beforeunload', function(e) {
    if (!examFinished) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Swipe gestures
var touchStartX = 0;
var touchEndX = 0;

document.addEventListener('touchstart', function(e) {
    touchStartX = e.changedTouches[0].screenX;
}, false);

document.addEventListener('touchend', function(e) {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
}, false);

function handleSwipe() {
    var diff = touchEndX - touchStartX;
    if (Math.abs(diff) > 50) {
        if (diff > 0) {
            if (isReviewMode) {
                reviewPrev();
            } else {
                prevQuestion();
            }
        } else {
            if (isReviewMode) {
                reviewNext();
            } else {
                nextQuestion();
            }
        }
    }
}

// Init
document.addEventListener('DOMContentLoaded', function() {
    renderQuestion();
    startTimer();
});
</script>

</body>
</html>
