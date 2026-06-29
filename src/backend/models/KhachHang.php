<?php
/**
 * Model: KhachHang
 * Truy vấn SQL cho hồ sơ người dùng / khách hàng.
 */
class KhachHang {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /** Lấy thông tin người dùng từ nguoi_dung */
    public function getProfile(int $userId): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, ho_ten, so_dien_thoai, trang_thai, ngay_tao as created_at
             FROM nguoi_dung WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /** Lấy thêm CCCD / địa chỉ từ bảng khach_hang theo SĐT */
    public function getExtraByPhone(string $sdt): ?array {
        $stmt = $this->db->prepare(
            "SELECT so_cccd, dia_chi FROM khach_hang WHERE so_dien_thoai = ? LIMIT 1"
        );
        $stmt->bind_param("s", $sdt);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /** Cập nhật họ tên trong nguoi_dung */
    public function updateHoTen(int $userId, string $hoTen): bool {
        $stmt = $this->db->prepare("UPDATE nguoi_dung SET ho_ten = ? WHERE id = ?");
        $stmt->bind_param("si", $hoTen, $userId);
        return $stmt->execute();
    }

    /** Cập nhật thông tin khách hàng theo SĐT */
    public function updateKhachHang(string $hoTen, string $soCccd, string $diaChi, string $sdt, int $userId): void {
        $stmt = $this->db->prepare("SELECT id FROM khach_hang WHERE so_dien_thoai = ? LIMIT 1");
        $stmt->bind_param("s", $sdt);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $khId = $row['id'];
            $stmtUpd = $this->db->prepare(
                "UPDATE khach_hang SET ho_ten = ?, so_cccd = ?, dia_chi = ?, nguoi_dung_id = ? WHERE id = ?"
            );
            $stmtUpd->bind_param("sssii", $hoTen, $soCccd, $diaChi, $userId, $khId);
            $stmtUpd->execute();
        } else {
            $stmtIns = $this->db->prepare(
                "INSERT INTO khach_hang (nguoi_dung_id, ho_ten, so_dien_thoai, so_cccd, dia_chi) VALUES (?, ?, ?, ?, ?)"
            );
            $stmtIns->bind_param("issss", $userId, $hoTen, $sdt, $soCccd, $diaChi);
            $stmtIns->execute();
        }
    }

    /** Lấy mật khẩu hiện tại */
    public function getPassword(int $userId): ?string {
        $stmt = $this->db->prepare("SELECT mat_khau FROM nguoi_dung WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (string)$row['mat_khau'] : null;
    }

    /** Lưu mật khẩu mới */
    public function setPassword(int $userId, string $newHash): bool {
        $stmt = $this->db->prepare("UPDATE nguoi_dung SET mat_khau = ? WHERE id = ?");
        $stmt->bind_param("si", $newHash, $userId);
        return $stmt->execute();
    }
}
