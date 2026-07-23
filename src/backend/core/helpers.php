<?php
/**
 * Hàm bổ trợ dùng chung — core/helpers.php
 * Không để lộ ra ngoài web (không có route trực tiếp).
 */

// ── Xác thực / Session ──────────────────────────────────────────────────────

/**
 * Kiểm tra request có phải là API call không (JSON / XHR / backend URL).
 */
function isApiRequest(): bool {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $uri    = $_SERVER['REQUEST_URI'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    if (stripos($script, 'index.php') !== false && stripos($uri, 'backend') !== false) return true;
    if (stripos($accept, 'application/json') !== false) return true;
    if (strcasecmp($xrw, 'XMLHttpRequest') === 0) return true;
    return false;
}

/**
 * Trả về lỗi xác thực dạng JSON và kết thúc request.
 */
function apiAuthFail(int $httpCode, string $message): void {
    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success'   => false,
        'data'      => null,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
    exit();
}

/**
 * Kiểm tra người dùng đã đăng nhập chưa.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Kiểm tra người dùng có vai trò cụ thể không.
 */
function hasRole(string $requiredRole): bool {
    return isLoggedIn() && $_SESSION['role'] === $requiredRole;
}

/**
 * Yêu cầu đăng nhập — trả JSON 401 nếu là API, redirect nếu là web.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        if (isApiRequest()) apiAuthFail(401, 'Bạn chưa đăng nhập');
        header('Location: ' . APP_BASE_URL . '/index.php');
        exit();
    }
}

/**
 * Yêu cầu vai trò cụ thể — trả JSON 403 nếu là API, redirect nếu là web.
 */
function requireRole(string $role): void {
    requireLogin();
    if (!hasRole($role)) {
        if (isApiRequest()) apiAuthFail(403, 'Bạn không có quyền truy cập');
        header('Location: ' . APP_BASE_URL . '/index.php');
        exit();
    }
}

/**
 * Trả về JSON chuẩn và kết thúc request.
 */
function response($success, $data = null, $message = '') {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode([
        'success'   => $success,
        'data'      => $data,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Định dạng tiền tệ VND.
 * VD: formatCurrency(35000) → "35.000 ₫"
 */
function formatCurrency(float $amount): string {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

/**
 * Tạo mã chuỗi ngẫu nhiên an toàn.
 * VD: generateCode('DH', 8) → "DH4F9A2C1E"
 */
function generateCode(string $prefix = '', int $length = 8): string {
    $bytes = random_bytes((int) ceil($length / 2));
    return strtoupper($prefix . substr(bin2hex($bytes), 0, $length));
}

/**
 * Che một phần số điện thoại.
 * VD: maskPhone('0909123456') → "090****456"
 */
function maskPhone(string $phone): string {
    if (strlen($phone) < 6) return $phone;
    return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
}

/**
 * Đăng nhập — dùng cho trang web (procedural).
 * Trả về vai_tro nếu thành công, false nếu thất bại.
 */
function login(string $username, string $password) {
    global $conn;

    $stmt = $conn->prepare(
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

/**
 * Đăng xuất — hủy session và redirect về trang chủ.
 */
function logout(): void {
    session_destroy();
    header('Location: ' . APP_BASE_URL . '/index.php');
    exit();
}

/**
 * Làm sạch chuỗi đầu vào (trim + strip_tags).
 */
function clean(string $value): string {
    return trim(strip_tags($value));
}
