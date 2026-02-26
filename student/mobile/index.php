<?php
/**
 * ==============================================
 * MOBILE DASHBOARD - TRANG CHỦ HỌC SINH
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/device.php';

// Redirect về desktop nếu không phải mobile
redirectIfDesktop(BASE_URL . '/student/dashboard.php');

// Kiểm tra đăng nhập
if (!isStudentLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$student = getCurrentStudent();
$conn = getDBConnection();

// Lấy thông tin lớp
$stmtLop = $conn->prepare("SELECT * FROM lop_hoc WHERE id = ?");
$stmtLop->execute(array($student['lop_id']));
$lop = $stmtLop->fetch();

// Lấy điểm xếp hạng
$stmtDiem = $conn->prepare("SELECT * FROM diem_tich_luy WHERE hoc_sinh_id = ?");
$stmtDiem->execute(array($student['id']));
$diemTichLuy = $stmtDiem->fetch();
$diemXH = $diemTichLuy ? $diemTichLuy['diem_xep_hang'] : 0;

// Lấy số bài thi đã làm
$stmtBaiLam = $conn->prepare("SELECT COUNT(*) as total FROM bai_lam WHERE hoc_sinh_id = ? AND trang_thai = 'hoan_thanh'");
$stmtBaiLam->execute(array($student['id']));
$soBaiLam = $stmtBaiLam->fetch()['total'];

// Lấy điểm trung bình
$stmtDTB = $conn->prepare("SELECT AVG(diem) as dtb FROM bai_lam WHERE hoc_sinh_id = ? AND trang_thai = 'hoan_thanh'");
$stmtDTB->execute(array($student['id']));
$dtb = $stmtDTB->fetch()['dtb'];
$dtb = $dtb ? round($dtb, 1) : 0;

// Lấy danh sách đề thi theo lớp
$stmtDeThi = $conn->prepare("
    SELECT dt.*, mh.ten_mon, mh.icon
    FROM de_thi dt
    JOIN mon_hoc mh ON dt.mon_hoc_id = mh.id
    WHERE dt.trang_thai = 1
    AND (dt.lop_id = ? OR dt.lop_id IS NULL)
    ORDER BY dt.is_chinh_thuc DESC, dt.thu_tu ASC, dt.created_at DESC
    LIMIT 6
");
$stmtDeThi->execute(array($student['lop_id']));
$deThiList = $stmtDeThi->fetchAll();

// Lấy kết quả gần đây
$stmtKQ = $conn->prepare("
    SELECT bl.*, dt.ten_de, mh.ten_mon, mh.icon
    FROM bai_lam bl
    JOIN de_thi dt ON bl.de_thi_id = dt.id
    JOIN mon_hoc mh ON dt.mon_hoc_id = mh.id
    WHERE bl.hoc_sinh_id = ? AND bl.trang_thai = 'hoan_thanh'
    ORDER BY bl.thoi_gian_ket_thuc DESC
    LIMIT 5
");
$stmtKQ->execute(array($student['id']));
$ketQuaList = $stmtKQ->fetchAll();

$pageTitle = 'Trang chủ';
$currentTab = 'home';
include 'header.php';
?>

<!-- Header với thông tin học sinh -->
<div class="header">
    <div class="header-content">
        <div class="header-left">
            <div class="header-avatar">
                <?php echo getStudentAvatar($student); ?>
            </div>
            <div class="header-info">
                <h1>Chào <?php echo htmlspecialchars($student['ho_ten']); ?>!</h1>
                <p><?php echo htmlspecialchars($lop['ten_lop']); ?></p>
            </div>
        </div>
        <div class="header-right">
            <div class="header-points">⭐ <?php echo number_format($diemXH); ?></div>
        </div>
    </div>
</div>

<main class="main">
    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-item">
            <div class="value"><?php echo $soBaiLam; ?></div>
            <div class="label">Bài đã thi</div>
        </div>
        <div class="stat-item success">
            <div class="value"><?php echo $dtb; ?></div>
            <div class="label">Điểm TB</div>
        </div>
        <div class="stat-item warning">
            <div class="value"><?php echo number_format($diemXH); ?></div>
            <div class="label">Điểm XH</div>
        </div>
    </div>

    <!-- Menu Grid -->
    <div class="menu-grid">
        <a href="exams.php" class="menu-item primary">
            <span class="icon">📝</span>
            <span class="label">Làm bài thi</span>
        </a>
        <a href="documents.php" class="menu-item">
            <span class="icon">📖</span>
            <span class="label">Tài liệu</span>
        </a>
        <a href="history.php" class="menu-item">
            <span class="icon">📊</span>
            <span class="label">Kết quả</span>
        </a>
        <a href="ranking.php" class="menu-item">
            <span class="icon">🏆</span>
            <span class="label">Xếp hạng</span>
        </a>
    </div>

    <!-- Đề thi mới -->
    <?php if (count($deThiList) > 0): ?>
    <div class="card mt-16">
        <div class="card-title">📝 Đề thi mới nhất</div>
        <?php foreach (array_slice($deThiList, 0, 3) as $deThi): ?>
        <a href="exam.php?id=<?php echo $deThi['id']; ?>" class="list-item">
            <div class="icon"><?php echo getSubjectIcon($deThi['icon']); ?></div>
            <div class="content">
                <div class="title"><?php echo htmlspecialchars($deThi['ten_de']); ?></div>
                <div class="subtitle"><?php echo htmlspecialchars($deThi['ten_mon']); ?> • <?php echo $deThi['so_cau']; ?> câu</div>
            </div>
            <div class="arrow">›</div>
        </a>
        <?php endforeach; ?>
        <a href="exams.php" class="btn btn-outline btn-block mt-16">Xem tất cả đề thi</a>
    </div>
    <?php endif; ?>

    <!-- Kết quả gần đây -->
    <?php if (count($ketQuaList) > 0): ?>
    <div class="card">
        <div class="card-title">📊 Kết quả gần đây</div>
        <?php foreach (array_slice($ketQuaList, 0, 3) as $kq): ?>
        <a href="result.php?session=<?php echo $kq['session_token']; ?>" class="list-item">
            <div class="icon"><?php echo getSubjectIcon($kq['icon']); ?></div>
            <div class="content">
                <div class="title"><?php echo htmlspecialchars($kq['ten_de']); ?></div>
                <div class="subtitle">
                    <?php
                    $diem = $kq['diem'];
                    $color = $diem >= 8 ? 'text-success' : ($diem >= 5 ? 'text-warning' : 'text-danger');
                    ?>
                    <span class="<?php echo $color; ?> font-bold"><?php echo $diem; ?>/10</span>
                    • <?php echo date('d/m', strtotime($kq['thoi_gian_ket_thuc'])); ?>
                </div>
            </div>
            <div class="arrow">›</div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Link xem desktop -->
    <div class="author-credit">
        <a href="<?php echo BASE_URL; ?>/student/dashboard.php?view=desktop" style="color: inherit; text-decoration: none;">
            💻 Xem phiên bản Desktop
        </a>
        <br><br>
        <strong>Tác giả:</strong> Trần Văn Phi Hoàng, Lê Quang Nguyên. GVHD: Đoàn Thị Ngọc Lĩnh
    </div>
</main>

<!-- Bottom Tab Bar -->
<nav class="tab-bar">
    <a href="index.php" class="tab-item active">
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
