<?php
/**
 * Route: POST /api/auth/login
 * Gọi AuthController->login()
 */
require_once __DIR__ . '/../../controllers/AuthController.php';

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
    'admin'                  => '/DATN/frontend/quantri/',
    'nhan_vien_tiep_nhan'    => '/DATN/frontend/tiepnhan/',
    'nhan_vien_dieu_phoi'    => '/DATN/frontend/dieuphoi/',
    'tai_xe'                 => '/DATN/frontend/taixe/',
    'shipper'                => '/DATN/frontend/giaohang/',
    'khach_hang'             => '/DATN/frontend/khachhang/',
];

response(true, [
    'vai_tro'  => $vaiTro,
    'ho_ten'   => $_SESSION['ho_ten'] ?? '',
    'redirect' => $redirectMap[$vaiTro] ?? '/DATN/frontend/trangchu/',
], 'Đăng nhập thành công');
