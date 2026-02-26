<?php
/**
 * ==============================================
 * CHI TIẾT ĐIỂM THI ĐUA CỦA LỚP
 * Module: Admin - Hệ thống Thi đua
 * ==============================================
 */

require_once '../../../includes/config.php';
require_once '../../../includes/permission_helper.php';
require_once '../../../includes/thidua_helper.php';

requireTongPhuTrach();

define('PAGE_TITLE', 'Chi tiết điểm thi đua');

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

// Lấy thông tin lớp
$stmtLop = $conn->prepare("SELECT * FROM lop_hoc WHERE id = ?");
$stmtLop->execute([$lop_id]);
$lopInfo = $stmtLop->fetch();

// Lấy thông tin tuần
$stmtTuan = $conn->prepare("SELECT * FROM tuan_hoc WHERE id = ?");
$stmtTuan->execute([$tuan_id]);
$tuanInfo = $stmtTuan->fetch();

if (!$lopInfo || !$tuanInfo) {
    $_SESSION['error'] = 'Không tìm thấy thông tin lớp hoặc tuần!';
    header('Location: index.php');
    exit;
}

// Lấy điểm chi tiết 5 tiêu chí
$stmtDiem = $conn->prepare("
    SELECT
        dtdt.*,
        tc.ma_tieu_chi,
        tc.ten_tieu_chi,
        tc.trong_so,
        tc.diem_toi_da,
        hs.ho_ten as ten_nguoi_cham,
        hs.ma_hs,
        a_duyet.ho_ten as ten_nguoi_duyet
    FROM diem_thi_dua_tuan dtdt
    JOIN tieu_chi_thi_dua tc ON dtdt.tieu_chi_id = tc.id
    LEFT JOIN hoc_sinh hs ON dtdt.nguoi_cham = hs.id
    LEFT JOIN admins a_duyet ON dtdt.nguoi_duyet = a_duyet.id
    WHERE dtdt.lop_id = ? AND dtdt.tuan_id = ?
    ORDER BY tc.thu_tu
");
$stmtDiem->execute([$lop_id, $tuan_id]);
$chiTietDiem = $stmtDiem->fetchAll();

// Tính tổng điểm
$tongDiem = 0;
$tongTrongSo = 0;
foreach ($chiTietDiem as $diem) {
    $diemCoTrongSo = ($diem['diem'] / $diem['diem_toi_da']) * $diem['trong_so'];
    $tongDiem += $diemCoTrongSo;
    $tongTrongSo += $diem['trong_so'];
}

// Xếp loại
$xepLoai = getXepLoai($tongDiem);
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
        .class-header {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }

        .score-card {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }

        .score-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.2);
        }

        .score-card.excellent { border-left: 5px solid #198754; }
        .score-card.good { border-left: 5px solid #0dcaf0; }
        .score-card.average { border-left: 5px solid #ffc107; }
        .score-card.poor { border-left: 5px solid #dc3545; }

        .score-value {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .score-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .tieu-chi-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .progress-custom {
            height: 25px;
            border-radius: 20px;
        }

        .badge-status {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .info-row {
            margin-bottom: 0.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .total-score-box {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            text-align: center;
        }

        .total-score-box .score {
            font-size: 4rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .xep-loai-badge {
            font-size: 1.2rem;
            padding: 0.75rem 1.5rem;
            border-radius: 30px;
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
                            <i class="fas fa-chart-line text-info"></i>
                            <?php echo PAGE_TITLE; ?>
                        </h1>
                        <a href="index.php?tuan=<?php echo $tuan_id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại danh sách
                        </a>
                    </div>
                </div>

                <!-- Class Header -->
                <div class="class-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-school"></i>
                                <?php echo htmlspecialchars($lopInfo['ten_lop']); ?>
                            </h2>
                            <p class="mb-0">
                                <i class="fas fa-calendar-week"></i>
                                <?php echo htmlspecialchars($tuanInfo['ten_tuan']); ?>
                                (<?php echo date('d/m/Y', strtotime($tuanInfo['ngay_bat_dau'])); ?>
                                - <?php echo date('d/m/Y', strtotime($tuanInfo['ngay_ket_thuc'])); ?>)
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="fs-1">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Cột trái: Chi tiết 5 tiêu chí -->
                    <div class="col-md-8">
                        <h5 class="mb-3">
                            <i class="fas fa-clipboard-list"></i>
                            Chi tiết điểm theo tiêu chí
                        </h5>

                        <?php if (count($chiTietDiem) > 0): ?>
                            <?php foreach ($chiTietDiem as $diem):
                                $phanTram = ($diem['diem'] / $diem['diem_toi_da']) * 100;
                                $progressClass = 'bg-danger';
                                if ($phanTram >= 90) $progressClass = 'bg-success';
                                elseif ($phanTram >= 80) $progressClass = 'bg-info';
                                elseif ($phanTram >= 70) $progressClass = 'bg-warning';

                                $diemCoTrongSo = round(($diem['diem'] / $diem['diem_toi_da']) * $diem['trong_so'], 2);
                            ?>
                                <div class="tieu-chi-item">
                                    <div class="row align-items-center mb-3">
                                        <div class="col-md-6">
                                            <h6 class="mb-1 fw-bold">
                                                <?php echo htmlspecialchars($diem['ten_tieu_chi']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                Trọng số: <?php echo $diem['trong_so']; ?>%
                                            </small>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <div class="d-flex justify-content-end align-items-center">
                                                <span class="me-3">
                                                    <span class="fs-4 fw-bold"><?php echo $diem['diem']; ?></span>
                                                    <span class="text-muted">/<?php echo $diem['diem_toi_da']; ?></span>
                                                </span>
                                                <span class="badge bg-primary">
                                                    <?php echo $diemCoTrongSo; ?> điểm
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="progress progress-custom mb-3">
                                        <div class="progress-bar <?php echo $progressClass; ?>"
                                             role="progressbar"
                                             style="width: <?php echo $phanTram; ?>%"
                                             aria-valuenow="<?php echo $phanTram; ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            <?php echo round($phanTram, 1); ?>%
                                        </div>
                                    </div>

                                    <!-- Thông tin chi tiết -->
                                    <div class="row small">
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <span class="info-label">Người chấm:</span>
                                                <?php echo htmlspecialchars(isset($diem['ten_nguoi_cham']) ? $diem['ten_nguoi_cham'] : 'N/A'); ?>
                                                <?php if ($diem['ma_hs']): ?>
                                                    (<?php echo $diem['ma_hs']; ?>)
                                                <?php endif; ?>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Lần chấm:</span>
                                                <?php echo $diem['cham_luc'] ? date('d/m/Y H:i', strtotime($diem['cham_luc'])) : 'N/A'; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <span class="info-label">Trạng thái:</span>
                                                <?php
                                                $badgeClass = 'bg-warning text-dark';
                                                $statusText = 'Chờ duyệt';
                                                if ($diem['trang_thai'] === 'da_duyet') {
                                                    $badgeClass = 'bg-success';
                                                    $statusText = 'Đã duyệt';
                                                } elseif ($diem['trang_thai'] === 'tu_choi') {
                                                    $badgeClass = 'bg-danger';
                                                    $statusText = 'Từ chối';
                                                }
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?> badge-status">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </div>
                                            <?php if ($diem['trang_thai'] === 'da_duyet' && $diem['nguoi_duyet']): ?>
                                                <div class="info-row">
                                                    <span class="info-label">Người duyệt:</span>
                                                    <?php echo htmlspecialchars($diem['ten_nguoi_duyet']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($diem['ghi_chu']): ?>
                                        <div class="mt-2">
                                            <span class="info-label">Ghi chú:</span>
                                            <div class="alert alert-info mb-0 mt-1 small">
                                                <?php echo nl2br(htmlspecialchars($diem['ghi_chu'])); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($diem['ly_do_tu_choi']): ?>
                                        <div class="mt-2">
                                            <span class="info-label text-danger">Lý do từ chối:</span>
                                            <div class="alert alert-danger mb-0 mt-1 small">
                                                <?php echo nl2br(htmlspecialchars($diem['ly_do_tu_choi'])); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Chưa có điểm nào được chấm cho lớp này trong tuần này.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Cột phải: Tổng điểm & Actions -->
                    <div class="col-md-4">
                        <!-- Total Score -->
                        <div class="total-score-box mb-4">
                            <div class="score-label mb-2">TỔNG ĐIỂM</div>
                            <div class="score"><?php echo number_format($tongDiem, 2); ?></div>
                            <div class="mt-2">
                                <small>/100 điểm</small>
                            </div>
                            <div class="mt-3">
                                <span class="xep-loai-badge badge bg-light text-dark">
                                    <?php echo getXepLoaiLabel($xepLoai); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Chi tiết trọng số -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <strong>Chi tiết tính điểm</strong>
                            </div>
                            <div class="card-body">
                                <?php foreach ($chiTietDiem as $diem):
                                    $diemCoTrongSo = round(($diem['diem'] / $diem['diem_toi_da']) * $diem['trong_so'], 2);
                                ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="small"><?php echo $diem['ten_tieu_chi']; ?>:</span>
                                        <strong><?php echo $diemCoTrongSo; ?></strong>
                                    </div>
                                <?php endforeach; ?>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Tổng:</strong>
                                    <strong class="text-primary"><?php echo number_format($tongDiem, 2); ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <?php
                        // Check trạng thái chung
                        $allApproved = true;
                        $allPending = true;
                        foreach ($chiTietDiem as $diem) {
                            if ($diem['trang_thai'] !== 'da_duyet') {
                                $allApproved = false;
                            }
                            if ($diem['trang_thai'] !== 'cho_duyet') {
                                $allPending = false;
                            }
                        }
                        ?>

                        <?php if (!$allApproved && count($chiTietDiem) > 0): ?>
                            <div class="d-grid gap-2">
                                <a href="duyet.php?lop=<?php echo $lop_id; ?>&tuan=<?php echo $tuan_id; ?>"
                                   class="btn btn-success btn-lg">
                                    <i class="fas fa-check-circle"></i>
                                    Duyệt tất cả
                                </a>
                                <a href="tu_choi.php?lop=<?php echo $lop_id; ?>&tuan=<?php echo $tuan_id; ?>"
                                   class="btn btn-danger">
                                    <i class="fas fa-times-circle"></i>
                                    Từ chối
                                </a>
                            </div>
                        <?php elseif ($allApproved): ?>
                            <div class="alert alert-success text-center">
                                <i class="fas fa-check-circle fs-3"></i>
                                <div class="mt-2">
                                    <strong>Đã duyệt hoàn tất</strong>
                                </div>
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
