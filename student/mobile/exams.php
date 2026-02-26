<?php
/**
 * ==============================================
 * MOBILE - DANH SÁCH ĐỀ THI
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

// Lấy danh sách đề thi (ưu tiên chính thức lên đầu)
$sql = "
    SELECT dt.*, mh.ten_mon, mh.icon, mh.mau_sac,
           (SELECT COUNT(*) FROM bai_lam bl WHERE bl.de_thi_id = dt.id AND bl.hoc_sinh_id = ? AND bl.trang_thai = 'hoan_thanh') as da_lam
    FROM de_thi dt
    JOIN mon_hoc mh ON dt.mon_hoc_id = mh.id
    WHERE dt.trang_thai = 1
    AND (dt.lop_id = ? OR dt.lop_id IS NULL)
";

if ($monFilter > 0) {
    $sql .= " AND dt.mon_hoc_id = " . $monFilter;
}

$sql .= " ORDER BY dt.is_chinh_thuc DESC, dt.thu_tu ASC, dt.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute(array($student['id'], $student['lop_id']));
$deThiList = $stmt->fetchAll();

$pageTitle = 'Làm bài thi';
$currentTab = 'exams';
include 'header.php';
?>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="page-header">
            <a href="index.php" class="back-btn">‹</a>
            <h1>📝 Làm bài thi</h1>
        </div>
    </div>
</div>

<main class="main">
    <!-- Filter Tabs -->
    <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 8px;">
        <a href="exams.php" class="btn <?php echo $monFilter == 0 ? 'btn-primary' : 'btn-outline'; ?>" style="white-space: nowrap; padding: 10px 16px; font-size: 14px;">
            Tất cả
        </a>
        <?php foreach ($monList as $mon): ?>
        <a href="exams.php?mon=<?php echo $mon['id']; ?>"
           class="btn <?php echo $monFilter == $mon['id'] ? 'btn-primary' : 'btn-outline'; ?>"
           style="white-space: nowrap; padding: 10px 16px; font-size: 14px;">
            <?php echo getSubjectIcon($mon['icon']); ?> <?php echo htmlspecialchars($mon['ten_mon']); ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Danh sách đề thi -->
    <?php if (count($deThiList) > 0): ?>
        <?php foreach ($deThiList as $deThi):
            $isChinhThuc = isset($deThi['is_chinh_thuc']) && $deThi['is_chinh_thuc'] == 1;
        ?>
        <a href="exam.php?id=<?php echo $deThi['id']; ?>" class="list-item" style="position: relative;">
            <?php if ($isChinhThuc): ?>
            <div style="position: absolute; top: 4px; right: 4px; background: linear-gradient(135deg, #FFD700, #FFA500); color: #7B4F00; padding: 2px 8px; border-radius: 8px; font-size: 10px; font-weight: 700;">
                ⭐ CHÍNH THỨC
            </div>
            <?php else: ?>
            <div style="position: absolute; top: 4px; right: 4px; background: #4F46E5; color: white; padding: 2px 8px; border-radius: 8px; font-size: 10px; font-weight: 700;">
                📝 LUYỆN THI
            </div>
            <?php endif; ?>
            <div class="icon" style="background: <?php echo $deThi['mau_sac'] ?: '#4F46E5'; ?>20;">
                <?php echo getSubjectIcon($deThi['icon']); ?>
            </div>
            <div class="content">
                <div class="title"><?php echo htmlspecialchars($deThi['ten_de']); ?></div>
                <div class="subtitle">
                    <?php echo htmlspecialchars($deThi['ten_mon']); ?>
                    • <?php echo $deThi['so_cau']; ?> câu
                    • <?php echo $deThi['thoi_gian_cau']; ?>s/câu
                    <?php if ($deThi['da_lam'] > 0): ?>
                        <span class="text-success">• Đã làm <?php echo $deThi['da_lam']; ?> lần</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="arrow">›</div>
        </a>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <div class="title">Chưa có đề thi</div>
            <div class="desc">Hiện tại chưa có đề thi nào cho bạn</div>
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
