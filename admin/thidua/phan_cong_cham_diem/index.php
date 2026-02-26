<?php
/**
 * ==============================================
 * QUẢN LÝ PHÂN CÔNG CHẤM ĐIỂM (CHẤM CHÉO)
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 *
 * Chức năng:
 * - Thiết lập lớp nào chấm lớp nào (chấm chéo)
 * - CRUD phân công chấm điểm
 * - Gán học sinh Cờ đỏ vào phân công
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';

// Kiểm tra quyền: Chỉ Admin mới được quản lý phân công
requireAdmin();

define('PAGE_TITLE', 'Quản lý Phân công Chấm điểm');

$conn = getDBConnection();
$admin = getCurrentAdmin();

// ============================================================
// XỬ LÝ TÌM KIẾM & FILTER
// ============================================================
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$khoi_filter = isset($_GET['khoi']) ? intval($_GET['khoi']) : 0;
$trang_thai_filter = isset($_GET['trang_thai']) ? sanitize($_GET['trang_thai']) : '';

// ============================================================
// PHÂN TRANG
// ============================================================
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// ============================================================
// XÂY DỰNG QUERY
// ============================================================
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (hs.ho_ten LIKE ? OR hs.ma_hs LIKE ? OR lh_cham.ten_lop LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($khoi_filter > 0) {
    $where .= " AND lh_cham.khoi = ?";
    $params[] = $khoi_filter;
}

if ($trang_thai_filter !== '') {
    $where .= " AND pc.trang_thai = ?";
    $params[] = $trang_thai_filter;
}

// Đếm tổng số records
$countSql = "
    SELECT COUNT(*) as total
    FROM phan_cong_cham_diem pc
    JOIN hoc_sinh hs ON pc.hoc_sinh_id = hs.id
    JOIN lop_hoc lh ON hs.lop_id = lh.id
    JOIN lop_hoc lh_cham ON pc.lop_duoc_cham_id = lh_cham.id
    WHERE {$where}
";
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

// Lấy danh sách phân công
$sql = "
    SELECT
        pc.*,
        hs.ho_ten as ten_hoc_sinh,
        hs.ma_hs,
        hs.gioi_tinh,
        lh.ten_lop as lop_cua_hs,
        lh.khoi as khoi_cua_hs,
        lh_cham.ten_lop as lop_duoc_cham,
        lh_cham.khoi as khoi_duoc_cham,
        lh_cham.khoi_label as khoi_label_cham,
        a.ho_ten as ten_nguoi_phan_cong
    FROM phan_cong_cham_diem pc
    JOIN hoc_sinh hs ON pc.hoc_sinh_id = hs.id
    JOIN lop_hoc lh ON hs.lop_id = lh.id
    JOIN lop_hoc lh_cham ON pc.lop_duoc_cham_id = lh_cham.id
    LEFT JOIN admins a ON pc.nguoi_phan_cong = a.id
    WHERE {$where}
    ORDER BY pc.created_at DESC, lh.ten_lop, hs.ho_ten
    LIMIT ? OFFSET ?
";
$params[] = $limit;
$params[] = $offset;
$stmtList = $conn->prepare($sql);
$stmtList->execute($params);
$danhSachPhanCong = $stmtList->fetchAll();

// Lấy danh sách khối (để filter)
$stmtKhoi = $conn->query("SELECT DISTINCT khoi FROM lop_hoc ORDER BY khoi");
$cacKhoi = $stmtKhoi->fetchAll();

// Thống kê tổng quan
$stmtStats = $conn->query("
    SELECT
        COUNT(*) as tong_phan_cong,
        SUM(CASE WHEN trang_thai = 'active' THEN 1 ELSE 0 END) as dang_hoat_dong,
        SUM(CASE WHEN trang_thai = 'inactive' THEN 1 ELSE 0 END) as khong_hoat_dong,
        COUNT(DISTINCT hoc_sinh_id) as so_hoc_sinh_co_do,
        COUNT(DISTINCT lop_duoc_cham_id) as so_lop_duoc_cham
    FROM phan_cong_cham_diem
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
        .stats-card.warning { border-left-color: #ffc107; }
        .stats-card.info { border-left-color: #0dcaf0; }
        .stats-card.danger { border-left-color: #dc3545; }

        .badge-active { background-color: #198754; }
        .badge-inactive { background-color: #6c757d; }

        .table-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .search-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .avatar-student {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }

        .cross-check-icon {
            color: #0d6efd;
            font-size: 1.2rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
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
                            <i class="fas fa-users-cog text-primary"></i>
                            <?php echo PAGE_TITLE; ?>
                        </h1>
                        <div>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tạo phân công mới
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
                    <div class="col-md-3">
                        <div class="card stats-card primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Tổng phân công</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['tong_phan_cong']); ?></h3>
                                    </div>
                                    <div class="fs-1 text-primary opacity-25">
                                        <i class="fas fa-tasks"></i>
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
                                        <p class="text-muted mb-1 small">Đang hoạt động</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['dang_hoat_dong']); ?></h3>
                                    </div>
                                    <div class="fs-1 text-success opacity-25">
                                        <i class="fas fa-check-circle"></i>
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
                                        <p class="text-muted mb-1 small">Học sinh Cờ đỏ</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['so_hoc_sinh_co_do']); ?></h3>
                                    </div>
                                    <div class="fs-1 text-info opacity-25">
                                        <i class="fas fa-user-graduate"></i>
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
                                        <p class="text-muted mb-1 small">Lớp được chấm</p>
                                        <h3 class="mb-0"><?php echo number_format($stats['so_lop_duoc_cham']); ?></h3>
                                    </div>
                                    <div class="fs-1 text-warning opacity-25">
                                        <i class="fas fa-school"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form tìm kiếm & Filter -->
                <div class="search-form">
                    <form method="GET" action="" id="searchForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tìm kiếm</label>
                                <input type="text"
                                       name="search"
                                       class="form-control"
                                       placeholder="Tên học sinh, mã HS, tên lớp..."
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Khối</label>
                                <select name="khoi" class="form-select">
                                    <option value="0">-- Tất cả khối --</option>
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
                                    <option value="active" <?php echo $trang_thai_filter === 'active' ? 'selected' : ''; ?>>
                                        Đang hoạt động
                                    </option>
                                    <option value="inactive" <?php echo $trang_thai_filter === 'inactive' ? 'selected' : ''; ?>>
                                        Không hoạt động
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Tìm kiếm
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Bảng danh sách -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i>
                                Danh sách phân công
                                <span class="badge bg-primary"><?php echo number_format($totalRecords); ?></span>
                            </h5>
                            <div class="text-muted small">
                                Trang <?php echo $page; ?> / <?php echo max(1, $totalPages); ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($danhSachPhanCong) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="20%">Học sinh Cờ đỏ</th>
                                            <th width="15%">Lớp của HS</th>
                                            <th width="5%" class="text-center">
                                                <i class="fas fa-exchange-alt cross-check-icon" title="Chấm chéo"></i>
                                            </th>
                                            <th width="15%">Lớp được chấm</th>
                                            <th width="12%">Ngày phân công</th>
                                            <th width="15%">Người phân công</th>
                                            <th width="8%" class="text-center">Trạng thái</th>
                                            <th width="10%" class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stt = $offset + 1;
                                        foreach ($danhSachPhanCong as $pc):
                                        ?>
                                            <tr>
                                                <td><?php echo $stt++; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-2">
                                                            <?php
                                                            $avatar = $pc['gioi_tinh'] == 1 ? '👦' : '👧';
                                                            echo '<span class="fs-4">' . $avatar . '</span>';
                                                            ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($pc['ten_hoc_sinh']); ?></div>
                                                            <small class="text-muted">Mã: <?php echo $pc['ma_hs']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($pc['lop_cua_hs']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <i class="fas fa-arrow-right text-primary"></i>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info text-dark">
                                                        <?php echo htmlspecialchars($pc['lop_duoc_cham']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="far fa-calendar"></i>
                                                        <?php echo date('d/m/Y', strtotime($pc['ngay_phan_cong'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo isset($pc['ten_nguoi_phan_cong']) ? $pc['ten_nguoi_phan_cong'] : 'N/A'; ?>
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($pc['trang_thai'] === 'active'): ?>
                                                        <span class="badge badge-active">
                                                            <i class="fas fa-check"></i> Hoạt động
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-inactive">
                                                            <i class="fas fa-times"></i> Tạm dừng
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center table-actions">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="edit.php?id=<?php echo $pc['id']; ?>"
                                                           class="btn btn-warning"
                                                           title="Chỉnh sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button"
                                                                class="btn btn-danger btn-delete"
                                                                data-id="<?php echo $pc['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($pc['ten_hoc_sinh']); ?>"
                                                                title="Xóa">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Phân trang -->
                            <?php if ($totalPages > 1): ?>
                                <div class="card-footer bg-white">
                                    <nav>
                                        <ul class="pagination pagination-sm justify-content-center mb-0">
                                            <!-- Previous -->
                                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&khoi=<?php echo $khoi_filter; ?>&trang_thai=<?php echo $trang_thai_filter; ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>

                                            <!-- Page Numbers -->
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

                                            <!-- Next -->
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
                            <!-- Empty State -->
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <h5 class="mt-3">Chưa có phân công nào</h5>
                                <p class="text-muted">
                                    Hãy tạo phân công chấm điểm mới để bắt đầu hệ thống thi đua.
                                </p>
                                <a href="create.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tạo phân công đầu tiên
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

        </main>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Xóa phân công
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;

                Swal.fire({
                    title: 'Xác nhận xóa?',
                    html: `Bạn có chắc muốn xóa phân công của<br><strong>${name}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash"></i> Xóa',
                    cancelButtonText: 'Hủy',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect to delete handler
                        window.location.href = `delete.php?id=${id}`;
                    }
                });
            });
        });

        // Auto dismiss alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
