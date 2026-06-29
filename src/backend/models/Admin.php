<?php
/**
 * Model: Admin
 * Xử lý truy vấn SQL cho quản lý hệ thống (users, xe, tuyến, chi nhánh, người giao, giá).
 */
class Admin {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    // ── Người dùng ────────────────────────────────────────────────

    public function getUsers(): array {
        $res = $this->db->query(
            "SELECT nd.id, nd.ho_ten, nd.so_dien_thoai, nd.trang_thai,
                    nd.ngay_tao as created_at,
                    COALESCE(
                        (SELECT vt2.ten_vai_tro
                         FROM vai_tro_nguoi_dung vtnd2
                         JOIN vai_tro vt2 ON vt2.id = vtnd2.vai_tro_id
                         WHERE vtnd2.nguoi_dung_id = nd.id
                         LIMIT 1),
                        'khach_hang'
                    ) as vai_tro
             FROM nguoi_dung nd
             ORDER BY 
                CASE vai_tro
                    WHEN 'admin' THEN 1
                    WHEN 'nhan_vien_tiep_nhan' THEN 2
                    WHEN 'nhan_vien_dieu_phoi' THEN 3
                    WHEN 'tai_xe' THEN 4
                    WHEN 'shipper' THEN 5
                    WHEN 'khach_hang' THEN 6
                    ELSE 7
                END ASC,
                nd.ho_ten ASC"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function createUser(string $hoTen, string $sdt, string $vaiTro, string $matKhau): int {
        $pass = password_hash($matKhau ?: '123456', PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "INSERT INTO nguoi_dung (ho_ten, so_dien_thoai, mat_khau, trang_thai) VALUES (?, ?, ?, 1)"
        );
        $stmt->bind_param("sss", $hoTen, $sdt, $pass);
        $stmt->execute();
        $uid   = (int)$this->db->insert_id;
        $stmtVt = $this->db->prepare("SELECT id FROM vai_tro WHERE ten_vai_tro = ? LIMIT 1");
        $stmtVt->bind_param("s", $vaiTro);
        $stmtVt->execute();
        $vtRow = $stmtVt->get_result()->fetch_assoc();
        if ($vtRow) {
            $stmtIns = $this->db->prepare("INSERT INTO vai_tro_nguoi_dung (nguoi_dung_id, vai_tro_id) VALUES (?, ?)");
            $stmtIns->bind_param("ii", $uid, $vtRow['id']);
            $stmtIns->execute();
        }
        return $uid;
    }

    public function updateUser(int $id, string $hoTen, string $sdt, string $vaiTro, int $trangThai, string $matKhau): void {
        if ($matKhau !== '') {
            $pass = password_hash($matKhau, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare(
                "UPDATE nguoi_dung SET ho_ten=?, so_dien_thoai=?, mat_khau=?, trang_thai=? WHERE id=?"
            );
            $stmt->bind_param("sssii", $hoTen, $sdt, $pass, $trangThai, $id);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE nguoi_dung SET ho_ten=?, so_dien_thoai=?, trang_thai=? WHERE id=?"
            );
            $stmt->bind_param("ssii", $hoTen, $sdt, $trangThai, $id);
        }
        $stmt->execute();

        // Cập nhật vai trò: xóa cũ rồi gán mới
        $stmtDel = $this->db->prepare("DELETE FROM vai_tro_nguoi_dung WHERE nguoi_dung_id = ?");
        $stmtDel->bind_param("i", $id);
        $stmtDel->execute();

        $stmtVt = $this->db->prepare("SELECT id FROM vai_tro WHERE ten_vai_tro = ? LIMIT 1");
        $stmtVt->bind_param("s", $vaiTro);
        $stmtVt->execute();
        $vtRow = $stmtVt->get_result()->fetch_assoc();
        if ($vtRow) {
            $stmtIns = $this->db->prepare("INSERT INTO vai_tro_nguoi_dung (nguoi_dung_id, vai_tro_id) VALUES (?, ?)");
            $stmtIns->bind_param("ii", $id, $vtRow['id']);
            $stmtIns->execute();
        }
    }

    public function deleteUser(int $id): void {
        // Tìm xem có thông tin khách hàng liên kết với tài khoản này không
        $stmt = $this->db->prepare("SELECT id FROM khach_hang WHERE nguoi_dung_id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $khId = (int)$row['id'];
            // Kiểm tra xem khách hàng này đã có đơn hàng nào chưa
            $checkOrder = $this->db->prepare("SELECT id FROM don_hang WHERE khach_hang_gui_id = ? OR khach_hang_nhan_id = ? LIMIT 1");
            $checkOrder->bind_param("ii", $khId, $khId);
            $checkOrder->execute();
            $orderRes = $checkOrder->get_result();
            if ($orderRes && $orderRes->num_rows === 0) {
                // Nếu chưa có đơn hàng nào, an toàn để xóa dòng trong bảng khach_hang
                $stmtDelKh = $this->db->prepare("DELETE FROM khach_hang WHERE id = ?");
                $stmtDelKh->bind_param("i", $khId);
                $stmtDelKh->execute();
            }
        }

        $this->db->query("DELETE FROM vai_tro_nguoi_dung WHERE nguoi_dung_id = $id");
        $this->db->query("DELETE FROM nguoi_dung WHERE id = $id");
    }

    // ── Xe vận tải ────────────────────────────────────────────────

    public function getVehicles(): array {
        $res = $this->db->query(
            "SELECT id, bien_so_xe as bien_so, trong_tai_toi_da_kg as trong_tai_kg,
                    loai_xe, trang_thai_hoat_dong as trang_thai FROM xe_van_tai"
        );
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function createVehicle(string $bienSo, float $trongTai, string $loaiXe): int {
        $stmt = $this->db->prepare(
            "INSERT INTO xe_van_tai (bien_so_xe, trong_tai_toi_da_kg, loai_xe) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sds", $bienSo, $trongTai, $loaiXe);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function deleteVehicle(int $id): void {
        $this->db->query("DELETE FROM xe_van_tai WHERE id = $id");
    }

    // ── Tuyến đường ───────────────────────────────────────────────

    public function getRoutes(): array {
        $res = $this->db->query(
            "SELECT td.id, td.khoang_cach_km as quang_duong_km,
                    td.thgian_vanchuyen_uoctinh as thoi_gian_phut,
                    cn_di.ten_chi_nhanh as diem_di, cn_den.ten_chi_nhanh as diem_den,
                    CONCAT(cn_di.ten_chi_nhanh, ' → ', cn_den.ten_chi_nhanh) AS ten_tuyen,
                    cn_di.id as chi_nhanh_di_id, cn_den.id as chi_nhanh_den_id
             FROM tuyen_duong td
             LEFT JOIN chi_nhanh cn_di  ON td.chi_nhanh_di_id  = cn_di.id
             LEFT JOIN chi_nhanh cn_den ON td.chi_nhanh_den_id = cn_den.id"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function createRoute(int $diId, int $denId, float $km, int $phut): int {
        $stmt = $this->db->prepare(
            "INSERT INTO tuyen_duong (chi_nhanh_di_id, chi_nhanh_den_id, khoang_cach_km, thgian_vanchuyen_uoctinh)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("iidi", $diId, $denId, $km, $phut);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function deleteRoute(int $id): void {
        $this->db->query("DELETE FROM tuyen_duong WHERE id = $id");
    }

    // ── Chi nhánh ─────────────────────────────────────────────────

    public function getBranches(): array {
        $res = $this->db->query("SELECT * FROM chi_nhanh");
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function createBranch(string $ma, string $ten, string $diaChi, string $sdt): int {
        $stmt = $this->db->prepare(
            "INSERT INTO chi_nhanh (ma_chi_nhanh, ten_chi_nhanh, dia_chi, so_dien_thoai) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $ma, $ten, $diaChi, $sdt);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function deleteBranch(int $id): void {
        $this->db->query("DELETE FROM chi_nhanh WHERE id = $id");
    }

    // ── Người giao hàng ───────────────────────────────────────────

    public function getDeliveryPersons(): array {
        $res = $this->db->query(
            "SELECT ngh.id, nd.ho_ten, nd.so_dien_thoai,
                    ngh.khu_vuc_phu_trach, cn.ten_chi_nhanh, ngh.chi_nhanh_id, nd.trang_thai
             FROM nguoi_giao_hang ngh
             JOIN nguoi_dung nd ON ngh.nguoi_dung_id = nd.id
             LEFT JOIN chi_nhanh cn ON ngh.chi_nhanh_id = cn.id
             ORDER BY nd.ho_ten ASC"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function createDeliveryPerson(string $ten, string $sdt, int $chiNhanhId, string $khuVuc): int {
        $pass = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "INSERT INTO nguoi_dung (ho_ten, so_dien_thoai, mat_khau, trang_thai) VALUES (?, ?, ?, 1)"
        );
        $stmt->bind_param("sss", $ten, $sdt, $pass);
        $stmt->execute();
        $uid   = (int)$this->db->insert_id;
        $vtRow = $this->db->query("SELECT id FROM vai_tro WHERE ten_vai_tro = 'shipper' LIMIT 1")->fetch_assoc();
        if ($vtRow) {
            $this->db->query("INSERT INTO vai_tro_nguoi_dung (nguoi_dung_id, vai_tro_id) VALUES ($uid, {$vtRow['id']})");
        }
        $s2 = $this->db->prepare(
            "INSERT INTO nguoi_giao_hang (nguoi_dung_id, chi_nhanh_id, khu_vuc_phu_trach) VALUES (?, ?, ?)"
        );
        $s2->bind_param("iis", $uid, $chiNhanhId, $khuVuc);
        $s2->execute();
        return (int)$this->db->insert_id;
    }

    public function deleteDeliveryPerson(int $id): void {
        $res = $this->db->query("SELECT nguoi_dung_id FROM nguoi_giao_hang WHERE id = $id");
        if ($row = $res->fetch_assoc()) {
            $uid = (int)$row['nguoi_dung_id'];
            $this->db->query("DELETE FROM nguoi_giao_hang WHERE id = $id");
            $this->db->query("DELETE FROM nguoi_dung WHERE id = $uid");
        }
    }

    // ── Bảng giá ──────────────────────────────────────────────────

    public function getPricing(): array {
        $res  = $this->db->query("SELECT * FROM bang_gia_cuoc ORDER BY khoi_luong_tu_kg");
        $data = [];
        while ($row = $res->fetch_assoc()) {
            $data[] = [
                'id'         => $row['id'],
                'tu_kg'      => $row['khoi_luong_tu_kg'],
                'den_kg'     => $row['khoi_luong_den_kg'],
                'phi_co_ban' => $row['gia_co_ban'],
                'phi_per_km' => $row['gia_theo_moi_ki_lo_met'],
            ];
        }
        return $data;
    }

    public function createPricing(float $tu, float $den, float $phi, float $pkm): int {
        $stmt = $this->db->prepare(
            "INSERT INTO bang_gia_cuoc (khoi_luong_tu_kg, khoi_luong_den_kg, gia_co_ban, gia_theo_moi_ki_lo_met)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("dddd", $tu, $den, $phi, $pkm);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function deletePricing(int $id): void {
        $this->db->query("DELETE FROM bang_gia_cuoc WHERE id = $id");
    }

    // ── Khách hàng ────────────────────────────────────────────────

    public function getCustomers(): array {
        $res = $this->db->query(
            "SELECT id, ho_ten, so_dien_thoai, so_cccd,
                    email, dia_chi
             FROM khach_hang ORDER BY ho_ten ASC"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }
}
