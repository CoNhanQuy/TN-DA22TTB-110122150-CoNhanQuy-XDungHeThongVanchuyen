<?php
/**
 * Route group: /api/gps/
 * GPS Tracking — lấy và cập nhật tọa độ tài xế / shipper
 */
require_once __DIR__ . '/../../controllers/giaohangcontroller.php';

$ctrl   = new GiaoHangController($conn);
$method = $_SERVER['REQUEST_METHOD'];
$userId = (int)($_SESSION['user_id'] ?? 0);
$role   = $_SESSION['role'] ?? '';

switch ($action) {

    // ── GET: Lấy tọa độ GPS của tất cả tài xế + shipper ──────────
    case 'gps_locations':
        // Admin và điều phối viên được xem
        if (!in_array($role, ['admin', 'nhan_vien_dieu_phoi'])) {
            response(false, null, 'Không có quyền truy cập');
        }
        $result = $ctrl->getActiveLocations();
        response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        break;

    // ── GET: Khách hàng tra cứu GPS theo mã đơn hàng ─────────────
    // Không yêu cầu quyền đặc biệt — bất kỳ ai có mã đơn đều tra cứu được
    case 'track_location':
        $maDon = trim($_GET['ma_don'] ?? '');
        if (!$maDon) {
            response(false, null, 'Thiếu mã đơn hàng');
        }
        $result = $ctrl->getLocationForOrder($maDon);
        response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        break;

    // ── POST: Tài xế / Shipper đẩy vị trí GPS lên ────────────────
    case 'gps_update':
        if ($method !== 'POST') response(false, null, 'Chỉ chấp nhận POST');
        if (!$userId) response(false, null, 'Chưa đăng nhập');
        if (!in_array($role, ['tai_xe', 'shipper'])) {
            response(false, null, 'Chỉ tài xế hoặc shipper mới có thể cập nhật vị trí');
        }

        $viDo   = (float)($_POST['vi_do']   ?? 0);
        $kinhDo = (float)($_POST['kinh_do'] ?? 0);

        if ($viDo == 0.0 || $kinhDo == 0.0) {
            response(false, null, 'Tọa độ không hợp lệ');
        }

        $result = $ctrl->upsertLocation($userId, $role, $viDo, $kinhDo);
        response($result['success'], null, $result['message']);
        break;
}
