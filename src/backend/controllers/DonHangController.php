<?php
require_once __DIR__ . '/../models/DonHang.php';
require_once __DIR__ . '/../models/KhachHang.php';

/**
 * DonHangController
 * Business logic cho đơn hàng — gọi DonHang model, trả kết quả cho route.
 */
class DonHangController {
    private DonHang $model;
    private KhachHang $khachHangModel;

    public function __construct(mysqli $db) {
        $this->model          = new DonHang($db);
        $this->khachHangModel = new KhachHang($db);
    }

    /** Tra cứu đơn hàng (public) */
    public function track(string $code): array {
        if (!$code) return ['success' => false, 'message' => 'Vui lòng nhập mã đơn hàng'];

        $row = $this->model->findByCode($code);
        if (!$row) {
            return ['success' => false, 'message' => 'Không tìm thấy đơn hàng với mã: ' . htmlspecialchars($code)];
        }

        $hangHoa = $this->model->getHangHoa((int)$row['id']);
        $timeline = $this->model->getTimeline((int)$row['id']);

        if (empty($timeline)) {
            $timeline = [['status' => $row['trang_thai_don_hang'], 'time' => $row['ngay_tao'] ?? '', 'note' => '', 'actor' => '']];
        }

        $progressMap = [
            'cho_tiep_nhan'   => 10, 'da_nhap_kho' => 25, 'dang_van_chuyen' => 50,
            'da_den_kho_dich' => 70, 'dang_giao_hang' => 85, 'hoan_tat' => 100, 'da_huy' => 0,
        ];

        $order = [
            'ma_don'             => $row['ma_don_hang'],
            'tong_khoi_luong_kg' => $row['tong_khoi_luong_kg'],
            'phi_van_chuyen'     => $row['phi_van_chuyen'],
            'tien_tra_truoc'     => $row['tien_tra_truoc'] ?? 0,
            'trang_thai'         => $row['trang_thai_don_hang'],
            'ngay_tao'           => $row['ngay_tao'] ?? '',
            'chi_nhanh_gui'      => $row['ten_chi_nhanh_gui'] ?? '',
            'chi_nhanh_nhan'     => $row['ten_chi_nhanh_nhan'] ?? '',
            'progress'           => $progressMap[$row['trang_thai_don_hang']] ?? 0,
            'hang_hoa'           => $hangHoa,
            'nguoi_gui'  => ['ho_ten' => $row['ng_gui_ten'] ?? '', 'so_dien_thoai' => $row['ng_gui_sdt'] ?? '', 'so_cccd' => $row['ng_gui_cccd'] ?? ''],
            'nguoi_nhan' => ['ho_ten' => $row['ng_nhan_ten'] ?? '', 'so_dien_thoai' => $row['ng_nhan_sdt'] ?? '', 'dia_chi' => $row['ng_nhan_dc'] ?? ''],
        ];

        return ['success' => true, 'data' => ['order' => $order, 'timeline' => $timeline]];
    }

    /** Danh sách đơn chờ điều phối */
    public function getPendingOrders(): array {
        return ['success' => true, 'data' => $this->model->getPendingOrders()];
    }

    /** Tất cả đơn hàng */
    public function getOrders(): array {
        return ['success' => true, 'data' => ['orders' => $this->model->getAll()]];
    }

    /** Đơn hàng NV tiếp nhận */
    public function getReceptionistOrders(): array {
        return ['success' => true, 'data' => $this->model->getReceptionistOrders()];
    }

    /** Chi tiết đơn hàng theo ID (dùng xuất phiếu in) */
    public function getOrderDetail(int $id): array {
        if (!$id) return ['success' => false, 'message' => 'Thiếu id đơn hàng'];
        $detail = $this->model->getOrderDetail($id);
        if (!$detail) return ['success' => false, 'message' => 'Không tìm thấy đơn hàng'];
        return ['success' => true, 'data' => $detail];
    }

    /** Cập nhật trạng thái */
    public function updateStatus(int $id, string $trangThai, string $actor, string $ghiChu = ''): array {
        $ok = $this->model->updateStatus($id, $trangThai, $actor, $ghiChu);
        return $ok ? ['success' => true, 'message' => 'Cập nhật trạng thái thành công'] : ['success' => false, 'message' => 'Lỗi cập nhật'];
    }

    /** Hủy đơn */
    public function cancelOrder(int $id, string $actor, string $reason): array {
        $this->model->updateStatus($id, 'da_huy', $actor, $reason);
        return ['success' => true, 'message' => 'Hủy đơn hàng thành công'];
    }

    /** Tạo đơn mới */
    public function createOrder(array $postData, string $actor): array {
        $postData['actor'] = $actor;
        try {
            $result = $this->model->create($postData);
            return ['success' => true, 'data' => $result, 'message' => 'Tạo đơn hàng thành công'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi tạo đơn: ' . $e->getMessage()];
        }
    }

    // ── Khách hàng ────────────────────────────────────────────────

    public function getProfile(int $userId, string $sdt): array {
        $row = $this->khachHangModel->getProfile($userId);
        if (!$row) return ['success' => false, 'message' => 'Không tìm thấy thông tin người dùng'];
        $extra = $this->khachHangModel->getExtraByPhone($sdt);
        if ($extra) {
            $row['so_cccd'] = $extra['so_cccd'];
            $row['dia_chi'] = $extra['dia_chi'];
        }
        return ['success' => true, 'data' => $row];
    }

    public function getMyOrders(string $sdt, int $limit, int $offset): array {
        $orders = $this->model->getByPhone($sdt, $limit, $offset);
        $total  = $this->model->countByPhone($sdt);
        return ['success' => true, 'data' => ['orders' => $orders, 'total' => $total]];
    }

    public function updateProfile(int $userId, string $sdt, string $hoTen, string $cccd, string $diaChi): array {
        if ($hoTen === '') return ['success' => false, 'message' => 'Họ tên không được để trống'];
        if (!$this->khachHangModel->updateHoTen($userId, $hoTen)) {
            return ['success' => false, 'message' => 'Lỗi cập nhật'];
        }
        if ($sdt) $this->khachHangModel->updateKhachHang($hoTen, $cccd, $diaChi, $sdt, $userId);
        $_SESSION['ho_ten'] = $hoTen;
        return ['success' => true, 'data' => ['ho_ten' => $hoTen], 'message' => 'Cập nhật hồ sơ thành công'];
    }

    public function changePassword(int $userId, string $matKhauCu, string $matKhauMoi, string $xacNhan): array {
        if (!$matKhauCu || !$matKhauMoi || !$xacNhan) {
            return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin'];
        }
        if (strlen($matKhauMoi) < 6) {
            return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự'];
        }
        if ($matKhauMoi !== $xacNhan) {
            return ['success' => false, 'message' => 'Xác nhận mật khẩu không khớp'];
        }
        $dbPass = $this->khachHangModel->getPassword($userId);
        if ($dbPass === null) return ['success' => false, 'message' => 'Không tìm thấy tài khoản'];

        $valid = password_verify($matKhauCu, $dbPass) || $dbPass === md5($matKhauCu) || hash_equals($dbPass, $matKhauCu);
        if (!$valid) return ['success' => false, 'message' => 'Mật khẩu hiện tại không đúng'];

        $newHash = password_hash($matKhauMoi, PASSWORD_DEFAULT);
        return $this->khachHangModel->setPassword($userId, $newHash)
            ? ['success' => true, 'message' => 'Đổi mật khẩu thành công']
            : ['success' => false, 'message' => 'Lỗi lưu mật khẩu'];
    }
}
