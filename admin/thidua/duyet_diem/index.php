<?php
/**
 * ==============================================
 * DUYỆT ĐIỂM THI ĐUA TUẦN
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 *
 * Chức năng:
 * - Xem danh sách điểm chờ duyệt
 * - Duyệt/Từ chối điểm theo lớp
 * - Xem chi tiết 5 tiêu chí
 * - Tính tổng điểm có trọng số
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';
require_once '../../../includes/thidua_helper.php';

// Kiểm tra quyền: TPT hoặc Admin
requireTongPhuTrach();

define('PAGE_TITLE', 'Duyệt điểm thi đua tuần');

$conn = getDBConnection();
$admin = getCurrentAdmin();

// ============================================================
// FILTER & SEARCH
// ============================================================
$tuan_filter = isset($_GET['tuan']) ? intval($_GET['tuan']) : 0;
$lop_filter = isset($_GET['lop']) ? intval($_GET['lop']) : 0;
$khoi_filter = isset($_GET['khoi']) ? intval($_GET['khoi']) : 0;
$trang_thai_filter = isset($_GET['trang_thai']) ? sanitize($_GET['trang_thai']) : 'cho_duyet';

// Lấy tuần hiện tại nếu chưa chọn
if ($tuan_filter == 0) {
    $tuanHienTai = getTuanHienTai();
    $tuan_filter = $tuanHienTai ? $tuanHienTai['id'] : 0;
}

// ============================================================
// LẤY THÔNG TIN TUẦN
// ============================================================
$tuanInfo = null;
if ($tuan_filter > 0) {
    $stmtTuan = $conn->prepare("SELECT * FROM tuan_hoc WHERE id = ?");
    $stmtTuan->execute([$tuan_filter]);
    $tuanInfo = $stmtTuan->fetch();
}

// ============================================================
// LẤY DANH SÁCH LỚP VÀ ĐIỂM
// ============================================================
$danhSachDiem = [];

if ($tuan_filter > 0) {
    // Query phức tạp: Lấy điểm của từng lớp theo tuần
    $where = "dtdt.tuan_id = ?";
    $params = [$tuan_filter];

    if ($lop_filter > 0) {
        $where .= " AND dtdt.lop_id = ?";
        $params[] = $lop_filter;
    }

    if ($khoi_filter > 0) {
        $where .= " AND lh.khoi = ?";
        $params[] = $khoi_filter;
    }

    if ($trang_thai_filter !== '') {
        $where .= " AND dtdt.trang_thai = ?";
        $params[] = $trang_thai_filter;
    }

    $sql = "
        SELECT
            dtdt.lop_id,
            lh.ten_lop,
            lh.khoi,
            lh.khoi_label,
            MAX(dtdt.trang_thai) as trang_thai_chung,
            COUNT(dtdt.id) as so_tieu_chi_da_cham,
            SUM(dtdt.diem * tc.trong_so / 100) as tong_diem_co_trong_so,
            MIN(dtdt.cham_luc) as lan_cham_dau,
            MAX(dtdt.cham_luc) as lan_cham_cuoi
        FROM diem_thi_dua_tuan dtdt
        JOIN lop_hoc lh ON dtdt.lop_id = lh.id
        JOIN tieu_chi_thi_dua tc ON dtdt.tieu_chi_id = tc.id
        WHERE {$where}
        GROUP BY dtdt.lop_id, lh.ten_lop, lh.khoi, lh.khoi_label
        ORDER BY lh.ten_lop
    ";

    $stmtList = $conn->prepare($sql);
    $stmtList->execute($params);
    $danhSachDiem = $stmtList->fetchAll();
}

// ============================================================
// THỐNG KÊ
// ============================================================
$stats = [
    'tong_lop' => 0,
    'cho_duyet' => 0,
    'da_duyet' => 0,
    'tu_choi' => 0
];

if ($tuan_filter > 0) {
    $stmtStats = $conn->prepare("
        SELECT
            COUNT(DISTINCT lop_id) as tong_lop,
            SUM(CASE WHEN trang_thai = 'cho_duyet' THEN 1 ELSE 0 END) as cho_duyet,
            SUM(CASE WHEN trang_thai = 'da_duyet' THEN 1 ELSE 0 END) as da_duyet,
            SUM(CASE WHEN trang_thai = 'tu_choi' THEN 1 ELSE 0 END) as tu_choi
        FROM diem_thi_dua_tuan
        WHERE tuan_id = ?
    ");
    $stmtStats->execute([$tuan_filter]);
    $statsResult = $stmtStats->fetch();
    if ($statsResult) {
        $stats = $statsResult;
    }
}

// Lấy danh sách tuần
$stmtTuanList = $conn->query("
    SELECT * FROM tuan_hoc
    ORDER BY ngay_bat_dau DESC
    LIMIT 20
");
$danhSachTuan = $stmtTuanList->fetchAll();

// Lấy danh sách khối
$stmtKhoi = $conn->query("SELECT DISTINCT khoi FROM lop_hoc ORDER BY khoi");
$cacKhoi = $stmtKhoi->fetchAll();

// Lấy danh sách lớp
$stmtLop = $conn->query("SELECT id, ten_lop, khoi FROM lop_hoc ORDER BY ten_lop");
$danhSachLop = $stmtLop->fetchAll();
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
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">

    <style>
        .stats-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stats-card.primary { border-left-color: #0d6efd; }
        .stats-card.warning { border-left-color: #ffc107; }
        .stats-card.success { border-left-color: #198754; }
        .stats-card.danger { border-left-color: #dc3545; }

        .search-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .score-display {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .score-excellent { color: #198754; }
        .score-good { color: #0dcaf0; }
        .score-average { color: #ffc107; }
        .score-poor { color: #dc3545; }

        .badge-cho-duyet {
            background: #ffc107;
            color: #000;
        }

        .badge-da-duyet {
            background: #198754;
        }

        .badge-tu-choi {
            background: #dc3545;
        }

        .table-actions .btn {
            padding: 0.35rem 0.75rem;
            font-size: 0.875rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
        }

        .week-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .progress-thin {
            height: 6px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="admin-main">
                <div class="pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h2">
                            <i class="fas fa-check-double text-success"></i>
                            <?php echo PAGE_TITLE; ?>
                        </h1>
                        <div>
                            <a href="lich_su.php" class="btn btn-info">
                                <i class="fas fa-history"></i> Lịch sử
                            </a>
                            <a href="../" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Quay lại
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

                <!-- Thông tin tuần -->
                <?php if ($tuanInfo): ?>
                    <div class="week-info">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-2">
                                    <i class="fas fa-calendar-week"></i>
                                    <?php echo htmlspecialchars($tuanInfo['ten_tuan']); ?>
                                </h4>
                                <p class="mb-0">
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($tuanInfo['ngay_bat_dau'])); ?>
                                    -
                                    <?php echo date('d/m/Y', strtotime($tuanInfo['ngay_ket_thuc'])); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="fs-1">
                                    <i class="fas fa-school"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Tổng lớp</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['tong_lop']); ?></h3>
                                    </div>
                                    <div class="fs-1 text-primary opacity-25">
                                        <i class="fas fa-school"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Chờ duyệt</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['cho_duyet']); ?></h3>
                                    </div>
                                    <div class="fs-1 text-warning opacity-25">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Đã duyệt</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['da_duyet']); ?></h3>
                                    </div>
                                    <div class="fs-1 text-success opacity-25">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Từ chối</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['tu_choi']); ?></h3>
                                    </div>
                                    <div class="fs-1 text-danger opacity-25">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Form -->
                <div class="search-form">
                    <form method="GET" action="">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Chọn tuần</label>
                                <select name="tuan" class="form-select" required onchange="this.form.submit()">
                                    <option value="0">-- Chọn tuần --</option>
                                    <?php foreach ($danhSachTuan as $tuan): ?>
                                        <option value="<?php echo $tuan['id']; ?>"
                                                <?php echo $tuan_filter == $tuan['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tuan['ten_tuan']); ?>
                                            (<?php echo date('d/m', strtotime($tuan['ngay_bat_dau'])); ?>
                                            - <?php echo date('d/m', strtotime($tuan['ngay_ket_thuc'])); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Khối</label>
                                <select name="khoi" class="form-select">
                                    <option value="0">-- Tất cả --</option>
                                    <?php foreach ($cacKhoi as $khoi): ?>
                                        <option value="<?php echo $khoi['khoi']; ?>"
                                                <?php echo $khoi_filter == $khoi['khoi'] ? 'selected' : ''; ?>>
                                            Khối <?php echo $khoi['khoi']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Trạng thái</label>
                                <select name="trang_thai" class="form-select">
                                    <option value="">-- Tất cả --</option>
                                    <option value="cho_duyet" <?php echo $trang_thai_filter === 'cho_duyet' ? 'selected' : ''; ?>>
                                        Chờ duyệt
                                    </option>
                                    <option value="da_duyet" <?php echo $trang_thai_filter === 'da_duyet' ? 'selected' : ''; ?>>
                                        Đã duyệt
                                    </option>
                                    <option value="tu_choi" <?php echo $trang_thai_filter === 'tu_choi' ? 'selected' : ''; ?>>
                                        Từ chối
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Lọc
                                </button>
                            </div>
                            <div class="col-md-1">
                                <a href="?" class="btn btn-secondary w-100">
                                    <i class="fas fa-redo"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Danh sách lớp -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i>
                                Danh sách lớp
                                <span class="badge bg-primary"><?php echo count($danhSachDiem); ?></span>
                            </h5>
                            <?php if (count($danhSachDiem) > 0 && $trang_thai_filter === 'cho_duyet'): ?>
                                <button type="button"
                                        class="btn btn-success btn-sm"
                                        onclick="duyetTatCa()">
                                    <i class="fas fa-check-double"></i> Duyệt tất cả
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($danhSachDiem) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="20%">Lớp</th>
                                            <th width="15%" class="text-center">Tổng điểm</th>
                                            <th width="15%" class="text-center">Tiêu chí đã chấm</th>
                                            <th width="15%" class="text-center">Trạng thái</th>
                                            <th width="15%" class="text-center">Lần chấm cuối</th>
                                            <th width="15%" class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stt = 1;
                                        foreach ($danhSachDiem as $diem):
                                            $tongDiem = isset($diem['tong_diem_co_trong_so']) ? $diem['tong_diem_co_trong_so'] : 0;
                                            $scoreClass = 'score-poor';
                                            if ($tongDiem >= 90) $scoreClass = 'score-excellent';
                                            elseif ($tongDiem >= 80) $scoreClass = 'score-good';
                                            elseif ($tongDiem >= 70) $scoreClass = 'score-average';
                                        ?>
                                            <tr>
                                                <td><?php echo $stt++; ?></td>
                                                <td>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($diem['ten_lop']); ?></div>
                                                        <small class="text-muted">Khối <?php echo $diem['khoi']; ?></small>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="score-display <?php echo $scoreClass; ?>">
                                                        <?php echo number_format($tongDiem, 2); ?>
                                                    </div>
                                                    <small class="text-muted">/100 điểm</small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">
                                                        <?php echo $diem['so_tieu_chi_da_cham']; ?>/5
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    $badgeClass = 'badge-cho-duyet';
                                                    $icon = 'fa-clock';
                                                    $text = 'Chờ duyệt';

                                                    if ($diem['trang_thai_chung'] === 'da_duyet') {
                                                        $badgeClass = 'badge-da-duyet';
                                                        $icon = 'fa-check-circle';
                                                        $text = 'Đã duyệt';
                                                    } elseif ($diem['trang_thai_chung'] === 'tu_choi') {
                                                        $badgeClass = 'badge-tu-choi';
                                                        $icon = 'fa-times-circle';
                                                        $text = 'Từ chối';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?>">
                                                        <i class="fas <?php echo $icon; ?>"></i>
                                                        <?php echo $text; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <small>
                                                        <?php echo $diem['lan_cham_cuoi'] ? date('d/m H:i', strtotime($diem['lan_cham_cuoi'])) : 'N/A'; ?>
                                                    </small>
                                                </td>
                                                <td class="text-center table-actions">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="chi_tiet.php?lop=<?php echo $diem['lop_id']; ?>&tuan=<?php echo $tuan_filter; ?>"
                                                           class="btn btn-info"
                                                           title="Xem chi tiết">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($diem['trang_thai_chung'] === 'cho_duyet'): ?>
                                                            <a href="duyet.php?lop=<?php echo $diem['lop_id']; ?>&tuan=<?php echo $tuan_filter; ?>"
                                                               class="btn btn-success"
                                                               title="Duyệt">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                            <a href="tu_choi.php?lop=<?php echo $diem['lop_id']; ?>&tuan=<?php echo $tuan_filter; ?>"
                                                               class="btn btn-danger"
                                                               title="Từ chối">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <h5 class="mt-3">Chưa có điểm nào</h5>
                                <p class="text-muted">
                                    <?php if ($tuan_filter == 0): ?>
                                        Vui lòng chọn tuần để xem điểm
                                    <?php else: ?>
                                        Chưa có lớp nào được chấm điểm trong tuần này
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function duyetTatCa() {
            Swal.fire({
                title: 'Duyệt tất cả?',
                text: 'Bạn có chắc muốn duyệt tất cả điểm chờ duyệt trong tuần này?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check"></i> Duyệt tất cả',
                cancelButtonText: 'Hủy',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'duyet_tat_ca.php?tuan=<?php echo $tuan_filter; ?>';
                }
            });
        }

        // Auto dismiss alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => new bootstrap.Alert(alert).close());
        }, 5000);
    </script>
</body>
</html>
