<?php
/**
 * API Entry Point & Router — backend/api/index.php
 *
 * Điểm vào duy nhất cho tất cả API request từ frontend.
 * URL: /DATN/backend/api/index.php?action=<tên_action>
 *
 * Cấu trúc route groups:
 *   auth/login.php, auth/logout.php, auth/verify_otp.php
 *   donhang/index.php   — track, orders, receptionist_orders, ...
 *   admin/index.php     — users, vehicles, routes, branches, pricing, customers
 *   taixe/index.php     — shipments, driver_orders, my_shipments, ...
 *   thongke/index.php   — statistics, quote, goods_types
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');

// Cho phép ngrok tunnel (bỏ qua ngrok browser warning page)
header('ngrok-skip-browser-warning: true');

// CORS — cho phép ngrok domain và localhost gọi API
$allowedOrigins = [
    'https://uncertain-hubcap-bootie.ngrok-free.dev',
    'http://localhost:8000',
    'http://127.0.0.1:8000',
    'http://localhost',
    'http://127.0.0.1',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (
    in_array($origin, $allowedOrigins, true)
    || str_starts_with($origin, 'http://localhost')
    || str_starts_with($origin, 'http://127.0.0.1')
) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/cauhinh.php';
require_once __DIR__ . '/../core/helpers.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if (!$action) {
    response(false, null, 'Thiếu tham số action');
}

const ROUTE_MAP = [
    // ── Auth ─────────────────────────────────────────────────────
    'login'          => 'auth/login.php',
    'logout'         => 'auth/logout.php',
    'request_otp'    => 'auth/verify_otp.php',
    'verify_otp'     => 'auth/verify_otp.php',
    'reset_password' => 'auth/verify_otp.php',

    // ── Đơn hàng ─────────────────────────────────────────────────
    'track'                  => 'donhang/index.php',
    'orders'                 => 'donhang/index.php',
    'receptionist_orders'    => 'donhang/index.php',
    'pending_orders'         => 'donhang/index.php',
    'order_status'           => 'donhang/index.php',
    'add_orders_to_shipment' => 'donhang/index.php',
    'my_profile'             => 'donhang/index.php',
    'update_profile'         => 'donhang/index.php',
    'change_password'        => 'donhang/index.php',
    'my_orders'              => 'donhang/index.php',

    // ── Admin ────────────────────────────────────────────────────
    'users'            => 'admin/index.php',
    'vehicles'         => 'admin/index.php',
    'routes'           => 'admin/index.php',
    'branches'         => 'admin/index.php',
    'delivery_persons' => 'admin/index.php',
    'pricing'          => 'admin/index.php',
    'customers'        => 'admin/index.php',

    // ── Tài xế / Điều phối / Shipper ────────────────────────────
    'shipments'               => 'taixe/index.php',
    'shipment_details'        => 'taixe/index.php',
    'orders_by_destination'   => 'taixe/index.php',
    'defer_expired_shipments' => 'taixe/index.php',
    'available_drivers'       => 'taixe/index.php',
    'available_vehicles'      => 'taixe/index.php',
    'my_shipments'            => 'taixe/index.php',
    'update_shipment_status'  => 'taixe/index.php',
    'dispatcher_stats'        => 'taixe/index.php',
    'driver_orders'           => 'taixe/index.php',
    'driver_update_status'    => 'taixe/index.php',
    'driver_delivery_log'     => 'taixe/index.php',
    'available_shippers'      => 'taixe/index.php',
    'orders_for_shipper'      => 'taixe/index.php',
    'assign_to_shipper'       => 'taixe/index.php',
    'driver_upload_photo'     => 'taixe/index.php',
    'driver_report_incident'  => 'taixe/index.php',

    // ── Thống kê ─────────────────────────────────────────────────
    'statistics'  => 'thongke/index.php',
    'quote'       => 'thongke/index.php',
    'goods_types' => 'thongke/index.php',

    // ── Chi tiết đơn hàng (xuất phiếu) ──────────────────────────
    'order_detail' => 'donhang/index.php',
];

$routeFile = ROUTE_MAP[$action] ?? null;

if (!$routeFile) {
    response(false, null, "Action '$action' không tồn tại");
}

$fullPath = __DIR__ . '/' . $routeFile;

if (!file_exists($fullPath)) {
    response(false, null, "Route file '$routeFile' không tìm thấy");
}

require $fullPath;

// Nếu route file không exit (không match case nào)
response(false, null, "Action '$action' không được xử lý trong route '$routeFile'");
