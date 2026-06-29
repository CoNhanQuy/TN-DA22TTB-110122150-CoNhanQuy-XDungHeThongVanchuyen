<?php
/**
 * Cấu hình chung hệ thống — config/cauhinh.php
 * (Di chuyển từ backend/cauhinh.php)
 */
if (defined('CAUHINH_LOADED')) return;
define('CAUHINH_LOADED', true);

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'vanchuyen_dn');

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
