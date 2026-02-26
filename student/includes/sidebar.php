<?php
/**
 * ==============================================
 * STUDENT SIDEBAR - Thi đua Navigation
 * Dùng cho các trang student/thidua/*
 * ==============================================
 */

$student = getCurrentStudent();
$currentPage = basename($_SERVER['PHP_SELF']);

// Check Cờ đỏ
$laCoDo = isset($student['la_co_do']) ? intval($student['la_co_do']) : 0;

// Lấy tên lớp
$lopTen = '';
if (isset($student['lop_id'])) {
    $connSB = getDBConnection();
    $stmtLopSB = $connSB->prepare("SELECT ten_lop FROM lop_hoc WHERE id = ?");
    $stmtLopSB->execute(array($student['lop_id']));
    $lopSB = $stmtLopSB->fetch();
    if ($lopSB) {
        $lopTen = $lopSB['ten_lop'];
    }
}
?>

<style>
.student-sidebar {
    padding: 0;
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    color: white;
    min-height: 100vh;
}
.student-sidebar .sidebar-header {
    padding: 20px 16px;
    background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
    text-align: center;
}
.student-sidebar .sidebar-header h6 {
    margin: 0;
    font-size: 0.85rem;
    opacity: 0.9;
}
.student-sidebar .sidebar-header p {
    margin: 4px 0 0;
    font-size: 0.75rem;
    opacity: 0.7;
}
.student-sidebar .nav-link {
    color: rgba(255,255,255,0.7);
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s;
    text-decoration: none;
    font-size: 0.9rem;
    border-left: 3px solid transparent;
}
.student-sidebar .nav-link:hover {
    color: white;
    background: rgba(255,255,255,0.08);
}
.student-sidebar .nav-link.active {
    color: white;
    background: rgba(79,70,229,0.3);
    border-left-color: #818CF8;
}
.student-sidebar .nav-link i {
    width: 20px;
    text-align: center;
}
.student-sidebar .nav-section {
    padding: 12px 16px 4px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255,255,255,0.4);
}
.student-sidebar .badge-co-do {
    background: #dc3545;
    color: white;
    font-size: 0.65rem;
    padding: 2px 6px;
    border-radius: 10px;
}
</style>

<div class="student-sidebar">
    <!-- Header -->
    <div class="sidebar-header">
        <h6><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($student['ho_ten']); ?></h6>
        <p><?php echo htmlspecialchars($lopTen); ?>
            <?php if ($laCoDo): ?>
                <span class="badge-co-do ms-1"><i class="fas fa-flag"></i> C&#7901; &#273;&#7887;</span>
            <?php endif; ?>
        </p>
    </div>

    <!-- Navigation -->
    <div class="nav-section">Thi &#273;ua</div>

    <?php if ($laCoDo): ?>
    <a href="cham_diem.php" class="nav-link <?php echo $currentPage === 'cham_diem.php' ? 'active' : ''; ?>">
        <i class="fas fa-edit"></i> Ch&#7845;m &#273;i&#7875;m
    </a>
    <?php endif; ?>

    <a href="xep_hang.php" class="nav-link <?php echo $currentPage === 'xep_hang.php' ? 'active' : ''; ?>">
        <i class="fas fa-trophy"></i> X&#7871;p h&#7841;ng l&#7899;p
    </a>

    <div class="nav-section" style="margin-top: 8px;">Chung</div>

    <a href="../dashboard.php" class="nav-link">
        <i class="fas fa-home"></i> Trang ch&#7911;
    </a>

    <a href="../../logout.php" class="nav-link">
        <i class="fas fa-sign-out-alt"></i> &#272;&#259;ng xu&#7845;t
    </a>
</div>
