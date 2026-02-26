<?php
/**
 * ==============================================
 * XẾP HẠNG LỚP THEO TUẦN
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';

requireAdmin();

define('PAGE_TITLE', 'Xếp hạng lớp theo tuần');

$conn = getDBConnection();

// ============================================================
// FILTER
// ============================================================
$tuan_id = isset($_GET['tuan']) ? intval($_GET['tuan']) : 0;
$khoi_filter = isset($_GET['khoi']) ? sanitize($_GET['khoi']) : '';

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

// Lấy thông tin tuần
$tuanInfo = null;
if ($tuan_id > 0) {
    $stmtTuan = $conn->prepare("SELECT * FROM tuan_hoc WHERE id = ?");
    $stmtTuan->execute([$tuan_id]);
    $tuanInfo = $stmtTuan->fetch();
}

// Lấy danh sách tuần
$stmtDanhSachTuan = $conn->query("
    SELECT * FROM tuan_hoc
    ORDER BY ngay_bat_dau DESC
    LIMIT 20
");
$danhSachTuan = $stmtDanhSachTuan->fetchAll();

// Lấy danh sách khối
$stmtKhoi = $conn->query("
    SELECT DISTINCT khoi
    FROM lop_hoc
    WHERE trang_thai = 1
    ORDER BY khoi
");
$danhSachKhoi = $stmtKhoi->fetchAll();

// ============================================================
// GET XẾP HẠNG
// ============================================================
$xepHang = [];
if ($tuan_id > 0) {
    $where = "xh.tuan_id = ?";
    $params = [$tuan_id];

    if ($khoi_filter) {
        $where .= " AND lh.khoi = ?";
        $params[] = $khoi_filter;
    }

    $sql = "
        SELECT
            xh.*,
            lh.ten_lop,
            lh.khoi,
            lh.khoi_label
        FROM xep_hang_lop_tuan xh
        JOIN lop_hoc lh ON xh.lop_id = lh.id
        WHERE {$where}
        ORDER BY xh.thu_hang_toan_truong ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $xepHang = $stmt->fetchAll();
}

// ============================================================
// STATS
// ============================================================
$stats = [
    'tong_lop' => 0,
    'xuat_sac' => 0,
    'tot' => 0,
    'kha' => 0,
    'trung_binh' => 0,
    'can_co_gang' => 0,
    'diem_cao_nhat' => 0,
    'diem_thap_nhat' => 0
];

if ($tuan_id > 0) {
    $stmtStats = $conn->prepare("
        SELECT
            COUNT(*) as tong_lop,
            MAX(tong_diem_co_trong_so) as diem_cao_nhat,
            MIN(tong_diem_co_trong_so) as diem_thap_nhat,
            SUM(CASE WHEN xep_loai = 'xuat_sac' THEN 1 ELSE 0 END) as xuat_sac,
            SUM(CASE WHEN xep_loai = 'tot' THEN 1 ELSE 0 END) as tot,
            SUM(CASE WHEN xep_loai = 'kha' THEN 1 ELSE 0 END) as kha,
            SUM(CASE WHEN xep_loai = 'trung_binh' THEN 1 ELSE 0 END) as trung_binh,
            SUM(CASE WHEN xep_loai = 'can_co_gang' THEN 1 ELSE 0 END) as can_co_gang
        FROM xep_hang_lop_tuan
        WHERE tuan_id = ?
    ");
    $stmtStats->execute([$tuan_id]);
    $statsResult = $stmtStats->fetch();
    if ($statsResult && $statsResult['tong_lop'] > 0) {
        $stats = $statsResult;
    }
}
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
        .stats-card.success { border-left-color: #198754; }
        .stats-card.danger { border-left-color: #dc3545; }

        .rank-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .rank-1 {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
            box-shadow: 0 2px 8px rgba(255,215,0,0.5);
        }

        .rank-2 {
            background: linear-gradient(135deg, #C0C0C0, #808080);
            color: white;
            box-shadow: 0 2px 8px rgba(192,192,192,0.5);
        }

        .rank-3 {
            background: linear-gradient(135deg, #CD7F32, #8B4513);
            color: white;
            box-shadow: 0 2px 8px rgba(205,127,50,0.5);
        }

        .rank-other {
            background: #e9ecef;
            color: #495057;
        }

        .xep-loai-badge {
            font-size: 0.85rem;
            padding: 0.3rem 0.7rem;
        }

        .progress-thin {
            height: 8px;
        }

        .table-ranking {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .table-ranking tbody tr {
            transition: background 0.2s;
        }

        .table-ranking tbody tr:hover {
            background: #f8f9fa;
        }

        .medal-icon {
            font-size: 1.5rem;
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
                            <i class="fas fa-trophy text-warning"></i>
                            <?php echo PAGE_TITLE; ?>
                        </h1>
                        <div>
                            <a href="../duyet_diem/index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-check-circle"></i> Duyệt điểm
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="far fa-calendar-alt"></i> Chọn tuần
                                    </label>
                                    <select name="tuan" class="form-select" onchange="this.form.submit()">
                                        <option value="">-- Chọn tuần --</option>
                                        <?php foreach ($danhSachTuan as $tuan): ?>
                                            <option value="<?php echo $tuan['id']; ?>"
                                                <?php echo $tuan_id == $tuan['id'] ? 'selected' : ''; ?>>
                                                Tuần <?php echo $tuan['so_tuan']; ?> -
                                                <?php echo date('d/m/Y', strtotime($tuan['ngay_bat_dau'])); ?> →
                                                <?php echo date('d/m/Y', strtotime($tuan['ngay_ket_thuc'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-layer-group"></i> Khối
                                    </label>
                                    <select name="khoi" class="form-select" onchange="this.form.submit()">
                                        <option value="">-- Tất cả khối --</option>
                                        <?php foreach ($danhSachKhoi as $khoi): ?>
                                            <option value="<?php echo $khoi['khoi']; ?>"
                                                <?php echo $khoi_filter == $khoi['khoi'] ? 'selected' : ''; ?>>
                                                Khối <?php echo $khoi['khoi']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <a href="tuan.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($tuanInfo): ?>
                    <!-- Thông tin tuần -->
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">
                                    <i class="far fa-calendar-check"></i>
                                    Tuần <?php echo $tuanInfo['so_tuan']; ?> - Học kỳ <?php echo $tuanInfo['hoc_ky_id']; ?>
                                </h5>
                                <p class="mb-0 text-muted">
                                    <?php echo date('d/m/Y', strtotime($tuanInfo['ngay_bat_dau'])); ?> →
                                    <?php echo date('d/m/Y', strtotime($tuanInfo['ngay_ket_thuc'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card stats-card primary">
                                <div class="card-body text-center">
                                    <div class="text-primary mb-2">
                                        <i class="fas fa-school fs-1 opacity-50"></i>
                                    </div>
                                    <h3 class="mb-0"><?php echo number_format($stats['tong_lop']); ?></h3>
                                    <p class="text-muted small mb-0">Tổng lớp</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="card stats-card success">
                                <div class="card-body text-center">
                                    <div class="text-success mb-2">
                                        <i class="fas fa-star fs-1 opacity-50"></i>
                                    </div>
                                    <h3 class="mb-0"><?php echo number_format($stats['xuat_sac']); ?></h3>
                                    <p class="text-muted small mb-0">Xuất sắc</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="card stats-card" style="border-left-color: #0dcaf0;">
                                <div class="card-body text-center">
                                    <div class="text-info mb-2">
                                        <i class="fas fa-check-circle fs-1 opacity-50"></i>
                                    </div>
                                    <h3 class="mb-0"><?php echo number_format($stats['tot']); ?></h3>
                                    <p class="text-muted small mb-0">Tốt</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="card stats-card" style="border-left-color: #ffc107;">
                                <div class="card-body text-center">
                                    <div class="text-warning mb-2">
                                        <i class="fas fa-thumbs-up fs-1 opacity-50"></i>
                                    </div>
                                    <h3 class="mb-0"><?php echo number_format($stats['kha']); ?></h3>
                                    <p class="text-muted small mb-0">Khá</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="card stats-card" style="border-left-color: #6c757d;">
                                <div class="card-body text-center">
                                    <div class="text-secondary mb-2">
                                        <i class="fas fa-equals fs-1 opacity-50"></i>
                                    </div>
                                    <h3 class="mb-0"><?php echo number_format($stats['trung_binh']); ?></h3>
                                    <p class="text-muted small mb-0">Trung bình</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="card stats-card danger">
                                <div class="card-body text-center">
                                    <div class="text-danger mb-2">
                                        <i class="fas fa-arrow-up fs-1 opacity-50"></i>
                                    </div>
                                    <h3 class="mb-0"><?php echo number_format($stats['can_co_gang']); ?></h3>
                                    <p class="text-muted small mb-0">Cần cố gắng</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bảng xếp hạng -->
                    <div class="card shadow-sm table-ranking">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-list-ol"></i>
                                    Bảng xếp hạng
                                    <?php if ($khoi_filter): ?>
                                        - Khối <?php echo $khoi_filter; ?>
                                    <?php endif; ?>
                                </h5>
                                <button class="btn btn-sm btn-success" onclick="exportExcel()">
                                    <i class="fas fa-file-excel"></i> Xuất Excel
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($xepHang) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="80" class="text-center">Hạng</th>
                                                <th>Lớp</th>
                                                <th width="100" class="text-center">Khối</th>
                                                <th width="120" class="text-center">Tổng điểm</th>
                                                <th width="250">Chi tiết điểm</th>
                                                <th width="150" class="text-center">Xếp loại</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($xepHang as $item):
                                                $rankClass = '';
                                                $medalIcon = '';
                                                if ($item['thu_hang_toan_truong'] == 1) {
                                                    $rankClass = 'rank-1';
                                                    $medalIcon = '<i class="fas fa-trophy medal-icon text-warning"></i>';
                                                } elseif ($item['thu_hang_toan_truong'] == 2) {
                                                    $rankClass = 'rank-2';
                                                    $medalIcon = '<i class="fas fa-medal medal-icon" style="color: silver;"></i>';
                                                } elseif ($item['thu_hang_toan_truong'] == 3) {
                                                    $rankClass = 'rank-3';
                                                    $medalIcon = '<i class="fas fa-medal medal-icon" style="color: #CD7F32;"></i>';
                                                } else {
                                                    $rankClass = 'rank-other';
                                                }

                                                // Xếp loại
                                                $xepLoaiText = '';
                                                $xepLoaiClass = '';
                                                switch ($item['xep_loai']) {
                                                    case 'xuat_sac':
                                                        $xepLoaiText = 'Xuất sắc';
                                                        $xepLoaiClass = 'bg-success';
                                                        break;
                                                    case 'tot':
                                                        $xepLoaiText = 'Tốt';
                                                        $xepLoaiClass = 'bg-info';
                                                        break;
                                                    case 'kha':
                                                        $xepLoaiText = 'Khá';
                                                        $xepLoaiClass = 'bg-warning';
                                                        break;
                                                    case 'trung_binh':
                                                        $xepLoaiText = 'Trung bình';
                                                        $xepLoaiClass = 'bg-secondary';
                                                        break;
                                                    case 'can_co_gang':
                                                        $xepLoaiText = 'Cần cố gắng';
                                                        $xepLoaiClass = 'bg-danger';
                                                        break;
                                                }
                                            ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <div class="rank-badge <?php echo $rankClass; ?>">
                                                            <?php echo $item['thu_hang_toan_truong']; ?>
                                                        </div>
                                                        <?php if ($medalIcon): ?>
                                                            <div class="mt-1"><?php echo $medalIcon; ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($item['ten_lop']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($item['khoi_label']); ?>
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary">
                                                            Khối <?php echo $item['khoi']; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <h5 class="mb-0 text-primary">
                                                            <?php echo number_format($item['tong_diem_co_trong_so'], 2); ?>
                                                        </h5>
                                                        <small class="text-muted">/100</small>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span>Học tập:</span>
                                                                <strong><?php echo number_format($item['diem_hoc_tap'], 2); ?></strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span>Nề nếp:</span>
                                                                <strong><?php echo number_format($item['diem_ne_nep'], 2); ?></strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span>Vệ sinh:</span>
                                                                <strong><?php echo number_format($item['diem_ve_sinh'], 2); ?></strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span>Hoạt động:</span>
                                                                <strong><?php echo number_format($item['diem_hoat_dong'], 2); ?></strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between">
                                                                <span>Đoàn kết:</span>
                                                                <strong><?php echo number_format($item['diem_doan_ket'], 2); ?></strong>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge <?php echo $xepLoaiClass; ?> xep-loai-badge">
                                                            <?php echo $xepLoaiText; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fs-1 opacity-25"></i>
                                    <p class="mt-3">Chưa có dữ liệu xếp hạng cho tuần này</p>
                                    <p class="small">Vui lòng duyệt điểm để hệ thống tính toán xếp hạng tự động</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Vui lòng chọn tuần để xem xếp hạng
                    </div>
                <?php endif; ?>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportExcel() {
            // TODO: Implement Excel export
            alert('Chức năng xuất Excel sẽ được triển khai sau');
        }
    </script>
</body>
</html>
