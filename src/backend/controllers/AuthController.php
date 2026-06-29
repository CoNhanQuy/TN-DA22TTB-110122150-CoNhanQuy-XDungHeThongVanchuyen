<?php
/**
 * AuthController
 * Xử lý đăng nhập, đăng xuất, xác thực OTP đặt lại mật khẩu.
 */
class AuthController {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /** Đăng nhập — trả về vai_tro nếu thành công, false nếu thất bại */
    public function login(string $username, string $password): string|false {
        $stmt = $this->db->prepare(
            "SELECT nd.*, vt.ten_vai_tro as vai_tro
             FROM nguoi_dung nd
             LEFT JOIN vai_tro_nguoi_dung vtnd ON vtnd.nguoi_dung_id = nd.id
             LEFT JOIN vai_tro vt ON vt.id = vtnd.vai_tro_id
             WHERE nd.so_dien_thoai = ? AND nd.trang_thai = 1
             LIMIT 1"
        );
        if (!$stmt) return false;
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) return false;

        $dbPass = (string)($user['mat_khau'] ?? '');
        $ok     = false;
        if ($dbPass !== '') {
            if (password_verify($password, $dbPass))         $ok = true;
            elseif ($dbPass === md5($password))              $ok = true;
            elseif (hash_equals($dbPass, $password))         $ok = true;
        }

        if ($ok) {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['so_dien_thoai'] = $user['so_dien_thoai'];
            $_SESSION['ho_ten']        = $user['ho_ten'];
            $_SESSION['role']          = $user['vai_tro'] ?? 'khach_hang';
            return $_SESSION['role'];
        }
        return false;
    }

    /** Đăng xuất */
    public function logout(): void {
        session_destroy();
    }

    // ── OTP ────────────────────────────────────────────────────────

    /** Yêu cầu OTP — tạo mã, kiểm tra rate limit, ghi DB */
    public function requestOtp(string $sdt): array {
        $sdt = preg_replace('/\D/', '', $sdt);
        if (!$sdt || strlen($sdt) < 9) {
            return ['success' => false, 'message' => 'Số điện thoại không hợp lệ'];
        }

        $stmt = $this->db->prepare(
            "SELECT id, ho_ten FROM nguoi_dung WHERE so_dien_thoai = ? AND trang_thai = 1 LIMIT 1"
        );
        $stmt->bind_param("s", $sdt);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) {
            return ['success' => false, 'message' => 'Số điện thoại chưa được đăng ký hoặc tài khoản bị khóa'];
        }

        $countRes = $this->db->query(
            "SELECT COUNT(*) as cnt FROM xac_minh_otp
             WHERE so_dien_thoai = '$sdt'
               AND loai_hanh_dong = 'khoi_phuc_mat_khau'
               AND thoi_gian_tao >= NOW() - INTERVAL 15 MINUTE"
        );
        if ((int)$countRes->fetch_assoc()['cnt'] >= 3) {
            return ['success' => false, 'message' => 'Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau 15 phút'];
        }

        $this->db->query(
            "UPDATE xac_minh_otp SET trang_thai = 2
             WHERE so_dien_thoai = '$sdt' AND loai_hanh_dong = 'khoi_phuc_mat_khau' AND trang_thai = 0"
        );

        $otp    = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hetHan = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $s2 = $this->db->prepare(
            "INSERT INTO xac_minh_otp (so_dien_thoai, ma_otp, loai_hanh_dong, thoi_gian_het_han, trang_thai)
             VALUES (?, ?, 'khoi_phuc_mat_khau', ?, 0)"
        );
        $s2->bind_param("sss", $sdt, $otp, $hetHan);
        if (!$s2->execute()) {
            return ['success' => false, 'message' => 'Lỗi hệ thống khi tạo OTP'];
        }

        return ['success' => true, 'otp' => $otp, 'sdt' => $sdt];
    }

    /** Xác thực mã OTP */
    public function verifyOtp(string $sdt, string $otp): bool {
        $sdt = preg_replace('/\D/', '', $sdt);
        $stmt = $this->db->prepare(
            "SELECT id FROM xac_minh_otp
             WHERE so_dien_thoai = ? AND ma_otp = ? AND loai_hanh_dong = 'khoi_phuc_mat_khau'
               AND trang_thai = 0 AND thoi_gian_het_han > NOW()
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param("ss", $sdt, $otp);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) return false;

        $otpId = (int)$row['id'];
        $this->db->query("UPDATE xac_minh_otp SET trang_thai = 1 WHERE id = $otpId");

        $_SESSION['otp_verified_sdt']  = $sdt;
        $_SESSION['otp_verified_time'] = time();
        return true;
    }

    /** Đặt lại mật khẩu sau xác thực OTP */
    public function resetPassword(string $sdt, string $pass, string $confirm): array {
        $sdt  = preg_replace('/\D/', '', $sdt);
        $verSdt  = $_SESSION['otp_verified_sdt']  ?? '';
        $verTime = (int)($_SESSION['otp_verified_time'] ?? 0);

        if (!$verSdt || $verSdt !== $sdt || (time() - $verTime) > 600) {
            return ['success' => false, 'message' => 'Phiên xác thực đã hết hạn. Vui lòng yêu cầu OTP lại'];
        }
        if (strlen($pass) < 6) {
            return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự'];
        }
        if ($pass !== $confirm) {
            return ['success' => false, 'message' => 'Xác nhận mật khẩu không khớp'];
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "UPDATE nguoi_dung SET mat_khau = ? WHERE so_dien_thoai = ? AND trang_thai = 1"
        );
        $stmt->bind_param("ss", $hash, $sdt);
        if (!$stmt->execute() || $stmt->affected_rows === 0) {
            return ['success' => false, 'message' => 'Không thể cập nhật mật khẩu. Vui lòng thử lại'];
        }

        unset($_SESSION['otp_verified_sdt'], $_SESSION['otp_verified_time']);
        return ['success' => true, 'message' => 'Đổi mật khẩu thành công! Bạn có thể đăng nhập với mật khẩu mới.'];
    }
}
