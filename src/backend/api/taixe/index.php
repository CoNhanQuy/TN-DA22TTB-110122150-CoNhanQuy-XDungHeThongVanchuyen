<?php
/**
 * Route group: /api/taixe/
 * Điều phối, tài xế, shipper — gọi GiaoHangController
 */
require_once __DIR__ . '/../../controllers/GiaoHangController.php';

$ctrl   = new GiaoHangController($conn);
$method = $_SERVER['REQUEST_METHOD'];
$userId = (int)($_SESSION['user_id'] ?? 0);
$hoTen  = $_SESSION['ho_ten'] ?? '';

switch ($action) {

    // ── Điều phối viên ─────────────────────────────────────────────
    case 'shipments':
        if ($method === 'GET') {
            $result = $ctrl->getShipments();
            response(true, $result['data']);
        } elseif ($method === 'POST') {
            $ids    = isset($_POST['don_hang_ids']) ? (array)$_POST['don_hang_ids'] : [];
            $ngay   = $_POST['ngay_gio_bat_dau'] ?? date('Y-m-d H:i:s');
            $result = $ctrl->createShipment(
                (int)($_POST['tuyen_id'] ?? 0),
                (int)($_POST['tai_xe_id'] ?? 0),
                (int)($_POST['xe_id'] ?? 0),
                $ngay, $ids
            );
            response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        }
        break;

    case 'shipment_details':
        $dotId  = (int)($_GET['id'] ?? 0);
        if (!$dotId) response(false, null, 'Thiếu id đợt vận chuyển');
        $result = $ctrl->getShipmentDetails($dotId);
        response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        break;

    case 'orders_by_destination':
        $result = $ctrl->getOrdersByDestination((int)($_GET['tuyen_id'] ?? 0));
        response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        break;

    case 'available_drivers':
        $result = $ctrl->getAvailableDrivers();
        response(true, $result['data']);
        break;

    case 'available_vehicles':
        $result = $ctrl->getAvailableVehicles();
        response(true, $result['data']);
        break;

    case 'dispatcher_stats':
        $result = $ctrl->getDispatcherStats();
        response(true, $result['data']);
        break;

    case 'defer_expired_shipments':
        $result = $ctrl->deferExpiredShipment((int)($_POST['dot_id'] ?? 0));
        response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        break;

    // ── Tài xế ────────────────────────────────────────────────────
    case 'my_shipments':
        $result = $ctrl->getMyShipments($userId);
        response(true, $result['data']);
        break;

    case 'update_shipment_status':
        $result = $ctrl->updateShipmentStatus($userId, (int)($_POST['dot_id'] ?? 0), $_POST['trang_thai'] ?? '');
        response($result['success'], null, $result['message']);
        break;

    // ── Shipper ───────────────────────────────────────────────────
    case 'driver_orders':
        $result = $ctrl->getDriverOrders($userId);
        response(true, $result['data']);
        break;

    case 'driver_update_status':
        $result = $ctrl->updateDriverStatus(
            $userId,
            (int)($_POST['don_hang_id'] ?? 0),
            $_POST['trang_thai'] ?? '',
            $_POST['nguoi_nhan_thuc_te'] ?? '',
            $_POST['ghi_chu'] ?? '',
            $hoTen
        );
        response($result['success'], null, $result['message']);
        break;

    case 'driver_delivery_log':
        $result = $ctrl->getDriverLog($hoTen);
        response(true, $result['data']);
        break;

    // ── Phân công shipper ─────────────────────────────────────────

    case 'available_shippers':
        $result = $ctrl->getAvailableShippers();
        response(true, $result['data']);
        break;

    case 'orders_for_shipper':
        $result = $ctrl->getOrdersForShipperAssignment();
        response(true, $result['data']);
        break;

    case 'assign_to_shipper':
        if ($method === 'POST') {
            $nghId      = (int)($_POST['ngh_id'] ?? 0);
            $donHangIds = isset($_POST['don_hang_ids']) ? (array)$_POST['don_hang_ids'] : [];
            $result     = $ctrl->assignOrdersToShipper($nghId, $donHangIds);
            response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        }
        break;

    case 'driver_upload_photo':
        if ($method === 'POST') {
            $dhId = (int)($_POST['don_hang_id'] ?? 0);
            if (!$dhId) response(false, null, 'Thiếu don_hang_id');

            $uploadDir = __DIR__ . '/../../../uploads/delivery/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (empty($_FILES['photo']['tmp_name'])) {
                response(false, null, 'Không có file ảnh');
            }

            $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed)) response(false, null, 'Định dạng ảnh không hợp lệ');

            $filename = 'giao_hang_' . $dhId . '_' . time() . '.' . $ext;
            $dest     = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                response(false, null, 'Không thể lưu file');
            }

            $photoPath = '/DATN/uploads/delivery/' . $filename;
            $result    = $ctrl->saveDeliveryPhoto($dhId, $userId, $photoPath);
            response($result['success'], ['photo_url' => $photoPath], $result['message']);
        }
        break;

    case 'driver_report_incident':
        if ($method === 'POST') {
            $maDon    = $_POST['ma_don'] ?? '';
            $maDot    = $_POST['ma_dot_van_chuyen'] ?? '';
            $loai     = $_POST['loai_su_co'] ?? '';
            $moTa     = $_POST['mo_ta'] ?? '';
            $viTri    = $_POST['vi_tri'] ?? '';
            $mucDo    = $_POST['muc_do'] ?? '';

            $result   = $ctrl->reportIncident($userId, $maDon, $maDot, $loai, $moTa, $viTri, $mucDo);
            response($result['success'], null, $result['message']);
        }
        break;
}
