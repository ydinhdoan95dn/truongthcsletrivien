<?php
/**
 * ==============================================
 * TỪ CHỐI ĐIỂM THI ĐUA
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';
require_once '../../../includes/thidua_helper.php';

requireTongPhuTrach();

define('PAGE_TITLE', 'Từ chối điểm thi đua');

$conn = getDBConnection();
$admin = getCurrentAdmin();

// Get parameters
$lop_id = isset($_GET['lop']) ? intval($_GET['lop']) : 0;
$tuan_id = isset($_GET['tuan']) ? intval($_GET['tuan']) : 0;

if ($lop_id <= 0 || $tuan_id <= 0) {
    $_SESSION['error'] = 'Tham số không hợp lệ!';
    header('Location: index.php');
    exit;
}

// Lấy thông tin lớp và tuần
$stmtLop = $conn->prepare("SELECT * FROM lop_hoc WHERE id = ?");
$stmtLop->execute([$lop_id]);
$lopInfo = $stmtLop->fetch();

$stmtTuan = $conn->prepare("SELECT * FROM tuan_hoc WHERE id = ?");
$stmtTuan->execute([$tuan_id]);
$tuanInfo = $stmtTuan->fetch();

if (!$lopInfo || !$tuanInfo) {
    $_SESSION['error'] = 'Không tìm thấy thông tin!';
    header('Location: index.php');
    exit;
}

$errors = [];

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Token bảo mật không hợp lệ!';
    } else {
        $ly_do = isset($_POST['ly_do']) ? sanitize($_POST['ly_do']) : '';

        if (empty($ly_do)) {
            $errors[] = 'Vui lòng nhập lý do từ chối!';
        }

        if (empty($errors)) {
            try {
                $conn->beginTransaction();

                // Update trạng thái từ chối
                $stmtUpdate = $conn->prepare("
                    UPDATE diem_thi_dua_tuan
                    SET trang_thai = 'tu_choi',
                        nguoi_duyet = ?,
                        ngay_duyet = CURDATE(),
                        ly_do_tu_choi = ?
                    WHERE lop_id = ? AND tuan_id = ?
                      AND trang_thai IN ('cho_duyet', 'nhap')
                ");
                $stmtUpdate->execute([$admin['id'], $ly_do, $lop_id, $tuan_id]);

                // Log activity
                logThiduaActivity(
                    'tu_choi_diem',
                    $admin['id'],
                    'admin',
                    "Từ chối điểm tuần cho lớp {$lopInfo['ten_lop']} - Tuần #{$tuan_id}",
                    $lop_id,
                    'diem_tuan',
                    null,
                    ['lop_id' => $lop_id, 'tuan_id' => $tuan_id, 'ly_do' => $ly_do]
                );

                $conn->commit();

                $_SESSION['success'] = "Đã từ chối điểm của lớp {$lopInfo['ten_lop']}!";
                header("Location: index.php?tuan={$tuan_id}");
                exit;

            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = 'Lỗi: ' . $e->getMessage();
            }
        }
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

    <style>
        .form-container {
            max-width: 700px;
            margin: 2rem auto;
        }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
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
                            <i class="fas fa-times-circle text-danger"></i>
                            <?php echo PAGE_TITLE; ?>
                        </h1>
                        <a href="chi_tiet.php?lop=<?php echo $lop_id; ?>&tuan=<?php echo $tuan_id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>

                <!-- Errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle"></i> Có lỗi!
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
                    <!-- Warning -->
                    <div class="warning-box">
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                <i class="fas fa-exclamation-triangle fs-1 text-warning"></i>
                            </div>
                            <div>
                                <h5 class="mb-2"><strong>Xác nhận từ chối điểm</strong></h5>
                                <p class="mb-1">
                                    Bạn đang từ chối điểm thi đua của:
                                </p>
                                <ul class="mb-0">
                                    <li><strong>Lớp:</strong> <?php echo htmlspecialchars($lopInfo['ten_lop']); ?></li>
                                    <li><strong>Tuần:</strong> <?php echo htmlspecialchars($tuanInfo['ten_tuan']); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        Lý do từ chối <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="ly_do"
                                              class="form-control"
                                              rows="5"
                                              required
                                              placeholder="Vui lòng nhập lý do từ chối chi tiết để học sinh Cờ đỏ biết và chỉnh sửa..."></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i>
                                        Lý do từ chối sẽ được gửi lại cho học sinh Cờ đỏ đã chấm điểm
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="chi_tiet.php?lop=<?php echo $lop_id; ?>&tuan=<?php echo $tuan_id; ?>"
                                       class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Hủy bỏ
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class="fas fa-times-circle"></i> Xác nhận từ chối
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
