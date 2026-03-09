<?php
/**
 * ==============================================
 * MOBILE - CHẤM ĐIỂM THI ĐUA (Cờ đỏ)
 * Phiên bản mobile cho student/thidua/cham_diem.php
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/device.php';
require_once '../../includes/permission_helper.php';

redirectIfDesktop(BASE_URL . '/student/thidua/cham_diem.php');

if (!isStudentLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$student = getCurrentStudent();
$conn = getDBConnection();

// Check Cờ đỏ
if ($student['la_co_do'] != 1) {
    $_SESSION['error'] = 'Chỉ học sinh Cờ đỏ mới được chấm điểm!';
    header('Location: index.php');
    exit;
}

// Lấy lớp được phân công chấm
$stmtPhanCong = $conn->prepare("
    SELECT pc.lop_duoc_cham_id, lh.ten_lop, lh.khoi, lh.khoi_label
    FROM phan_cong_cham_diem pc
    JOIN lop_hoc lh ON pc.lop_duoc_cham_id = lh.id
    WHERE pc.hoc_sinh_id = ? AND pc.trang_thai = 'active'
");
$stmtPhanCong->execute(array($student['id']));
$cacLopDuocCham = $stmtPhanCong->fetchAll();

if (count($cacLopDuocCham) == 0) {
    $_SESSION['error'] = 'Bạn chưa được phân công chấm lớp nào!';
    header('Location: thidua.php');
    exit;
}

// Filter
$lop_cham_id = isset($_GET['lop']) ? intval($_GET['lop']) : $cacLopDuocCham[0]['lop_duoc_cham_id'];
$tuan_id = isset($_GET['tuan']) ? intval($_GET['tuan']) : 0;

// Validate lớp
$allowed = false;
foreach ($cacLopDuocCham as $lop) {
    if ($lop['lop_duoc_cham_id'] == $lop_cham_id) {
        $allowed = true;
        break;
    }
}
if (!$allowed) {
    $_SESSION['error'] = 'Bạn không có quyền chấm lớp này!';
    header('Location: thidua-cham-diem.php');
    exit;
}

// Tuần hiện tại
if ($tuan_id == 0) {
    $stmtCurrentTuan = $conn->query("
        SELECT id FROM tuan_hoc
        WHERE CURDATE() BETWEEN ngay_bat_dau AND ngay_ket_thuc
        LIMIT 1
    ");
    $currentTuan = $stmtCurrentTuan->fetch();
    $tuan_id = $currentTuan ? $currentTuan['id'] : 0;
}

// Lấy info
$stmtLopCham = $conn->prepare("SELECT * FROM lop_hoc WHERE id = ?");
$stmtLopCham->execute(array($lop_cham_id));
$lopChamInfo = $stmtLopCham->fetch();

$tuanInfo = null;
if ($tuan_id > 0) {
    $stmtTuan = $conn->prepare("SELECT * FROM tuan_hoc WHERE id = ?");
    $stmtTuan->execute(array($tuan_id));
    $tuanInfo = $stmtTuan->fetch();
}

$stmtDanhSachTuan = $conn->query("
    SELECT * FROM tuan_hoc WHERE trang_thai = 1
    ORDER BY ngay_bat_dau DESC LIMIT 5
");
$danhSachTuan = $stmtDanhSachTuan->fetchAll();

// Tiêu chí
$stmtTieuChi = $conn->query("
    SELECT * FROM tieu_chi_thi_dua WHERE trang_thai = 1 ORDER BY thu_tu ASC
");
$cacTieuChi = $stmtTieuChi->fetchAll();

// Điểm đã chấm
$diemDaCham = array();
if ($tuan_id > 0) {
    $stmtDiem = $conn->prepare("
        SELECT * FROM diem_thi_dua_tuan
        WHERE lop_id = ? AND tuan_id = ? AND nguoi_cham = ?
    ");
    $stmtDiem->execute(array($lop_cham_id, $tuan_id, $student['id']));
    $diemRows = $stmtDiem->fetchAll();
    foreach ($diemRows as $row) {
        $diemDaCham[$row['tieu_chi_id']] = $row;
    }
}

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']);
    try {
        $conn->beginTransaction();
        foreach ($cacTieuChi as $tc) {
            $tieu_chi_id = $tc['id'];
            $diem = isset($_POST['diem_' . $tieu_chi_id]) ? floatval($_POST['diem_' . $tieu_chi_id]) : 0;
            $ghi_chu = isset($_POST['ghi_chu_' . $tieu_chi_id]) ? sanitize($_POST['ghi_chu_' . $tieu_chi_id]) : '';

            if ($diem < 0 || $diem > $tc['diem_toi_da']) {
                throw new Exception("Điểm {$tc['ten_tieu_chi']} không hợp lệ (0-{$tc['diem_toi_da']})");
            }

            $diemCoTrongSo = round(($diem / $tc['diem_toi_da']) * $tc['trong_so'], 2);
            $trangThai = ($action === 'gui_duyet') ? 'cho_duyet' : 'nhap';

            if (isset($diemDaCham[$tieu_chi_id])) {
                $stmtUpdate = $conn->prepare("
                    UPDATE diem_thi_dua_tuan
                    SET diem = ?, diem_co_trong_so = ?, ghi_chu = ?, trang_thai = ?,
                        cham_luc = NOW(), gui_tong_hop_luc = " . ($action === 'gui_duyet' ? 'NOW()' : 'NULL') . "
                    WHERE id = ?
                ");
                $stmtUpdate->execute(array($diem, $diemCoTrongSo, $ghi_chu, $trangThai, $diemDaCham[$tieu_chi_id]['id']));
            } else {
                $stmtInsert = $conn->prepare("
                    INSERT INTO diem_thi_dua_tuan
                    (lop_id, tieu_chi_id, tuan_id, diem, diem_co_trong_so,
                     nguoi_cham, loai_nguoi_cham, ghi_chu, trang_thai,
                     cham_luc, gui_tong_hop_luc)
                    VALUES (?, ?, ?, ?, ?, ?, 'hoc_sinh', ?, ?, NOW(), " . ($action === 'gui_duyet' ? 'NOW()' : 'NULL') . ")
                ");
                $stmtInsert->execute(array(
                    $lop_cham_id, $tieu_chi_id, $tuan_id,
                    $diem, $diemCoTrongSo, $student['id'], $ghi_chu, $trangThai
                ));
            }
        }

        logThiduaActivity(
            $action === 'gui_duyet' ? 'gui_duyet_diem' : 'luu_tam_diem',
            $student['id'], 'hoc_sinh',
            "Cờ đỏ {$student['ho_ten']} " . ($action === 'gui_duyet' ? 'gửi duyệt' : 'lưu tạm') . " điểm lớp {$lopChamInfo['ten_lop']}",
            $lop_cham_id, 'lop_hoc'
        );

        $conn->commit();
        $_SESSION['success'] = ($action === 'gui_duyet')
            ? 'Gửi điểm thành công! Chờ giáo viên duyệt.'
            : 'Lưu tạm thành công!';
        header("Location: thidua-cham-diem.php?lop={$lop_cham_id}&tuan={$tuan_id}");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
    }
}

// Pre-calculate locked state
$hasChoduyet = false;
$hasTuChoi = false;
foreach ($diemDaCham as $d) {
    if ($d['trang_thai'] === 'cho_duyet' || $d['trang_thai'] === 'da_duyet') {
        $hasChoduyet = true;
        break;
    }
    if ($d['trang_thai'] === 'tu_choi') {
        $hasTuChoi = true;
    }
}

$criteriaEmojis = array(
    'hoc_tap' => '📚', 'ne_nep' => '📋', 've_sinh' => '🧹',
    'hoat_dong' => '🎯', 'doan_ket' => '🤝'
);
$criteriaColors = array(
    'hoc_tap' => '#4F46E5', 'ne_nep' => '#0D9488', 've_sinh' => '#0EA5E9',
    'hoat_dong' => '#F59E0B', 'doan_ket' => '#EF4444'
);

$pageTitle = 'Chấm điểm thi đua';
$currentTab = 'thidua';

$extraStyles = '<style>
    .score-card {
        background: var(--card); border-radius: var(--radius);
        box-shadow: var(--shadow); margin-bottom: 12px; overflow: hidden;
    }
    .score-card-stripe { height: 4px; }
    .score-card-body { padding: 16px; }
    .score-card-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 12px;
    }
    .score-card-header .left { display: flex; align-items: center; gap: 10px; }
    .score-card-header .emoji { font-size: 28px; }
    .score-card-header .name { font-weight: 700; font-size: 15px; }
    .score-card-header .weight { font-size: 11px; color: var(--text-light); }
    .score-card-header .badge {
        padding: 3px 10px; border-radius: 20px;
        font-size: 10px; font-weight: 700; white-space: nowrap;
    }
    .score-display {
        text-align: center; margin-bottom: 8px;
    }
    .score-display .big { font-size: 2.5rem; font-weight: 800; line-height: 1; }
    .score-display .max { font-size: 1rem; color: var(--text-light); }
    .score-slider {
        -webkit-appearance: none; width: 100%; height: 8px;
        border-radius: 4px; outline: none; background: #e9ecef;
    }
    .score-slider::-webkit-slider-thumb {
        -webkit-appearance: none; width: 28px; height: 28px;
        border-radius: 50%; cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2); border: 3px solid #fff;
    }
    .score-slider:disabled { opacity: 0.5; }
    .weighted-row {
        display: flex; align-items: center; gap: 8px; margin-top: 8px;
    }
    .weighted-bar {
        flex: 1; height: 6px; background: #e9ecef; border-radius: 3px; overflow: hidden;
    }
    .weighted-bar-fill { height: 100%; border-radius: 3px; transition: width 0.3s; }
    .weighted-label { font-size: 12px; font-weight: 700; color: #555; white-space: nowrap; }
    .note-input {
        width: 100%; padding: 8px 12px; border: 1px solid var(--border);
        border-radius: 10px; font-family: inherit; font-size: 13px;
        margin-top: 8px; resize: none; display: none;
    }
    .note-input.show { display: block; }
    .note-input:disabled { background: #f8f9fa; }
    .note-toggle {
        font-size: 12px; color: var(--text-light); margin-top: 8px;
        cursor: pointer; display: inline-flex; align-items: center; gap: 4px;
    }

    .action-sticky {
        position: sticky; bottom: 76px; z-index: 50;
        background: var(--card); border-radius: var(--radius);
        box-shadow: 0 -2px 20px rgba(0,0,0,0.1);
        padding: 16px; text-align: center;
    }
    .total-row {
        display: flex; align-items: center; justify-content: center;
        gap: 8px; margin-bottom: 12px;
    }
    .total-row .label { font-size: 14px; color: var(--text-light); font-weight: 600; }
    .total-row .value { font-size: 1.8rem; font-weight: 800; color: var(--primary); }
    .total-row .max { font-size: 14px; color: var(--text-light); }
    .btn-row { display: flex; gap: 10px; }
    .btn-row .btn { flex: 1; }
</style>';

include 'header.php';
?>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="page-header">
            <a href="thidua.php" class="back-btn">‹</a>
            <h1>🚩 Chấm điểm</h1>
        </div>
    </div>
</div>

<main class="main">
    <!-- Cờ đỏ info -->
    <div style="background:linear-gradient(135deg,#EF4444,#DC2626); color:white; border-radius:var(--radius); padding:12px 16px; margin-bottom:12px; font-size:13px;">
        <strong>Cờ đỏ:</strong> <?php echo htmlspecialchars($student['ho_ten']); ?> |
        <strong>Chấm:</strong> <?php echo htmlspecialchars($lopChamInfo['ten_lop']); ?>
    </div>

    <!-- Filter -->
    <form method="GET" action="" style="display:flex; gap:8px; margin-bottom:12px;">
        <select name="lop" onchange="this.form.submit()" style="flex:1; padding:10px 12px; border:1px solid var(--border); border-radius:12px; font-family:inherit; font-size:13px; font-weight:600; background:white;">
            <?php foreach ($cacLopDuocCham as $l): ?>
            <option value="<?php echo $l['lop_duoc_cham_id']; ?>" <?php echo $lop_cham_id == $l['lop_duoc_cham_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($l['ten_lop']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="tuan" onchange="this.form.submit()" style="flex:1; padding:10px 12px; border:1px solid var(--border); border-radius:12px; font-family:inherit; font-size:13px; font-weight:600; background:white;">
            <option value="">-- Tuần --</option>
            <?php foreach ($danhSachTuan as $tuan): ?>
            <option value="<?php echo $tuan['id']; ?>" <?php echo $tuan_id == $tuan['id'] ? 'selected' : ''; ?>>
                T<?php echo $tuan['so_tuan']; ?> (<?php echo date('d/m', strtotime($tuan['ngay_bat_dau'])); ?>-<?php echo date('d/m', strtotime($tuan['ngay_ket_thuc'])); ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Flash messages -->
    <?php if (isset($_SESSION['success'])): ?>
    <div style="padding:12px 16px; background:#D1FAE5; color:#065F46; border-radius:12px; margin-bottom:12px; font-size:13px; font-weight:600;">
        ✅ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
    <div style="padding:12px 16px; background:#FEE2E2; color:#991B1B; border-radius:12px; margin-bottom:12px; font-size:13px; font-weight:600;">
        ❌ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
    <?php endif; ?>

    <?php if ($hasTuChoi): ?>
    <div style="padding:12px 16px; background:#FEF3C7; color:#92400E; border-radius:12px; margin-bottom:12px; font-size:13px; font-weight:600;">
        ⚠️ Điểm bị từ chối, vui lòng chấm lại!
    </div>
    <?php endif; ?>

    <?php if ($tuanInfo): ?>
    <!-- Scoring Form -->
    <form method="POST" action="" id="formChamDiem">
        <?php foreach ($cacTieuChi as $tc):
            $tcId = $tc['id'];
            $ma = $tc['ma_tieu_chi'];
            $emoji = isset($criteriaEmojis[$ma]) ? $criteriaEmojis[$ma] : '📌';
            $color = isset($criteriaColors[$ma]) ? $criteriaColors[$ma] : '#6B7280';
            $diemHienTai = isset($diemDaCham[$tcId]) ? floatval($diemDaCham[$tcId]['diem']) : 0;
            $ghiChu = isset($diemDaCham[$tcId]) ? $diemDaCham[$tcId]['ghi_chu'] : '';
            $trangThai = isset($diemDaCham[$tcId]) ? $diemDaCham[$tcId]['trang_thai'] : '';
            $isLocked = ($trangThai === 'cho_duyet' || $trangThai === 'da_duyet');
            if ($trangThai === 'tu_choi') $isLocked = false;
            $diemMax = floatval($tc['diem_toi_da']);
            $trongSo = floatval($tc['trong_so']);
            $weightedVal = ($diemMax > 0) ? round(($diemHienTai / $diemMax) * $trongSo, 1) : 0;
            $pct = ($diemMax > 0) ? round(($diemHienTai / $diemMax) * 100) : 0;
        ?>
        <div class="score-card">
            <div class="score-card-stripe" style="background:<?php echo $color; ?>;"></div>
            <div class="score-card-body">
                <div class="score-card-header">
                    <div class="left">
                        <span class="emoji"><?php echo $emoji; ?></span>
                        <div>
                            <div class="name"><?php echo htmlspecialchars($tc['ten_tieu_chi']); ?></div>
                            <div class="weight">Trọng số: <?php echo intval($trongSo); ?>%</div>
                        </div>
                    </div>
                    <?php if ($trangThai === 'tu_choi'): ?>
                        <span class="badge" style="background:#FEE2E2; color:#DC2626;">Từ chối</span>
                    <?php elseif ($trangThai === 'cho_duyet'): ?>
                        <span class="badge" style="background:#FEF3C7; color:#D97706;">Chờ duyệt</span>
                    <?php elseif ($trangThai === 'da_duyet'): ?>
                        <span class="badge" style="background:#D1FAE5; color:#059669;">Đã duyệt</span>
                    <?php endif; ?>
                </div>

                <div class="score-display">
                    <span class="big" id="scoreVal_<?php echo $tcId; ?>" style="color:<?php echo $color; ?>;"><?php echo number_format($diemHienTai, 1); ?></span>
                    <span class="max"> / <?php echo intval($diemMax); ?></span>
                </div>

                <input type="range" class="score-slider"
                       id="slider_<?php echo $tcId; ?>"
                       min="0" max="<?php echo $diemMax; ?>" step="0.5"
                       value="<?php echo $diemHienTai; ?>"
                       oninput="updateScore(<?php echo $tcId; ?>, this.value)"
                       style="background:linear-gradient(to right, <?php echo $color; ?> <?php echo $pct; ?>%, #e9ecef <?php echo $pct; ?>%);"
                       <?php echo $isLocked ? 'disabled' : ''; ?>>
                <input type="hidden" name="diem_<?php echo $tcId; ?>" id="diemInput_<?php echo $tcId; ?>" value="<?php echo $diemHienTai; ?>">

                <div class="weighted-row">
                    <div class="weighted-bar">
                        <div class="weighted-bar-fill" id="weightedBar_<?php echo $tcId; ?>"
                             style="width:<?php echo $pct; ?>%; background:<?php echo $color; ?>;"></div>
                    </div>
                    <span class="weighted-label" id="weightedVal_<?php echo $tcId; ?>"><?php echo number_format($weightedVal, 1); ?>/<?php echo intval($trongSo); ?></span>
                </div>

                <div class="note-toggle" onclick="toggleNote(<?php echo $tcId; ?>)">💬 Ghi chú</div>
                <textarea class="note-input <?php echo $ghiChu ? 'show' : ''; ?>"
                          id="noteArea_<?php echo $tcId; ?>"
                          name="ghi_chu_<?php echo $tcId; ?>"
                          rows="2" placeholder="Nhận xét..."
                          <?php echo $isLocked ? 'disabled' : ''; ?>><?php echo htmlspecialchars($ghiChu); ?></textarea>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Action bar -->
        <div class="action-sticky">
            <div class="total-row">
                <span class="label">Tổng:</span>
                <span class="value" id="totalScore">0.0</span>
                <span class="max">/ 100</span>
            </div>
            <?php if (!$hasChoduyet || $hasTuChoi): ?>
            <div class="btn-row">
                <button type="submit" name="action" value="luu_tam" class="btn btn-outline">
                    💾 Lưu tạm
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmSubmit()">
                    📤 Gửi duyệt
                </button>
            </div>
            <?php else: ?>
            <div style="color:var(--text-light); font-size:13px; font-weight:600;">
                🔒 Điểm đã gửi duyệt
            </div>
            <a href="thidua.php" class="btn btn-primary btn-block mt-16" style="margin-top:12px;">
                🏆 Xem xếp hạng
            </a>
            <?php endif; ?>
        </div>
    </form>

    <?php else: ?>
    <div class="card" style="text-align:center; padding:40px 20px;">
        <div style="font-size:48px; margin-bottom:12px;">📅</div>
        <div style="font-weight:700; color:#666;">Chọn lớp và tuần để chấm điểm</div>
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

<script>
var CRITERIA = <?php echo json_encode(array_map(function($tc) {
    return array(
        'id' => $tc['id'],
        'ma' => $tc['ma_tieu_chi'],
        'diem_toi_da' => floatval($tc['diem_toi_da']),
        'trong_so' => floatval($tc['trong_so'])
    );
}, $cacTieuChi)); ?>;

var CRITERIA_COLORS = {
    'hoc_tap': '#4F46E5', 'ne_nep': '#0D9488', 've_sinh': '#0EA5E9',
    'hoat_dong': '#F59E0B', 'doan_ket': '#EF4444'
};

var CRITERIA_MAP = {};
for (var i = 0; i < CRITERIA.length; i++) {
    CRITERIA_MAP[CRITERIA[i].id] = CRITERIA[i];
}

function updateScore(tcId, value) {
    var val = parseFloat(value) || 0;
    var tc = CRITERIA_MAP[tcId];
    if (!tc) return;
    var max = tc.diem_toi_da;
    var weight = tc.trong_so;
    var pct = (max > 0) ? (val / max) * 100 : 0;
    var weighted = (max > 0) ? (val / max) * weight : 0;
    var color = CRITERIA_COLORS[tc.ma] || '#6B7280';

    document.getElementById('scoreVal_' + tcId).textContent = val.toFixed(1);
    document.getElementById('diemInput_' + tcId).value = val;
    document.getElementById('weightedVal_' + tcId).textContent = weighted.toFixed(1) + '/' + Math.round(weight);
    document.getElementById('weightedBar_' + tcId).style.width = pct + '%';

    var slider = document.getElementById('slider_' + tcId);
    slider.style.background = 'linear-gradient(to right, ' + color + ' ' + pct + '%, #e9ecef ' + pct + '%)';

    recalculateTotal();
}

function recalculateTotal() {
    var total = 0;
    for (var i = 0; i < CRITERIA.length; i++) {
        var tc = CRITERIA[i];
        var input = document.getElementById('diemInput_' + tc.id);
        if (input) {
            var val = parseFloat(input.value) || 0;
            total += (tc.diem_toi_da > 0) ? (val / tc.diem_toi_da) * tc.trong_so : 0;
        }
    }
    document.getElementById('totalScore').textContent = total.toFixed(1);
}

function toggleNote(tcId) {
    var area = document.getElementById('noteArea_' + tcId);
    if (area.classList.contains('show')) {
        area.classList.remove('show');
    } else {
        area.classList.add('show');
    }
}

function confirmSubmit() {
    if (confirm('Sau khi gửi, bạn không thể chỉnh sửa nữa!\n\nXác nhận gửi duyệt?')) {
        var form = document.getElementById('formChamDiem');
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'action';
        input.value = 'gui_duyet';
        form.appendChild(input);
        form.submit();
    }
}

// Init
recalculateTotal();
</script>

<?php include 'footer.php'; ?>
