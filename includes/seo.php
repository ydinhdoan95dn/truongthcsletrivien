<?php
/**
 * ==============================================
 * SEO HELPER
 * Tối ưu SEO cho website
 * Trường Tiểu học Bùi Thị Xuân
 * ==============================================
 */

// Thông tin SEO mặc định
define('SEO_TITLE', 'Trường THCS Lê Trí Viễn');
define('SEO_DESCRIPTION', 'Hệ thống Học tập & Thi đua Trực tuyến - Trường THCS Lê Trí Viễn, Phường Điện Bàn Bắc, TP Đà Nẵng.');
define('SEO_KEYWORDS', 'trường THCS, lê trí viễn, học trực tuyến, thi trực tuyến, thi đua, học sinh THCS, đà nẵng, điện bàn bắc');
define('SEO_AUTHOR', 'Trần Văn Phi Hoàng, Lê Quang Nguyên. GVHD: Đoàn Thị Ngọc Lĩnh');
define('SEO_SITE_NAME', 'Trường THCS Lê Trí Viễn');

/**
 * Lấy URL đầy đủ của trang hiện tại
 */
function getSeoCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    return $protocol . '://' . $host . $uri;
}

/**
 * Lấy URL ảnh SEO (banner)
 */
function getSeoImage() {
    return BASE_URL . '/banner_seo1.jpg';
}

/**
 * Lấy URL favicon
 */
function getSeoFavicon() {
    return BASE_URL . '/favicon.ico';
}

/**
 * Tạo meta tags SEO
 * @param string $title - Tiêu đề trang
 * @param string $description - Mô tả trang (tùy chọn)
 * @param string $image - URL ảnh (tùy chọn)
 * @return string - HTML meta tags
 */
function getSeoMetaTags($title = '', $description = '', $image = '') {
    $fullTitle = $title ? $title . ' - ' . SEO_TITLE : SEO_TITLE;
    $desc = $description ? $description : SEO_DESCRIPTION;
    $img = $image ? $image : getSeoImage();
    $url = getSeoCurrentUrl();

    $html = '
    <!-- SEO Meta Tags -->
    <meta name="description" content="' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '">
    <meta name="keywords" content="' . SEO_KEYWORDS . '">
    <meta name="author" content="' . SEO_AUTHOR . '">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    <link rel="canonical" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="' . getSeoFavicon() . '">
    <link rel="shortcut icon" type="image/x-icon" href="' . getSeoFavicon() . '">
    <link rel="apple-touch-icon" href="' . getSeoFavicon() . '">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">
    <meta property="og:title" content="' . htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') . '">
    <meta property="og:description" content="' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '">
    <meta property="og:image" content="' . htmlspecialchars($img, ENT_QUOTES, 'UTF-8') . '">
    <meta property="og:site_name" content="' . SEO_SITE_NAME . '">
    <meta property="og:locale" content="vi_VN">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">
    <meta name="twitter:title" content="' . htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') . '">
    <meta name="twitter:description" content="' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '">
    <meta name="twitter:image" content="' . htmlspecialchars($img, ENT_QUOTES, 'UTF-8') . '">

    <!-- Additional SEO -->
    <meta name="geo.region" content="VN-DN">
    <meta name="geo.placename" content="Đà Nẵng">
    <meta name="geo.position" content="16.054407;108.202167">
    <meta name="ICBM" content="16.054407, 108.202167">
    ';

    return $html;
}

/**
 * Tạo JSON-LD Structured Data cho website
 * @param string $pageType - Loại trang (WebSite, Organization, EducationalOrganization)
 * @return string - JSON-LD script
 */
function getSeoJsonLd($pageType = 'WebSite') {
    $baseUrl = BASE_URL;

    $data = array(
        '@context' => 'https://schema.org',
        '@type' => 'EducationalOrganization',
        'name' => 'Trường THCS Lê Trí Viễn',
        'alternateName' => 'THCS Lê Trí Viễn',
        'description' => SEO_DESCRIPTION,
        'url' => $baseUrl,
        'logo' => getSeoImage(),
        'image' => getSeoImage(),
        'address' => array(
            '@type' => 'PostalAddress',
            'streetAddress' => 'Phường Điện Bàn Bắc',
            'addressLocality' => 'Đà Nẵng',
            'addressRegion' => 'Đà Nẵng',
            'addressCountry' => 'VN'
        ),
        'sameAs' => array(
            $baseUrl
        )
    );

    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    return '
    <!-- Structured Data -->
    <script type="application/ld+json">
    ' . $json . '
    </script>';
}

/**
 * Hàm tiện ích: In toàn bộ SEO tags
 * @param string $title - Tiêu đề trang
 * @param string $description - Mô tả trang (tùy chọn)
 */
function printSeoTags($title = '', $description = '') {
    echo getSeoMetaTags($title, $description);
    echo getSeoJsonLd();
}
