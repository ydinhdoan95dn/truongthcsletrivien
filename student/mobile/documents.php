<?php
/**
 * ==============================================
 * MOBILE - TÀI LIỆU HỌC TẬP
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

// Lấy danh sách môn học
$stmtMon = $conn->query("SELECT * FROM mon_hoc WHERE trang_thai = 1 ORDER BY thu_tu");
$monList = $stmtMon->fetchAll();

// Lọc theo môn
$monFilter = isset($_GET['mon']) ? intval($_GET['mon']) : 0;

// Lấy tài liệu
$sql = "
    SELECT tl.*, mh.ten_mon, mh.icon, mh.mau_sac, lh.ten_lop
    FROM tai_lieu tl
    JOIN mon_hoc mh ON tl.mon_hoc_id = mh.id
    LEFT JOIN lop_hoc lh ON tl.lop_id = lh.id
    WHERE tl.is_public = 1 AND tl.trang_thai = 1
    AND (tl.lop_id = ? OR tl.lop_id IS NULL)
";

if ($monFilter > 0) {
    $sql .= " AND tl.mon_hoc_id = " . $monFilter;
}

$sql .= " ORDER BY tl.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute(array($student['lop_id']));
$taiLieuList = $stmt->fetchAll();

// Icon theo loại file
$fileIcons = array(
    'pdf' => '📄',
    'word' => '📝',
    'ppt' => '📊',
    'video' => '🎬',
    'image' => '🖼️',
    'youtube' => '🎬',
    'editor' => '📝'
);

$pageTitle = 'Tài liệu học tập';
$currentTab = 'documents';
include 'header.php';
?>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="page-header">
            <a href="index.php" class="back-btn">‹</a>
            <h1>📖 Tài liệu học tập</h1>
        </div>
    </div>
</div>

<main class="main">
    <!-- Filter Tabs -->
    <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 8px;">
        <a href="documents.php" class="btn <?php echo $monFilter == 0 ? 'btn-primary' : 'btn-outline'; ?>" style="white-space: nowrap; padding: 10px 16px; font-size: 14px;">
            Tất cả
        </a>
        <?php foreach ($monList as $mon): ?>
        <a href="documents.php?mon=<?php echo $mon['id']; ?>"
           class="btn <?php echo $monFilter == $mon['id'] ? 'btn-primary' : 'btn-outline'; ?>"
           style="white-space: nowrap; padding: 10px 16px; font-size: 14px;">
            <?php echo getSubjectIcon($mon['icon']); ?> <?php echo htmlspecialchars($mon['ten_mon']); ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Danh sách tài liệu -->
    <?php if (count($taiLieuList) > 0): ?>
        <?php foreach ($taiLieuList as $tl): ?>
        <?php
        // Xác định icon và loại nguồn
        $icon = '📁';
        $sourceType = '';
        if (!empty($tl['youtube_id'])) {
            $icon = '🎬';
            $sourceType = 'YouTube';
        } elseif (!empty($tl['google_drive_id'])) {
            $icon = isset($fileIcons[$tl['loai_file']]) ? $fileIcons[$tl['loai_file']] : '📄';
            $sourceType = 'G-Drive';
        } elseif ($tl['loai_file'] === 'editor') {
            $icon = '📝';
            $sourceType = 'Bài soạn';
        }

        // Xác định link xem
        $viewUrl = 'document-view.php?id=' . $tl['id'];
        ?>
        <a href="<?php echo $viewUrl; ?>" class="list-item">
            <div class="icon" style="background: <?php echo $tl['mau_sac'] ?: '#4F46E5'; ?>20; font-size: 28px;">
                <?php echo $icon; ?>
            </div>
            <div class="content">
                <div class="title"><?php echo htmlspecialchars($tl['tieu_de']); ?></div>
                <div class="subtitle">
                    <?php echo htmlspecialchars($tl['ten_mon']); ?>
                    <?php if ($tl['ten_lop']): ?>
                        • <?php echo htmlspecialchars($tl['ten_lop']); ?>
                    <?php endif; ?>
                    <?php if ($sourceType): ?>
                        • <?php echo $sourceType; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="arrow">›</div>
        </a>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <div class="title">Chưa có tài liệu</div>
            <div class="desc">Hiện tại chưa có tài liệu nào cho bạn</div>
        </div>
    <?php endif; ?>
</main>

<!-- Bottom Tab Bar -->
<nav class="tab-bar">
    <a href="index.php" class="tab-item">
        <span class="icon">🏠</span>
        <span class="label">Trang chủ</span>
    </a>
    <a href="exams.php" class="tab-item">
        <span class="icon">📝</span>
        <span class="label">Làm bài</span>
    </a>
    <a href="<?php echo BASE_URL; ?>/student/thidua/xep_hang.php" class="tab-item">
        <span class="icon">🏅</span>
        <span class="label">Thi đua</span>
    </a>
    <a href="documents.php" class="tab-item active">
        <span class="icon">📖</span>
        <span class="label">Tài liệu</span>
    </a>
    <a href="profile.php" class="tab-item">
        <span class="icon">👤</span>
        <span class="label">Tôi</span>
    </a>
</nav>

<?php include 'footer.php'; ?>
