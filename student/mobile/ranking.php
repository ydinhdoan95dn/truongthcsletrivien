<?php
/**
 * ==============================================
 * MOBILE - BẢNG XẾP HẠNG & VINH DANH
 * Đồng bộ với phiên bản desktop (filter tuần/tháng/loại)
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/device.php';

redirectIfDesktop(BASE_URL . '/student/ranking.php');

if (!isStudentLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$student = getCurrentStudent();
$conn = getDBConnection();

// Filter parameters
$filterPeriod = isset($_GET['period']) ? $_GET['period'] : 'week';
$filterType = isset($_GET['type']) ? $_GET['type'] : 'chinh_thuc';

// Lấy tuần hiện tại
$today = date('Y-m-d');
$stmtTuan = $conn->prepare("SELECT * FROM tuan_hoc WHERE ? BETWEEN ngay_bat_dau AND ngay_ket_thuc LIMIT 1");
$stmtTuan->execute(array($today));
$currentWeek = $stmtTuan->fetch();

// Xác định khoảng thời gian
$dateFilter = '';
$periodLabel = 'Tất cả';
if ($filterPeriod === 'week' && $currentWeek) {
    $dateFilter = " AND bl.thoi_gian_ket_thuc >= '{$currentWeek['ngay_bat_dau']}' AND bl.thoi_gian_ket_thuc <= '{$currentWeek['ngay_ket_thuc']} 23:59:59'";
    $periodLabel = 'Tuần này';
} elseif ($filterPeriod === 'month') {
    $firstDayOfMonth = date('Y-m-01');
    $lastDayOfMonth = date('Y-m-t');
    $dateFilter = " AND bl.thoi_gian_ket_thuc >= '{$firstDayOfMonth}' AND bl.thoi_gian_ket_thuc <= '{$lastDayOfMonth} 23:59:59'";
    $periodLabel = 'Tháng ' . date('m');
}

// Xác định loại bài thi
$typeFilter = '';
$typeLabel = 'Tất cả';
if ($filterType === 'chinh_thuc') {
    $typeFilter = ' AND bl.is_chinh_thuc = 1';
    $typeLabel = 'Chính thức';
} elseif ($filterType === 'luyen_thi') {
    $typeFilter = ' AND (bl.is_chinh_thuc = 0 OR bl.is_chinh_thuc IS NULL)';
    $typeLabel = 'Luyện thi';
}

// Query xếp hạng
$stmtXH = $conn->query("
    SELECT
        hs.id,
        hs.ho_ten,
        hs.gioi_tinh,
        hs.chuoi_ngay_hoc,
        lh.ten_lop,
        COUNT(bl.id) as so_bai_thi,
        COALESCE(AVG(bl.diem), 0) as diem_trung_binh
    FROM hoc_sinh hs
    JOIN lop_hoc lh ON hs.lop_id = lh.id
    LEFT JOIN bai_lam bl ON hs.id = bl.hoc_sinh_id
        AND bl.trang_thai = 'hoan_thanh'{$dateFilter}{$typeFilter}
    WHERE hs.trang_thai = 1 AND lh.trang_thai = 1
    GROUP BY hs.id
    HAVING so_bai_thi > 0
    ORDER BY diem_trung_binh DESC, so_bai_thi DESC
    LIMIT 50
");
$xepHangList = $stmtXH->fetchAll();

// Tìm vị trí của học sinh hiện tại
$myRank = 0;
$myStats = null;
foreach ($xepHangList as $idx => $hs) {
    if ($hs['id'] == $student['id']) {
        $myRank = $idx + 1;
        $myStats = $hs;
        break;
    }
}

$pageTitle = 'Bảng xếp hạng';
$currentTab = 'home';
include 'header.php';
?>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="page-header">
            <a href="index.php" class="back-btn">‹</a>
            <h1>🏆 Bảng xếp hạng</h1>
        </div>
    </div>
</div>

<main class="main">
    <!-- Filter Tabs -->
    <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
        <a href="?period=week&type=<?php echo $filterType; ?>"
           style="padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none;
                  <?php echo $filterPeriod === 'week' ? 'background: var(--primary); color: white;' : 'background: #f3f4f6; color: #666;'; ?>">
            📅 Tuần
        </a>
        <a href="?period=month&type=<?php echo $filterType; ?>"
           style="padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none;
                  <?php echo $filterPeriod === 'month' ? 'background: var(--primary); color: white;' : 'background: #f3f4f6; color: #666;'; ?>">
            📆 Tháng
        </a>
        <a href="?period=all&type=<?php echo $filterType; ?>"
           style="padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none;
                  <?php echo $filterPeriod === 'all' ? 'background: var(--primary); color: white;' : 'background: #f3f4f6; color: #666;'; ?>">
            📊 Tổng
        </a>
    </div>

    <div style="display: flex; gap: 8px; margin-bottom: 16px;">
        <a href="?period=<?php echo $filterPeriod; ?>&type=chinh_thuc"
           style="padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none;
                  <?php echo $filterType === 'chinh_thuc' ? 'background: linear-gradient(135deg, #FFD700, #FFA500); color: #7B4F00;' : 'background: #f3f4f6; color: #666;'; ?>">
            ⭐ Chính thức
        </a>
        <a href="?period=<?php echo $filterPeriod; ?>&type=luyen_thi"
           style="padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none;
                  <?php echo $filterType === 'luyen_thi' ? 'background: var(--primary); color: white;' : 'background: #f3f4f6; color: #666;'; ?>">
            📝 Luyện thi
        </a>
        <a href="?period=<?php echo $filterPeriod; ?>&type=all"
           style="padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none;
                  <?php echo $filterType === 'all' ? 'background: var(--primary); color: white;' : 'background: #f3f4f6; color: #666;'; ?>">
            🔄 Tất cả
        </a>
    </div>

    <!-- My Rank Card -->
    <?php if ($myRank > 0 && $myStats): ?>
    <div class="card" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="font-size: 48px; width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <?php echo $student['gioi_tinh'] == 1 ? '👦' : '👧'; ?>
            </div>
            <div style="flex: 1;">
                <div style="font-size: 14px; opacity: 0.9;">Xếp hạng của bạn</div>
                <div style="font-size: 32px; font-weight: 700;">#<?php echo $myRank; ?></div>
                <div style="font-size: 12px; opacity: 0.8;">
                    ĐTB: <?php echo number_format($myStats['diem_trung_binh'], 1); ?> | <?php echo $myStats['so_bai_thi']; ?> bài
                </div>
            </div>
            <div style="font-size: 40px;">
                <?php
                if ($myRank == 1) echo '👑';
                elseif ($myRank == 2) echo '🥈';
                elseif ($myRank == 3) echo '🥉';
                else echo '🎖️';
                ?>
            </div>
        </div>
    </div>
    <?php elseif (count($xepHangList) == 0): ?>
    <div class="card" style="text-align: center; padding: 32px;">
        <div style="font-size: 48px; margin-bottom: 12px;">📭</div>
        <div style="color: #666;">Chưa có dữ liệu xếp hạng cho bộ lọc này</div>
    </div>
    <?php endif; ?>

    <!-- Top 3 -->
    <?php if (count($xepHangList) >= 3): ?>
    <div style="display: flex; justify-content: center; align-items: flex-end; gap: 12px; margin: 24px 0; padding: 16px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 16px;">
        <!-- Rank 2 -->
        <div style="text-align: center;">
            <div style="font-size: 32px; margin-bottom: 4px;"><?php echo $xepHangList[1]['gioi_tinh'] == 1 ? '👦' : '👧'; ?></div>
            <div style="font-size: 11px; font-weight: 600; color: white; max-width: 70px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <?php echo htmlspecialchars($xepHangList[1]['ho_ten']); ?>
            </div>
            <div style="background: #C0C0C0; color: white; padding: 4px 10px; border-radius: 10px; margin-top: 4px; font-weight: 700; font-size: 12px;">
                🥈 <?php echo number_format($xepHangList[1]['diem_trung_binh'], 1); ?>
            </div>
        </div>
        <!-- Rank 1 -->
        <div style="text-align: center; transform: translateY(-10px);">
            <div style="font-size: 44px; margin-bottom: 4px;"><?php echo $xepHangList[0]['gioi_tinh'] == 1 ? '👦' : '👧'; ?></div>
            <div style="font-size: 13px; font-weight: 700; color: white; max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <?php echo htmlspecialchars($xepHangList[0]['ho_ten']); ?>
            </div>
            <div style="background: linear-gradient(135deg, #FFD700, #FFA500); color: white; padding: 6px 14px; border-radius: 12px; margin-top: 4px; font-weight: 700; font-size: 14px;">
                🥇 <?php echo number_format($xepHangList[0]['diem_trung_binh'], 1); ?>
            </div>
        </div>
        <!-- Rank 3 -->
        <div style="text-align: center;">
            <div style="font-size: 32px; margin-bottom: 4px;"><?php echo $xepHangList[2]['gioi_tinh'] == 1 ? '👦' : '👧'; ?></div>
            <div style="font-size: 11px; font-weight: 600; color: white; max-width: 70px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <?php echo htmlspecialchars($xepHangList[2]['ho_ten']); ?>
            </div>
            <div style="background: #CD7F32; color: white; padding: 4px 10px; border-radius: 10px; margin-top: 4px; font-weight: 700; font-size: 12px;">
                🥉 <?php echo number_format($xepHangList[2]['diem_trung_binh'], 1); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Full List -->
    <?php if (count($xepHangList) > 3): ?>
    <div class="card">
        <div class="card-title">📊 Bảng xếp hạng (<?php echo count($xepHangList); ?> học sinh)</div>
        <?php
        $rank = 0;
        foreach ($xepHangList as $hs):
            $rank++;
            if ($rank <= 3) continue;
            $isMe = $hs['id'] == $student['id'];
        ?>
        <div class="list-item" style="<?php echo $isMe ? 'background: rgba(79,70,229,0.1); border: 2px solid var(--primary); border-radius: 12px;' : ''; ?>">
            <div style="width: 32px; text-align: center; font-weight: 700; color: <?php echo $rank <= 10 ? 'var(--primary)' : 'var(--text-light)'; ?>;">
                <?php echo $rank; ?>
            </div>
            <div style="font-size: 28px; margin-right: 4px;"><?php echo $hs['gioi_tinh'] == 1 ? '👦' : '👧'; ?></div>
            <div class="content">
                <div class="title">
                    <?php echo htmlspecialchars($hs['ho_ten']); ?>
                    <?php if ($isMe): ?><span style="background: var(--danger); color: white; padding: 2px 6px; border-radius: 8px; font-size: 10px; margin-left: 4px;">Bạn</span><?php endif; ?>
                </div>
                <div class="subtitle"><?php echo htmlspecialchars($hs['ten_lop']); ?> • <?php echo $hs['so_bai_thi']; ?> bài</div>
            </div>
            <div style="text-align: right;">
                <div class="text-primary font-bold"><?php echo number_format($hs['diem_trung_binh'], 1); ?></div>
                <div class="text-muted" style="font-size: 10px;">ĐTB</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Link to Desktop -->
    <div style="text-align: center; margin: 24px 0; padding: 16px;">
        <a href="<?php echo BASE_URL; ?>/student/ranking.php?view=desktop" style="color: var(--primary); font-weight: 600; text-decoration: none;">
            💻 Xem phiên bản Desktop đầy đủ
        </a>
    </div>
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
