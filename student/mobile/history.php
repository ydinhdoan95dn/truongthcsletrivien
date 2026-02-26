<?php
/**
 * ==============================================
 * MOBILE - LỊCH SỬ THI
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/device.php';

redirectIfDesktop(BASE_URL . '/student/dashboard.php');

if (!isStudentLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$student = getCurrentStudent();
$conn = getDBConnection();

// Lấy lịch sử thi
$stmt = $conn->prepare("
    SELECT bl.*, dt.ten_de, dt.so_cau, mh.ten_mon, mh.icon
    FROM bai_lam bl
    JOIN de_thi dt ON bl.de_thi_id = dt.id
    JOIN mon_hoc mh ON dt.mon_hoc_id = mh.id
    WHERE bl.hoc_sinh_id = ? AND bl.trang_thai = 'hoan_thanh'
    ORDER BY bl.thoi_gian_ket_thuc DESC
    LIMIT 50
");
$stmt->execute(array($student['id']));
$lichSuList = $stmt->fetchAll();

$pageTitle = 'Lịch sử thi';
$currentTab = 'exams';
include 'header.php';
?>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="page-header">
            <a href="index.php" class="back-btn">‹</a>
            <h1>📊 Lịch sử thi</h1>
        </div>
    </div>
</div>

<main class="main">
    <?php if (count($lichSuList) > 0): ?>
        <?php foreach ($lichSuList as $bl): ?>
        <?php
        $diem = $bl['diem'];
        $color = $diem >= 8 ? 'success' : ($diem >= 5 ? 'warning' : 'danger');
        ?>
        <a href="result.php?session=<?php echo $bl['session_token']; ?>" class="list-item">
            <div class="icon"><?php echo getSubjectIcon($bl['icon']); ?></div>
            <div class="content">
                <div class="title"><?php echo htmlspecialchars($bl['ten_de']); ?></div>
                <div class="subtitle">
                    <?php echo htmlspecialchars($bl['ten_mon']); ?>
                    • <?php echo date('d/m/Y H:i', strtotime($bl['thoi_gian_ket_thuc'])); ?>
                </div>
            </div>
            <div style="text-align: right;">
                <div class="text-<?php echo $color; ?> font-bold" style="font-size: 18px;"><?php echo $diem; ?></div>
                <div class="text-muted" style="font-size: 12px;">điểm</div>
            </div>
        </a>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <div class="title">Chưa có lịch sử thi</div>
            <div class="desc">Bạn chưa hoàn thành bài thi nào</div>
            <a href="exams.php" class="btn btn-primary mt-16">Làm bài thi ngay</a>
        </div>
    <?php endif; ?>
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
