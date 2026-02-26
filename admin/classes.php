<?php
/**
 * ==============================================
 * QUẢN LÝ LỚP HỌC - CHỈ ADMIN
 * ==============================================
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

// Chỉ admin mới được truy cập
requireAdmin();

$admin = getCurrentAdminFull();
$conn = getDBConnection();

$message = '';
$messageType = '';

// Lấy danh sách giáo viên để làm GVCN
$stmtGV = $conn->query("SELECT id, ho_ten FROM admins WHERE role = 'gvcn' AND trang_thai = 1 ORDER BY ho_ten");
$gvList = $stmtGV->fetchAll();

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'add') {
        $tenLop = sanitize($_POST['ten_lop']);
        $khoi = intval($_POST['khoi']);
        $khoiLabel = sanitize($_POST['khoi_label']);
        $gvcnId = !empty($_POST['gvcn_id']) ? intval($_POST['gvcn_id']) : null;
        $thuTu = intval($_POST['thu_tu']);

        // Kiểm tra tên lớp tồn tại
        $stmt = $conn->prepare("SELECT id FROM lop_hoc WHERE ten_lop = ?");
        $stmt->execute(array($tenLop));
        if ($stmt->fetch()) {
            $message = 'Tên lớp đã tồn tại!';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO lop_hoc (ten_lop, khoi, khoi_label, gvcn_id, trang_thai, thu_tu) VALUES (?, ?, ?, ?, 1, ?)");
            $stmt->execute(array($tenLop, $khoi, $khoiLabel, $gvcnId, $thuTu));

            $message = 'Thêm lớp học thành công!';
            $messageType = 'success';
            logActivity('admin', $admin['id'], 'Thêm lớp học', 'Thêm: ' . $tenLop);
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $tenLop = sanitize($_POST['ten_lop']);
        $khoi = intval($_POST['khoi']);
        $khoiLabel = sanitize($_POST['khoi_label']);
        $gvcnId = !empty($_POST['gvcn_id']) ? intval($_POST['gvcn_id']) : null;
        $trangThai = isset($_POST['trang_thai']) ? 1 : 0;
        $thuTu = intval($_POST['thu_tu']);

        // Cập nhật
        $stmt = $conn->prepare("UPDATE lop_hoc SET ten_lop = ?, khoi = ?, khoi_label = ?, gvcn_id = ?, trang_thai = ?, thu_tu = ? WHERE id = ?");
        $stmt->execute(array($tenLop, $khoi, $khoiLabel, $gvcnId, $trangThai, $thuTu, $id));

        $message = 'Cập nhật thành công!';
        $messageType = 'success';
        logActivity('admin', $admin['id'], 'Sửa lớp học', 'Sửa: ' . $tenLop);
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);

        // Kiểm tra xem có học sinh trong lớp không
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM hoc_sinh WHERE lop_id = ?");
        $stmtCheck->execute(array($id));
        $hsCount = $stmtCheck->fetchColumn();

        if ($hsCount > 0) {
            $message = 'Không thể xóa lớp đang có ' . $hsCount . ' học sinh! Vui lòng chuyển học sinh sang lớp khác trước.';
            $messageType = 'error';
        } else {
            // Kiểm tra xem có đề thi cho lớp không
            $stmtCheckEx = $conn->prepare("SELECT COUNT(*) FROM de_thi WHERE lop_id = ?");
            $stmtCheckEx->execute(array($id));
            $exCount = $stmtCheckEx->fetchColumn();

            if ($exCount > 0) {
                $message = 'Không thể xóa lớp đang có ' . $exCount . ' đề thi liên quan!';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("DELETE FROM lop_hoc WHERE id = ?");
                $stmt->execute(array($id));
                $message = 'Xóa lớp học thành công!';
                $messageType = 'success';
                logActivity('admin', $admin['id'], 'Xóa lớp học', 'ID: ' . $id);
            }
        }
    }
}

// Lấy danh sách lớp
$stmtLop = $conn->query("
    SELECT lh.*, a.ho_ten as ten_gvcn,
           (SELECT COUNT(*) FROM hoc_sinh hs WHERE hs.lop_id = lh.id) as si_so_thuc
    FROM lop_hoc lh
    LEFT JOIN admins a ON lh.gvcn_id = a.id
    ORDER BY lh.thu_tu, lh.ten_lop
");
$lopList = $stmtLop->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý lớp học -
        <?php echo SITE_NAME; ?>
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        .status-active {
            color: #10B981;
            font-weight: 600;
        }

        .status-inactive {
            color: #EF4444;
            font-weight: 600;
        }

        .khoi-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            background: #EEF2FF;
            color: #4F46E5;
            font-size: 0.75rem;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
                <h1 style="font-size: 1.5rem; font-weight: 700; color: #1F2937;">🏫 Quản lý lớp học</h1>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i data-feather="plus"></i> Thêm lớp mới
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"
                    style="margin-bottom: 20px; padding: 16px; border-radius: 12px; background: <?php echo $messageType === 'success' ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'; ?>; color: <?php echo $messageType === 'success' ? '#10B981' : '#EF4444'; ?>;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Table -->
            <div class="card" style="padding: 0; overflow: hidden; background: white; border-radius: 16px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #F9FAFB;">
                            <th
                                style="padding: 16px; text-align: center; font-weight: 600; color: #6B7280; width: 60px;">
                                STT</th>
                            <th style="padding: 16px; text-align: left; font-weight: 600; color: #6B7280;">Tên lớp</th>
                            <th style="padding: 16px; text-align: center; font-weight: 600; color: #6B7280;">Khối</th>
                            <th style="padding: 16px; text-align: left; font-weight: 600; color: #6B7280;">GV Chủ nhiệm
                            </th>
                            <th style="padding: 16px; text-align: center; font-weight: 600; color: #6B7280;">Sĩ số</th>
                            <th style="padding: 16px; text-align: center; font-weight: 600; color: #6B7280;">Trạng thái
                            </th>
                            <th style="padding: 16px; text-align: right; font-weight: 600; color: #6B7280;">Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lopList as $lop): ?>
                            <tr style="border-top: 1px solid #E5E7EB;">
                                <td style="padding: 16px; text-align: center; font-weight: 600;">
                                    <?php echo $lop['thu_tu']; ?>
                                </td>
                                <td style="padding: 16px;">
                                    <div style="font-weight: 700; color: #1e293b;">
                                        <?php echo htmlspecialchars($lop['ten_lop']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <?php echo htmlspecialchars($lop['khoi_label']); ?>
                                    </div>
                                </td>
                                <td style="padding: 16px; text-align: center;">
                                    <span class="khoi-badge">Khối
                                        <?php echo $lop['khoi']; ?>
                                    </span>
                                </td>
                                <td style="padding: 16px;">
                                    <?php echo $lop['ten_gvcn'] ? htmlspecialchars($lop['ten_gvcn']) : '<span style="color: #9CA3AF;">Chưa phân công</span>'; ?>
                                </td>
                                <td style="padding: 16px; text-align: center;">
                                    <span style="font-weight: 600;">
                                        <?php echo $lop['si_so_thuc']; ?>
                                    </span>
                                    <span style="color: #9CA3AF; font-size: 0.8rem;">/
                                        <?php echo $lop['si_so']; ?>
                                    </span>
                                </td>
                                <td style="padding: 16px; text-align: center;">
                                    <?php if ($lop['trang_thai'] == 1): ?>
                                        <span class="status-active">✓ Mở</span>
                                    <?php else: ?>
                                        <span class="status-inactive">✗ Đóng</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 16px; text-align: right;">
                                    <button class="btn btn-ghost btn-sm" onclick='editLop(<?php echo json_encode($lop); ?>)'
                                        title="Sửa">
                                        <i data-feather="edit-2"></i>
                                    </button>
                                    <button class="btn btn-ghost btn-sm" style="color: #EF4444;"
                                        onclick="deleteLop(<?php echo $lop['id']; ?>, '<?php echo addslashes($lop['ten_lop']); ?>')"
                                        title="Xóa">
                                        <i data-feather="trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            <h3 class="modal-title">Thêm lớp học mới</h3>

            <form method="POST">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label class="form-label">Tên lớp *</label>
                    <input type="text" name="ten_lop" class="form-input" required placeholder="vd: 6A1">
                </div>

                <div class="form-group">
                    <label class="form-label">Khối *</label>
                    <select name="khoi" class="form-input" required>
                        <option value="6">Khối 6</option>
                        <option value="7">Khối 7</option>
                        <option value="8">Khối 8</option>
                        <option value="9">Khối 9</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nhãn khối (Khoi Label)</label>
                    <input type="text" name="khoi_label" class="form-input" placeholder="vd: Khối 6 - 2023">
                </div>

                <div class="form-group">
                    <label class="form-label">Giáo viên chủ nhiệm</label>
                    <select name="gvcn_id" class="form-input">
                        <option value="">-- Chọn giáo viên --</option>
                        <?php foreach ($gvList as $gv): ?>
                            <option value="<?php echo $gv['id']; ?>">
                                <?php echo htmlspecialchars($gv['ho_ten']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Thứ tự hiển thị</label>
                    <input type="number" name="thu_tu" class="form-input" value="1">
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i data-feather="plus"></i> Thêm lớp học
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            <h3 class="modal-title">Chỉnh sửa lớp học</h3>

            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label class="form-label">Tên lớp *</label>
                    <input type="text" name="ten_lop" id="edit_ten_lop" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Khối *</label>
                    <select name="khoi" id="edit_khoi" class="form-input" required>
                        <option value="6">Khối 6</option>
                        <option value="7">Khối 7</option>
                        <option value="8">Khối 8</option>
                        <option value="9">Khối 9</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nhãn khối (Khoi Label)</label>
                    <input type="text" name="khoi_label" id="edit_khoi_label" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Giáo viên chủ nhiệm</label>
                    <select name="gvcn_id" id="edit_gvcn_id" class="form-input">
                        <option value="">-- Chọn giáo viên --</option>
                        <?php foreach ($gvList as $gv): ?>
                            <option value="<?php echo $gv['id']; ?>">
                                <?php echo htmlspecialchars($gv['ho_ten']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Thứ tự hiển thị</label>
                    <input type="number" name="thu_tu" id="edit_thu_tu" class="form-input">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="trang_thai" id="edit_trang_thai"
                            style="width: 20px; height: 20px;">
                        <span>Trạng thái hoạt động (Mở)</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i data-feather="save"></i> Lưu thay đổi
                </button>
            </form>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script>
        feather.replace();

        function showAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function editLop(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_ten_lop').value = data.ten_lop;
            document.getElementById('edit_khoi').value = data.khoi;
            document.getElementById('edit_khoi_label').value = data.khoi_label || '';
            document.getElementById('edit_gvcn_id').value = data.gvcn_id || '';
            document.getElementById('edit_thu_tu').value = data.thu_tu;
            document.getElementById('edit_trang_thai').checked = data.trang_thai == 1;

            document.getElementById('editModal').classList.add('active');
        }

        function deleteLop(id, name) {
            if (confirm('Bạn có chắc muốn xóa lớp "' + name + '"?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>

</html>