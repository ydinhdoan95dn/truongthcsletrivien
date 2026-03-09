<?php
/**
 * ==============================================
 * CẤU HÌNH HỆ THỐNG
 * Hệ thống Học tập & Thi đua Trực tuyến THCS
 * Trường THCS Lê Trí Viễn - Phường Điện Bàn Bắc - TP Đà Nẵng
 * ==============================================
 */

// Múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Cấu hình đường dẫn - Tự động detect môi trường
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// Production vs Development - Hỗ trợ nhiều domain
$productionDomains = array(
    'truongthcsletrivien.com',
    'www.truongthcsletrivien.com',
    'truongbuithixuan.online',
    'www.truongbuithixuan.online',
    'topwebvietnam.com',
    'truongbuithixuan.topwebvietnam.com'
);

$isProduction = false;
foreach ($productionDomains as $domain) {
    if (strpos($host, $domain) !== false) {
        $isProduction = true;
        break;
    }
}

if ($isProduction) {
    define('BASE_URL', $protocol . '://' . $host);
    define('IS_PRODUCTION', true);
    // Tắt hiển thị lỗi trên production
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    define('BASE_URL', 'http://localhost/chi_2');
    define('IS_PRODUCTION', false);
    // Bật hiển thị lỗi trên development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Cấu hình Database - Tự động theo môi trường
define('DB_HOST', 'localhost');
define('DB_NAME', 'truongthcsletrivien');
// Production: Thay đổi user/pass phù hợp với hosting
define('DB_USER', IS_PRODUCTION ? 'root' : 'root');
define('DB_PASS', IS_PRODUCTION ? '' : '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/');

// Cấu hình website
define('SITE_NAME', 'Trường THCS Lê Trí Viễn');
define('SITE_DESCRIPTION', 'Hệ thống Học tập & Thi đua Trực tuyến');

// Cấu hình thi
define('DEFAULT_QUESTIONS', 10);      // Số câu hỏi mặc định
define('DEFAULT_TIME_PER_QUESTION', 15); // Giây/câu

// Hệ số tính điểm xếp hạng
define('RANK_FACTOR_AVG_SCORE', 60);   // 60%
define('RANK_FACTOR_ATTEMPTS', 20);    // 20%
define('RANK_FACTOR_SPEED', 10);       // 10%
define('RANK_FACTOR_STREAK', 10);      // 10%

// Cấu hình bảo mật
define('HASH_COST', 10);
define('SESSION_LIFETIME', 3600); // 1 giờ

/**
 * Hàm kết nối Database
 */
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            );
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Lỗi kết nối database: " . $e->getMessage());
        }
    }

    return $conn;
}

/**
 * Hàm bảo mật input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Hàm tạo random bytes tương thích PHP 5.6
 */
function generateRandomBytes($length = 32) {
    if (function_exists('random_bytes')) {
        return random_bytes($length);
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        return openssl_random_pseudo_bytes($length);
    } else {
        // Fallback cho PHP cũ
        $bytes = '';
        for ($i = 0; $i < $length; $i++) {
            $bytes .= chr(mt_rand(0, 255));
        }
        return $bytes;
    }
}

/**
 * Hàm tạo token chống CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(generateRandomBytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Hàm kiểm tra token CSRF
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) return false;
    // hash_equals có thể không có trong PHP 5.5
    if (function_exists('hash_equals')) {
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    return $_SESSION['csrf_token'] === $token;
}

/**
 * Hàm tạo session token cho bài thi (chống gian lận)
 */
function generateExamToken() {
    return bin2hex(generateRandomBytes(32));
}

/**
 * Hàm hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
}

/**
 * Hàm verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Hàm redirect
 */
function redirect($url) {
    header("Location: " . BASE_URL . "/" . $url);
    exit;
}

/**
 * Hàm lấy IP người dùng
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Hàm format ngày tháng tiếng Việt
 */
function formatDateVN($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Hàm format thời gian
 */
function formatTime($seconds) {
    $minutes = floor($seconds / 60);
    $secs = $seconds % 60;
    return sprintf("%02d:%02d", $minutes, $secs);
}

/**
 * Hàm tính điểm xếp hạng theo công thức
 * Điểm = (ĐTB × 60%) + (Số lần thi × 20%) + (Tốc độ × 10%) + (Chuỗi ngày × 10%)
 */
function calculateRankScore($avgScore, $attempts, $avgSpeed, $streak) {
    // Chuẩn hóa các chỉ số
    $normalizedAvg = min($avgScore, 10);  // Tối đa 10 điểm
    $normalizedAttempts = min($attempts / 50, 1) * 10; // Tối đa 50 lần = 10 điểm
    $normalizedSpeed = max(0, (DEFAULT_TIME_PER_QUESTION - $avgSpeed) / DEFAULT_TIME_PER_QUESTION) * 10; // Càng nhanh càng cao
    $normalizedStreak = min($streak / 30, 1) * 10; // Tối đa 30 ngày = 10 điểm

    // Tính điểm xếp hạng
    $rankScore = ($normalizedAvg * RANK_FACTOR_AVG_SCORE / 100)
               + ($normalizedAttempts * RANK_FACTOR_ATTEMPTS / 100)
               + ($normalizedSpeed * RANK_FACTOR_SPEED / 100)
               + ($normalizedStreak * RANK_FACTOR_STREAK / 100);

    return round($rankScore, 2);
}

/**
 * Hàm log hoạt động
 */
function logActivity($userType, $userId, $action, $detail = '') {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO log_hoat_dong (user_type, user_id, hanh_dong, chi_tiet, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(array($userType, $userId, $action, $detail, getUserIP()));
}

/**
 * Hàm kiểm tra đăng nhập học sinh
 */
function isStudentLoggedIn() {
    return isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
}

/**
 * Hàm kiểm tra đăng nhập admin
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Hàm lấy thông tin học sinh đang đăng nhập
 */
function getCurrentStudent() {
    if (!isStudentLoggedIn()) return null;

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT hs.*, lh.ten_lop, lh.khoi FROM hoc_sinh hs
                           JOIN lop_hoc lh ON hs.lop_id = lh.id
                           WHERE hs.id = ?");
    $stmt->execute(array($_SESSION['student_id']));
    return $stmt->fetch();
}

/**
 * Hàm lấy thông tin admin đang đăng nhập
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) return null;

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute(array($_SESSION['admin_id']));
    return $stmt->fetch();
}

/**
 * Hàm đánh giá kết quả thi
 */
function evaluateResult($score) {
    if ($score >= 9) {
        return array('text' => 'Xuất sắc!', 'icon' => '🌟', 'class' => 'excellent');
    } elseif ($score >= 7) {
        return array('text' => 'Tốt lắm!', 'icon' => '👍', 'class' => 'good');
    } elseif ($score >= 5) {
        return array('text' => 'Khá!', 'icon' => '😊', 'class' => 'fair');
    } else {
        return array('text' => 'Cần cố gắng!', 'icon' => '💪', 'class' => 'need-improve');
    }
}

/**
 * Hàm cập nhật chuỗi ngày học liên tục
 */
function updateStudyStreak($studentId) {
    $conn = getDBConnection();
    $today = date('Y-m-d');

    // Lấy thông tin ngày học cuối
    $stmt = $conn->prepare("SELECT ngay_hoc_cuoi, chuoi_ngay_hoc FROM hoc_sinh WHERE id = ?");
    $stmt->execute(array($studentId));
    $student = $stmt->fetch();

    $lastStudyDate = $student['ngay_hoc_cuoi'];
    $currentStreak = $student['chuoi_ngay_hoc'];

    if ($lastStudyDate == $today) {
        // Đã học hôm nay rồi, không cần cập nhật
        return $currentStreak;
    }

    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($lastStudyDate == $yesterday) {
        // Học liên tục, tăng chuỗi
        $newStreak = $currentStreak + 1;
    } else {
        // Bị gián đoạn, reset về 1
        $newStreak = 1;
    }

    // Cập nhật
    $stmt = $conn->prepare("UPDATE hoc_sinh SET ngay_hoc_cuoi = ?, chuoi_ngay_hoc = ? WHERE id = ?");
    $stmt->execute(array($today, $newStreak, $studentId));

    return $newStreak;
}

/**
 * Hàm lấy icon môn học (chuyển từ tên icon sang emoji)
 * @param string $iconName - Tên icon từ database (calculator, book, etc.)
 * @param string $fallback - Emoji mặc định nếu không tìm thấy
 * @return string Emoji
 */
function getSubjectIcon($iconName, $fallback = '📚') {
    $iconMap = array(
        // Môn học chính
        'calculator' => '🔢',
        'math' => '🔢',
        'book' => '📖',
        'book-open' => '📖',
        'pencil' => '✏️',
        'pen' => '🖊️',

        // Ngôn ngữ
        'language' => '🔤',
        'abc' => '🔤',
        'vietnamese' => '🇻🇳',
        'english' => '🇬🇧',
        'globe' => '🌍',

        // Khoa học
        'science' => '🔬',
        'flask' => '🧪',
        'atom' => '⚛️',
        'leaf' => '🌿',
        'tree' => '🌳',
        'nature' => '🌿',

        // Nghệ thuật
        'music' => '🎵',
        'art' => '🎨',
        'palette' => '🎨',
        'paint' => '🖌️',

        // Thể chất
        'sports' => '⚽',
        'running' => '🏃',
        'physical' => '🏃',

        // Công nghệ
        'computer' => '💻',
        'code' => '👨‍💻',
        'tech' => '💻',

        // Lịch sử - Địa lý
        'history' => '📜',
        'geography' => '🗺️',
        'map' => '🗺️',

        // Đạo đức
        'ethics' => '💝',
        'heart' => '❤️',
        'moral' => '💝',

        // Khác
        'star' => '⭐',
        'trophy' => '🏆',
        'clock' => '⏰',
        'calendar' => '📅',
        'folder' => '📁',
        'file' => '📄',
        'document' => '📄',
        'question' => '❓',
        'quiz' => '❓',
        'test' => '📝',
        'exam' => '📝'
    );

    $iconName = strtolower(trim($iconName));

    // Nếu đã là emoji (ký tự unicode), trả về luôn
    if (mb_strlen($iconName) <= 4 && preg_match('/[\x{1F300}-\x{1F9FF}]/u', $iconName)) {
        return $iconName;
    }

    return isset($iconMap[$iconName]) ? $iconMap[$iconName] : $fallback;
}

/**
 * Hàm lấy avatar học sinh (emoji theo giới tính)
 * @param array $student - Thông tin học sinh (cần có avatar, gioi_tinh)
 * @param bool $asEmoji - Trả về emoji hay URL ảnh
 * @return string
 */
function getStudentAvatar($student, $asEmoji = true) {
    // Nếu đã có avatar tùy chỉnh (không phải default)
    if (!empty($student['avatar']) && $student['avatar'] !== 'default_student.png') {
        if ($asEmoji) {
            // Kiểm tra xem avatar có phải là emoji không
            if (mb_strlen($student['avatar']) <= 4) {
                return $student['avatar'];
            }
            // Nếu là file ảnh, trả về emoji mặc định theo giới tính
            return ($student['gioi_tinh'] == 1) ? '👦' : '👧';
        }
        return BASE_URL . '/uploads/avatars/' . $student['avatar'];
    }

    // Trả về emoji theo giới tính
    if ($asEmoji) {
        return ($student['gioi_tinh'] == 1) ? '👦' : '👧';
    }

    // Trả về ảnh mặc định theo giới tính
    $defaultImg = ($student['gioi_tinh'] == 1) ? 'default_boy.png' : 'default_girl.png';
    return BASE_URL . '/uploads/avatars/' . $defaultImg;
}

// Khởi tạo session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
