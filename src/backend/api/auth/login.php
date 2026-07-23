<?php
/**
 * Route: POST /api/auth/login
 * Gọi AuthController->login()
 */
require_once __DIR__ . '/../../controllers/authcontroller.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(false, null, 'Phương thức không hợp lệ');
}

$username = trim($_POST['username'] ?? $_POST['so_dien_thoai'] ?? '');
$password = $_POST['password'] ?? $_POST['mat_khau'] ?? '';

if (!$username || !$password) {
    response(false, null, 'Vui lòng nhập số điện thoại và mật khẩu');
}

$auth  = new AuthController($conn);
$vaiTro = $auth->login($username, $password);

if ($vaiTro === false) {
    response(false, null, 'Số điện thoại hoặc mật khẩu không đúng');
}

// Redirect URL theo vai trò
$redirectMap = [
    'admin'                  => APP_BASE_URL . '/frontend/quantri/',
    'nhan_vien_tiep_nhan'    => APP_BASE_URL . '/frontend/tiepnhan/',
    'nhan_vien_dieu_phoi'    => APP_BASE_URL . '/frontend/dieuphoi/',
    'tai_xe'                 => APP_BASE_URL . '/frontend/taixe/',
    'shipper'                => APP_BASE_URL . '/frontend/giaohang/',
    'khach_hang'             => APP_BASE_URL . '/frontend/khachhang/',
];

response(true, [
    'vai_tro'  => $vaiTro,
    'ho_ten'   => $_SESSION['ho_ten'] ?? '',
    'redirect' => $redirectMap[$vaiTro] ?? (APP_BASE_URL . '/index.php'),
], 'Đăng nhập thành công');
