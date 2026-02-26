<?php
/**
 * ==============================================
 * LỊCH SỬ DUYỆT ĐIỂM
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';

requireTongPhuTrach();

define('PAGE_TITLE', 'Lịch sử duyệt điểm');

$conn = getDBConnection();

// Filter
$loai_filter = isset($_GET['loai']) ? sanitize($_GET['loai']) : '';
$from_date = isset($_GET['from']) ? sanitize($_GET['from']) : '';
$to_date = isset($_GET['to']) ? sanitize($_GET['to']) : '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Query
$where = "lsh.loai_hoat_dong IN ('duyet_diem', 'tu_choi_diem', 'duyet_tat_ca')";
$params = [];

if ($loai_filter) {
    $where .= " AND lsh.loai_hoat_dong = ?";
    $params[] = $loai_filter;
}

if ($from_date) {
    $where .= " AND DATE(lsh.created_at) >= ?";
    $params[] = $from_date;
}

if ($to_date) {
    $where .= " AND DATE(lsh.created_at) <= ?";
    $params[] = $to_date;
}

// Count
$countSql = "SELECT COUNT(*) as total FROM lich_su_hoat_dong lsh WHERE {$where}";
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get history
$sql = "
    SELECT
        lsh.*,
        a.ho_ten as ten_nguoi_thuc_hien
    FROM lich_su_hoat_dong lsh
    LEFT JOIN admins a ON lsh.nguoi_thuc_hien = a.id
    WHERE {$where}
    ORDER BY lsh.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $limit;
$params[] = $offset;
$stmtList = $conn->prepare($sql);
$stmtList->execute($params);
$history = $stmtList->fetchAll();

// Stats
$stmtStats = $conn->query("
    SELECT
        COUNT(*) as tong,
        SUM(CASE WHEN loai_hoat_dong = 'duyet_diem' THEN 1 ELSE 0 END) as duyet,
        SUM(CASE WHEN loai_hoat_dong = 'tu_choi_diem' THEN 1 ELSE 0 END) as tu_choi,
        SUM(CASE WHEN loai_hoat_dong = 'duyet_tat_ca' THEN 1 ELSE 0 END) as duyet_tat_ca
    FROM lich_su_hoat_dong
    WHERE loai_hoat_dong IN ('duyet_diem', 'tu_choi_diem', 'duyet_tat_ca')
");
$stats = $stmtStats->fetch();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?> - Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
        .stats-card.info { border-left-color: #0dcaf0; }

        .search-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .timeline-item {
            padding: 1rem;
            border-left: 3px solid #dee2e6;
            margin-left: 1rem;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -9px;
            top: 1.2rem;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: white;
            border: 3px solid;
        }

        .timeline-item.duyet::before {
            border-color: #198754;
        }

        .timeline-item.tu-choi::before {
            border-color: #dc3545;
        }

        .timeline-item.duyet-tat-ca::before {
            border-color: #0dcaf0;
        }

        .badge-duyet {
            background: #198754;
        }

        .badge-tu-choi {
            background: #dc3545;
        }

        .badge-duyet-tat-ca {
            background: #0dcaf0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 d-md-block bg-dark sidebar">
                <?php include '../../includes/sidebar.php'; ?>
            </nav>

            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h2">
                            <i class="fas fa-history text-info"></i>
                            <?php echo PAGE_TITLE; ?>
                        </h1>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Tổng thao tác</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['tong']); ?></h3>
                                    </div>
                                    <div class="fs-1 text-primary opacity-25">
                                        <i class="fas fa-history"></i>
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
                                        <h3 class="mb-0"><?php echo number_format($stats['duyet']); ?></h3>
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
                    <div class="col-md-3">
                        <div class="card stats-card info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Duyệt hàng loạt</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['duyet_tat_ca']); ?></h3>
                                    </div>
                                    <div class="fs-1 text-info opacity-25">
                                        <i class="fas fa-check-double"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="search-form">
                    <form method="GET" action="">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Loại</label>
                                <select name="loai" class="form-select">
                                    <option value="">-- Tất cả --</option>
                                    <option value="duyet_diem" <?php echo $loai_filter === 'duyet_diem' ? 'selected' : ''; ?>>
                                        Duyệt điểm
                                    </option>
                                    <option value="tu_choi_diem" <?php echo $loai_filter === 'tu_choi_diem' ? 'selected' : ''; ?>>
                                        Từ chối điểm
                                    </option>
                                    <option value="duyet_tat_ca" <?php echo $loai_filter === 'duyet_tat_ca' ? 'selected' : ''; ?>>
                                        Duyệt tất cả
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Từ ngày</label>
                                <input type="date"
                                       name="from"
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($from_date); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Đến ngày</label>
                                <input type="date"
                                       name="to"
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($to_date); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Lọc
                                </button>
                            </div>
                            <div class="col-md-1">
                                <a href="lich_su.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-redo"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Timeline -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-stream"></i>
                            Lịch sử hoạt động
                            <span class="badge bg-primary"><?php echo number_format($totalRecords); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($history) > 0): ?>
                            <?php foreach ($history as $item):
                                $cssClass = '';
                                $badgeClass = '';
                                $icon = '';
                                $text = '';

                                if ($item['loai_hoat_dong'] === 'duyet_diem') {
                                    $cssClass = 'duyet';
                                    $badgeClass = 'badge-duyet';
                                    $icon = 'fa-check-circle';
                                    $text = 'Duyệt điểm';
                                } elseif ($item['loai_hoat_dong'] === 'tu_choi_diem') {
                                    $cssClass = 'tu-choi';
                                    $badgeClass = 'badge-tu-choi';
                                    $icon = 'fa-times-circle';
                                    $text = 'Từ chối điểm';
                                } else {
                                    $cssClass = 'duyet-tat-ca';
                                    $badgeClass = 'badge-duyet-tat-ca';
                                    $icon = 'fa-check-double';
                                    $text = 'Duyệt tất cả';
                                }
                            ?>
                                <div class="timeline-item <?php echo $cssClass; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge <?php echo $badgeClass; ?> me-2">
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                    <?php echo $text; ?>
                                                </span>
                                                <span class="text-muted small">
                                                    <i class="far fa-clock"></i>
                                                    <?php echo date('d/m/Y H:i:s', strtotime($item['created_at'])); ?>
                                                </span>
                                            </div>
                                            <p class="mb-1">
                                                <strong><?php echo htmlspecialchars($item['mo_ta']); ?></strong>
                                            </p>
                                            <p class="mb-0 text-muted small">
                                                <i class="fas fa-user"></i>
                                                Người thực hiện: <strong><?php echo htmlspecialchars(isset($item['ten_nguoi_thuc_hien']) ? $item['ten_nguoi_thuc_hien'] : 'N/A'); ?></strong>
                                            </p>
                                            <?php if ($item['ip_address']): ?>
                                                <p class="mb-0 text-muted small">
                                                    <i class="fas fa-network-wired"></i>
                                                    IP: <?php echo htmlspecialchars($item['ip_address']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&loai=<?php echo $loai_filter; ?>&from=<?php echo $from_date; ?>&to=<?php echo $to_date; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                        <?php
                                        $start = max(1, $page - 2);
                                        $end = min($totalPages, $page + 2);
                                        for ($i = $start; $i <= $end; $i++):
                                        ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&loai=<?php echo $loai_filter; ?>&from=<?php echo $from_date; ?>&to=<?php echo $to_date; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&loai=<?php echo $loai_filter; ?>&from=<?php echo $from_date; ?>&to=<?php echo $to_date; ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fs-1 opacity-25"></i>
                                <p class="mt-3">Chưa có lịch sử</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
