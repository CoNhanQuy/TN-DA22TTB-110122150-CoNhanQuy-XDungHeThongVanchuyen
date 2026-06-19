<?php
include_once __DIR__ . '/cauhinh.php';

function isApiRequest() {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $uri    = $_SERVER['REQUEST_URI'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    if (stripos($script, 'index.php') !== false && stripos($uri, 'backend') !== false) return true;
    if (stripos($accept, 'application/json') !== false) return true;
    if (strcasecmp($xrw, 'XMLHttpRequest') === 0) return true;
    return false;
}

function apiAuthFail($httpCode, $message) {
    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['success' => false, 'data' => null, 'message' => $message, 'timestamp' => date('Y-m-d H:i:s')]);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function hasRole($requiredRole) {
    return isLoggedIn() && $_SESSION['role'] === $requiredRole;
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (isApiRequest()) apiAuthFail(401, 'Bạn chưa đăng nhập');
        header('Location: /DATN/frontend/trangchu/index.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        if (isApiRequest()) apiAuthFail(403, 'Bạn không có quyền truy cập');
        header('Location: /DATN/frontend/trangchu/index.php');
        exit();
    }
}

function login($username, $password) {
    global $conn;

    // Schema mới: bảng nguoi_dung, vai_tro_nguoi_dung, vai_tro
    $sql = "SELECT nd.*, vt.ten_vai_tro as vai_tro
            FROM nguoi_dung nd
            LEFT JOIN vai_tro_nguoi_dung vtnd ON vtnd.nguoi_dung_id = nd.id
            LEFT JOIN vai_tro vt ON vt.id = vtnd.vai_tro_id
            WHERE nd.so_dien_thoai = ? AND nd.trang_thai = 1
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user   = $result->fetch_assoc();
        $dbPass = (string)($user['mat_khau'] ?? '');
        $ok     = false;

        if ($dbPass !== '') {
            if (password_verify($password, $dbPass))              $ok = true;
            elseif ($dbPass === md5($password))                   $ok = true;
            elseif (hash_equals($dbPass, (string)$password))     $ok = true;
        }

        if ($ok) {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['so_dien_thoai'] = $user['so_dien_thoai'];
            $_SESSION['ho_ten']        = $user['ho_ten'];
            $_SESSION['role']          = $user['vai_tro'] ?? 'khach_hang';
            return $_SESSION['role'];
        }
    }

    return false;
}

function logout() {
    session_destroy();
    header('Location: /DATN/frontend/trangchu/index.php');
    exit();
}
