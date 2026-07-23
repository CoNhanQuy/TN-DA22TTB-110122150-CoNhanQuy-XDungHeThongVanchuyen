<?php
/**
 * Cấu hình chung hệ thống — config/cauhinh.php
 * (Di chuyển từ backend/cauhinh.php)
 */
if (defined('CAUHINH_LOADED')) return;
define('CAUHINH_LOADED', true);

// Polyfills cho PHP 8.0 string functions nếu server chạy phiên bản cũ (PHP 7.4)
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || $needle === substr($haystack, -strlen($needle));
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }
}

// Tải biến môi trường từ file .env ở thư mục gốc (nếu có)
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Cấu hình Database (đọc từ $_ENV hoặc dùng giá trị mặc định)
if (!defined('DB_HOST')) define('DB_HOST', $_ENV['DB_HOST'] ?? 'sql108.infinityfree.com');
if (!defined('DB_USER')) define('DB_USER', $_ENV['DB_USER'] ?? 'if0_42253679');
if (!defined('DB_PASS')) define('DB_PASS', $_ENV['DB_PASS'] ?? 'CoNhanQuy123Yuq');
if (!defined('DB_NAME')) define('DB_NAME', $_ENV['DB_NAME'] ?? 'if0_42253679_vanchuyen_dn');

// Cấu hình URL cơ sở của hệ thống (đọc từ .env hoặc tự động nhận diện)
if (!defined('APP_BASE_URL')) {
    $appBase = $_ENV['APP_BASE_URL'] ?? '';
    if ($appBase === '') {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (str_contains($scriptName, '/frontend/')) {
            $appBase = explode('/frontend/', $scriptName)[0];
        } elseif (str_contains($scriptName, '/backend/')) {
            $appBase = explode('/backend/', $scriptName)[0];
        } else {
            $appBase = rtrim(dirname($scriptName), '/\\');
        }
    }
    define('APP_BASE_URL', $appBase !== '' ? rtrim($appBase, '/') : '');
}

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error, 0);
        die("Đã xảy ra lỗi kết nối cơ sở dữ liệu. Vui lòng thử lại sau hoặc liên hệ quản trị viên.");
    }

    $conn->set_charset("utf8mb4");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
