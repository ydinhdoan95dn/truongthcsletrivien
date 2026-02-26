<?php
/**
 * ==============================================
 * HƯỚNG DẪN SỬ DỤNG WEBSITE
 * Trang public - Không cần đăng nhập
 * ==============================================
 */
require_once 'includes/config.php';
$baseUrl = BASE_URL;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hướng dẫn sử dụng - <?php echo SITE_NAME; ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f4f8;
            color: #1e293b;
            line-height: 1.7;
        }

        /* ============================== */
        /* HEADER                          */
        /* ============================== */
        .guide-header {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: white;
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 4px 20px rgba(79,70,229,0.3);
        }

        .header-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-brand i {
            font-size: 1.4rem;
            opacity: 0.9;
        }

        .header-brand h1 {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .header-brand span {
            font-size: 0.78rem;
            opacity: 0.75;
            display: block;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .header-actions a {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-home {
            background: rgba(255,255,255,0.15);
            color: white;
        }

        .btn-home:hover {
            background: rgba(255,255,255,0.25);
        }

        .btn-login {
            background: white;
            color: #4F46E5;
        }

        .btn-login:hover {
            background: #f0f0ff;
        }

        /* ============================== */
        /* HERO BANNER                     */
        /* ============================== */
        .hero-banner {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 50%, #6366F1 100%);
            color: white;
            text-align: center;
            padding: 48px 24px 56px;
            position: relative;
            overflow: hidden;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.1), transparent 60%);
        }

        .hero-banner h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
            position: relative;
        }

        .hero-banner p {
            font-size: 1rem;
            opacity: 0.85;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .hero-features {
            display: flex;
            justify-content: center;
            gap: 32px;
            margin-top: 28px;
            position: relative;
            flex-wrap: wrap;
        }

        .hero-feat {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            background: rgba(255,255,255,0.12);
            padding: 8px 18px;
            border-radius: 24px;
        }

        /* ============================== */
        /* LAYOUT                          */
        /* ============================== */
        .guide-layout {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            gap: 0;
            min-height: calc(100vh - 60px);
        }

        /* ============================== */
        /* SIDEBAR TOC                     */
        /* ============================== */
        .toc-sidebar {
            width: 280px;
            flex-shrink: 0;
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 24px 0;
            position: sticky;
            top: 54px;
            height: calc(100vh - 54px);
            overflow-y: auto;
        }

        .toc-title {
            padding: 0 20px 12px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
        }

        .toc-section {
            margin-bottom: 4px;
        }

        .toc-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s;
            border-left: 3px solid transparent;
            text-decoration: none;
        }

        .toc-section-title:hover {
            background: #f8fafc;
            color: #4F46E5;
        }

        .toc-section-title.active {
            background: rgba(79,70,229,0.06);
            color: #4F46E5;
            border-left-color: #4F46E5;
        }

        .toc-section-title .toc-icon {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: white;
            flex-shrink: 0;
        }

        .toc-sub {
            padding: 0 20px 0 52px;
        }

        .toc-sub a {
            display: block;
            padding: 4px 0;
            font-size: 0.78rem;
            color: #64748b;
            text-decoration: none;
            transition: color 0.15s;
        }

        .toc-sub a:hover { color: #4F46E5; }

        /* Mobile TOC toggle */
        .toc-toggle {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: white;
            border: none;
            font-size: 18px;
            cursor: pointer;
            z-index: 300;
            box-shadow: 0 4px 15px rgba(79,70,229,0.4);
        }

        .toc-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 250;
        }

        /* ============================== */
        /* MAIN CONTENT                    */
        /* ============================== */
        .guide-content {
            flex: 1;
            min-width: 0;
            padding: 32px 40px 60px;
        }

        /* Section */
        .guide-section {
            margin-bottom: 48px;
            scroll-margin-top: 70px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
        }

        .section-header h2 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1e293b;
        }

        .section-header h2 small {
            font-size: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
            display: block;
        }

        /* Subsection */
        .subsection {
            margin-bottom: 28px;
            scroll-margin-top: 70px;
        }

        .subsection h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .subsection p {
            font-size: 0.92rem;
            color: #475569;
            margin-bottom: 12px;
        }

        /* Step cards */
        .steps {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .step-card {
            background: white;
            border-radius: 14px;
            padding: 20px 20px 20px 72px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            position: relative;
            border: 1px solid #f1f5f9;
            transition: box-shadow 0.2s;
        }

        .step-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }

        .step-num {
            position: absolute;
            left: 20px;
            top: 20px;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 800;
            color: white;
        }

        .step-card h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .step-card p {
            font-size: 0.88rem;
            color: #64748b;
            margin: 0;
        }

        .step-card .step-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #4F46E5;
            text-decoration: none;
        }

        .step-card .step-link:hover {
            text-decoration: underline;
        }

        /* Info & warning boxes */
        .info-box, .warning-box, .tip-box {
            border-radius: 12px;
            padding: 16px 18px 16px 50px;
            margin: 16px 0;
            position: relative;
            font-size: 0.88rem;
        }

        .info-box::before, .warning-box::before, .tip-box::before {
            position: absolute;
            left: 16px;
            top: 16px;
            font-size: 1.1rem;
        }

        .info-box {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            color: #1E40AF;
        }
        .info-box::before { content: '\f05a'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: #3B82F6; }

        .warning-box {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            color: #92400E;
        }
        .warning-box::before { content: '\f071'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: #F59E0B; }

        .tip-box {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            color: #166534;
        }
        .tip-box::before { content: '\f0eb'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: #22C55E; }

        /* Criteria grid */
        .criteria-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin: 16px 0;
        }

        .criteria-item {
            text-align: center;
            padding: 14px 8px;
            border-radius: 12px;
            background: white;
            border: 1px solid #f1f5f9;
        }

        .criteria-item .c-icon {
            font-size: 1.4rem;
            margin-bottom: 4px;
        }

        .criteria-item .c-name {
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .criteria-item .c-weight {
            font-size: 0.72rem;
            color: #94a3b8;
            font-weight: 600;
        }

        /* Role badges */
        .role-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin: 16px 0;
        }

        .role-card {
            background: white;
            border-radius: 14px;
            padding: 18px;
            border: 1px solid #f1f5f9;
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .role-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
        }

        .role-card h4 {
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .role-card p {
            font-size: 0.8rem;
            color: #64748b;
            margin: 0;
        }

        /* Workflow */
        .workflow {
            display: flex;
            align-items: center;
            gap: 0;
            margin: 20px 0;
            flex-wrap: wrap;
            justify-content: center;
        }

        .workflow-step {
            text-align: center;
            padding: 12px 16px;
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            min-width: 140px;
        }

        .workflow-step .wf-icon { font-size: 1.5rem; margin-bottom: 4px; }
        .workflow-step .wf-title { font-size: 0.78rem; font-weight: 700; color: #334155; }
        .workflow-step .wf-desc { font-size: 0.7rem; color: #94a3b8; }

        .workflow-arrow {
            font-size: 1.2rem;
            color: #cbd5e1;
            padding: 0 6px;
        }

        /* FAQ */
        .faq-item {
            background: white;
            border-radius: 14px;
            margin-bottom: 12px;
            border: 1px solid #f1f5f9;
            overflow: hidden;
        }

        .faq-q {
            padding: 16px 20px;
            font-size: 0.92rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }

        .faq-q:hover { background: #f8fafc; }

        .faq-q i {
            transition: transform 0.3s;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .faq-item.open .faq-q i {
            transform: rotate(180deg);
        }

        .faq-a {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s;
            font-size: 0.88rem;
            color: #64748b;
        }

        .faq-item.open .faq-a {
            padding: 0 20px 16px;
            max-height: 300px;
        }

        /* Classification table */
        .class-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 0.85rem;
        }

        .class-table th {
            background: #f8fafc;
            padding: 10px 14px;
            text-align: left;
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .class-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        .class-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        /* Back to top */
        .back-top {
            position: fixed;
            bottom: 20px;
            right: 80px;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: white;
            color: #4F46E5;
            border: 1px solid #e2e8f0;
            font-size: 16px;
            cursor: pointer;
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }

        .back-top:hover {
            background: #4F46E5;
            color: white;
        }

        .back-top.show { display: flex; }

        /* Footer */
        .guide-footer {
            text-align: center;
            padding: 24px;
            background: white;
            border-top: 1px solid #e2e8f0;
            font-size: 0.82rem;
            color: #94a3b8;
        }

        /* ============================== */
        /* RESPONSIVE                      */
        /* ============================== */
        @media (max-width: 1024px) {
            .guide-content { padding: 24px 28px 48px; }
            .criteria-grid { grid-template-columns: repeat(3, 1fr); }
            .role-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .toc-sidebar {
                position: fixed;
                left: -300px;
                top: 0;
                width: 280px;
                height: 100vh;
                z-index: 280;
                transition: left 0.3s ease;
                top: 0;
                padding-top: 20px;
            }

            .toc-sidebar.open { left: 0; }
            .toc-overlay.show { display: block; }
            .toc-toggle { display: flex; align-items: center; justify-content: center; }
            .back-top { right: 80px; bottom: 20px; }

            .guide-content { padding: 20px 16px 48px; }
            .hero-banner { padding: 32px 16px 40px; }
            .hero-banner h2 { font-size: 1.5rem; }
            .hero-features { gap: 12px; }
            .hero-feat { font-size: 0.8rem; padding: 6px 14px; }

            .criteria-grid { grid-template-columns: repeat(3, 1fr); }
            .workflow { flex-direction: column; gap: 8px; }
            .workflow-arrow { transform: rotate(90deg); }

            .header-brand span { display: none; }
        }

        @media (max-width: 480px) {
            .criteria-grid { grid-template-columns: repeat(2, 1fr); }
            .header-actions a { padding: 6px 12px; font-size: 0.78rem; }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="guide-header">
        <div class="header-inner">
            <div class="header-brand">
                <i class="fas fa-graduation-cap"></i>
                <div>
                    <h1><?php echo SITE_NAME; ?></h1>
                    <span>Hướng dẫn sử dụng website</span>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo $baseUrl; ?>" class="btn-home"><i class="fas fa-home"></i> Trang chủ</a>
                <a href="<?php echo $baseUrl; ?>/login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
            </div>
        </div>
    </header>

    <!-- HERO -->
    <div class="hero-banner">
        <h2><i class="fas fa-book-reader"></i> Hướng dẫn sử dụng</h2>
        <p>Tài liệu hướng dẫn đầy đủ các tính năng của hệ thống Học tập &amp; Thi đua Trực tuyến dành cho học sinh và giáo viên.</p>
        <div class="hero-features">
            <div class="hero-feat"><i class="fas fa-pencil-alt"></i> Thi trực tuyến</div>
            <div class="hero-feat"><i class="fas fa-trophy"></i> Thi đua lớp học</div>
            <div class="hero-feat"><i class="fas fa-file-alt"></i> Tài liệu học tập</div>
            <div class="hero-feat"><i class="fas fa-chart-line"></i> Xếp hạng &amp; Thống kê</div>
        </div>
    </div>

    <!-- LAYOUT -->
    <div class="guide-layout">

        <!-- TOC SIDEBAR -->
        <nav class="toc-sidebar" id="tocSidebar">
            <div class="toc-title">Mục lục</div>

            <div class="toc-section">
                <a href="#tong-quan" class="toc-section-title" onclick="closeToc()">
                    <span class="toc-icon" style="background:#4F46E5;"><i class="fas fa-info"></i></span>
                    Tổng quan
                </a>
            </div>

            <div class="toc-section">
                <a href="#hoc-sinh" class="toc-section-title" onclick="closeToc()">
                    <span class="toc-icon" style="background:#6366F1;"><i class="fas fa-user-graduate"></i></span>
                    Dành cho Học sinh
                </a>
                <div class="toc-sub">
                    <a href="#hs-dangnhap" onclick="closeToc()">Đăng nhập</a>
                    <a href="#hs-dashboard" onclick="closeToc()">Trang chính</a>
                    <a href="#hs-lamthi" onclick="closeToc()">Làm bài thi</a>
                    <a href="#hs-tailieu" onclick="closeToc()">Tài liệu học tập</a>
                    <a href="#hs-xephang" onclick="closeToc()">Xếp hạng &amp; Thi đua</a>
                </div>
            </div>

            <div class="toc-section">
                <a href="#co-do" class="toc-section-title" onclick="closeToc()">
                    <span class="toc-icon" style="background:#EF4444;"><i class="fas fa-flag"></i></span>
                    Học sinh Cờ đỏ
                </a>
                <div class="toc-sub">
                    <a href="#cd-giithieu" onclick="closeToc()">Cờ đỏ là gì?</a>
                    <a href="#cd-chamdiem" onclick="closeToc()">Chấm điểm thi đua</a>
                    <a href="#cd-tieuchi" onclick="closeToc()">5 tiêu chí chấm điểm</a>
                </div>
            </div>

            <div class="toc-section">
                <a href="#giao-vien" class="toc-section-title" onclick="closeToc()">
                    <span class="toc-icon" style="background:#0D9488;"><i class="fas fa-chalkboard-teacher"></i></span>
                    Giáo viên / Admin
                </a>
                <div class="toc-sub">
                    <a href="#gv-dangnhap" onclick="closeToc()">Đăng nhập Admin</a>
                    <a href="#gv-thidua" onclick="closeToc()">Quản lý Thi đua</a>
                    <a href="#gv-duyetdiem" onclick="closeToc()">Duyệt điểm</a>
                </div>
            </div>

            <div class="toc-section">
                <a href="#luong-nghiep-vu" class="toc-section-title" onclick="closeToc()">
                    <span class="toc-icon" style="background:#F59E0B;"><i class="fas fa-project-diagram"></i></span>
                    Quy trình thi đua
                </a>
            </div>

            <div class="toc-section">
                <a href="#faq" class="toc-section-title" onclick="closeToc()">
                    <span class="toc-icon" style="background:#64748b;"><i class="fas fa-question"></i></span>
                    Câu hỏi thường gặp
                </a>
            </div>
        </nav>

        <div class="toc-overlay" id="tocOverlay" onclick="closeToc()"></div>

        <!-- MAIN CONTENT -->
        <main class="guide-content">

            <!-- ==================== TỔNG QUAN ==================== -->
            <section class="guide-section" id="tong-quan">
                <div class="section-header">
                    <div class="section-icon" style="background:linear-gradient(135deg,#4F46E5,#7C3AED);">
                        <i class="fas fa-info"></i>
                    </div>
                    <h2>Tổng quan hệ thống <small>Giới thiệu website</small></h2>
                </div>

                <p style="font-size:0.92rem;color:#475569;">
                    Website <strong><?php echo SITE_NAME; ?></strong> là hệ thống học tập và thi đua trực tuyến, bao gồm 2 chức năng chính:
                </p>

                <div class="role-grid" style="margin-top:16px;">
                    <div class="role-card">
                        <div class="role-icon" style="background:linear-gradient(135deg,#4F46E5,#7C3AED);">
                            <i class="fas fa-pencil-alt"></i>
                        </div>
                        <div>
                            <h4>Thi trực tuyến</h4>
                            <p>Học sinh làm bài thi online với đồng hồ đếm giờ. Có bài thi chính thức và luyện thi. Xếp hạng theo điểm.</p>
                        </div>
                    </div>
                    <div class="role-card">
                        <div class="role-icon" style="background:linear-gradient(135deg,#F59E0B,#EF4444);">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div>
                            <h4>Thi đua lớp học</h4>
                            <p>Hệ thống chấm điểm thi đua lớp theo 5 tiêu chí. Học sinh Cờ đỏ chấm chéo (chấm lớp khác). Xếp hạng lớp theo tuần.</p>
                        </div>
                    </div>
                </div>

                <div class="subsection">
                    <h3><i class="fas fa-users" style="color:#4F46E5;"></i> Các vai trò trong hệ thống</h3>

                    <div class="role-grid">
                        <div class="role-card">
                            <div class="role-icon" style="background:#EF4444;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <h4>Admin (Quản trị viên)</h4>
                                <p>Toàn quyền hệ thống. Quản lý đề thi, học sinh, phân công Cờ đỏ, duyệt điểm thi đua.</p>
                            </div>
                        </div>
                        <div class="role-card">
                            <div class="role-icon" style="background:#0D9488;">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div>
                                <h4>Tổng phụ trách</h4>
                                <p>Tổng hợp điểm từ Cờ đỏ, chỉnh sửa và gửi lên Admin duyệt.</p>
                            </div>
                        </div>
                        <div class="role-card">
                            <div class="role-icon" style="background:#F59E0B;">
                                <i class="fas fa-flag"></i>
                            </div>
                            <div>
                                <h4>Học sinh Cờ đỏ</h4>
                                <p>Được phân công chấm điểm thi đua cho LỚP KHÁC (không phải lớp mình). Đảm bảo công bằng.</p>
                            </div>
                        </div>
                        <div class="role-card">
                            <div class="role-icon" style="background:#6366F1;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <h4>Học sinh thường</h4>
                                <p>Làm bài thi, xem tài liệu, xem điểm thi đua của lớp và xếp hạng.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==================== HỌC SINH ==================== -->
            <section class="guide-section" id="hoc-sinh">
                <div class="section-header">
                    <div class="section-icon" style="background:linear-gradient(135deg,#6366F1,#818CF8);">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h2>Dành cho Học sinh <small>Hướng dẫn từng bước</small></h2>
                </div>

                <!-- Đăng nhập -->
                <div class="subsection" id="hs-dangnhap">
                    <h3><i class="fas fa-sign-in-alt" style="color:#6366F1;"></i> Bước 1: Đăng nhập</h3>
                    <div class="steps">
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#6366F1,#818CF8);">1</div>
                            <h4>Truy cập trang đăng nhập</h4>
                            <p>Mở trình duyệt web (Chrome, Firefox, Safari...) và truy cập địa chỉ website của trường.</p>
                            <a href="<?php echo $baseUrl; ?>/login.php" class="step-link" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Mở trang đăng nhập
                            </a>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#6366F1,#818CF8);">2</div>
                            <h4>Nhập thông tin đăng nhập</h4>
                            <p>Nhập <strong>Mã học sinh</strong> (ví dụ: HS3001) và <strong>Mật khẩu</strong> được giáo viên cung cấp. Nhấn nút "Đăng nhập".</p>
                        </div>
                    </div>
                    <div class="tip-box">
                        <strong>Mẹo:</strong> Mật khẩu mặc định thường là <strong>123456</strong>. Hãy đổi mật khẩu ngay sau khi đăng nhập lần đầu!
                    </div>
                </div>

                <!-- Dashboard -->
                <div class="subsection" id="hs-dashboard">
                    <h3><i class="fas fa-home" style="color:#6366F1;"></i> Bước 2: Trang chính (Dashboard)</h3>
                    <p>Sau khi đăng nhập, bạn sẽ thấy trang Dashboard với các thông tin:</p>
                    <div class="steps">
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#6366F1,#818CF8);"><i class="fas fa-chart-bar" style="font-size:14px;"></i></div>
                            <h4>Thống kê cá nhân</h4>
                            <p>Điểm trung bình, tổng số bài thi đã làm, điểm xếp hạng, chuỗi ngày học liên tục.</p>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#6366F1,#818CF8);"><i class="fas fa-th-large" style="font-size:14px;"></i></div>
                            <h4>Menu chức năng</h4>
                            <p>Thanh điều hướng bên trái với các mục: Trang chủ, Bài thi, Tài liệu, Lịch sử, Xếp hạng, Thi đua.</p>
                        </div>
                    </div>
                </div>

                <!-- Làm thi -->
                <div class="subsection" id="hs-lamthi">
                    <h3><i class="fas fa-pencil-alt" style="color:#6366F1;"></i> Bước 3: Làm bài thi</h3>
                    <p>Nhấn vào mục <strong>"Bài thi"</strong> trên thanh điều hướng để xem danh sách bài thi.</p>

                    <div class="role-grid">
                        <div class="role-card" style="border-left: 3px solid #F59E0B;">
                            <div class="role-icon" style="background:#F59E0B;">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <h4>Bài thi CHÍNH THỨC</h4>
                                <p>Có giới hạn số lần thi trong tuần. Chỉ thi vào ngày được quy định. Điểm tính vào xếp hạng chính thức.</p>
                            </div>
                        </div>
                        <div class="role-card" style="border-left: 3px solid #6366F1;">
                            <div class="role-icon" style="background:#6366F1;">
                                <i class="fas fa-redo"></i>
                            </div>
                            <div>
                                <h4>Bài LUYỆN THI</h4>
                                <p>Thi không giới hạn số lần. Luyện tập để cải thiện kỹ năng. Hiển thị điểm cao nhất.</p>
                            </div>
                        </div>
                    </div>

                    <div class="warning-box">
                        <strong>Lưu ý:</strong> Mỗi câu hỏi có <strong>đồng hồ đếm giờ</strong>. Khi hết giờ, hệ thống tự động chuyển sang câu tiếp theo. Hãy đọc kỹ câu hỏi trước khi trả lời!
                    </div>
                </div>

                <!-- Tài liệu -->
                <div class="subsection" id="hs-tailieu">
                    <h3><i class="fas fa-file-alt" style="color:#6366F1;"></i> Bước 4: Tài liệu học tập</h3>
                    <p>Nhấn vào mục <strong>"Tài liệu"</strong> để xem tài liệu do giáo viên đăng tải. Bạn có thể:</p>
                    <div class="steps">
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#6366F1,#818CF8);"><i class="fas fa-eye" style="font-size:14px;"></i></div>
                            <h4>Xem trực tiếp</h4>
                            <p>Xem file PDF, Word, PowerPoint ngay trên trình duyệt mà không cần tải về.</p>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#6366F1,#818CF8);"><i class="fas fa-download" style="font-size:14px;"></i></div>
                            <h4>Tải về máy</h4>
                            <p>Nhấn nút "Tải về" để lưu file về máy tính hoặc điện thoại của bạn.</p>
                        </div>
                    </div>
                </div>

                <!-- Xếp hạng & Thi đua -->
                <div class="subsection" id="hs-xephang">
                    <h3><i class="fas fa-trophy" style="color:#6366F1;"></i> Bước 5: Xếp hạng &amp; Thi đua lớp</h3>
                    <p>Hệ thống có 2 loại xếp hạng:</p>
                    <div class="role-grid">
                        <div class="role-card">
                            <div class="role-icon" style="background:#6366F1;">
                                <i class="fas fa-medal"></i>
                            </div>
                            <div>
                                <h4>Xếp hạng cá nhân</h4>
                                <p>Xếp hạng dựa trên điểm thi, số bài đã làm, tốc độ và chuỗi ngày học. Mục "Xếp hạng" trên menu.</p>
                            </div>
                        </div>
                        <div class="role-card">
                            <div class="role-icon" style="background:#F59E0B;">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div>
                                <h4>Xếp hạng lớp (Thi đua)</h4>
                                <p>Xem điểm thi đua của lớp mình, so sánh với các lớp cùng khối. Mục "Thi đua" trên menu.</p>
                                <a href="<?php echo $baseUrl; ?>/student/thidua/xep_hang.php" class="step-link" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> Xếp hạng lớp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==================== CỜ ĐỎ ==================== -->
            <section class="guide-section" id="co-do">
                <div class="section-header">
                    <div class="section-icon" style="background:linear-gradient(135deg,#EF4444,#F97316);">
                        <i class="fas fa-flag"></i>
                    </div>
                    <h2>Học sinh Cờ đỏ <small>Hướng dẫn chấm điểm thi đua</small></h2>
                </div>

                <!-- Cờ đỏ là gì -->
                <div class="subsection" id="cd-giithieu">
                    <h3><i class="fas fa-question-circle" style="color:#EF4444;"></i> Học sinh Cờ đỏ là gì?</h3>
                    <p>Học sinh Cờ đỏ là những học sinh được <strong>giáo viên chỉ định</strong> để chấm điểm thi đua cho các lớp. Đặc biệt:</p>
                    <div class="warning-box">
                        <strong>Nguyên tắc CHẤM CHÉO:</strong> Học sinh Cờ đỏ của lớp A sẽ chấm điểm cho <strong>LỚP KHÁC</strong> (ví dụ lớp B), KHÔNG chấm lớp mình. Điều này đảm bảo <strong>công bằng và khách quan</strong>.
                    </div>
                </div>

                <!-- Chấm điểm -->
                <div class="subsection" id="cd-chamdiem">
                    <h3><i class="fas fa-edit" style="color:#EF4444;"></i> Cách chấm điểm thi đua</h3>
                    <div class="steps">
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#EF4444,#F97316);">1</div>
                            <h4>Đăng nhập và vào trang Chấm điểm</h4>
                            <p>Sau khi đăng nhập, nhấn vào mục <strong>"Chấm điểm"</strong> trên thanh menu bên trái. (Chỉ hiển thị nếu bạn là Cờ đỏ).</p>
                            <a href="<?php echo $baseUrl; ?>/student/thidua/cham_diem.php" class="step-link" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Trang chấm điểm
                            </a>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#EF4444,#F97316);">2</div>
                            <h4>Chọn lớp và tuần</h4>
                            <p>Hệ thống tự động hiển thị lớp được phân công chấm. Chọn tuần cần chấm điểm.</p>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#EF4444,#F97316);">3</div>
                            <h4>Chấm điểm 5 tiêu chí</h4>
                            <p>Kéo thanh trượt để chấm điểm từ <strong>0 đến 10</strong> cho mỗi tiêu chí. Điểm sẽ được tự động tính trọng số.</p>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#EF4444,#F97316);">4</div>
                            <h4>Ghi chú (tùy chọn)</h4>
                            <p>Nhấn "Ghi chú" dưới mỗi tiêu chí để thêm nhận xét cụ thể (ví dụ: "Lớp đi học trễ 2 bạn").</p>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#EF4444,#F97316);">5</div>
                            <h4>Lưu tạm hoặc Gửi duyệt</h4>
                            <p><strong>Lưu tạm:</strong> Lưu lại để chỉnh sửa sau. <strong>Gửi duyệt:</strong> Gửi lên Tổng phụ trách / Admin (không thể chỉnh sửa sau khi gửi).</p>
                        </div>
                    </div>
                </div>

                <!-- 5 tiêu chí -->
                <div class="subsection" id="cd-tieuchi">
                    <h3><i class="fas fa-list-check" style="color:#EF4444;"></i> 5 Tiêu chí chấm điểm</h3>
                    <p>Mỗi lớp được chấm theo 5 tiêu chí, tổng trọng số = 100%:</p>

                    <div class="criteria-grid">
                        <div class="criteria-item" style="border-top: 3px solid #4F46E5;">
                            <div class="c-icon"><i class="fas fa-book" style="color:#4F46E5;"></i></div>
                            <div class="c-name" style="color:#4F46E5;">Học tập</div>
                            <div class="c-weight">Trọng số: 40%</div>
                        </div>
                        <div class="criteria-item" style="border-top: 3px solid #0D9488;">
                            <div class="c-icon"><i class="fas fa-user-check" style="color:#0D9488;"></i></div>
                            <div class="c-name" style="color:#0D9488;">Nề nếp</div>
                            <div class="c-weight">Trọng số: 25%</div>
                        </div>
                        <div class="criteria-item" style="border-top: 3px solid #0EA5E9;">
                            <div class="c-icon"><i class="fas fa-broom" style="color:#0EA5E9;"></i></div>
                            <div class="c-name" style="color:#0EA5E9;">Vệ sinh</div>
                            <div class="c-weight">Trọng số: 15%</div>
                        </div>
                        <div class="criteria-item" style="border-top: 3px solid #F59E0B;">
                            <div class="c-icon"><i class="fas fa-users" style="color:#F59E0B;"></i></div>
                            <div class="c-name" style="color:#F59E0B;">Hoạt động</div>
                            <div class="c-weight">Trọng số: 15%</div>
                        </div>
                        <div class="criteria-item" style="border-top: 3px solid #EF4444;">
                            <div class="c-icon"><i class="fas fa-handshake" style="color:#EF4444;"></i></div>
                            <div class="c-name" style="color:#EF4444;">Đoàn kết</div>
                            <div class="c-weight">Trọng số: 5%</div>
                        </div>
                    </div>

                    <div class="info-box">
                        <strong>Công thức tính điểm:</strong> Điểm có trọng số = (Điểm / 10) &times; Trọng số. Ví dụ: Học tập 8.5/10 &times; 40% = <strong>34 điểm</strong>. Tổng tối đa: <strong>100 điểm</strong>.
                    </div>
                </div>
            </section>

            <!-- ==================== GIÁO VIÊN ==================== -->
            <section class="guide-section" id="giao-vien">
                <div class="section-header">
                    <div class="section-icon" style="background:linear-gradient(135deg,#0D9488,#14B8A6);">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h2>Giáo viên / Admin <small>Quản lý hệ thống</small></h2>
                </div>

                <!-- Đăng nhập -->
                <div class="subsection" id="gv-dangnhap">
                    <h3><i class="fas fa-sign-in-alt" style="color:#0D9488;"></i> Đăng nhập Admin</h3>
                    <div class="steps">
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#0D9488,#14B8A6);">1</div>
                            <h4>Truy cập trang đăng nhập Admin</h4>
                            <p>Truy cập địa chỉ website của trường, thêm <strong>/admin</strong> vào cuối địa chỉ.</p>
                            <a href="<?php echo $baseUrl; ?>/admin/login.php" class="step-link" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Mở trang Admin
                            </a>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#0D9488,#14B8A6);">2</div>
                            <h4>Nhập tài khoản và mật khẩu</h4>
                            <p>Sử dụng tài khoản admin được cấp. Sau khi đăng nhập, bạn sẽ thấy Dashboard quản trị.</p>
                        </div>
                    </div>
                </div>

                <!-- Quản lý thi đua -->
                <div class="subsection" id="gv-thidua">
                    <h3><i class="fas fa-cogs" style="color:#0D9488;"></i> Quản lý Thi đua lớp học</h3>
                    <p>Trong menu admin, mục <strong>"Thi đua lớp học"</strong> bao gồm:</p>

                    <div class="steps">
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#0D9488,#14B8A6);"><i class="fas fa-flag" style="font-size:14px;"></i></div>
                            <h4>Quản lý Học sinh Cờ đỏ</h4>
                            <p>Gán / gỡ bỏ quyền Cờ đỏ cho học sinh. Xem lịch sử phân công.</p>
                            <a href="<?php echo $baseUrl; ?>/admin/thidua/hoc_sinh_co_do/" class="step-link" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Quản lý Cờ đỏ
                            </a>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#0D9488,#14B8A6);"><i class="fas fa-random" style="font-size:14px;"></i></div>
                            <h4>Phân công chấm chéo</h4>
                            <p>Thiết lập: Cờ đỏ lớp A chấm lớp B, Cờ đỏ lớp B chấm lớp C,... theo vòng tròn. Đảm bảo công bằng.</p>
                            <a href="<?php echo $baseUrl; ?>/admin/thidua/phan_cong_cham_diem/" class="step-link" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Phân công chấm chéo
                            </a>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#0D9488,#14B8A6);"><i class="fas fa-check-double" style="font-size:14px;"></i></div>
                            <h4>Duyệt điểm thi đua</h4>
                            <p>Xem điểm Cờ đỏ đã chấm, chỉnh sửa nếu cần, duyệt hoặc từ chối. Chỉ Admin có quyền duyệt cuối cùng.</p>
                            <a href="<?php echo $baseUrl; ?>/admin/thidua/duyet_diem/" class="step-link" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Duyệt điểm
                            </a>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:linear-gradient(135deg,#0D9488,#14B8A6);"><i class="fas fa-trophy" style="font-size:14px;"></i></div>
                            <h4>Xếp hạng lớp theo tuần</h4>
                            <p>Xem bảng xếp hạng toàn trường và từng khối. Sau khi duyệt điểm, hệ thống tự tính tổng điểm và xếp hạng.</p>
                            <a href="<?php echo $baseUrl; ?>/admin/thidua/xep_hang/tuan.php" class="step-link" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Xếp hạng tuần
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Duyệt điểm -->
                <div class="subsection" id="gv-duyetdiem">
                    <h3><i class="fas fa-check-circle" style="color:#0D9488;"></i> Quy trình duyệt điểm</h3>
                    <p>Điểm thi đua đi qua 3 bước trước khi được công bố:</p>

                    <div class="steps">
                        <div class="step-card">
                            <div class="step-num" style="background:#F59E0B;">1</div>
                            <h4>Chấm từ thứ 2 đến thứ 7</h4>
                            <p>Học sinh Cờ đỏ đăng nhập, chấm điểm cho lớp được phân công, gửi lên hệ thống.</p>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:#0D9488;">2</div>
                            <h4>Lớp trực tuần tổng hợp: vào cuối thứ 7</h4>
                            <p>Kiểm tra lại điểm, chỉnh sửa nếu cần, gửi lên Admin duyệt.</p>
                        </div>
                        <div class="step-card">
                            <div class="step-num" style="background:#4F46E5;">3</div>
                            <h4>Admin duyệt vào: thứ 2 tuần tiếp</h4>
                            <p>Duyệt tất cả hoặc từ chối. Sau khi duyệt, hệ thống tự tính tổng điểm và xếp hạng.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==================== QUY TRÌNH ==================== -->
            <section class="guide-section" id="luong-nghiep-vu">
                <div class="section-header">
                    <div class="section-icon" style="background:linear-gradient(135deg,#F59E0B,#EF4444);">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <h2>Quy trình thi đua hàng tuần <small>Từ chấm điểm đến công bố</small></h2>
                </div>

                <div class="workflow">
                    <div class="workflow-step">
                        <div class="wf-icon"><i class="fas fa-flag" style="color:#EF4444;"></i></div>
                        <div class="wf-title">Cờ đỏ chấm điểm</div>
                        <div class="wf-desc">T2 - T5</div>
                    </div>
                    <div class="workflow-arrow"><i class="fas fa-arrow-right"></i></div>
                    <div class="workflow-step">
                        <div class="wf-icon"><i class="fas fa-user-tie" style="color:#0D9488;"></i></div>
                        <div class="wf-title">TPT tổng hợp</div>
                        <div class="wf-desc">Thứ 6</div>
                    </div>
                    <div class="workflow-arrow"><i class="fas fa-arrow-right"></i></div>
                    <div class="workflow-step">
                        <div class="wf-icon"><i class="fas fa-check-double" style="color:#4F46E5;"></i></div>
                        <div class="wf-title">Admin duyệt</div>
                        <div class="wf-desc">Thứ 7</div>
                    </div>
                    <div class="workflow-arrow"><i class="fas fa-arrow-right"></i></div>
                    <div class="workflow-step">
                        <div class="wf-icon"><i class="fas fa-bullhorn" style="color:#F59E0B;"></i></div>
                        <div class="wf-title">Công bố kết quả</div>
                        <div class="wf-desc">Chủ nhật</div>
                    </div>
                </div>

                <div class="subsection" style="margin-top:24px;">
                    <h3><i class="fas fa-star" style="color:#F59E0B;"></i> Bảng xếp loại</h3>
                    <table class="class-table">
                        <thead>
                            <tr>
                                <th>Xếp loại</th>
                                <th>Điểm</th>
                                <th>Mô tả</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="class-badge" style="background:#FEF3C7;color:#D97706;">Xuất sắc</span></td>
                                <td><strong>90 - 100</strong></td>
                                <td>Lớp đạt thành tích xuất sắc trên mọi mặt</td>
                            </tr>
                            <tr>
                                <td><span class="class-badge" style="background:#D1FAE5;color:#059669;">Tốt</span></td>
                                <td><strong>80 - 89</strong></td>
                                <td>Lớp có nhiều mặt tốt, cần phát huy</td>
                            </tr>
                            <tr>
                                <td><span class="class-badge" style="background:#DBEAFE;color:#2563EB;">Khá</span></td>
                                <td><strong>70 - 79</strong></td>
                                <td>Lớp khá, còn một số mặt cần cải thiện</td>
                            </tr>
                            <tr>
                                <td><span class="class-badge" style="background:#F3F4F6;color:#6B7280;">Trung bình</span></td>
                                <td><strong>50 - 69</strong></td>
                                <td>Cần nỗ lực nhiều hơn ở các tiêu chí</td>
                            </tr>
                            <tr>
                                <td><span class="class-badge" style="background:#FEE2E2;color:#DC2626;">Cần cố gắng</span></td>
                                <td><strong>Dưới 50</strong></td>
                                <td>Cần cải thiện gấp để vươn lên</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ==================== FAQ ==================== -->
            <section class="guide-section" id="faq">
                <div class="section-header">
                    <div class="section-icon" style="background:linear-gradient(135deg,#64748b,#94a3b8);">
                        <i class="fas fa-question"></i>
                    </div>
                    <h2>Câu hỏi thường gặp <small>FAQ</small></h2>
                </div>

                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)">
                        Mật khẩu mặc định là gì?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-a">
                        Mật khẩu mặc định cho học sinh thường là <strong>123456</strong>. Giáo viên sẽ thông báo mật khẩu cụ thể. Sau khi đăng nhập lần đầu, hãy đổi mật khẩu bằng cách vào <strong>Avatar > Đổi mật khẩu</strong> trên Dashboard.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)">
                        Tôi quên mật khẩu, phải làm sao?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-a">
                        Liên hệ với giáo viên chủ nhiệm hoặc Admin để được cấp lại mật khẩu. Hệ thống chưa có chức năng tự phục hồi mật khẩu.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)">
                        Tại sao tôi không thấy mục "Chấm điểm" trên menu?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-a">
                        Mục "Chấm điểm" chỉ hiển thị cho <strong>Học sinh Cờ đỏ</strong>. Nếu bạn là học sinh thường, bạn sẽ không thấy mục này. Liên hệ giáo viên nếu bạn được chỉ định làm Cờ đỏ nhưng chưa thấy.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)">
                        Điểm thi đua được tính như thế nào?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-a">
                        Mỗi tiêu chí được chấm từ 0 đến 10, sau đó nhân với trọng số:
                        <br>- Học tập: &times;40% | Nề nếp: &times;25% | Vệ sinh: &times;15% | Hoạt động: &times;15% | Đoàn kết: &times;5%
                        <br>Ví dụ: Học tập 8.5 &rarr; (8.5/10) &times; 40 = <strong>34 điểm</strong>. Tổng 5 tiêu chí = tổng điểm lớp (tối đa 100).
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)">
                        Bài thi chính thức khác gì với luyện thi?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-a">
                        <strong>Bài thi chính thức:</strong> Có giới hạn số lần thi, điểm tính vào xếp hạng. Thường chỉ thi vào cuối tuần.
                        <br><strong>Luyện thi:</strong> Thi không giới hạn, để ôn tập và rèn luyện. Điểm không ảnh hưởng xếp hạng chính thức.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)">
                        Website có dùng được trên điện thoại không?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-a">
                        Có! Website đã được tối ưu cho điện thoại di động. Bạn có thể truy cập bằng trình duyệt Chrome hoặc Safari trên điện thoại. Hệ thống tự động chuyển sang giao diện mobile phù hợp.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-q" onclick="toggleFaq(this)">
                        Cờ đỏ có thể chấm điểm lớp mình không?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-a">
                        <strong>Không.</strong> Để đảm bảo công bằng, Cờ đỏ chỉ được chấm lớp mà Admin đã phân công (thường là lớp khác). Hệ thống sẽ không cho phép chấm lớp mình.
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- Footer -->
    <div class="guide-footer">
        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - <?php echo SITE_ADDRESS; ?>
        <br>Giáo viên phụ trách: <?php echo defined('SITE_TEACHER') ? SITE_TEACHER : ''; ?>
    </div>

    <!-- TOC Toggle (Mobile) -->
    <button class="toc-toggle" id="tocToggle" onclick="toggleToc()">
        <i class="fas fa-list"></i>
    </button>

    <!-- Back to top -->
    <button class="back-top" id="backTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
    // TOC sidebar (mobile)
    function toggleToc() {
        document.getElementById('tocSidebar').classList.toggle('open');
        document.getElementById('tocOverlay').classList.toggle('show');
    }

    function closeToc() {
        document.getElementById('tocSidebar').classList.remove('open');
        document.getElementById('tocOverlay').classList.remove('show');
    }

    // FAQ accordion
    function toggleFaq(el) {
        var item = el.parentElement;
        var wasOpen = item.classList.contains('open');
        // Close all
        document.querySelectorAll('.faq-item').forEach(function(f) { f.classList.remove('open'); });
        // Toggle current
        if (!wasOpen) item.classList.add('open');
    }

    // Back to top button
    window.addEventListener('scroll', function() {
        var btn = document.getElementById('backTop');
        if (window.scrollY > 400) {
            btn.classList.add('show');
        } else {
            btn.classList.remove('show');
        }
    });

    // Smooth scroll for TOC links
    document.querySelectorAll('.toc-section-title, .toc-sub a').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                e.preventDefault();
                var target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    // Highlight active TOC item on scroll
    var sections = document.querySelectorAll('.guide-section');
    var tocLinks = document.querySelectorAll('.toc-section-title');

    window.addEventListener('scroll', function() {
        var current = '';
        sections.forEach(function(section) {
            var sectionTop = section.offsetTop - 100;
            if (window.scrollY >= sectionTop) {
                current = section.getAttribute('id');
            }
        });

        tocLinks.forEach(function(link) {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    });
    </script>
</body>
</html>
