<?php
/**
 * ==============================================
 * MOBILE - KẾT QUẢ THI
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/device.php';

redirectIfDesktop(BASE_URL . '/student/result.php' . (isset($_GET['session']) ? '?session=' . $_GET['session'] : ''));

if (!isStudentLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$student = getCurrentStudent();
$conn = getDBConnection();

$sessionToken = isset($_GET['session']) ? $_GET['session'] : '';

if (empty($sessionToken)) {
    redirect(BASE_URL . '/student/mobile/index.php');
}

// Lấy thông tin bài làm
$stmtBL = $conn->prepare("
    SELECT bl.*, dt.ten_de, dt.so_cau, mh.ten_mon, mh.icon
    FROM bai_lam bl
    JOIN de_thi dt ON bl.de_thi_id = dt.id
    JOIN mon_hoc mh ON dt.mon_hoc_id = mh.id
    WHERE bl.session_token = ? AND bl.hoc_sinh_id = ?
");
$stmtBL->execute(array($sessionToken, $student['id']));
$baiLam = $stmtBL->fetch();

if (!$baiLam) {
    redirect(BASE_URL . '/student/mobile/index.php');
}

// Lấy chi tiết bài làm
$stmtCT = $conn->prepare("
    SELECT ctbl.*, ch.noi_dung, ch.dap_an_a, ch.dap_an_b, ch.dap_an_c, ch.dap_an_d, ch.dap_an_dung, ch.giai_thich
    FROM chi_tiet_bai_lam ctbl
    JOIN cau_hoi ch ON ctbl.cau_hoi_id = ch.id
    WHERE ctbl.bai_lam_id = ?
    ORDER BY ctbl.id ASC
");
$stmtCT->execute(array($baiLam['id']));
$chiTietList = $stmtCT->fetchAll();

// Tính toán
$soCauDung = $baiLam['so_cau_dung'];
$tongCau = $baiLam['so_cau'];
$diem = $baiLam['diem'];
$thoiGian = $baiLam['tong_thoi_gian'];

// Emoji và đánh giá
if ($diem >= 9) {
    $emoji = '🎉';
    $stars = '⭐⭐⭐⭐⭐';
    $message = 'Xuất sắc!';
} elseif ($diem >= 8) {
    $emoji = '😊';
    $stars = '⭐⭐⭐⭐';
    $message = 'Giỏi lắm!';
} elseif ($diem >= 6.5) {
    $emoji = '🙂';
    $stars = '⭐⭐⭐';
    $message = 'Khá tốt!';
} elseif ($diem >= 5) {
    $emoji = '😐';
    $stars = '⭐⭐';
    $message = 'Cần cố gắng hơn!';
} else {
    $emoji = '😢';
    $stars = '⭐';
    $message = 'Cố gắng lần sau nhé!';
}

$pageTitle = 'Kết quả thi';
$currentTab = 'exams';
include 'header.php';
?>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="page-header">
            <a href="index.php" class="back-btn">‹</a>
            <h1><?php echo getSubjectIcon($baiLam['icon']); ?> Kết quả thi</h1>
        </div>
    </div>
</div>

<main class="main">
    <!-- Score Card -->
    <div class="card">
        <div class="result-score">
            <div class="emoji"><?php echo $emoji; ?></div>
            <div class="score"><?php echo $diem; ?>/10</div>
            <div class="label"><?php echo $message; ?></div>
            <div class="stars"><?php echo $stars; ?></div>
        </div>

        <div class="stats-grid">
            <div class="stat-item success">
                <div class="value"><?php echo $soCauDung; ?></div>
                <div class="label">Đúng</div>
            </div>
            <div class="stat-item danger">
                <div class="value"><?php echo $tongCau - $soCauDung; ?></div>
                <div class="label">Sai</div>
            </div>
            <div class="stat-item">
                <div class="value"><?php echo gmdate('i:s', $thoiGian); ?></div>
                <div class="label">Thời gian</div>
            </div>
        </div>
    </div>

    <!-- Exam Info -->
    <div class="card">
        <div class="card-title">📝 Thông tin bài thi</div>
        <div style="font-size: 15px; line-height: 1.8;">
            <div><strong>Đề thi:</strong> <?php echo htmlspecialchars($baiLam['ten_de']); ?></div>
            <div><strong>Môn:</strong> <?php echo htmlspecialchars($baiLam['ten_mon']); ?></div>
            <div><strong>Thời gian nộp:</strong> <?php echo date('H:i - d/m/Y', strtotime($baiLam['thoi_gian_ket_thuc'])); ?></div>
        </div>
    </div>

    <!-- Chi tiết câu hỏi -->
    <div class="card">
        <div class="card-title">📋 Chi tiết bài làm</div>

        <?php
        $stt = 0;
        foreach ($chiTietList as $ct):
            $stt++;
            $isCorrect = $ct['is_dung'] == 1;
            $answered = !empty($ct['dap_an_chon']);
        ?>
        <div style="padding: 16px; background: <?php echo $isCorrect ? 'rgba(16,185,129,0.08)' : 'rgba(239,68,68,0.08)'; ?>; border-radius: 12px; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <span style="font-size: 20px;"><?php echo $isCorrect ? '✅' : '❌'; ?></span>
                <strong>Câu <?php echo $stt; ?>:</strong>
            </div>
            <div style="font-size: 15px; margin-bottom: 12px;"><?php echo htmlspecialchars($ct['noi_dung']); ?></div>

            <div style="font-size: 14px; color: #6B7280;">
                <?php if ($answered): ?>
                    <div>Bạn chọn: <strong class="<?php echo $isCorrect ? 'text-success' : 'text-danger'; ?>"><?php echo $ct['dap_an_chon']; ?></strong></div>
                <?php else: ?>
                    <div class="text-danger">Chưa trả lời</div>
                <?php endif; ?>

                <?php if (!$isCorrect): ?>
                    <div class="text-success">Đáp án đúng: <strong><?php echo $ct['dap_an_dung']; ?></strong></div>
                <?php endif; ?>

                <?php if (!empty($ct['giai_thich'])): ?>
                    <div style="margin-top: 8px; padding: 8px; background: rgba(0,0,0,0.05); border-radius: 8px; font-style: italic;">
                        💡 <?php echo htmlspecialchars($ct['giai_thich']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Actions -->
    <div style="display: flex; gap: 12px; margin-bottom: 24px;">
        <a href="exam.php?id=<?php echo $baiLam['de_thi_id']; ?>" class="btn btn-primary btn-block">
            🔄 Làm lại
        </a>
        <a href="index.php" class="btn btn-outline btn-block">
            🏠 Về trang chủ
        </a>
    </div>
</main>

<!-- Bottom Tab Bar -->
<nav class="tab-bar">
    <a href="index.php" class="tab-item">
        <span class="icon">🏠</span>
        <span class="label">Trang chủ</span>
    </a>
    <a href="exams.php" class="tab-item active">
        <span class="icon">📝</span>
        <span class="label">Làm bài</span>
    </a>
    <a href="<?php echo BASE_URL; ?>/student/thidua/xep_hang.php" class="tab-item">
        <span class="icon">🏅</span>
        <span class="label">Thi đua</span>
    </a>
    <a href="documents.php" class="tab-item">
        <span class="icon">📖</span>
        <span class="label">Tài liệu</span>
    </a>
    <a href="profile.php" class="tab-item">
        <span class="icon">👤</span>
        <span class="label">Tôi</span>
    </a>
</nav>

<?php include 'footer.php'; ?>
