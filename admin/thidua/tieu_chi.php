<?php
/**
 * ==============================================
 * QUẢN LÝ TIÊU CHÍ THI ĐUA - CHỈ ADMIN
 * ==============================================
 */

require_once '../../includes/config.php';
require_once '../../includes/permission_helper.php';

requireAdmin();

define('PAGE_TITLE', 'Quản lý tiêu chí thi đua');

$conn = getDBConnection();
$admin = getCurrentAdmin();

$message = '';
$messageType = '';

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'add') {
        $maTieuChi = sanitize($_POST['ma_tieu_chi']);
        $tenTieuChi = sanitize($_POST['ten_tieu_chi']);
        $moTa = sanitize($_POST['mo_ta']);
        $diemToiDa = floatval($_POST['diem_toi_da']);
        $trongSo = intval($_POST['trong_so']);
        $thuTu = intval($_POST['thu_tu']);

        // Check trùng mã
        $stmt = $conn->prepare("SELECT id FROM tieu_chi_thi_dua WHERE ma_tieu_chi = ?");
        $stmt->execute(array($maTieuChi));
        if ($stmt->fetch()) {
            $message = 'Mã tiêu chí đã tồn tại!';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO tieu_chi_thi_dua (ma_tieu_chi, ten_tieu_chi, mo_ta, diem_toi_da, trong_so, thu_tu, trang_thai) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute(array($maTieuChi, $tenTieuChi, $moTa, $diemToiDa, $trongSo, $thuTu));
            $message = 'Thêm tiêu chí thành công!';
            $messageType = 'success';
            logThiduaActivity('add_tieu_chi', $admin['id'], 'admin', "Thêm tiêu chí: {$tenTieuChi}");
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $tenTieuChi = sanitize($_POST['ten_tieu_chi']);
        $moTa = sanitize($_POST['mo_ta']);
        $diemToiDa = floatval($_POST['diem_toi_da']);
        $trongSo = intval($_POST['trong_so']);
        $thuTu = intval($_POST['thu_tu']);
        $trangThai = isset($_POST['trang_thai']) ? 'active' : 'inactive';

        $stmt = $conn->prepare("UPDATE tieu_chi_thi_dua SET ten_tieu_chi = ?, mo_ta = ?, diem_toi_da = ?, trong_so = ?, thu_tu = ?, trang_thai = ? WHERE id = ?");
        $stmt->execute(array($tenTieuChi, $moTa, $diemToiDa, $trongSo, $thuTu, $trangThai, $id));
        $message = 'Cập nhật tiêu chí thành công!';
        $messageType = 'success';
        logThiduaActivity('edit_tieu_chi', $admin['id'], 'admin', "Sửa tiêu chí: {$tenTieuChi}");
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);

        // Kiểm tra đã có điểm chấm chưa
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM diem_thi_dua_tuan WHERE tieu_chi_id = ?");
        $stmtCheck->execute(array($id));
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            $message = "Không thể xóa! Đã có {$count} bản ghi điểm sử dụng tiêu chí này.";
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM tieu_chi_thi_dua WHERE id = ?");
            $stmt->execute(array($id));
            $message = 'Xóa tiêu chí thành công!';
            $messageType = 'success';
        }
    }
}

// Lấy danh sách tiêu chí
$stmtList = $conn->query("SELECT * FROM tieu_chi_thi_dua ORDER BY thu_tu, id");
$danhSach = $stmtList->fetchAll();

// Tính tổng trọng số
$tongTrongSo = 0;
foreach ($danhSach as $tc) {
    if ($tc['trang_thai'] == 1 || $tc['trang_thai'] === 'active') $tongTrongSo += $tc['trong_so'];
}

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
        .tc-card {
            background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;
            padding: 20px; margin-bottom: 12px; transition: box-shadow 0.2s;
        }
        .tc-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .tc-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 20px; flex-shrink: 0;
        }
        .weight-bar { height: 10px; border-radius: 5px; background: #e5e7eb; flex: 1; overflow: hidden; }
        .weight-bar-fill { height: 100%; border-radius: 5px; transition: width 0.3s; }
        .weight-warning { background: #FEF3C7; color: #92400E; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include '../../admin/includes/sidebar.php'; ?>

        <main class="admin-main">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h1 class="h2">
                        <i class="fas fa-list-check text-primary"></i>
                        <?php echo PAGE_TITLE; ?>
                    </h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Thêm tiêu chí
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Tổng trọng số -->
            <?php if ($tongTrongSo != 100): ?>
                <div class="weight-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Cảnh báo:</strong> Tổng trọng số hiện tại là <strong><?php echo $tongTrongSo; ?>%</strong> (phải = 100%).
                    Vui lòng điều chỉnh lại!
                </div>
            <?php else: ?>
                <div class="alert alert-success py-2">
                    <i class="fas fa-check-circle"></i> Tổng trọng số: <strong>100%</strong> - Hợp lệ
                </div>
            <?php endif; ?>

            <!-- Danh sách tiêu chí -->
            <?php foreach ($danhSach as $tc):
                $maTc = $tc['ma_tieu_chi'];
                $icon = isset($criteriaIcons[$maTc]) ? $criteriaIcons[$maTc] : 'fa-star';
                $color = isset($criteriaColors[$maTc]) ? $criteriaColors[$maTc] : '#6B7280';
                $weightPct = ($tongTrongSo > 0) ? round(($tc['trong_so'] / $tongTrongSo) * 100) : 0;
            ?>
            <div class="tc-card <?php echo ($tc['trang_thai'] == 0 && $tc['trang_thai'] !== 'active') ? 'opacity-50' : ''; ?>">
                <div class="d-flex align-items-center gap-3">
                    <div class="tc-icon" style="background: <?php echo $color; ?>;">
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($tc['ten_tieu_chi']); ?></h5>
                            <code class="small"><?php echo htmlspecialchars($tc['ma_tieu_chi']); ?></code>
                            <?php if ($tc['trang_thai'] == 0 && $tc['trang_thai'] !== 'active'): ?>
                                <span class="badge bg-secondary">Tắt</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($tc['mo_ta']); ?></p>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-primary">Trọng số: <?php echo $tc['trong_so']; ?>%</span>
                            <span class="badge bg-info">Max: <?php echo $tc['diem_toi_da']; ?> điểm</span>
                            <span class="badge bg-secondary">Thứ tự: <?php echo $tc['thu_tu']; ?></span>
                            <div class="weight-bar ms-2" style="max-width: 150px;">
                                <div class="weight-bar-fill" style="width: <?php echo $tc['trong_so']; ?>%; background: <?php echo $color; ?>;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm"
                                onclick='editTieuChi(<?php echo json_encode($tc); ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm"
                                onclick="deleteTieuChi(<?php echo $tc['id']; ?>, '<?php echo addslashes($tc['ten_tieu_chi']); ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($danhSach)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-clipboard-list" style="font-size: 3rem; opacity: 0.3;"></i>
                    <h5 class="mt-3">Chưa có tiêu chí nào</h5>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Thêm tiêu chí</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Mã tiêu chí *</label>
                            <input type="text" name="ma_tieu_chi" class="form-control" required
                                   placeholder="vd: hoc_tap, ne_nep, ve_sinh..." pattern="[a-z_]+">
                            <small class="text-muted">Chỉ chữ thường và dấu gạch dưới</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên tiêu chí *</label>
                            <input type="text" name="ten_tieu_chi" class="form-control" required placeholder="vd: Học tập">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả</label>
                            <textarea name="mo_ta" class="form-control" rows="2" placeholder="Mô tả chi tiết..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Điểm tối đa</label>
                                <input type="number" name="diem_toi_da" class="form-control" value="10" min="1" step="0.5">
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Trọng số (%)</label>
                                <input type="number" name="trong_so" class="form-control" value="20" min="1" max="100">
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Thứ tự</label>
                                <input type="number" name="thu_tu" class="form-control" value="<?php echo count($danhSach) + 1; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Sửa tiêu chí</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Mã tiêu chí</label>
                            <input type="text" id="edit_ma" class="form-control" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên tiêu chí *</label>
                            <input type="text" name="ten_tieu_chi" id="edit_ten" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả</label>
                            <textarea name="mo_ta" id="edit_mo_ta" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Điểm tối đa</label>
                                <input type="number" name="diem_toi_da" id="edit_diem_max" class="form-control" min="1" step="0.5">
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Trọng số (%)</label>
                                <input type="number" name="trong_so" id="edit_trong_so" class="form-control" min="1" max="100">
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Thứ tự</label>
                                <input type="number" name="thu_tu" id="edit_thu_tu" class="form-control">
                            </div>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="trang_thai" id="edit_trang_thai" class="form-check-input">
                            <label class="form-check-label" for="edit_trang_thai">Đang hoạt động</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editTieuChi(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_ma').value = data.ma_tieu_chi;
            document.getElementById('edit_ten').value = data.ten_tieu_chi;
            document.getElementById('edit_mo_ta').value = data.mo_ta || '';
            document.getElementById('edit_diem_max').value = data.diem_toi_da;
            document.getElementById('edit_trong_so').value = data.trong_so;
            document.getElementById('edit_thu_tu').value = data.thu_tu;
            document.getElementById('edit_trang_thai').checked = (data.trang_thai == 1 || data.trang_thai === 'active');
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function deleteTieuChi(id, name) {
            if (confirm('Bạn có chắc muốn xóa tiêu chí "' + name + '"?\nChỉ xóa được nếu chưa có điểm nào sử dụng.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
