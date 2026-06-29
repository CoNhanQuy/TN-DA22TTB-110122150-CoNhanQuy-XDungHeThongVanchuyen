<?php
/**
 * Route: POST /api/auth/verify_otp
 * Xử lý 3 action OTP: request_otp, verify_otp, reset_password
 * Gọi AuthController
 */
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../config/sms_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(false, null, 'Phương thức không hợp lệ');
}

$action = trim($_POST['action'] ?? '');
$auth   = new AuthController($conn);

if ($action === 'request_otp') {
    $sdt    = trim($_POST['so_dien_thoai'] ?? '');
    $result = $auth->requestOtp($sdt);

    if (!$result['success']) {
        response(false, null, $result['message']);
    }

    // Gửi SMS
    $otp  = $result['otp'];
    $sdtE = $result['sdt'];
    $msg  = "Van Tai Xanh: Ma xac thuc cua ban la $otp. Het han sau 5 phut. Khong chia se ma nay cho bat ky ai.";
    $sms  = sendSMS($sdtE, $msg);

    $sdtMasked = maskPhone($sdtE);
    if (!$sms['success']) {
        response(true, ['sdt_masked' => $sdtMasked],
            'OTP đã tạo nhưng gửi SMS thất bại: ' . $sms['message'] . '. Liên hệ hỗ trợ nếu cần.');
    }
    response(true, ['sdt_masked' => $sdtMasked], "Mã OTP đã được gửi đến $sdtMasked. Có hiệu lực trong 5 phút.");
}

if ($action === 'verify_otp') {
    $sdt = trim($_POST['so_dien_thoai'] ?? '');
    $otp = trim($_POST['ma_otp'] ?? '');
    if (!$sdt || !$otp) response(false, null, 'Thiếu thông tin xác thực');

    $auth->verifyOtp($sdt, $otp)
        ? response(true, null, 'Xác thực OTP thành công. Vui lòng nhập mật khẩu mới.')
        : response(false, null, 'Mã OTP không đúng hoặc đã hết hạn');
}

if ($action === 'reset_password') {
    $sdt  = trim($_POST['so_dien_thoai'] ?? '');
    $pass = $_POST['mat_khau_moi'] ?? '';
    $conf = $_POST['xac_nhan_mat_khau'] ?? '';

    $result = $auth->resetPassword($sdt, $pass, $conf);
    response($result['success'], null, $result['message']);
}

response(false, null, 'Action không hợp lệ');
