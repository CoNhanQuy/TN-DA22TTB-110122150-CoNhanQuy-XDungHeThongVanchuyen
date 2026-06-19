<?php
/**
 * API OTP — Quên mật khẩu / Đổi mật khẩu qua OTP SMS
 *
 * Các action (POST):
 *   action=request_otp   : Tạo OTP và gửi SMS
 *   action=verify_otp    : Xác thực mã OTP
 *   action=reset_password: Đổi mật khẩu sau khi đã xác thực OTP
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/cauhinh.php';
require_once __DIR__ . '/sms_config.php';

function jsonOut(bool $success, ?array $data, string $message): void {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode([
        'success'   => $success,
        'data'      => $data,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, null, 'Phương thức không hợp lệ');
}

$action = trim($_POST['action'] ?? '');

// ====================================================================
// 1. YÊU CẦU OTP — nhập SĐT, hệ thống tạo và gửi SMS
// ====================================================================
if ($action === 'request_otp') {
    $sdt = preg_replace('/\D/', '', trim($_POST['so_dien_thoai'] ?? ''));
    if (!$sdt || strlen($sdt) < 9) {
        jsonOut(false, null, 'Số điện thoại không hợp lệ');
    }

    // Kiểm tra số điện thoại tồn tại trong nguoi_dung
    $stmt = $conn->prepare("SELECT id, ho_ten FROM nguoi_dung WHERE so_dien_thoai = ? AND trang_thai = 1 LIMIT 1");
    $stmt->bind_param("s", $sdt);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        jsonOut(false, null, 'Số điện thoại chưa được đăng ký hoặc tài khoản bị khóa');
    }

    // Rate limit: không cho gửi quá 3 lần trong 15 phút
    $countRes = $conn->query(
        "SELECT COUNT(*) as cnt FROM xac_minh_otp
         WHERE so_dien_thoai = '$sdt'
           AND loai_hanh_dong = 'khoi_phuc_mat_khau'
           AND thoi_gian_tao >= NOW() - INTERVAL 15 MINUTE"
    );
    $cnt = (int)($countRes->fetch_assoc()['cnt'] ?? 0);
    if ($cnt >= 3) {
        jsonOut(false, null, 'Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau 15 phút');
    }

    // Đánh hết hạn các OTP cũ chưa dùng
    $conn->query(
        "UPDATE xac_minh_otp SET trang_thai = 2
         WHERE so_dien_thoai = '$sdt'
           AND loai_hanh_dong = 'khoi_phuc_mat_khau'
           AND trang_thai = 0"
    );

    // Tạo OTP 6 chữ số
    $otp     = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $het_han = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $stmt2 = $conn->prepare(
        "INSERT INTO xac_minh_otp (so_dien_thoai, ma_otp, loai_hanh_dong, thoi_gian_het_han, trang_thai)
         VALUES (?, ?, 'khoi_phuc_mat_khau', ?, 0)"
    );
    $stmt2->bind_param("sss", $sdt, $otp, $het_han);
    if (!$stmt2->execute()) {
        jsonOut(false, null, 'Lỗi hệ thống khi tạo OTP');
    }

    // Gửi SMS
    $hoTen  = $user['ho_ten'];
    $msg    = "Van Tai Xanh: Ma xac thuc cua ban la $otp. Het han sau 5 phut. Khong chia se ma nay cho bat ky ai.";
    $result = sendSMS($sdt, $msg);

    if (!$result['success']) {
        // Vẫn thành công về DB nhưng cảnh báo SMS
        jsonOut(true, ['sdt_masked' => maskPhone($sdt)],
            'OTP đã tạo nhưng gửi SMS thất bại: ' . $result['message'] . '. Liên hệ hỗ trợ nếu cần.');
    }

    jsonOut(true, ['sdt_masked' => maskPhone($sdt)],
        'Mã OTP đã được gửi đến ' . maskPhone($sdt) . '. Có hiệu lực trong 5 phút.');
}

// ====================================================================
// 2. XÁC THỰC OTP
// ====================================================================
if ($action === 'verify_otp') {
    $sdt = preg_replace('/\D/', '', trim($_POST['so_dien_thoai'] ?? ''));
    $otp = trim($_POST['ma_otp'] ?? '');

    if (!$sdt || !$otp) {
        jsonOut(false, null, 'Thiếu thông tin xác thực');
    }

    $stmt = $conn->prepare(
        "SELECT id FROM xac_minh_otp
         WHERE so_dien_thoai = ?
           AND ma_otp = ?
           AND loai_hanh_dong = 'khoi_phuc_mat_khau'
           AND trang_thai = 0
           AND thoi_gian_het_han > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param("ss", $sdt, $otp);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        // Đếm số lần sai
        $fail = $conn->query(
            "SELECT COUNT(*) as cnt FROM xac_minh_otp
             WHERE so_dien_thoai = '$sdt' AND loai_hanh_dong = 'khoi_phuc_mat_khau'
               AND trang_thai = 0 AND thoi_gian_het_han > NOW()"
        )->fetch_assoc()['cnt'] ?? 0;

        jsonOut(false, null, 'Mã OTP không đúng hoặc đã hết hạn');
    }

    // Đánh dấu OTP đã xác minh (trang_thai = 1)
    $otpId = (int)$row['id'];
    $conn->query("UPDATE xac_minh_otp SET trang_thai = 1 WHERE id = $otpId");

    // Tạo token tạm thời lưu session để bước đổi mật khẩu không cần gửi lại SĐT
    $_SESSION['otp_verified_sdt']  = $sdt;
    $_SESSION['otp_verified_time'] = time();

    jsonOut(true, null, 'Xác thực OTP thành công. Vui lòng nhập mật khẩu mới.');
}

// ====================================================================
// 3. ĐỔI MẬT KHẨU SAU KHI XÁC THỰC OTP
// ====================================================================
if ($action === 'reset_password') {
    $sdt  = preg_replace('/\D/', '', trim($_POST['so_dien_thoai'] ?? ''));
    $pass = $_POST['mat_khau_moi'] ?? '';
    $conf = $_POST['xac_nhan_mat_khau'] ?? '';

    // Kiểm tra session xác thực còn hiệu lực (10 phút)
    $verifiedSdt  = $_SESSION['otp_verified_sdt']  ?? '';
    $verifiedTime = (int)($_SESSION['otp_verified_time'] ?? 0);

    if (!$verifiedSdt || $verifiedSdt !== $sdt || (time() - $verifiedTime) > 600) {
        jsonOut(false, null, 'Phiên xác thực đã hết hạn. Vui lòng yêu cầu OTP lại');
    }

    if (strlen($pass) < 6) {
        jsonOut(false, null, 'Mật khẩu phải có ít nhất 6 ký tự');
    }

    if ($pass !== $conf) {
        jsonOut(false, null, 'Xác nhận mật khẩu không khớp');
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE nguoi_dung SET mat_khau = ? WHERE so_dien_thoai = ? AND trang_thai = 1");
    $stmt->bind_param("ss", $hash, $sdt);

    if (!$stmt->execute() || $stmt->affected_rows === 0) {
        jsonOut(false, null, 'Không thể cập nhật mật khẩu. Vui lòng thử lại');
    }

    // Xóa session xác thực
    unset($_SESSION['otp_verified_sdt'], $_SESSION['otp_verified_time']);

    jsonOut(true, null, 'Đổi mật khẩu thành công! Bạn có thể đăng nhập với mật khẩu mới.');
}

jsonOut(false, null, 'Action không hợp lệ');

// ====================================================================
// Helper: che số điện thoại (0909***456)
// ====================================================================
function maskPhone(string $phone): string {
    if (strlen($phone) < 6) return $phone;
    return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
}
