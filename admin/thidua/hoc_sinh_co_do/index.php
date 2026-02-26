<?php
/**
 * ==============================================
 * QUẢN LÝ HỌC SINH CỜ ĐỎ
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 *
 * Chức năng:
 * - Gắn/gỡ Cờ đỏ cho học sinh (AJAX)
 * - Danh sách học sinh theo lớp
 * - Thống kê Cờ đỏ theo lớp/khối
 * - Lịch sử gán Cờ đỏ
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';

// Kiểm tra quyền: Chỉ Admin
requireAdmin();

define('PAGE_TITLE', 'Quản lý học sinh Cờ đỏ');

$conn = getDBConnection();
$admin = getCurrentAdmin();

// ============================================================
// XỬ LÝ FILTER & SEARCH
// ============================================================
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$lop_filter = isset($_GET['lop']) ? intval($_GET['lop']) : 0;
$khoi_filter = isset($_GET['khoi']) ? intval($_GET['khoi']) : 0;
$trang_thai_filter = isset($_GET['trang_thai']) ? sanitize($_GET['trang_thai']) : '';

// ============================================================
// PHÂN TRANG
// ============================================================
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 30;
$offset = ($page - 1) * $limit;

// ============================================================
// XÂY DỰNG QUERY
// ============================================================
$where = "hs.trang_thai = 1"; // Chỉ lấy học sinh đang hoạt động
$params = [];

if ($search) {
    $where .= " AND (hs.ho_ten LIKE ? OR hs.ma_hs LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($lop_filter > 0) {
    $where .= " AND hs.lop_id = ?";
    $params[] = $lop_filter;
}

if ($khoi_filter > 0) {
    $where .= " AND lh.khoi = ?";
    $params[] = $khoi_filter;
}

if ($trang_thai_filter !== '') {
    if ($trang_thai_filter === 'co_do') {
        $where .= " AND hs.la_co_do = 1";
    } elseif ($trang_thai_filter === 'thuong') {
        $where .= " AND hs.la_co_do = 0";
    }
}

// Đếm tổng
$countSql = "
    SELECT COUNT(*) as total
    FROM hoc_sinh hs
    JOIN lop_hoc lh ON hs.lop_id = lh.id
    WHERE {$where}
";
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

// Lấy danh sách học sinh
$sql = "
    SELECT
        hs.*,
        lh.ten_lop,
        lh.khoi,
        lh.khoi_label,
        a.ho_ten as ten_nguoi_gan
    FROM hoc_sinh hs
    JOIN lop_hoc lh ON hs.lop_id = lh.id
    LEFT JOIN admins a ON hs.nguoi_gan = a.id
    WHERE {$where}
    ORDER BY lh.ten_lop, hs.ho_ten
    LIMIT ? OFFSET ?
";
$params[] = $limit;
$params[] = $offset;
$stmtList = $conn->prepare($sql);
$stmtList->execute($params);
$danhSachHocSinh = $stmtList->fetchAll();

// Lấy danh sách khối
$stmtKhoi = $conn->query("SELECT DISTINCT khoi FROM lop_hoc ORDER BY khoi");
$cacKhoi = $stmtKhoi->fetchAll();

// Lấy danh sách lớp
$stmtLop = $conn->query("SELECT id, ten_lop, khoi FROM lop_hoc ORDER BY ten_lop");
$danhSachLop = $stmtLop->fetchAll();

// ============================================================
// THỐNG KÊ
// ============================================================
$stmtStats = $conn->query("
    SELECT
        COUNT(*) as tong_hoc_sinh,
        SUM(CASE WHEN la_co_do = 1 THEN 1 ELSE 0 END) as tong_co_do,
        SUM(CASE WHEN la_co_do = 0 THEN 1 ELSE 0 END) as tong_thuong
    FROM hoc_sinh
    WHERE trang_thai = 1
");
$stats = $stmtStats->fetch();

// Thống kê theo lớp
$stmtStatsByClass = $conn->query("
    SELECT
        lh.id,
        lh.ten_lop,
        lh.khoi,
        COUNT(hs.id) as tong_hs,
        SUM(CASE WHEN hs.la_co_do = 1 THEN 1 ELSE 0 END) as so_co_do
    FROM lop_hoc lh
    LEFT JOIN hoc_sinh hs ON lh.id = hs.lop_id AND hs.trang_thai = 1
    GROUP BY lh.id
    ORDER BY lh.ten_lop
");
$statsByClass = $stmtStatsByClass->fetchAll();
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
        .stats-card.secondary { border-left-color: #6c757d; }

        .search-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .student-avatar {
            font-size: 2rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 50%;
        }

        .badge-co-do {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        .badge-thuong {
            background: #e9ecef;
            color: #6c757d;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        /* Toggle Switch Cờ đỏ */
        .toggle-co-do {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .toggle-co-do input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }

        input:disabled + .toggle-slider {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .table-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .stats-by-class {
            max-height: 400px;
            overflow-y: auto;
        }

        .progress-thin {
            height: 8px;
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

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay.show {
            display: flex;
        }

        .spinner-large {
            width: 3rem;
            height: 3rem;
            border-width: 0.3rem;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border spinner-large text-light" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="admin-layout">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="admin-main">
                <div class="pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h2">
                            <i class="fas fa-flag text-danger"></i>
                            <?php echo PAGE_TITLE; ?>
                        </h1>
                        <div>
                            <a href="history.php" class="btn btn-info">
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
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Thống kê tổng quan -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stats-card primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Tổng học sinh</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['tong_hoc_sinh']); ?></h3>
                                    </div>
                                    <div class="fs-1 text-primary opacity-25">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Học sinh Cờ đỏ</p>
                                        <h3 class="mb-0 text-danger"><?php echo number_format($stats['tong_co_do']); ?></h3>
                                        <small class="text-muted">
                                            <?php
                                            $percent = $stats['tong_hoc_sinh'] > 0
                                                ? round(($stats['tong_co_do'] / $stats['tong_hoc_sinh']) * 100, 1)
                                                : 0;
                                            echo $percent . '%';
                                            ?>
                                        </small>
                                    </div>
                                    <div class="fs-1 text-danger opacity-25">
                                        <i class="fas fa-flag"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card secondary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Học sinh thường</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['tong_thuong']); ?></h3>
                                        <small class="text-muted">
                                            <?php
                                            $percent = $stats['tong_hoc_sinh'] > 0
                                                ? round(($stats['tong_thuong'] / $stats['tong_hoc_sinh']) * 100, 1)
                                                : 0;
                                            echo $percent . '%';
                                            ?>
                                        </small>
                                    </div>
                                    <div class="fs-1 text-secondary opacity-25">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Danh sách học sinh (Cột trái - 8) -->
                    <div class="col-md-8">
                        <!-- Form tìm kiếm -->
                        <div class="search-form">
                            <form method="GET" action="" id="searchForm">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Tìm kiếm</label>
                                        <input type="text"
                                               name="search"
                                               class="form-control"
                                               placeholder="Tên, mã học sinh..."
                                               value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <div class="col-md-3">
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
                                            <option value="co_do" <?php echo $trang_thai_filter === 'co_do' ? 'selected' : ''; ?>>
                                                Cờ đỏ
                                            </option>
                                            <option value="thuong" <?php echo $trang_thai_filter === 'thuong' ? 'selected' : ''; ?>>
                                                Học sinh thường
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Bảng danh sách -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-list"></i>
                                    Danh sách học sinh
                                    <span class="badge bg-primary"><?php echo number_format($totalRecords); ?></span>
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (count($danhSachHocSinh) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="5%">#</th>
                                                    <th width="40%">Học sinh</th>
                                                    <th width="20%">Lớp</th>
                                                    <th width="20%" class="text-center">Trạng thái</th>
                                                    <th width="15%" class="text-center">Cờ đỏ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stt = $offset + 1;
                                                foreach ($danhSachHocSinh as $hs):
                                                ?>
                                                    <tr id="row-<?php echo $hs['id']; ?>">
                                                        <td><?php echo $stt++; ?></td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="student-avatar me-2">
                                                                    <?php echo $hs['gioi_tinh'] == 1 ? '👦' : '👧'; ?>
                                                                </div>
                                                                <div>
                                                                    <div class="fw-bold"><?php echo htmlspecialchars($hs['ho_ten']); ?></div>
                                                                    <small class="text-muted">Mã: <?php echo $hs['ma_hs']; ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary">
                                                                <?php echo htmlspecialchars($hs['ten_lop']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge-status-<?php echo $hs['id']; ?>">
                                                                <?php if ($hs['la_co_do'] == 1): ?>
                                                                    <span class="badge-co-do">
                                                                        <i class="fas fa-flag"></i> Cờ đỏ
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge-thuong">
                                                                        Học sinh thường
                                                                    </span>
                                                                <?php endif; ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <label class="toggle-co-do">
                                                                <input type="checkbox"
                                                                       class="toggle-co-do-checkbox"
                                                                       data-student-id="<?php echo $hs['id']; ?>"
                                                                       data-student-name="<?php echo htmlspecialchars($hs['ho_ten']); ?>"
                                                                       <?php echo $hs['la_co_do'] == 1 ? 'checked' : ''; ?>>
                                                                <span class="toggle-slider"></span>
                                                            </label>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <?php if ($totalPages > 1): ?>
                                        <div class="card-footer bg-white">
                                            <nav>
                                                <ul class="pagination pagination-sm justify-content-center mb-0">
                                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&khoi=<?php echo $khoi_filter; ?>&trang_thai=<?php echo $trang_thai_filter; ?>">
                                                            <i class="fas fa-chevron-left"></i>
                                                        </a>
                                                    </li>
                                                    <?php
                                                    $start = max(1, $page - 2);
                                                    $end = min($totalPages, $page + 2);
                                                    for ($i = $start; $i <= $end; $i++):
                                                    ?>
                                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&khoi=<?php echo $khoi_filter; ?>&trang_thai=<?php echo $trang_thai_filter; ?>">
                                                                <?php echo $i; ?>
                                                            </a>
                                                        </li>
                                                    <?php endfor; ?>
                                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&khoi=<?php echo $khoi_filter; ?>&trang_thai=<?php echo $trang_thai_filter; ?>">
                                                            <i class="fas fa-chevron-right"></i>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </nav>
                                        </div>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-users-slash"></i>
                                        <h5 class="mt-3">Không tìm thấy học sinh</h5>
                                        <p class="text-muted">Thử thay đổi điều kiện tìm kiếm</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê theo lớp (Cột phải - 4) -->
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar"></i>
                                    Thống kê theo lớp
                                </h5>
                            </div>
                            <div class="card-body p-0 stats-by-class">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($statsByClass as $stat): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong><?php echo htmlspecialchars($stat['ten_lop']); ?></strong>
                                                <span class="badge bg-danger">
                                                    <?php echo $stat['so_co_do']; ?>/<?php echo $stat['tong_hs']; ?>
                                                </span>
                                            </div>
                                            <div class="progress progress-thin">
                                                <?php
                                                $percent = $stat['tong_hs'] > 0
                                                    ? ($stat['so_co_do'] / $stat['tong_hs']) * 100
                                                    : 0;
                                                ?>
                                                <div class="progress-bar bg-danger"
                                                     role="progressbar"
                                                     style="width: <?php echo $percent; ?>%"
                                                     aria-valuenow="<?php echo $percent; ?>"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo number_format($percent, 1); ?>% Cờ đỏ
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Toggle Cờ đỏ
            $('.toggle-co-do-checkbox').on('change', function() {
                const checkbox = $(this);
                const studentId = checkbox.data('student-id');
                const studentName = checkbox.data('student-name');
                const isChecked = checkbox.is(':checked');

                // Disable checkbox khi đang xử lý
                checkbox.prop('disabled', true);

                // Show loading
                $('#loadingOverlay').addClass('show');

                // AJAX request
                $.ajax({
                    url: 'toggle_co_do.php',
                    method: 'POST',
                    data: {
                        student_id: studentId,
                        action: isChecked ? 'assign' : 'remove'
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loadingOverlay').removeClass('show');
                        checkbox.prop('disabled', false);

                        if (response.success) {
                            // Update badge
                            const badgeHtml = isChecked
                                ? '<span class="badge-co-do"><i class="fas fa-flag"></i> Cờ đỏ</span>'
                                : '<span class="badge-thuong">Học sinh thường</span>';

                            $('.badge-status-' + studentId).html(badgeHtml);

                            // Toast notification
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: response.message,
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });

                            // Reload page sau 1s để update stats
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            // Revert checkbox
                            checkbox.prop('checked', !isChecked);

                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi!',
                                text: response.message || 'Không thể thực hiện thao tác'
                            });
                        }
                    },
                    error: function() {
                        $('#loadingOverlay').removeClass('show');
                        checkbox.prop('disabled', false);
                        checkbox.prop('checked', !isChecked);

                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: 'Không thể kết nối đến server'
                        });
                    }
                });
            });

            // Auto dismiss alerts
            setTimeout(() => {
                $('.alert').fadeOut();
            }, 5000);
        });
    </script>
</body>
</html>
