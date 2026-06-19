<?php
/**
 * Backend Router — điểm vào duy nhất cho tất cả API request
 *
 * URL: /DATN/backend/index.php?action=<tên_action>
 *
 * Các module:
 *   donhang  → api/donhang.php   (track, orders, receptionist_orders, pending_orders, ...)
 *   vanchuyen→ api/vanchuyen.php (shipments, shipment_details, dispatcher_stats, ...)
 *   giaohang → api/giaohang.php  (driver_orders, driver_update_status, driver_delivery_log)
 *   admin    → api/admin.php     (users, vehicles, routes, delivery_persons, pricing, customers)
 *   thongke  → api/thongke.php   (statistics, quote, goods_types)
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/xacthuc_dangnhap.php';
require_once __DIR__ . '/api/helpers.php';

// Bảng ánh xạ: action => module file
const ACTION_MAP = [
    // ── Đơn hàng ──────────────────────────────────────────────────
    'track'                  => 'donhang',
    'orders'                 => 'donhang',
    'receptionist_orders'    => 'donhang',
    'pending_orders'         => 'donhang',
    'order_status'           => 'donhang',
    'add_orders_to_shipment' => 'donhang',

    // ── Vận chuyển ────────────────────────────────────────────────
    'shipments'              => 'vanchuyen',
    'shipment_details'       => 'vanchuyen',
    'orders_by_destination'  => 'vanchuyen',
    'defer_expired_shipments'=> 'vanchuyen',
    'available_drivers'      => 'vanchuyen',
    'available_vehicles'     => 'vanchuyen',
    'my_shipments'           => 'vanchuyen',
    'update_shipment_status' => 'vanchuyen',
    'dispatcher_stats'       => 'vanchuyen',

    // ── Giao hàng ─────────────────────────────────────────────────
    'driver_orders'          => 'giaohang',
    'driver_update_status'   => 'giaohang',
    'driver_delivery_log'    => 'giaohang',

    // ── Admin ─────────────────────────────────────────────────────
    'users'                  => 'admin',
    'vehicles'               => 'admin',
    'routes'                 => 'admin',
    'branches'               => 'admin',
    'delivery_persons'       => 'admin',
    'pricing'                => 'admin',
    'customers'              => 'admin',

    // ── Khách hàng ────────────────────────────────────────────────
    'my_profile'             => 'donhang',
    'update_profile'         => 'donhang',
    'change_password'        => 'donhang',
    'my_orders'              => 'donhang',

    // ── Thống kê / Danh mục ───────────────────────────────────────
    'statistics'             => 'thongke',
    'quote'                  => 'thongke',
    'goods_types'            => 'thongke',
];

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if (!$action) {
    response(false, null, 'Thiếu tham số action');
}

$module = ACTION_MAP[$action] ?? null;

if (!$module) {
    response(false, null, "Action '$action' không tồn tại");
}

$module_file = __DIR__ . "/api/{$module}.php";

if (!file_exists($module_file)) {
    response(false, null, "Module '$module' không tìm thấy");
}

require $module_file;

// Nếu action không khớp case nào trong module
response(false, null, "Action '$action' không được xử lý trong module '$module'");
