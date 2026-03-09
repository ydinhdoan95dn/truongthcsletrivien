<?php
/**
 * ==============================================
 * ADMIN CHẤM / SỬA ĐIỂM THI ĐUA
 * Module: Admin - Hệ thống Thi đua
 *
 * Chức năng:
 * - Admin chấm điểm trực tiếp cho bất kỳ lớp nào
 * - Sửa điểm đã chấm (kể cả đã duyệt)
 * - Duyệt luôn khi chấm
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/permission_helper.php';
require_once '../../includes/thidua_helper.php';

requireTongPhuTrach();

define('PAGE_TITLE', 'Chấm / Sửa điểm thi đua');

$conn = getDBConnection();
$admin = getCurrentAdmin();
$isAdminRole = isAdmin();

// ============================================================
// FILTER
// ============================================================
$lop_id = isset($_GET['lop']) ? intval($_GET['lop']) : 0;
$tuan_id = isset($_GET['tuan']) ? intval($_GET['tuan']) : 0;

// Lấy tuần hiện tại nếu chưa chọn
if ($tuan_id == 0) {
    $stmtCurrent = $conn->query("SELECT id FROM tuan_hoc WHERE CURDATE() BETWEEN ngay_bat_dau AND ngay_ket_thuc LIMIT 1");
    $current = $stmtCurrent->fetch();
    if ($current) $tuan_id = $current['id'];
}

// Danh sách lớp
$stmtLopList = $conn->query("SELECT id, ten_lop, khoi FROM lop_hoc WHERE trang_thai = 1 ORDER BY thu_tu, ten_lop");
$danhSachLop = $stmtLopList->fetchAll();

// Danh sách tuần
$stmtTuanList = $conn->query("SELECT * FROM tuan_hoc ORDER BY ngay_bat_dau DESC LIMIT 20");
$danhSachTuan = $stmtTuanList->fetchAll();

// Lấy tiêu chí
$stmtTieuChi = $conn->query("SELECT * FROM tieu_chi_thi_dua WHERE trang_thai = 1 ORDER BY thu_tu ASC");
$cacTieuChi = $stmtTieuChi->fetchAll();

// Thông tin lớp và tuần
$lopInfo = null;
$tuanInfo = null;
$diemHienTai = array();

if ($lop_id > 0) {
    $stmtLop = $conn->prepare("SELECT * FROM lop_hoc WHERE id = ?");
    $stmtLop->execute(array($lop_id));
    $lopInfo = $stmtLop->fetch();
}

if ($tuan_id > 0) {
    $stmtTuan = $conn->prepare("SELECT * FROM tuan_hoc WHERE id = ?");
    $stmtTuan->execute(array($tuan_id));
    $tuanInfo = $stmtTuan->fetch();
}

// Lấy điểm hiện tại (nếu có)
if ($lop_id > 0 && $tuan_id > 0) {
    $stmtDiem = $conn->prepare("SELECT * FROM diem_thi_dua_tuan WHERE lop_id = ? AND tuan_id = ?");
    $stmtDiem->execute(array($lop_id, $tuan_id));
    $diemRows = $stmtDiem->fetchAll();
    foreach ($diemRows as $row) {
        $diemHienTai[$row['tieu_chi_id']] = $row;
    }
}

// ============================================================
// HANDLE SUBMIT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']);
    $postLop = intval($_POST['lop_id']);
    $postTuan = intval($_POST['tuan_id']);
    $trangThaiMoi = ($action === 'cham_va_duyet') ? 'da_duyet' : 'cho_duyet';

    try {
        $conn->beginTransaction();

        foreach ($cacTieuChi as $tc) {
            $tcId = $tc['id'];
            $diem = isset($_POST['diem_' . $tcId]) ? floatval($_POST['diem_' . $tcId]) : 0;
            $ghiChu = isset($_POST['ghi_chu_' . $tcId]) ? sanitize($_POST['ghi_chu_' . $tcId]) : '';

            // Validate
            if ($diem < 0 || $diem > $tc['diem_toi_da']) {
                throw new Exception("Điểm {$tc['ten_tieu_chi']} không hợp lệ (0-{$tc['diem_toi_da']})");
            }

            $diemCoTrongSo = round(($diem / $tc['diem_toi_da']) * $tc['trong_so'], 2);

            // Check existing
            $stmtCheck = $conn->prepare("SELECT id FROM diem_thi_dua_tuan WHERE lop_id = ? AND tuan_id = ? AND tieu_chi_id = ?");
            $stmtCheck->execute(array($postLop, $postTuan, $tcId));
            $existing = $stmtCheck->fetch();

            if ($existing) {
                $stmtUpdate = $conn->prepare("
                    UPDATE diem_thi_dua_tuan
                    SET diem = ?, diem_co_trong_so = ?, ghi_chu = ?, trang_thai = ?,
                        nguoi_cham = ?, loai_nguoi_cham = 'admin', cham_luc = NOW(),
                        nguoi_duyet = ?, ngay_duyet = CURDATE(), duyet_luc = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute(array(
                    $diem, $diemCoTrongSo, $ghiChu, $trangThaiMoi,
                    $admin['id'],
                    ($trangThaiMoi === 'da_duyet') ? $admin['id'] : null,
                    $existing['id']
                ));
            } else {
                $stmtInsert = $conn->prepare("
                    INSERT INTO diem_thi_dua_tuan
                    (lop_id, tieu_chi_id, tuan_id, diem, diem_co_trong_so,
                     nguoi_cham, loai_nguoi_cham, ghi_chu, trang_thai,
                     cham_luc, nguoi_duyet, ngay_duyet, duyet_luc)
                    VALUES (?, ?, ?, ?, ?, ?, 'admin', ?, ?, NOW(), ?, CURDATE(), " .
                    ($trangThaiMoi === 'da_duyet' ? 'NOW()' : 'NULL') . ")
                ");
                $stmtInsert->execute(array(
                    $postLop, $tcId, $postTuan, $diem, $diemCoTrongSo,
                    $admin['id'], $ghiChu, $trangThaiMoi,
                    ($trangThaiMoi === 'da_duyet') ? $admin['id'] : null
                ));
            }
        }

        // Log
        $stmtLopName = $conn->prepare("SELECT ten_lop FROM lop_hoc WHERE id = ?");
        $stmtLopName->execute(array($postLop));
        $lopName = $stmtLopName->fetchColumn();

        logThiduaActivity(
            'admin_cham_diem',
            $admin['id'],
            'admin',
            "Admin chấm điểm lớp {$lopName} - Tuần #{$postTuan} ({$action})",
            $postLop,
            'lop_hoc'
        );

        $conn->commit();

        // Nếu duyệt luôn => tính xếp hạng
        if ($trangThaiMoi === 'da_duyet') {
            $rankResult = tinhToanXepHangTuan($postTuan);
            $_SESSION['success'] = "Chấm + duyệt điểm lớp {$lopName} thành công! Xếp hạng đã cập nhật.";
        } else {
            $_SESSION['success'] = "Lưu điểm lớp {$lopName} thành công (chờ duyệt).";
        }

        header("Location: cham_diem.php?lop={$postLop}&tuan={$postTuan}");
        exit;

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
    }
}

// Criteria metadata
$criteriaIcons = array(
    'hoc_tap' => 'fa-book', 'ne_nep' => 'fa-user-check',
    've_sinh' => 'fa-broom', 'hoat_dong' => 'fa-users', 'doan_ket' => 'fa-handshake'
);
$criteriaColors = array(
    'hoc_tap' => '#4F46E5', 'ne_nep' => '#0D9488',
    've_sinh' => '#0EA5E9', 'hoat_dong' => '#F59E0B', 'doan_ket' => '#EF4444'
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        .filter-bar { background: #f8f9fa; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem; }
        .score-card {
            background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;
            padding: 20px; margin-bottom: 16px; transition: box-shadow 0.2s;
        }
        .score-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .score-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .score-icon {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 18px; flex-shrink: 0;
        }
        .score-title { font-weight: 700; font-size: 15px; }
        .score-weight { font-size: 12px; color: #6B7280; font-weight: 600; }
        .score-input-group { display: flex; align-items: center; gap: 12px; }
        .score-input {
            width: 100px; padding: 10px 14px; border: 2px solid #e5e7eb;
            border-radius: 10px; font-size: 18px; font-weight: 700;
            text-align: center; outline: none; transition: border-color 0.2s;
        }
        .score-input:focus { border-color: #4F46E5; }
        .score-slider { flex: 1; height: 8px; border-radius: 4px; cursor: pointer; }
        .weighted-display { font-size: 14px; font-weight: 700; color: #4F46E5; min-width: 80px; text-align: right; }
        .score-note { width: 100%; margin-top: 10px; }
        .score-note textarea {
            width: 100%; padding: 8px 12px; border: 1px solid #e5e7eb;
            border-radius: 8px; font-size: 13px; resize: none; height: 40px;
        }
        .status-info { display: flex; align-items: center; gap: 8px; font-size: 13px; margin-left: auto; }
        .action-panel {
            background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;
            padding: 20px; position: sticky; bottom: 16px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        }
        .total-big { font-size: 2rem; font-weight: 700; color: #4F46E5; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include '../../admin/includes/sidebar.php'; ?>

        <main class="admin-main">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h1 class="h2">
                        <i class="fas fa-edit text-primary"></i>
                        <?php echo PAGE_TITLE; ?>
                    </h1>
                    <div>
                        <a href="duyet_diem/" class="btn btn-info btn-sm">
                            <i class="fas fa-check-double"></i> Duyệt điểm
                        </a>
                        <a href="xep_hang/tuan.php" class="btn btn-success btn-sm">
                            <i class="fas fa-trophy"></i> Xếp hạng
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <form method="GET" action="">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Chọn lớp *</label>
                            <select name="lop" class="form-select" required onchange="this.form.submit()">
                                <option value="0">-- Chọn lớp --</option>
                                <?php
                                $currentKhoi = 0;
                                foreach ($danhSachLop as $lop):
                                    if ($lop['khoi'] != $currentKhoi):
                                        if ($currentKhoi != 0) echo '</optgroup>';
                                        $currentKhoi = $lop['khoi'];
                                        echo '<optgroup label="Khối ' . $currentKhoi . '">';
                                    endif;
                                ?>
                                    <option value="<?php echo $lop['id']; ?>"
                                            <?php echo $lop_id == $lop['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lop['ten_lop']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($currentKhoi != 0) echo '</optgroup>'; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Chọn tuần *</label>
                            <select name="tuan" class="form-select" required onchange="this.form.submit()">
                                <option value="0">-- Chọn tuần --</option>
                                <?php foreach ($danhSachTuan as $tuan): ?>
                                    <option value="<?php echo $tuan['id']; ?>"
                                            <?php echo $tuan_id == $tuan['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tuan['ten_tuan']); ?>
                                        (<?php echo date('d/m', strtotime($tuan['ngay_bat_dau'])); ?> - <?php echo date('d/m', strtotime($tuan['ngay_ket_thuc'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <?php if ($lopInfo && $tuanInfo): ?>
                                <div class="alert alert-info mb-0 py-2 px-3 small">
                                    <i class="fas fa-info-circle"></i>
                                    <strong><?php echo htmlspecialchars($lopInfo['ten_lop']); ?></strong> -
                                    <?php echo htmlspecialchars($tuanInfo['ten_tuan']); ?>
                                    <?php if (count($diemHienTai) > 0): ?>
                                        <br><span class="text-success"><i class="fas fa-check"></i> Đã có <?php echo count($diemHienTai); ?>/<?php echo count($cacTieuChi); ?> tiêu chí</span>
                                    <?php else: ?>
                                        <br><span class="text-warning"><i class="fas fa-exclamation"></i> Chưa chấm</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($lopInfo && $tuanInfo): ?>
            <!-- Scoring Form -->
            <form method="POST" action="" id="formAdminCham">
                <input type="hidden" name="lop_id" value="<?php echo $lop_id; ?>">
                <input type="hidden" name="tuan_id" value="<?php echo $tuan_id; ?>">

                <div class="row">
                    <?php foreach ($cacTieuChi as $tc):
                        $tcId = $tc['id'];
                        $maTc = $tc['ma_tieu_chi'];
                        $icon = isset($criteriaIcons[$maTc]) ? $criteriaIcons[$maTc] : 'fa-star';
                        $color = isset($criteriaColors[$maTc]) ? $criteriaColors[$maTc] : '#6B7280';
                        $diemMax = floatval($tc['diem_toi_da']);
                        $trongSo = floatval($tc['trong_so']);

                        $diemVal = isset($diemHienTai[$tcId]) ? floatval($diemHienTai[$tcId]['diem']) : 0;
                        $ghiChuVal = isset($diemHienTai[$tcId]) ? $diemHienTai[$tcId]['ghi_chu'] : '';
                        $trangThaiVal = isset($diemHienTai[$tcId]) ? $diemHienTai[$tcId]['trang_thai'] : '';
                        $weightedVal = ($diemMax > 0) ? round(($diemVal / $diemMax) * $trongSo, 2) : 0;
                    ?>
                    <div class="col-md-6">
                        <div class="score-card">
                            <div class="score-card-header">
                                <div class="score-icon" style="background: <?php echo $color; ?>;">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div>
                                    <div class="score-title"><?php echo htmlspecialchars($tc['ten_tieu_chi']); ?></div>
                                    <div class="score-weight">Trọng số: <?php echo intval($trongSo); ?>% | Max: <?php echo intval($diemMax); ?> điểm</div>
                                </div>
                                <?php if ($trangThaiVal): ?>
                                <div class="status-info">
                                    <?php if ($trangThaiVal === 'da_duyet'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Đã duyệt</span>
                                    <?php elseif ($trangThaiVal === 'cho_duyet'): ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Chờ duyệt</span>
                                    <?php elseif ($trangThaiVal === 'tu_choi'): ?>
                                        <span class="badge bg-danger"><i class="fas fa-times"></i> Từ chối</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nháp</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="score-input-group">
                                <input type="number" name="diem_<?php echo $tcId; ?>"
                                       id="diem_<?php echo $tcId; ?>"
                                       class="score-input" min="0" max="<?php echo $diemMax; ?>" step="0.5"
                                       value="<?php echo $diemVal; ?>"
                                       oninput="updateAdminScore(<?php echo $tcId; ?>)">
                                <input type="range" class="form-range score-slider"
                                       id="slider_<?php echo $tcId; ?>"
                                       min="0" max="<?php echo $diemMax; ?>" step="0.5"
                                       value="<?php echo $diemVal; ?>"
                                       oninput="document.getElementById('diem_<?php echo $tcId; ?>').value=this.value; updateAdminScore(<?php echo $tcId; ?>)">
                                <div class="weighted-display" id="weighted_<?php echo $tcId; ?>"><?php echo number_format($weightedVal, 2); ?> / <?php echo intval($trongSo); ?></div>
                            </div>

                            <div class="score-note">
                                <textarea name="ghi_chu_<?php echo $tcId; ?>"
                                          placeholder="Ghi chú (tùy chọn)..."><?php echo htmlspecialchars($ghiChuVal); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Action Panel -->
                <div class="action-panel">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div>
                                <small class="text-muted">Tổng điểm</small>
                                <div class="total-big" id="totalScoreAdmin">0.00</div>
                            </div>
                            <div>
                                <small class="text-muted">Xếp loại</small>
                                <div id="xepLoaiAdmin" class="badge bg-secondary fs-6">--</div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" name="action" value="luu_cho_duyet" class="btn btn-warning">
                                <i class="fas fa-save"></i> Lưu (chờ duyệt)
                            </button>
                            <?php if ($isAdminRole): ?>
                            <button type="submit" name="action" value="cham_va_duyet" class="btn btn-success btn-lg">
                                <i class="fas fa-check-double"></i> Chấm + Duyệt luôn
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-hand-point-up" style="font-size: 3rem; opacity: 0.3;"></i>
                    <h5 class="mt-3">Chọn lớp và tuần để bắt đầu chấm điểm</h5>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var CRITERIA = <?php echo json_encode(array_map(function($tc) {
            return array('id' => $tc['id'], 'ma' => $tc['ma_tieu_chi'],
                         'diem_toi_da' => floatval($tc['diem_toi_da']),
                         'trong_so' => floatval($tc['trong_so']));
        }, $cacTieuChi)); ?>;

        var CRITERIA_MAP = {};
        for (var i = 0; i < CRITERIA.length; i++) {
            CRITERIA_MAP[CRITERIA[i].id] = CRITERIA[i];
        }

        function updateAdminScore(tcId) {
            var input = document.getElementById('diem_' + tcId);
            var slider = document.getElementById('slider_' + tcId);
            var val = parseFloat(input.value) || 0;
            var tc = CRITERIA_MAP[tcId];
            if (!tc) return;

            slider.value = val;
            var weighted = (tc.diem_toi_da > 0) ? (val / tc.diem_toi_da) * tc.trong_so : 0;
            document.getElementById('weighted_' + tcId).textContent = weighted.toFixed(2) + ' / ' + Math.round(tc.trong_so);

            recalcTotal();
        }

        function recalcTotal() {
            var total = 0;
            for (var i = 0; i < CRITERIA.length; i++) {
                var tc = CRITERIA[i];
                var input = document.getElementById('diem_' + tc.id);
                if (input) {
                    var val = parseFloat(input.value) || 0;
                    total += (tc.diem_toi_da > 0) ? (val / tc.diem_toi_da) * tc.trong_so : 0;
                }
            }
            document.getElementById('totalScoreAdmin').textContent = total.toFixed(2);

            var badge = document.getElementById('xepLoaiAdmin');
            if (total >= 90) { badge.textContent = 'Xu\u1EA5t s\u1EAFc'; badge.className = 'badge bg-warning text-dark fs-6'; }
            else if (total >= 80) { badge.textContent = 'T\u1ED1t'; badge.className = 'badge bg-success fs-6'; }
            else if (total >= 70) { badge.textContent = 'Kh\u00E1'; badge.className = 'badge bg-info fs-6'; }
            else if (total >= 50) { badge.textContent = 'Trung b\u00ECnh'; badge.className = 'badge bg-secondary fs-6'; }
            else if (total > 0) { badge.textContent = 'C\u1EA7n c\u1ED1 g\u1EAFng'; badge.className = 'badge bg-danger fs-6'; }
            else { badge.textContent = '--'; badge.className = 'badge bg-secondary fs-6'; }
        }

        recalcTotal();

        // Auto dismiss alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(a) { try { new bootstrap.Alert(a).close(); } catch(e){} });
        }, 5000);
    </script>
</body>
</html>
