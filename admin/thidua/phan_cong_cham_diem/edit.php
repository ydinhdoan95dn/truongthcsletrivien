<?php
/**
 * ==============================================
 * CHỈNH SỬA PHÂN CÔNG CHẤM ĐIỂM
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';

// Kiểm tra quyền Admin
requireAdmin();

define('PAGE_TITLE', 'Chỉnh sửa phân công chấm điểm');

$conn = getDBConnection();
$admin = getCurrentAdmin();

$errors = [];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $_SESSION['error'] = 'ID không hợp lệ!';
    header('Location: index.php');
    exit;
}

// ============================================================
// LẤY THÔNG TIN PHÂN CÔNG HIỆN TẠI
// ============================================================
$stmtCurrent = $conn->prepare("
    SELECT
        pc.*,
        hs.ho_ten as ten_hoc_sinh,
        hs.ma_hs,
        hs.gioi_tinh,
        hs.lop_id as lop_cua_hs,
        lh_hs.ten_lop as ten_lop_hs,
        lh_hs.khoi as khoi_hs,
        lh_cham.ten_lop as ten_lop_cham
    FROM phan_cong_cham_diem pc
    JOIN hoc_sinh hs ON pc.hoc_sinh_id = hs.id
    JOIN lop_hoc lh_hs ON hs.lop_id = lh_hs.id
    JOIN lop_hoc lh_cham ON pc.lop_duoc_cham_id = lh_cham.id
    WHERE pc.id = ?
");
$stmtCurrent->execute([$id]);
$phanCong = $stmtCurrent->fetch();

if (!$phanCong) {
    $_SESSION['error'] = 'Không tìm thấy phân công!';
    header('Location: index.php');
    exit;
}

// ============================================================
// XỬ LÝ FORM SUBMIT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Token bảo mật không hợp lệ!';
    } else {
        $lop_duoc_cham_id = isset($_POST['lop_duoc_cham_id']) ? intval($_POST['lop_duoc_cham_id']) : 0;
        $ngay_phan_cong = isset($_POST['ngay_phan_cong']) ? sanitize($_POST['ngay_phan_cong']) : '';
        $ghi_chu = isset($_POST['ghi_chu']) ? sanitize($_POST['ghi_chu']) : '';
        $trang_thai = isset($_POST['trang_thai']) ? sanitize($_POST['trang_thai']) : 'active';

        // Validation
        if ($lop_duoc_cham_id <= 0) {
            $errors[] = 'Vui lòng chọn lớp được chấm!';
        } else {
            // Kiểm tra lớp tồn tại
            $stmtLop = $conn->prepare("SELECT id FROM lop_hoc WHERE id = ?");
            $stmtLop->execute([$lop_duoc_cham_id]);
            if (!$stmtLop->fetch()) {
                $errors[] = 'Lớp được chấm không tồn tại!';
            }
        }

        // Kiểm tra CHẤM CHÉO
        if ($phanCong['lop_cua_hs'] == $lop_duoc_cham_id) {
            $errors[] = 'LOGIC CHẤM CHÉO: Học sinh không được chấm lớp của chính mình!';
        }

        // Kiểm tra trùng lặp (ngoại trừ chính nó)
        if (empty($errors)) {
            $stmtCheck = $conn->prepare("
                SELECT id FROM phan_cong_cham_diem
                WHERE hoc_sinh_id = ?
                  AND lop_duoc_cham_id = ?
                  AND id != ?
            ");
            $stmtCheck->execute([$phanCong['hoc_sinh_id'], $lop_duoc_cham_id, $id]);
            if ($stmtCheck->fetch()) {
                $errors[] = 'Phân công này đã tồn tại! Học sinh đã được phân công chấm lớp này rồi.';
            }
        }

        // Kiểm tra ngày
        if (empty($ngay_phan_cong) || !strtotime($ngay_phan_cong)) {
            $errors[] = 'Ngày phân công không hợp lệ!';
        }

        // Thực hiện update
        if (empty($errors)) {
            try {
                $sql = "
                    UPDATE phan_cong_cham_diem
                    SET lop_duoc_cham_id = ?,
                        ngay_phan_cong = ?,
                        ghi_chu = ?,
                        trang_thai = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ";

                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([
                    $lop_duoc_cham_id,
                    $ngay_phan_cong,
                    $ghi_chu,
                    $trang_thai,
                    $id
                ]);

                if ($result) {
                    // Log activity
                    logThiduaActivity(
                        'phan_cong_cham_diem',
                        $admin['id'],
                        'admin',
                        "Cập nhật phân công #{$id}: {$phanCong['ten_hoc_sinh']} chấm lớp #{$lop_duoc_cham_id}",
                        $id,
                        'phan_cong',
                        [
                            'lop_duoc_cham_id_old' => $phanCong['lop_duoc_cham_id'],
                            'trang_thai_old' => $phanCong['trang_thai']
                        ],
                        [
                            'lop_duoc_cham_id' => $lop_duoc_cham_id,
                            'trang_thai' => $trang_thai
                        ]
                    );

                    $_SESSION['success'] = 'Cập nhật phân công thành công!';
                    header('Location: index.php');
                    exit;
                } else {
                    $errors[] = 'Không thể cập nhật phân công!';
                }
            } catch (PDOException $e) {
                $errors[] = 'Lỗi database: ' . $e->getMessage();
            }
        }
    }
}

// ============================================================
// LẤY DỮ LIỆU CHO FORM
// ============================================================

// Lấy danh sách lớp
$stmtLop = $conn->query("
    SELECT id, ten_lop, khoi, khoi_label, si_so
    FROM lop_hoc
    ORDER BY ten_lop
");
$danhSachLop = $stmtLop->fetchAll();

// Group theo khối
$lopTheoKhoi = [];
foreach ($danhSachLop as $lop) {
    $khoi = $lop['khoi'];
    if (!isset($lopTheoKhoi[$khoi])) {
        $lopTheoKhoi[$khoi] = [];
    }
    $lopTheoKhoi[$khoi][] = $lop;
}
ksort($lopTheoKhoi);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?> - Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        .form-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .form-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border-left: 4px solid #ffc107;
            margin-bottom: 1.5rem;
        }
        .student-readonly {
            background: #e9ecef;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 2px solid #dee2e6;
        }
        .student-readonly .avatar {
            font-size: 3rem;
        }
        .locked-info {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
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
                            <i class="fas fa-edit text-warning"></i>
                            <?php echo PAGE_TITLE; ?>
                        </h1>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại danh sách
                        </a>
                    </div>
                </div>

                <!-- Errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle"></i> Có lỗi xảy ra!
                        </h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <!-- Thông tin học sinh (Readonly) -->
                    <div class="student-readonly mb-4">
                        <div class="locked-info">
                            <i class="fas fa-lock text-warning"></i>
                            <strong>Lưu ý:</strong> Không thể thay đổi học sinh sau khi đã tạo phân công.
                            Nếu muốn thay đổi, vui lòng xóa và tạo phân công mới.
                        </div>

                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar">
                                    <?php echo $phanCong['gioi_tinh'] == 1 ? '👦' : '👧'; ?>
                                </span>
                            </div>
                            <div class="col">
                                <h5 class="mb-1">
                                    <strong><?php echo htmlspecialchars($phanCong['ten_hoc_sinh']); ?></strong>
                                </h5>
                                <p class="mb-0 text-muted">
                                    Mã HS: <strong><?php echo $phanCong['ma_hs']; ?></strong> |
                                    Lớp: <strong><?php echo htmlspecialchars($phanCong['ten_lop_hs']); ?></strong>
                                    (Khối <?php echo $phanCong['khoi_hs']; ?>)
                                </p>
                                <p class="mb-0 text-danger small mt-1">
                                    <i class="fas fa-ban"></i>
                                    <strong>Không được chấm:</strong> <?php echo htmlspecialchars($phanCong['ten_lop_hs']); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Form chỉnh sửa -->
                    <form method="POST" action="" id="editForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <!-- Chọn lớp được chấm -->
                        <div class="form-section">
                            <h5><i class="fas fa-school"></i> Lớp được chấm</h5>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Chọn lớp mới</label>
                                <select name="lop_duoc_cham_id"
                                        id="lop_duoc_cham_id"
                                        class="form-select select2"
                                        required>
                                    <option value="">-- Chọn lớp --</option>
                                    <?php foreach ($lopTheoKhoi as $khoi => $cacLop): ?>
                                        <optgroup label="Khối <?php echo $khoi; ?>">
                                            <?php foreach ($cacLop as $lop): ?>
                                                <option value="<?php echo $lop['id']; ?>"
                                                        <?php echo $lop['id'] == $phanCong['lop_duoc_cham_id'] ? 'selected' : ''; ?>
                                                        <?php echo $lop['id'] == $phanCong['lop_cua_hs'] ? 'disabled' : ''; ?>>
                                                    <?php echo htmlspecialchars($lop['ten_lop']); ?>
                                                    (<?php echo $lop['si_so']; ?> HS)
                                                    <?php if ($lop['id'] == $phanCong['lop_cua_hs']): ?>
                                                        - ⛔ Lớp của học sinh
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    Lớp cũ: <strong><?php echo htmlspecialchars($phanCong['ten_lop_cham']); ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin phân công -->
                        <div class="form-section">
                            <h5><i class="fas fa-calendar-alt"></i> Thông tin phân công</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Ngày phân công</label>
                                    <input type="date"
                                           name="ngay_phan_cong"
                                           class="form-control"
                                           value="<?php echo $phanCong['ngay_phan_cong']; ?>"
                                           max="<?php echo date('Y-m-d'); ?>"
                                           required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Trạng thái</label>
                                    <select name="trang_thai" class="form-select" required>
                                        <option value="active" <?php echo $phanCong['trang_thai'] === 'active' ? 'selected' : ''; ?>>
                                            Hoạt động
                                        </option>
                                        <option value="inactive" <?php echo $phanCong['trang_thai'] === 'inactive' ? 'selected' : ''; ?>>
                                            Tạm dừng
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Ghi chú</label>
                                <textarea name="ghi_chu"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Ghi chú về phân công"><?php echo htmlspecialchars($phanCong['ghi_chu']); ?></textarea>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Hủy bỏ
                            </a>
                            <button type="submit" class="btn btn-warning btn-lg text-white">
                                <i class="fas fa-save"></i> Cập nhật phân công
                            </button>
                        </div>
                    </form>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Validation
            $('#editForm').on('submit', function(e) {
                const lopId = $('#lop_duoc_cham_id').val();
                const lopCuaHS = <?php echo $phanCong['lop_cua_hs']; ?>;

                if (!lopId) {
                    e.preventDefault();
                    alert('Vui lòng chọn lớp được chấm!');
                    return false;
                }

                if (parseInt(lopId) === parseInt(lopCuaHS)) {
                    e.preventDefault();
                    alert('CHẤM CHÉO: Học sinh không được chấm lớp của chính mình!');
                    return false;
                }
            });
        });
    </script>
</body>
</html>
