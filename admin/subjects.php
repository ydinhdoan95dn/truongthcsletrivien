<?php
/**
 * ==============================================
 * QUẢN LÝ MÔN HỌC - CHỈ ADMIN
 * ==============================================
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

requireAdmin();

$admin = getCurrentAdminFull();
$conn = getDBConnection();

$message = '';
$messageType = '';

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'add') {
        $tenMon = sanitize($_POST['ten_mon']);
        $icon = sanitize($_POST['icon']);
        $mauSac = sanitize($_POST['mau_sac']);
        $thuTu = intval($_POST['thu_tu']);

        // Kiểm tra tên môn tồn tại
        $stmt = $conn->prepare("SELECT id FROM mon_hoc WHERE ten_mon = ?");
        $stmt->execute(array($tenMon));
        if ($stmt->fetch()) {
            $message = 'Tên môn học đã tồn tại!';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO mon_hoc (ten_mon, icon, mau_sac, thu_tu, trang_thai) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute(array($tenMon, $icon, $mauSac, $thuTu));

            $message = 'Thêm môn học thành công!';
            $messageType = 'success';
            logActivity('admin', $admin['id'], 'Thêm môn học', 'Thêm: ' . $tenMon);
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $tenMon = sanitize($_POST['ten_mon']);
        $icon = sanitize($_POST['icon']);
        $mauSac = sanitize($_POST['mau_sac']);
        $trangThai = isset($_POST['trang_thai']) ? 1 : 0;
        $thuTu = intval($_POST['thu_tu']);

        $stmt = $conn->prepare("UPDATE mon_hoc SET ten_mon = ?, icon = ?, mau_sac = ?, trang_thai = ?, thu_tu = ? WHERE id = ?");
        $stmt->execute(array($tenMon, $icon, $mauSac, $trangThai, $thuTu, $id));

        $message = 'Cập nhật môn học thành công!';
        $messageType = 'success';
        logActivity('admin', $admin['id'], 'Sửa môn học', 'Sửa: ' . $tenMon);
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);

        // Kiểm tra đề thi liên quan
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM de_thi WHERE mon_hoc_id = ?");
        $stmtCheck->execute(array($id));
        $dtCount = $stmtCheck->fetchColumn();

        if ($dtCount > 0) {
            $message = 'Không thể xóa môn học đang có ' . $dtCount . ' đề thi liên quan!';
            $messageType = 'error';
        } else {
            // Kiểm tra tài liệu liên quan
            $stmtCheck2 = $conn->prepare("SELECT COUNT(*) FROM tai_lieu WHERE mon_hoc_id = ?");
            $stmtCheck2->execute(array($id));
            $tlCount = $stmtCheck2->fetchColumn();

            if ($tlCount > 0) {
                $message = 'Không thể xóa môn học đang có ' . $tlCount . ' tài liệu liên quan!';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("DELETE FROM mon_hoc WHERE id = ?");
                $stmt->execute(array($id));
                $message = 'Xóa môn học thành công!';
                $messageType = 'success';
                logActivity('admin', $admin['id'], 'Xóa môn học', 'ID: ' . $id);
            }
        }
    }
}

// Lấy danh sách môn học + đếm đề thi, tài liệu
$stmtMon = $conn->query("
    SELECT mh.*,
           (SELECT COUNT(*) FROM de_thi dt WHERE dt.mon_hoc_id = mh.id) as so_de_thi,
           (SELECT COUNT(*) FROM tai_lieu tl WHERE tl.mon_hoc_id = mh.id) as so_tai_lieu
    FROM mon_hoc mh
    ORDER BY mh.thu_tu, mh.ten_mon
");
$monList = $stmtMon->fetchAll();

// Danh sách icon có sẵn
$iconOptions = array(
    'calculator' => 'Calculator (Toán)',
    'book-open' => 'Book Open (Văn)',
    'globe' => 'Globe (Tiếng Anh)',
    'leaf' => 'Leaf (Sinh học)',
    'flask' => 'Flask (Hóa học)',
    'atom' => 'Atom (Vật lý)',
    'map' => 'Map (Địa lý)',
    'book' => 'Book (Lịch sử)',
    'heart' => 'Heart (GDCD)',
    'music' => 'Music (Âm nhạc)',
    'palette' => 'Palette (Mỹ thuật)',
    'monitor' => 'Monitor (Tin học)',
    'activity' => 'Activity (Thể dục)',
    'star' => 'Star (Khác)',
    'pen-tool' => 'Pen (Công nghệ)'
);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý môn học - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        .status-active { color: #10B981; font-weight: 600; }
        .status-inactive { color: #EF4444; font-weight: 600; }
        .subject-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            color: white; font-size: 1.1rem;
        }
        .subject-icon i { width: 20px; height: 20px; }
        .color-preview {
            width: 28px; height: 28px; border-radius: 6px;
            display: inline-block; vertical-align: middle;
            border: 2px solid #E5E7EB;
        }
        .stat-badge {
            display: inline-block; padding: 2px 8px; border-radius: 4px;
            font-size: 0.75rem; font-weight: 600; margin-right: 4px;
        }
        .stat-badge.exam { background: #FEF3C7; color: #D97706; }
        .stat-badge.doc { background: #DBEAFE; color: #2563EB; }
    </style>
</head>

<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
                <h1 style="font-size: 1.5rem; font-weight: 700; color: #1F2937;">📚 Quản lý môn học</h1>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i data-feather="plus"></i> Thêm môn học
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
                            <th style="padding: 16px; text-align: center; font-weight: 600; color: #6B7280; width: 60px;">STT</th>
                            <th style="padding: 16px; text-align: left; font-weight: 600; color: #6B7280;">Môn học</th>
                            <th style="padding: 16px; text-align: center; font-weight: 600; color: #6B7280;">Icon</th>
                            <th style="padding: 16px; text-align: center; font-weight: 600; color: #6B7280;">Màu sắc</th>
                            <th style="padding: 16px; text-align: center; font-weight: 600; color: #6B7280;">Đề thi / Tài liệu</th>
                            <th style="padding: 16px; text-align: center; font-weight: 600; color: #6B7280;">Trạng thái</th>
                            <th style="padding: 16px; text-align: right; font-weight: 600; color: #6B7280;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monList)): ?>
                            <tr><td colspan="7" style="padding: 40px; text-align: center; color: #9CA3AF;">Chưa có môn học nào</td></tr>
                        <?php else: ?>
                            <?php foreach ($monList as $mon): ?>
                                <tr style="border-top: 1px solid #E5E7EB;">
                                    <td style="padding: 16px; text-align: center; font-weight: 600;"><?php echo $mon['thu_tu']; ?></td>
                                    <td style="padding: 16px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div class="subject-icon" style="background: <?php echo htmlspecialchars($mon['mau_sac']); ?>;">
                                                <i data-feather="<?php echo htmlspecialchars($mon['icon']); ?>"></i>
                                            </div>
                                            <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($mon['ten_mon']); ?></div>
                                        </div>
                                    </td>
                                    <td style="padding: 16px; text-align: center;">
                                        <code style="background: #F3F4F6; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;"><?php echo htmlspecialchars($mon['icon']); ?></code>
                                    </td>
                                    <td style="padding: 16px; text-align: center;">
                                        <span class="color-preview" style="background: <?php echo htmlspecialchars($mon['mau_sac']); ?>;"></span>
                                        <code style="font-size: 0.75rem; color: #6B7280; margin-left: 4px;"><?php echo htmlspecialchars($mon['mau_sac']); ?></code>
                                    </td>
                                    <td style="padding: 16px; text-align: center;">
                                        <span class="stat-badge exam"><?php echo $mon['so_de_thi']; ?> đề</span>
                                        <span class="stat-badge doc"><?php echo $mon['so_tai_lieu']; ?> tài liệu</span>
                                    </td>
                                    <td style="padding: 16px; text-align: center;">
                                        <?php if ($mon['trang_thai'] == 1): ?>
                                            <span class="status-active">&#10003; Mở</span>
                                        <?php else: ?>
                                            <span class="status-inactive">&#10007; Đóng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px; text-align: right;">
                                        <button class="btn btn-ghost btn-sm" onclick='editMon(<?php echo json_encode($mon); ?>)' title="Sửa">
                                            <i data-feather="edit-2"></i>
                                        </button>
                                        <button class="btn btn-ghost btn-sm" style="color: #EF4444;"
                                            onclick="deleteMon(<?php echo $mon['id']; ?>, '<?php echo addslashes($mon['ten_mon']); ?>')" title="Xóa">
                                            <i data-feather="trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            <h3 class="modal-title">Thêm môn học mới</h3>

            <form method="POST">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label class="form-label">Tên môn học *</label>
                    <input type="text" name="ten_mon" class="form-input" required placeholder="vd: Toán, Văn, Tiếng Anh...">
                </div>

                <div class="form-group">
                    <label class="form-label">Icon (Feather Icons)</label>
                    <select name="icon" class="form-input">
                        <?php foreach ($iconOptions as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Màu sắc</label>
                    <input type="color" name="mau_sac" class="form-input" value="#4F46E5" style="height: 45px; padding: 4px;">
                </div>

                <div class="form-group">
                    <label class="form-label">Thứ tự hiển thị</label>
                    <input type="number" name="thu_tu" class="form-input" value="<?php echo count($monList) + 1; ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i data-feather="plus"></i> Thêm môn học
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            <h3 class="modal-title">Chỉnh sửa môn học</h3>

            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label class="form-label">Tên môn học *</label>
                    <input type="text" name="ten_mon" id="edit_ten_mon" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Icon (Feather Icons)</label>
                    <select name="icon" id="edit_icon" class="form-input">
                        <?php foreach ($iconOptions as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Màu sắc</label>
                    <input type="color" name="mau_sac" id="edit_mau_sac" class="form-input" style="height: 45px; padding: 4px;">
                </div>

                <div class="form-group">
                    <label class="form-label">Thứ tự hiển thị</label>
                    <input type="number" name="thu_tu" id="edit_thu_tu" class="form-input">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="trang_thai" id="edit_trang_thai" style="width: 20px; height: 20px;">
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

        function editMon(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_ten_mon').value = data.ten_mon;
            document.getElementById('edit_icon').value = data.icon || 'book';
            document.getElementById('edit_mau_sac').value = data.mau_sac || '#4F46E5';
            document.getElementById('edit_thu_tu').value = data.thu_tu;
            document.getElementById('edit_trang_thai').checked = data.trang_thai == 1;

            document.getElementById('editModal').classList.add('active');
            feather.replace();
        }

        function deleteMon(id, name) {
            if (confirm('Bạn có chắc muốn xóa môn "' + name + '"?\nCác đề thi và tài liệu liên quan sẽ không bị xóa.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>

</html>
