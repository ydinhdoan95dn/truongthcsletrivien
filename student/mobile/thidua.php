<?php
/**
 * ==============================================
 * MOBILE - XẾP HẠNG THI ĐUA LỚP
 * Phiên bản mobile cho student/thidua/xep_hang.php
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/device.php';
require_once '../../includes/permission_helper.php';

redirectIfDesktop(BASE_URL . '/student/thidua/xep_hang.php');

if (!isStudentLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$student = getCurrentStudent();
$conn = getDBConnection();
$lop_id = $student['lop_id'];

// Lấy thông tin lớp
$stmtLop = $conn->prepare("SELECT * FROM lop_hoc WHERE id = ?");
$stmtLop->execute(array($lop_id));
$lopInfo = $stmtLop->fetch();

// Check Cờ đỏ
$laCoDo = isset($student['la_co_do']) ? intval($student['la_co_do']) : 0;

// Filter
$tuan_id = isset($_GET['tuan']) ? intval($_GET['tuan']) : 0;
$view_mode = isset($_GET['view']) ? sanitize($_GET['view']) : 'my_class';

// Lấy tuần hiện tại nếu không chọn
if ($tuan_id == 0) {
    $stmtCurrentTuan = $conn->query("
        SELECT id FROM tuan_hoc
        WHERE CURDATE() BETWEEN ngay_bat_dau AND ngay_ket_thuc
        LIMIT 1
    ");
    $currentTuan = $stmtCurrentTuan->fetch();
    $tuan_id = $currentTuan ? $currentTuan['id'] : 0;
}

// Thông tin tuần
$tuanInfo = null;
if ($tuan_id > 0) {
    $stmtTuan = $conn->prepare("SELECT * FROM tuan_hoc WHERE id = ?");
    $stmtTuan->execute(array($tuan_id));
    $tuanInfo = $stmtTuan->fetch();
}

// Danh sách tuần (10 tuần gần nhất)
$stmtDanhSachTuan = $conn->query("
    SELECT * FROM tuan_hoc
    WHERE trang_thai = 1
    ORDER BY ngay_bat_dau DESC
    LIMIT 10
");
$danhSachTuan = $stmtDanhSachTuan->fetchAll();

// Xếp hạng lớp mình
$xepHangLopMinh = null;
if ($tuan_id > 0) {
    $stmtMyRank = $conn->prepare("
        SELECT xh.*, lh.ten_lop, lh.khoi, lh.khoi_label
        FROM xep_hang_lop_tuan xh
        JOIN lop_hoc lh ON xh.lop_id = lh.id
        WHERE xh.lop_id = ? AND xh.tuan_id = ?
    ");
    $stmtMyRank->execute(array($lop_id, $tuan_id));
    $xepHangLopMinh = $stmtMyRank->fetch();
}

// Điểm chi tiết lớp mình
$chiTietDiem = array();
if ($tuan_id > 0) {
    $stmtChiTiet = $conn->prepare("
        SELECT dtd.*, tc.ten_tieu_chi, tc.ma_tieu_chi, tc.diem_toi_da, tc.trong_so
        FROM diem_thi_dua_tuan dtd
        JOIN tieu_chi_thi_dua tc ON dtd.tieu_chi_id = tc.id
        WHERE dtd.lop_id = ? AND dtd.tuan_id = ? AND dtd.trang_thai = 'da_duyet'
        ORDER BY tc.thu_tu
    ");
    $stmtChiTiet->execute(array($lop_id, $tuan_id));
    $chiTietDiem = $stmtChiTiet->fetchAll();
}

// Xếp hạng tất cả lớp cùng khối
$allRankings = array();
if ($view_mode === 'all_classes' && $tuan_id > 0) {
    $stmtAll = $conn->prepare("
        SELECT xh.*, lh.ten_lop, lh.khoi, lh.khoi_label
        FROM xep_hang_lop_tuan xh
        JOIN lop_hoc lh ON xh.lop_id = lh.id
        WHERE xh.tuan_id = ? AND lh.khoi = ?
        ORDER BY xh.thu_hang_cung_khoi ASC
    ");
    $stmtAll->execute(array($tuan_id, $lopInfo['khoi']));
    $allRankings = $stmtAll->fetchAll();
}

// Helper data
$criteriaEmojis = array(
    'hoc_tap' => '📚', 'ne_nep' => '📋', 've_sinh' => '🧹',
    'hoat_dong' => '🎯', 'doan_ket' => '🤝'
);
$criteriaColors = array(
    'hoc_tap' => '#4F46E5', 'ne_nep' => '#0D9488', 've_sinh' => '#0EA5E9',
    'hoat_dong' => '#F59E0B', 'doan_ket' => '#EF4444'
);

$pageTitle = 'Thi đua lớp';
$currentTab = 'thidua';
include 'header.php';
?>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="page-header">
            <a href="index.php" class="back-btn">‹</a>
            <h1>🏅 Thi đua lớp</h1>
        </div>
    </div>
</div>

<main class="main">
    <!-- Cờ đỏ Quick Action -->
    <?php if ($laCoDo): ?>
    <a href="thidua-cham-diem.php" style="display:flex; align-items:center; gap:12px; background:linear-gradient(135deg,#EF4444,#DC2626); color:white; border-radius:var(--radius); padding:14px 18px; margin-bottom:16px; text-decoration:none;">
        <span style="font-size:28px;">🚩</span>
        <div style="flex:1;">
            <div style="font-weight:700; font-size:15px;">Bạn là Cờ đỏ</div>
            <div style="font-size:12px; opacity:0.9;">Nhấn để chấm điểm lớp được phân công</div>
        </div>
        <span style="font-size:20px;">›</span>
    </a>
    <?php endif; ?>

    <!-- Filter tuần -->
    <div style="display:flex; gap:8px; margin-bottom:16px; overflow-x:auto; padding-bottom:4px;">
        <?php foreach (array_slice($danhSachTuan, 0, 5) as $tuan): ?>
        <a href="?tuan=<?php echo $tuan['id']; ?>&view=<?php echo $view_mode; ?>"
           style="padding:8px 14px; border-radius:20px; font-size:12px; font-weight:700; text-decoration:none; white-space:nowrap;
                  <?php echo $tuan_id == $tuan['id'] ? 'background:var(--primary); color:white;' : 'background:white; color:#666; border:1px solid var(--border);'; ?>">
            T<?php echo $tuan['so_tuan']; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- View mode toggle -->
    <div style="display:flex; gap:8px; margin-bottom:16px;">
        <a href="?tuan=<?php echo $tuan_id; ?>&view=my_class"
           style="flex:1; padding:10px; border-radius:12px; font-size:13px; font-weight:700; text-decoration:none; text-align:center;
                  <?php echo $view_mode !== 'all_classes' ? 'background:var(--primary); color:white;' : 'background:white; color:#666;'; ?>">
            🏫 Lớp tôi
        </a>
        <a href="?tuan=<?php echo $tuan_id; ?>&view=all_classes"
           style="flex:1; padding:10px; border-radius:12px; font-size:13px; font-weight:700; text-decoration:none; text-align:center;
                  <?php echo $view_mode === 'all_classes' ? 'background:var(--primary); color:white;' : 'background:white; color:#666;'; ?>">
            🏆 Cùng khối
        </a>
    </div>

    <?php if ($tuan_id == 0): ?>
        <div class="card" style="text-align:center; padding:40px 20px;">
            <div style="font-size:48px; margin-bottom:12px;">📅</div>
            <div style="font-weight:700; color:#666;">Chưa có tuần học nào</div>
        </div>
    <?php elseif ($view_mode !== 'all_classes'): ?>
        <!-- ==================== LỚP TÔI ==================== -->
        <?php if ($xepHangLopMinh): ?>
        <!-- Rank Card -->
        <div class="card" style="background:linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color:white; text-align:center; padding:24px;">
            <div style="font-size:14px; opacity:0.9; margin-bottom:4px;">
                <?php echo htmlspecialchars($xepHangLopMinh['ten_lop']); ?> - Tuần <?php echo $tuanInfo ? $tuanInfo['so_tuan'] : ''; ?>
            </div>
            <div style="font-size:48px; font-weight:800; line-height:1.1;">
                #<?php echo $xepHangLopMinh['thu_hang_toan_truong'] ? $xepHangLopMinh['thu_hang_toan_truong'] : '-'; ?>
            </div>
            <div style="font-size:13px; opacity:0.8; margin:4px 0 12px;">Toàn trường</div>
            <div style="display:flex; justify-content:center; gap:20px;">
                <div>
                    <div style="font-size:24px; font-weight:700;"><?php echo number_format($xepHangLopMinh['tong_diem_co_trong_so'], 1); ?></div>
                    <div style="font-size:11px; opacity:0.8;">Điểm / 100</div>
                </div>
                <div style="width:1px; background:rgba(255,255,255,0.3);"></div>
                <div>
                    <div style="font-size:24px; font-weight:700;">
                        #<?php echo $xepHangLopMinh['thu_hang_cung_khoi'] ? $xepHangLopMinh['thu_hang_cung_khoi'] : '-'; ?>
                    </div>
                    <div style="font-size:11px; opacity:0.8;">Cùng khối</div>
                </div>
                <div style="width:1px; background:rgba(255,255,255,0.3);"></div>
                <div>
                    <?php
                    $xepLoaiText = '';
                    $xl = $xepHangLopMinh['xep_loai'];
                    if ($xl === 'xuat_sac') $xepLoaiText = 'Xuất sắc';
                    elseif ($xl === 'tot') $xepLoaiText = 'Tốt';
                    elseif ($xl === 'kha') $xepLoaiText = 'Khá';
                    elseif ($xl === 'trung_binh') $xepLoaiText = 'TB';
                    else $xepLoaiText = 'Cần CG';
                    ?>
                    <div style="font-size:24px;">
                        <?php
                        if ($xl === 'xuat_sac') echo '⭐';
                        elseif ($xl === 'tot') echo '🌟';
                        elseif ($xl === 'kha') echo '👍';
                        elseif ($xl === 'trung_binh') echo '📊';
                        else echo '💪';
                        ?>
                    </div>
                    <div style="font-size:11px; opacity:0.8;"><?php echo $xepLoaiText; ?></div>
                </div>
            </div>
        </div>

        <!-- Chi tiết điểm -->
        <?php if (count($chiTietDiem) > 0): ?>
        <div class="card">
            <div class="card-title">📊 Chi tiết điểm</div>
            <?php foreach ($chiTietDiem as $ct):
                $ma = $ct['ma_tieu_chi'];
                $emoji = isset($criteriaEmojis[$ma]) ? $criteriaEmojis[$ma] : '📌';
                $color = isset($criteriaColors[$ma]) ? $criteriaColors[$ma] : '#6B7280';
                $pct = ($ct['diem_toi_da'] > 0) ? round(($ct['diem'] / $ct['diem_toi_da']) * 100) : 0;
            ?>
            <div style="display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--border);">
                <div style="font-size:24px;"><?php echo $emoji; ?></div>
                <div style="flex:1;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <span style="font-weight:700; font-size:14px;"><?php echo htmlspecialchars($ct['ten_tieu_chi']); ?></span>
                        <span style="font-weight:700; font-size:14px; color:<?php echo $color; ?>;">
                            <?php echo number_format($ct['diem'], 1); ?>/<?php echo intval($ct['diem_toi_da']); ?>
                        </span>
                    </div>
                    <div style="height:6px; background:#E5E7EB; border-radius:3px; overflow:hidden;">
                        <div style="height:100%; width:<?php echo $pct; ?>%; background:<?php echo $color; ?>; border-radius:3px;"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:4px;">
                        <span style="font-size:11px; color:var(--text-light);">Trọng số: <?php echo intval($ct['trong_so']); ?>%</span>
                        <span style="font-size:11px; font-weight:700; color:<?php echo $color; ?>;"><?php echo number_format($ct['diem_co_trong_so'], 1); ?> đ</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Chưa có điểm -->
        <div class="card" style="text-align:center; padding:40px 20px;">
            <div style="font-size:48px; margin-bottom:12px;">📭</div>
            <div style="font-weight:700; color:#666; margin-bottom:4px;">Chưa có xếp hạng</div>
            <div style="font-size:13px; color:var(--text-light);">
                <?php echo htmlspecialchars($lopInfo['ten_lop']); ?> - Tuần <?php echo $tuanInfo ? $tuanInfo['so_tuan'] : ''; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- ==================== CÙNG KHỐI ==================== -->
        <?php if (count($allRankings) > 0): ?>
        <div class="card">
            <div class="card-title">🏆 Xếp hạng Khối <?php echo htmlspecialchars($lopInfo['khoi']); ?> - Tuần <?php echo $tuanInfo ? $tuanInfo['so_tuan'] : ''; ?></div>
            <?php foreach ($allRankings as $rank):
                $isMyClass = ($rank['lop_id'] == $lop_id);
                $xl = $rank['xep_loai'];
                if ($xl === 'xuat_sac') $medal = '🥇';
                elseif ($xl === 'tot') $medal = '🥈';
                elseif ($xl === 'kha') $medal = '🥉';
                else $medal = '🎖️';
            ?>
            <div class="list-item" style="<?php echo $isMyClass ? 'background:rgba(79,70,229,0.08); border:2px solid var(--primary);' : ''; ?>">
                <div style="width:32px; text-align:center; font-size:18px; font-weight:800; color:var(--primary);">
                    <?php echo $rank['thu_hang_cung_khoi'] ? $rank['thu_hang_cung_khoi'] : '-'; ?>
                </div>
                <div style="font-size:24px;"><?php echo $medal; ?></div>
                <div class="content">
                    <div class="title">
                        <?php echo htmlspecialchars($rank['ten_lop']); ?>
                        <?php if ($isMyClass): ?>
                            <span style="background:var(--primary); color:white; padding:2px 6px; border-radius:8px; font-size:10px; margin-left:4px;">Lớp tôi</span>
                        <?php endif; ?>
                    </div>
                    <div class="subtitle"><?php echo number_format($rank['tong_diem_co_trong_so'], 1); ?>/100 điểm</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card" style="text-align:center; padding:40px 20px;">
            <div style="font-size:48px; margin-bottom:12px;">📭</div>
            <div style="font-weight:700; color:#666;">Chưa có dữ liệu xếp hạng khối này</div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Link desktop -->
    <div style="text-align:center; margin:24px 0; padding:16px;">
        <a href="<?php echo BASE_URL; ?>/student/thidua/xep_hang.php?view=desktop" style="color:var(--primary); font-weight:600; text-decoration:none;">
            💻 Xem phiên bản Desktop
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
    <a href="thidua.php" class="tab-item active">
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
