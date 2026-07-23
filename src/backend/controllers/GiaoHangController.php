<?php
require_once __DIR__ . '/../models/taixe.php';

/**
 * GiaoHangController
 * Logic cho điều phối viên + tài xế (đợt vận chuyển) + shipper (giao hàng tận nơi).
 */
class GiaoHangController {
    private TaiXe $model;

    public function __construct(mysqli $db) {
        $this->model = new TaiXe($db);
    }

    // ── Điều phối viên ─────────────────────────────────────────────

    public function getShipments(): array {
        return ['success' => true, 'data' => $this->model->getShipments()];
    }

    public function getShipmentDetails(int $id): array {
        $shipment = $this->model->getShipmentById($id);
        if (!$shipment) return ['success' => false, 'message' => 'Không tìm thấy đợt vận chuyển'];
        $orders = $this->model->getShipmentOrders($id);
        return ['success' => true, 'data' => ['shipment' => $shipment, 'orders' => $orders]];
    }

    public function createShipment(int $tuyenId, int $taiXeId, int $xeId, string $ngayGio, array $donHangIds): array {
        if (!$tuyenId || !$taiXeId || !$xeId) {
            return ['success' => false, 'message' => 'Thiếu thông tin bắt buộc (tuyến, tài xế, xe)'];
        }
        try {
            $result = $this->model->createShipment($tuyenId, $taiXeId, $xeId, $ngayGio, $donHangIds);
            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi tạo đợt: ' . $e->getMessage()];
        }
    }

    public function getOrdersByDestination(int $tuyenId): array {
        if (!$tuyenId) return ['success' => false, 'message' => 'Thiếu tuyen_id'];
        $data = $this->model->getOrdersByDestination($tuyenId);
        return empty($data)
            ? ['success' => false, 'message' => 'Không tìm thấy tuyến đường']
            : ['success' => true, 'data' => $data];
    }

    public function getAvailableDrivers(): array {
        $drivers = $this->model->getAvailableDrivers();
        return ['success' => true, 'data' => ['count' => count($drivers), 'drivers' => $drivers]];
    }

    public function getAvailableVehicles(): array {
        $vehicles = $this->model->getAvailableVehicles();
        return ['success' => true, 'data' => ['count' => count($vehicles), 'vehicles' => $vehicles]];
    }

    public function getDispatcherStats(): array {
        return ['success' => true, 'data' => $this->model->getDispatcherStats()];
    }

    public function deferExpiredShipment(int $dotId): array {
        if (!$dotId) return ['success' => false, 'message' => 'Thiếu dot_id'];
        try {
            $result = $this->model->deferExpiredShipment($dotId);
            $count  = $result['deferred'];
            return ['success' => true, 'data' => $result, 'message' => $count > 0 ? "$count đơn đã được dời về hàng chờ" : 'Không có đơn nào cần dời'];
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Tài xế ────────────────────────────────────────────────────

    public function getMyShipments(int $userId): array {
        return ['success' => true, 'data' => $this->model->getMyShipments($userId)];
    }

    public function updateShipmentStatus(int $userId, int $dotId, string $trangThai): array {
        // Debug: kiểm tra ownership
        $debugInfo = $this->model->debugDriverOwnership($userId, $dotId);
        if (!$debugInfo['owns']) {
            return [
                'success' => false,
                'message' => 'Không có quyền cập nhật đợt vận chuyển này',
                'data'    => $debugInfo
            ];
        }
        $ok = $this->model->updateShipmentStatus($dotId, $trangThai);
        return $ok
            ? ['success' => true, 'message' => 'Cập nhật đợt vận chuyển thành công']
            : ['success' => false, 'message' => 'Lỗi cập nhật'];
    }

    // ── Shipper ───────────────────────────────────────────────────

    public function getDriverOrders(int $userId): array {
        return ['success' => true, 'data' => $this->model->getShipperOrders($userId)];
    }

    public function updateDriverStatus(int $userId, int $dhId, string $trangThai, string $nguoiNhan, string $ghiChu, string $hoTen): array {
        if (!$dhId || !$trangThai) return ['success' => false, 'message' => 'Thiếu thông tin bắt buộc'];
        $ok = $this->model->updateShipperStatus($userId, $dhId, $trangThai, $nguoiNhan, $ghiChu, $hoTen);
        return $ok
            ? ['success' => true, 'message' => 'Cập nhật trạng thái thành công']
            : ['success' => false, 'message' => 'Đơn hàng không tồn tại'];
    }

    public function getDriverLog(string $hoTen): array {
        return ['success' => true, 'data' => $this->model->getShipperLog($hoTen)];
    }

    // ── Phân công shipper ─────────────────────────────────────────

    public function getAvailableShippers(): array {
        $shippers = $this->model->getAvailableShippers();
        return ['success' => true, 'data' => ['count' => count($shippers), 'shippers' => $shippers]];
    }

    public function getOrdersForShipperAssignment(): array {
        return ['success' => true, 'data' => $this->model->getOrdersForShipperAssignment()];
    }

    public function assignOrdersToShipper(int $nghId, array $donHangIds): array {
        if (!$nghId || empty($donHangIds)) {
            return ['success' => false, 'message' => 'Thiếu thông tin bắt buộc (shipper, đơn hàng)'];
        }
        $result = $this->model->assignOrdersToShipper($nghId, $donHangIds);
        $msg = "Phân công thành công {$result['assigned']} đơn";
        if ($result['skipped'] > 0) $msg .= " ({$result['skipped']} đơn đã được phân công trước)";
        return ['success' => true, 'data' => $result, 'message' => $msg];
    }

    public function saveDeliveryPhoto(int $dhId, int $userId, string $photoPath): array {
        if (!$dhId || !$photoPath) return ['success' => false, 'message' => 'Thiếu thông tin'];
        $ok = $this->model->saveDeliveryPhoto($dhId, $userId, $photoPath);
        return $ok
            ? ['success' => true, 'message' => 'Lưu ảnh thành công']
            : ['success' => false, 'message' => 'Không thể lưu ảnh'];
    }

    public function reportIncident(int $userId, string $maDon, string $maDot, string $loai, string $moTa, string $viTri, string $mucDo): array {
        if (empty($loai) || empty($moTa)) {
            return ['success' => false, 'message' => 'Vui lòng cung cấp loại sự cố và mô tả chi tiết'];
        }
        $ok = $this->model->reportIncident($userId, $maDon, $maDot, $loai, $moTa, $viTri, $mucDo);
        return $ok
            ? ['success' => true, 'message' => 'Báo cáo sự cố đã được gửi thành công']
            : ['success' => false, 'message' => 'Không thể gửi báo cáo sự cố. Đã xảy ra lỗi hệ thống.'];
    }

    // ── GPS Tracking ──────────────────────────────────────────────

    /** Lấy vị trí GPS của tất cả tài xế và shipper đang hoạt động */
    public function getActiveLocations(): array {
        return ['success' => true, 'data' => $this->model->getActiveLocations()];
    }

    /** Khách hàng tra cứu vị trí GPS của shipper/tài xế theo mã đơn hàng */
    public function getLocationForOrder(string $maDon): array {
        $location = $this->model->getLocationForOrder($maDon);
        if ($location === null) {
            return ['success' => false, 'message' => 'Chưa có dữ liệu GPS cho đơn hàng này'];
        }
        return ['success' => true, 'data' => $location];
    }

    /** Tài xế / Shipper đẩy vị trí GPS lên server */
    public function upsertLocation(int $userId, string $role, float $viDo, float $kinhDo): array {
        if ($viDo == 0.0 || $kinhDo == 0.0) {
            return ['success' => false, 'message' => 'Tọa độ không hợp lệ'];
        }

        if ($role === 'tai_xe') {
            $dotId = $this->model->getActiveShipmentIdForDriver($userId);
            if (!$dotId) {
                return ['success' => false, 'message' => 'Không có chuyến xe đang di chuyển'];
            }
            $ok = $this->model->upsertLocation('trung_chuyen_kho', $dotId, $viDo, $kinhDo);
        } else {
            // shipper
            $nghId = $this->model->getShipperNghId($userId);
            if (!$nghId) {
                return ['success' => false, 'message' => 'Không tìm thấy thông tin người giao hàng'];
            }
            $ok = $this->model->upsertLocation('shipper_giao_khach', $nghId, $viDo, $kinhDo);
        }

        return $ok
            ? ['success' => true, 'message' => 'Vị trí đã được cập nhật']
            : ['success' => false, 'message' => 'Lỗi lưu vị trí'];
    }
}
