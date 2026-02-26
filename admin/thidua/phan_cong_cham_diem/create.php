<?php
/**
 * ==============================================
 * TẠO PHÂN CÔNG CHẤM ĐIỂM MỚI
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';

// Kiểm tra quyền Admin
requireAdmin();

define('PAGE_TITLE', 'Tạo phân công chấm điểm');

$conn = getDBConnection();
$admin = getCurrentAdmin();

$errors = [];
$success = false;

// ============================================================
// XỬ LÝ FORM SUBMIT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Token bảo mật không hợp lệ!';
    } else {
        // Lấy dữ liệu
        $hoc_sinh_id = isset($_POST['hoc_sinh_id']) ? intval($_POST['hoc_sinh_id']) : 0;
        $lop_duoc_cham_id = isset($_POST['lop_duoc_cham_id']) ? intval($_POST['lop_duoc_cham_id']) : 0;
        $ngay_phan_cong = isset($_POST['ngay_phan_cong']) ? sanitize($_POST['ngay_phan_cong']) : date('Y-m-d');
        $ghi_chu = isset($_POST['ghi_chu']) ? sanitize($_POST['ghi_chu']) : '';
        $trang_thai = isset($_POST['trang_thai']) ? sanitize($_POST['trang_thai']) : 'active';

        // ====================================================
        // VALIDATION
        // ====================================================

        // 1. Kiểm tra học sinh ID
        if ($hoc_sinh_id <= 0) {
            $errors[] = 'Vui lòng chọn học sinh!';
        } else {
            // Kiểm tra học sinh có tồn tại không
            $stmtHS = $conn->prepare("SELECT id, ho_ten, lop_id, la_co_do FROM hoc_sinh WHERE id = ?");
            $stmtHS->execute([$hoc_sinh_id]);
            $hocSinh = $stmtHS->fetch();

            if (!$hocSinh) {
                $errors[] = 'Học sinh không tồn tại!';
            } elseif ($hocSinh['la_co_do'] != 1) {
                $errors[] = 'Học sinh này chưa được gắn Cờ đỏ! Vui lòng gắn Cờ đỏ trước khi phân công.';
            }
        }

        // 2. Kiểm tra lớp được chấm
        if ($lop_duoc_cham_id <= 0) {
            $errors[] = 'Vui lòng chọn lớp được chấm!';
        } else {
            // Kiểm tra lớp có tồn tại không
            $stmtLop = $conn->prepare("SELECT id, ten_lop FROM lop_hoc WHERE id = ?");
            $stmtLop->execute([$lop_duoc_cham_id]);
            $lopDuocCham = $stmtLop->fetch();

            if (!$lopDuocCham) {
                $errors[] = 'Lớp được chấm không tồn tại!';
            }
        }

        // 3. LOGIC CHẤM CHÉO: Không được chấm lớp của chính mình
        if (isset($hocSinh) && $hocSinh['lop_id'] == $lop_duoc_cham_id) {
            $errors[] = 'LOGIC CHẤM CHÉO: Học sinh không được chấm lớp của chính mình!';
        }

        // 4. Kiểm tra trùng lặp phân công
        if (empty($errors)) {
            $stmtCheck = $conn->prepare("
                SELECT id FROM phan_cong_cham_diem
                WHERE hoc_sinh_id = ? AND lop_duoc_cham_id = ?
            ");
            $stmtCheck->execute([$hoc_sinh_id, $lop_duoc_cham_id]);
            $existing = $stmtCheck->fetch();

            if ($existing) {
                $errors[] = 'Phân công này đã tồn tại! Học sinh đã được phân công chấm lớp này rồi.';
            }
        }

        // 5. Kiểm tra ngày phân công
        if (empty($ngay_phan_cong) || !strtotime($ngay_phan_cong)) {
            $errors[] = 'Ngày phân công không hợp lệ!';
        }

        // ====================================================
        // THỰC HIỆN INSERT NẾU KHÔNG CÓ LỖI
        // ====================================================
        if (empty($errors)) {
            try {
                $sql = "
                    INSERT INTO phan_cong_cham_diem (
                        hoc_sinh_id,
                        lop_duoc_cham_id,
                        ngay_phan_cong,
                        nguoi_phan_cong,
                        ghi_chu,
                        trang_thai
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ";

                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([
                    $hoc_sinh_id,
                    $lop_duoc_cham_id,
                    $ngay_phan_cong,
                    $admin['id'],
                    $ghi_chu,
                    $trang_thai
                ]);

                if ($result) {
                    // Log activity
                    logThiduaActivity(
                        'phan_cong_cham_diem',
                        $admin['id'],
                        'admin',
                        "Tạo phân công mới: Học sinh #{$hoc_sinh_id} chấm lớp #{$lop_duoc_cham_id}",
                        $conn->lastInsertId(),
                        'phan_cong',
                        null,
                        [
                            'hoc_sinh_id' => $hoc_sinh_id,
                            'lop_duoc_cham_id' => $lop_duoc_cham_id,
                            'ngay_phan_cong' => $ngay_phan_cong
                        ]
                    );

                    $_SESSION['success'] = 'Tạo phân công thành công!';
                    header('Location: index.php');
                    exit;
                } else {
                    $errors[] = 'Không thể tạo phân công. Vui lòng thử lại!';
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

// Lấy danh sách học sinh Cờ đỏ
$stmtCoDo = $conn->query("
    SELECT
        hs.id,
        hs.ho_ten,
        hs.ma_hs,
        hs.gioi_tinh,
        lh.ten_lop,
        lh.khoi,
        lh.id as lop_id
    FROM hoc_sinh hs
    JOIN lop_hoc lh ON hs.lop_id = lh.id
    WHERE hs.la_co_do = 1 AND hs.trang_thai = 1
    ORDER BY lh.ten_lop, hs.ho_ten
");
$danhSachCoDo = $stmtCoDo->fetchAll();

// Lấy danh sách tất cả các lớp
$stmtLop = $conn->query("
    SELECT
        id,
        ten_lop,
        khoi,
        khoi_label,
        si_so
    FROM lop_hoc
    ORDER BY ten_lop
");
$danhSachLop = $stmtLop->fetchAll();

// Group lớp theo khối
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
            border-left: 4px solid #0d6efd;
            margin-bottom: 1.5rem;
        }

        .form-section h5 {
            margin-bottom: 1rem;
            color: #0d6efd;
        }

        .cross-check-info {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .cross-check-info i {
            color: #ffc107;
            font-size: 1.5rem;
        }

        .student-info-display {
            background: white;
            border: 2px dashed #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 0.5rem;
            display: none;
        }

        .student-info-display.show {
            display: block;
        }

        .student-info-display .avatar {
            font-size: 3rem;
        }

        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }

        .required-field::after {
            content: " *";
            color: #dc3545;
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
                            <i class="fas fa-plus-circle text-primary"></i>
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

                <!-- Thông tin CHẤM CHÉO -->
                <div class="cross-check-info">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <h6 class="mb-2"><strong>Lưu ý về CHẤM CHÉO:</strong></h6>
                            <ul class="mb-0 small">
                                <li>Học sinh <strong>KHÔNG được chấm lớp của chính mình</strong></li>
                                <li>Chỉ học sinh đã được <strong>gắn Cờ đỏ</strong> mới có thể được phân công</li>
                                <li>Mỗi học sinh có thể được phân công chấm nhiều lớp khác nhau</li>
                                <li>Ví dụ: Học sinh Cờ đỏ lớp 6A1 → Chấm lớp 6A2, 6A3...</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="form-container">
                    <form method="POST" action="" id="createForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <!-- SECTION 1: Chọn học sinh Cờ đỏ -->
                        <div class="form-section">
                            <h5><i class="fas fa-user-graduate"></i> Bước 1: Chọn học sinh Cờ đỏ</h5>

                            <div class="mb-3">
                                <label class="form-label fw-bold required-field">Học sinh Cờ đỏ</label>
                                <select name="hoc_sinh_id"
                                        id="hoc_sinh_id"
                                        class="form-select select2"
                                        required>
                                    <option value="">-- Chọn học sinh --</option>
                                    <?php foreach ($danhSachCoDo as $hs): ?>
                                        <option value="<?php echo $hs['id']; ?>"
                                                data-lop-id="<?php echo $hs['lop_id']; ?>"
                                                data-ten-lop="<?php echo htmlspecialchars($hs['ten_lop']); ?>"
                                                data-khoi="<?php echo $hs['khoi']; ?>"
                                                data-gioi-tinh="<?php echo $hs['gioi_tinh']; ?>">
                                            <?php echo htmlspecialchars($hs['ho_ten']); ?> (<?php echo $hs['ma_hs']; ?>) - Lớp <?php echo $hs['ten_lop']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i>
                                    Chỉ hiển thị học sinh đã được gắn Cờ đỏ
                                </div>
                            </div>

                            <!-- Hiển thị thông tin học sinh đã chọn -->
                            <div class="student-info-display" id="studentInfo">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="avatar" id="studentAvatar"></span>
                                    </div>
                                    <div class="col">
                                        <h6 class="mb-1"><strong id="studentName"></strong></h6>
                                        <p class="mb-0 text-muted small">
                                            Lớp: <strong id="studentClass"></strong> (Khối <span id="studentKhoi"></span>)
                                        </p>
                                        <p class="mb-0 text-danger small">
                                            <i class="fas fa-ban"></i>
                                            <strong>Không được chấm:</strong> <span id="studentOwnClass"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: Chọn lớp được chấm -->
                        <div class="form-section">
                            <h5><i class="fas fa-school"></i> Bước 2: Chọn lớp được chấm</h5>

                            <div class="mb-3">
                                <label class="form-label fw-bold required-field">Lớp được chấm</label>
                                <select name="lop_duoc_cham_id"
                                        id="lop_duoc_cham_id"
                                        class="form-select select2"
                                        required>
                                    <option value="">-- Chọn lớp --</option>
                                    <?php foreach ($lopTheoKhoi as $khoi => $cacLop): ?>
                                        <optgroup label="Khối <?php echo $khoi; ?>">
                                            <?php foreach ($cacLop as $lop): ?>
                                                <option value="<?php echo $lop['id']; ?>">
                                                    <?php echo htmlspecialchars($lop['ten_lop']); ?>
                                                    (<?php echo $lop['si_so']; ?> HS)
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i>
                                    Lớp mà học sinh Cờ đỏ sẽ chấm điểm (KHÁC lớp của học sinh)
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: Thông tin phân công -->
                        <div class="form-section">
                            <h5><i class="fas fa-calendar-alt"></i> Bước 3: Thông tin phân công</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold required-field">Ngày phân công</label>
                                    <input type="date"
                                           name="ngay_phan_cong"
                                           class="form-control"
                                           value="<?php echo date('Y-m-d'); ?>"
                                           max="<?php echo date('Y-m-d'); ?>"
                                           required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold required-field">Trạng thái</label>
                                    <select name="trang_thai" class="form-select" required>
                                        <option value="active">Hoạt động</option>
                                        <option value="inactive">Tạm dừng</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Ghi chú</label>
                                <textarea name="ghi_chu"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Ghi chú về phân công (không bắt buộc)"></textarea>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Hủy bỏ
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Tạo phân công
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
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Khi chọn học sinh
            $('#hoc_sinh_id').on('change', function() {
                const selectedOption = $(this).find(':selected');

                if (selectedOption.val()) {
                    const lopId = selectedOption.data('lop-id');
                    const tenLop = selectedOption.data('ten-lop');
                    const khoi = selectedOption.data('khoi');
                    const gioiTinh = selectedOption.data('gioi-tinh');
                    const tenHS = selectedOption.text().split(' (')[0];

                    // Hiển thị thông tin
                    $('#studentAvatar').text(gioiTinh == 1 ? '👦' : '👧');
                    $('#studentName').text(tenHS);
                    $('#studentClass').text(tenLop);
                    $('#studentKhoi').text(khoi);
                    $('#studentOwnClass').text(tenLop);
                    $('#studentInfo').addClass('show');

                    // Disable lớp của chính học sinh trong dropdown lớp được chấm
                    $('#lop_duoc_cham_id option').prop('disabled', false);
                    $('#lop_duoc_cham_id option[value="' + lopId + '"]').prop('disabled', true);

                    // Refresh Select2
                    $('#lop_duoc_cham_id').select2('destroy').select2({
                        theme: 'bootstrap-5',
                        width: '100%'
                    });
                } else {
                    $('#studentInfo').removeClass('show');
                    $('#lop_duoc_cham_id option').prop('disabled', false);
                }
            });

            // Validation form
            $('#createForm').on('submit', function(e) {
                const hsId = $('#hoc_sinh_id').val();
                const lopId = $('#lop_duoc_cham_id').val();

                if (!hsId) {
                    e.preventDefault();
                    alert('Vui lòng chọn học sinh Cờ đỏ!');
                    return false;
                }

                if (!lopId) {
                    e.preventDefault();
                    alert('Vui lòng chọn lớp được chấm!');
                    return false;
                }

                // Kiểm tra không được chấm lớp của chính mình
                const lopCuaHS = $('#hoc_sinh_id :selected').data('lop-id');
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
