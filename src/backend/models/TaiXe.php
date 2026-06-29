<?php
/**
 * Model: TaiXe
 * Xử lý truy vấn SQL cho đợt vận chuyển, tài xế, shipper.
 */
class TaiXe {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    // ── Đợt vận chuyển ────────────────────────────────────────────

    public function getShipments(): array {
        $this->autoUpdateShipments();
        $res = $this->db->query(
            "SELECT dvc.id,
                    dvc.ma_dot_van_chuyen   AS ma_dot,
                    dvc.trang_thai_dot_van_chuyen AS trang_thai,
                    dvc.ngay_gio_khoi_hanh  AS ngay_gio_bat_dau,
                    td.khoang_cach_km,
                    CONCAT(cn_di.ten_chi_nhanh, ' → ', cn_den.ten_chi_nhanh) AS ten_tuyen,
                    cn_di.ten_chi_nhanh AS diem_di, cn_den.ten_chi_nhanh AS diem_den,
                    nd.ho_ten AS tai_xe, xvt.bien_so_xe AS bien_so,
                    (SELECT COUNT(*) FROM chi_tiet_dot_van_chuyen c WHERE c.dot_van_chuyen_id = dvc.id) AS so_don
             FROM dot_van_chuyen dvc
             LEFT JOIN tuyen_duong td    ON dvc.tuyen_duong_id  = td.id
             LEFT JOIN chi_nhanh cn_di   ON td.chi_nhanh_di_id  = cn_di.id
             LEFT JOIN chi_nhanh cn_den  ON td.chi_nhanh_den_id = cn_den.id
             LEFT JOIN tai_xe tx         ON dvc.tai_xe_id        = tx.id
             LEFT JOIN nguoi_dung nd     ON tx.nguoi_dung_id     = nd.id
             LEFT JOIN xe_van_tai xvt    ON dvc.xe_van_tai_id    = xvt.id
             ORDER BY dvc.ngay_gio_khoi_hanh DESC"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getShipmentById(int $id): ?array {
        $this->autoUpdateShipments();
        $stmt = $this->db->prepare(
            "SELECT dvc.id,
                    dvc.ma_dot_van_chuyen   AS ma_dot,
                    dvc.trang_thai_dot_van_chuyen AS trang_thai,
                    dvc.ngay_gio_khoi_hanh  AS ngay_gio_bat_dau,
                    CONCAT(cn_di.ten_chi_nhanh, ' → ', cn_den.ten_chi_nhanh) AS ten_tuyen,
                    cn_di.ten_chi_nhanh AS diem_di, cn_den.ten_chi_nhanh AS diem_den,
                    nd.ho_ten AS tai_xe, xvt.bien_so_xe AS bien_so,
                    (SELECT COUNT(*) FROM chi_tiet_dot_van_chuyen c WHERE c.dot_van_chuyen_id = dvc.id) AS so_don,
                    (SELECT COALESCE(SUM(dh2.tong_khoi_luong_kg),0)
                     FROM chi_tiet_dot_van_chuyen c2 JOIN don_hang dh2 ON c2.don_hang_id = dh2.id
                     WHERE c2.dot_van_chuyen_id = dvc.id) AS tong_khoi_luong
             FROM dot_van_chuyen dvc
             LEFT JOIN tuyen_duong td   ON dvc.tuyen_duong_id  = td.id
             LEFT JOIN chi_nhanh cn_di  ON td.chi_nhanh_di_id  = cn_di.id
             LEFT JOIN chi_nhanh cn_den ON td.chi_nhanh_den_id = cn_den.id
             LEFT JOIN tai_xe tx        ON dvc.tai_xe_id        = tx.id
             LEFT JOIN nguoi_dung nd    ON tx.nguoi_dung_id     = nd.id
             LEFT JOIN xe_van_tai xvt   ON dvc.xe_van_tai_id    = xvt.id
             WHERE dvc.id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getShipmentOrders(int $dotId): array {
        $stmt = $this->db->prepare(
            "SELECT dh.id, dh.ma_don_hang as ma_don, dh.tong_khoi_luong_kg as khoi_luong_kg,
                    kg.ho_ten as sender_name, kn.ho_ten as receiver_name, kn.dia_chi as receiver_address,
                    (SELECT cthh.ten_mat_hang FROM chi_tiet_hang_hoa cthh
                     WHERE cthh.don_hang_id = dh.id LIMIT 1) as ten_hang_hoa
             FROM chi_tiet_dot_van_chuyen cdvc
             JOIN don_hang dh ON cdvc.don_hang_id = dh.id
             LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
             LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
             WHERE cdvc.dot_van_chuyen_id = ?"
        );
        $stmt->bind_param("i", $dotId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createShipment(int $tuyenId, int $taiXeId, int $xeId, string $ngayGio, array $donHangIds): array {
        $maDot = "DOT_" . date("YmdHis");
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO dot_van_chuyen (ma_dot_van_chuyen, tuyen_duong_id, tai_xe_id, xe_van_tai_id, ngay_gio_khoi_hanh)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("siiis", $maDot, $tuyenId, $taiXeId, $xeId, $ngayGio);
            if (!$stmt->execute()) throw new Exception($this->db->error);
            $dotId = (int)$this->db->insert_id;

            foreach ($donHangIds as $dhId) {
                $dhId = (int)$dhId;
                if ($dhId > 0) {
                    $this->db->query(
                        "INSERT INTO chi_tiet_dot_van_chuyen (dot_van_chuyen_id, don_hang_id, trang_thai_trong_dot)
                         VALUES ($dotId, $dhId, 'da_xep_len_xe')"
                    );
                    $this->db->query(
                        "UPDATE don_hang SET trang_thai_don_hang = 'dang_van_chuyen'
                         WHERE id = $dhId AND trang_thai_don_hang IN ('cho_tiep_nhan','da_nhap_kho')"
                    );
                }
            }
            $this->db->commit();
            return ['id' => $dotId, 'ma_dot' => $maDot, 'so_don' => count($donHangIds)];
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function updateShipmentStatus(int $dotId, string $trangThai): bool {
        $stmt = $this->db->prepare("UPDATE dot_van_chuyen SET trang_thai_dot_van_chuyen = ? WHERE id = ?");
        $stmt->bind_param("si", $trangThai, $dotId);
        if (!$stmt->execute()) return false;

        if ($trangThai === 'dang_di_chuyen') {
            $this->db->query("UPDATE chi_tiet_dot_van_chuyen SET trang_thai_trong_dot = 'dang_van_chuyen' WHERE dot_van_chuyen_id = $dotId");
            $this->db->query("UPDATE don_hang dh JOIN chi_tiet_dot_van_chuyen cdvc ON dh.id = cdvc.don_hang_id SET dh.trang_thai_don_hang = 'dang_van_chuyen' WHERE cdvc.dot_van_chuyen_id = $dotId AND dh.trang_thai_don_hang = 'da_nhap_kho'");
        } elseif ($trangThai === 'da_den_kho_nhan') {
            $this->db->query("UPDATE chi_tiet_dot_van_chuyen SET trang_thai_trong_dot = 'da_giao_kho_dich' WHERE dot_van_chuyen_id = $dotId");
            $this->db->query("UPDATE don_hang dh JOIN chi_tiet_dot_van_chuyen cdvc ON dh.id = cdvc.don_hang_id SET dh.trang_thai_don_hang = 'da_den_kho_dich' WHERE cdvc.dot_van_chuyen_id = $dotId");
        }
        return true;
    }

    public function getMyShipments(int $userId): array {
        $this->autoUpdateShipments();
        $res = $this->db->query("SELECT id FROM tai_xe WHERE nguoi_dung_id = $userId LIMIT 1");
        $taiXe = $res->fetch_assoc();
        if (!$taiXe) return [];

        $taiXeId = $taiXe['id'];
        $res2 = $this->db->query(
            "SELECT dvc.id,
                    dvc.ma_dot_van_chuyen   AS ma_dot,
                    dvc.trang_thai_dot_van_chuyen AS trang_thai,
                    dvc.ngay_gio_khoi_hanh  AS ngay_gio_bat_dau,
                    CONCAT(cn_di.ten_chi_nhanh, ' → ', cn_den.ten_chi_nhanh) AS ten_tuyen,
                    cn_di.ten_chi_nhanh AS diem_di, cn_den.ten_chi_nhanh AS diem_den,
                    xvt.bien_so_xe AS bien_so
             FROM dot_van_chuyen dvc
             LEFT JOIN tuyen_duong td   ON dvc.tuyen_duong_id  = td.id
             LEFT JOIN chi_nhanh cn_di  ON td.chi_nhanh_di_id  = cn_di.id
             LEFT JOIN chi_nhanh cn_den ON td.chi_nhanh_den_id = cn_den.id
             LEFT JOIN xe_van_tai xvt   ON dvc.xe_van_tai_id   = xvt.id
             WHERE dvc.tai_xe_id = $taiXeId
             ORDER BY dvc.ngay_gio_khoi_hanh DESC"
        );
        return $res2 ? $res2->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function driverOwnsShipment(int $userId, int $dotId): bool {
        $res = $this->db->query(
            "SELECT tx.id FROM tai_xe tx
             JOIN dot_van_chuyen dvc ON tx.id = dvc.tai_xe_id
             WHERE tx.nguoi_dung_id = $userId AND dvc.id = $dotId LIMIT 1"
        );
        return $res->num_rows > 0;
    }

    // ── Shipper (giao_hang_tan_noi) ────────────────────────────────

    public function getShipperOrders(int $userId): array {
        $res = $this->db->query("SELECT id FROM nguoi_giao_hang WHERE nguoi_dung_id = $userId LIMIT 1");
        $ngh = $res->fetch_assoc();
        if (!$ngh) return [];

        $nghId = $ngh['id'];
        $res2  = $this->db->query(
            "SELECT gh.id as giao_hang_id, gh.trang_thai_giao_hang, gh.nguoi_nhan_thuc_te, gh.ngay_gio_giao,
                    dh.id as don_hang_id, dh.ma_don_hang as ma_don,
                    dh.tong_khoi_luong_kg as khoi_luong_kg, dh.phi_van_chuyen,
                    dh.trang_thai_don_hang as trang_thai,
                    kg.ho_ten as ng_gui, kg.so_dien_thoai as sdt_gui,
                    kn.ho_ten as ng_nhan, kn.so_dien_thoai as sdt_nhan, kn.dia_chi as dia_chi_nhan
             FROM giao_hang_tan_noi gh
             JOIN don_hang dh ON gh.don_hang_id = dh.id
             LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
             LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
             WHERE gh.nguoi_giao_hang_id = $nghId
               AND gh.trang_thai_giao_hang IN ('cho_lay_hang','dang_giao')
             ORDER BY gh.id DESC"
        );
        return $res2->fetch_all(MYSQLI_ASSOC);
    }

    public function updateShipperStatus(int $userId, int $dhId, string $trangThai, string $nguoiNhan, string $ghiChu, string $hoTen): bool {
        $map = [
            'cho_lay_hang' => 'dang_giao_hang',
            'dang_giao'    => 'dang_giao_hang',
            'thanh_cong'   => 'hoan_tat',
            'that_bai'     => 'da_nhap_kho',
        ];
        // kiểm tra đơn tồn tại
        $stmt = $this->db->prepare("SELECT trang_thai_don_hang FROM don_hang WHERE id = ?");
        $stmt->bind_param("i", $dhId);
        $stmt->execute();
        $dh = $stmt->get_result()->fetch_assoc();
        if (!$dh) return false;

        $ttDon = $map[$trangThai] ?? $dh['trang_thai_don_hang'];

        $res   = $this->db->query("SELECT id FROM nguoi_giao_hang WHERE nguoi_dung_id = $userId LIMIT 1");
        $ngh   = $res->fetch_assoc();
        if ($ngh) {
            $nghId       = $ngh['id'];
            $ngayGiao    = ($trangThai === 'thanh_cong') ? "'" . date('Y-m-d H:i:s') . "'" : "NULL";
            $nguoiNhanSql= $nguoiNhan ? "'" . $this->db->real_escape_string($nguoiNhan) . "'" : "NULL";
            $this->db->query(
                "UPDATE giao_hang_tan_noi
                 SET trang_thai_giao_hang = '$trangThai', nguoi_nhan_thuc_te = $nguoiNhanSql, ngay_gio_giao = $ngayGiao
                 WHERE don_hang_id = $dhId AND nguoi_giao_hang_id = $nghId"
            );
        }

        $this->db->query("UPDATE don_hang SET trang_thai_don_hang = '$ttDon' WHERE id = $dhId");
        $hoTenE = $this->db->real_escape_string($hoTen);
        $ghiChuE= $this->db->real_escape_string($ghiChu);
        $this->db->query(
            "INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu)
             VALUES ($dhId, '$ttDon', 'Shipper: $hoTenE', '$ghiChuE')"
        );
        return true;
    }

    public function getShipperLog(string $hoTen): array {
        $likeName = $this->db->real_escape_string("Shipper: $hoTen%");
        $stmt = $this->db->prepare(
            "SELECT ls.*, dh.ma_don_hang as ma_don, kn.ho_ten as ng_nhan, kn.dia_chi as dia_chi_nhan
             FROM lich_su_trang_thai ls
             JOIN don_hang dh ON ls.don_hang_id = dh.id
             LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
             WHERE ls.nguoi_thuc_hien LIKE ?
             ORDER BY ls.thoi_gian_cap_nhat DESC LIMIT 100"
        );
        $stmt->bind_param("s", $likeName);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ── Tài xế / xe dropdown ──────────────────────────────────────

    public function getAvailableDrivers(): array {
        $res = $this->db->query(
            "SELECT tx.id, nd.ho_ten FROM tai_xe tx
             JOIN nguoi_dung nd ON tx.nguoi_dung_id = nd.id
             WHERE nd.trang_thai = 1"
        );
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getAvailableVehicles(): array {
        $res = $this->db->query(
            "SELECT id, bien_so_xe as bien_so, trong_tai_toi_da_kg as trong_tai_kg
             FROM xe_van_tai WHERE trang_thai_hoat_dong = 1"
        );
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    // ── Đơn hàng theo điểm đến ───────────────────────────────────

    public function getOrdersByDestination(int $tuyenId): array {
        $stmt = $this->db->prepare(
            "SELECT cn_den.ten_chi_nhanh as diem_den, cn_den.dia_chi as dia_chi_den, cn_den.id as cn_den_id
             FROM tuyen_duong td JOIN chi_nhanh cn_den ON td.chi_nhanh_den_id = cn_den.id
             WHERE td.id = ?"
        );
        $stmt->bind_param("i", $tuyenId);
        $stmt->execute();
        $tuyen = $stmt->get_result()->fetch_assoc();
        if (!$tuyen) return [];

        $diemDen  = $tuyen['diem_den'];     // tên chi nhánh đích (VD: "Chi nhánh Vĩnh Long")
        $diaChi   = $tuyen['dia_chi_den'] ?? '';
        $cnDenId  = (int)$tuyen['cn_den_id'];

        // Đơn hàng chờ điều phối
        $base = "SELECT dh.id, dh.ma_don_hang as ma_don,
                        dh.tong_khoi_luong_kg as khoi_luong_kg, dh.trang_thai_don_hang as trang_thai,
                        kg.ho_ten as sender_name, kn.ho_ten as receiver_name,
                        kn.dia_chi as receiver_address, kn.so_dien_thoai as receiver_phone
                 FROM don_hang dh
                 LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
                 LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
                 WHERE dh.trang_thai_don_hang IN ('cho_tiep_nhan','da_nhap_kho')";

        // Ưu tiên match theo tên chi nhánh đích; fallback LIKE dia_chi_den
        $matched = [];

        // Cách 1: khớp tuyến đích qua chi_nhanh_nhan_id nếu có
        if ($cnDenId > 0) {
            $s1 = $this->db->prepare($base . " AND dh.chi_nhanh_nhan_id = ? ORDER BY dh.ngay_tao ASC");
            $s1->bind_param("i", $cnDenId);
            $s1->execute();
            $matched = $s1->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // Cách 2 (fallback): LIKE theo tên chi nhánh hoặc địa chỉ chi nhánh đích
        if (empty($matched)) {
            $keywords = array_filter([
                $diemDen,
                $diaChi,
            ]);
            foreach ($keywords as $kw) {
                if (!$kw) continue;
                $like = '%' . $this->db->real_escape_string($kw) . '%';
                $s2 = $this->db->prepare($base . " AND kn.dia_chi LIKE ? ORDER BY dh.ngay_tao ASC");
                $s2->bind_param("s", $like);
                $s2->execute();
                $matched = $s2->get_result()->fetch_all(MYSQLI_ASSOC);
                if (!empty($matched)) break;
            }
        }

        $all = $this->db->query($base . " ORDER BY dh.ngay_tao ASC")->fetch_all(MYSQLI_ASSOC);

        return ['diem_den' => $diemDen, 'matched' => $matched, 'all' => $all];
    }

    // ── Stats nhanh ────────────────────────────────────────────────

    public function getDispatcherStats(): array {
        return [
            'pending_orders'     => (int)$this->db->query("SELECT COUNT(*) as c FROM don_hang WHERE trang_thai_don_hang IN ('cho_tiep_nhan','da_nhap_kho')")->fetch_assoc()['c'],
            'today_shipments'    => (int)$this->db->query("SELECT COUNT(*) as c FROM dot_van_chuyen WHERE DATE(ngay_gio_khoi_hanh) = CURDATE()")->fetch_assoc()['c'],
            'available_drivers'  => (int)$this->db->query("SELECT COUNT(*) as c FROM tai_xe")->fetch_assoc()['c'],
            'available_vehicles' => (int)$this->db->query("SELECT COUNT(*) as c FROM xe_van_tai WHERE trang_thai_hoat_dong = 1")->fetch_assoc()['c'],
        ];
    }

    // ── Dời đơn ────────────────────────────────────────────────────

    public function deferExpiredShipment(int $dotId): array {
        $stmt = $this->db->prepare("SELECT * FROM dot_van_chuyen WHERE id = ?");
        $stmt->bind_param("i", $dotId);
        $stmt->execute();
        $dot = $stmt->get_result()->fetch_assoc();
        if (!$dot) throw new RuntimeException('Không tìm thấy đợt vận chuyển');

        $ngayGio = new DateTime($dot['ngay_gio_khoi_hanh']);
        if ($ngayGio > new DateTime() && $dot['trang_thai_dot_van_chuyen'] === 'cho_khoi_hanh') {
            throw new RuntimeException('Đợt vận chuyển chưa đến giờ khởi hành');
        }

        $s2 = $this->db->prepare(
            "SELECT don_hang_id FROM chi_tiet_dot_van_chuyen
             WHERE dot_van_chuyen_id = ? AND trang_thai_trong_dot IN ('da_xep_len_xe','dang_van_chuyen')"
        );
        $s2->bind_param("i", $dotId);
        $s2->execute();
        $rows   = $s2->get_result()->fetch_all(MYSQLI_ASSOC);
        $donIds = array_column($rows, 'don_hang_id');

        if (empty($donIds)) return ['deferred' => 0, 'don_ids' => []];

        if ($dot['trang_thai_dot_van_chuyen'] === 'cho_khoi_hanh') {
            $this->db->query("UPDATE dot_van_chuyen SET trang_thai_dot_van_chuyen = 'huy' WHERE id = $dotId");
        }
        foreach ($donIds as $dhId) {
            $dhId = (int)$dhId;
            $this->db->query("UPDATE don_hang SET trang_thai_don_hang = 'da_nhap_kho' WHERE id = $dhId");
            $this->db->query("UPDATE chi_tiet_dot_van_chuyen SET trang_thai_trong_dot = 'tra_lai' WHERE dot_van_chuyen_id = $dotId AND don_hang_id = $dhId");
        }
        return ['deferred' => count($donIds), 'don_ids' => $donIds];
    }

    // ── Phân công shipper ─────────────────────────────────────────

    /** Lấy danh sách người giao hàng đang hoạt động */
    public function getAvailableShippers(): array {
        $res = $this->db->query(
            "SELECT ngh.id, nd.ho_ten, nd.so_dien_thoai
             FROM nguoi_giao_hang ngh
             JOIN nguoi_dung nd ON ngh.nguoi_dung_id = nd.id
             WHERE nd.trang_thai = 1
             ORDER BY nd.ho_ten ASC"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Phân công danh sách đơn hàng cho shipper */
    public function assignOrdersToShipper(int $nghId, array $donHangIds): array {
        $assigned = 0;
        $skipped  = 0;
        foreach ($donHangIds as $dhId) {
            $dhId = (int)$dhId;
            if ($dhId <= 0) continue;

            // Kiểm tra xem đã có bản ghi chưa
            $check = $this->db->query(
                "SELECT id FROM giao_hang_tan_noi WHERE don_hang_id = $dhId AND nguoi_giao_hang_id = $nghId LIMIT 1"
            );
            if ($check && $check->num_rows > 0) {
                $skipped++;
                continue;
            }

            $this->db->query(
                "INSERT INTO giao_hang_tan_noi (don_hang_id, nguoi_giao_hang_id, trang_thai_giao_hang)
                 VALUES ($dhId, $nghId, 'cho_lay_hang')"
            );
            if ($this->db->affected_rows > 0) $assigned++;
        }
        return ['assigned' => $assigned, 'skipped' => $skipped];
    }

    /** Lưu ảnh giao hàng */
    public function saveDeliveryPhoto(int $dhId, int $userId, string $photoPath): bool {
        $photoPathE = $this->db->real_escape_string($photoPath);
        $res = $this->db->query("SELECT id FROM nguoi_giao_hang WHERE nguoi_dung_id = $userId LIMIT 1");
        $ngh = $res ? $res->fetch_assoc() : null;
        if (!$ngh) return false;
        $nghId = $ngh['id'];
        $this->db->query(
            "UPDATE giao_hang_tan_noi SET anh_minh_chung = '$photoPathE'
             WHERE don_hang_id = $dhId AND nguoi_giao_hang_id = $nghId"
        );
        return true;
    }

    /** Lấy đơn hàng cần phân công (da_nhap_kho hoặc dang_van_chuyen, chưa có shipper) */
    public function getOrdersForShipperAssignment(): array {
        $res = $this->db->query(
            "SELECT dh.id, dh.ma_don_hang AS ma_don,
                    dh.tong_khoi_luong_kg AS khoi_luong_kg,
                    dh.trang_thai_don_hang AS trang_thai,
                    kg.ho_ten AS sender_name,
                    kn.ho_ten AS receiver_name, kn.dia_chi AS receiver_address, kn.so_dien_thoai AS receiver_phone,
                    (SELECT cthh.ten_mat_hang FROM chi_tiet_hang_hoa cthh
                     WHERE cthh.don_hang_id = dh.id LIMIT 1) AS ten_hang_hoa,
                    (SELECT ngh2.id FROM giao_hang_tan_noi g2
                     JOIN nguoi_giao_hang ngh2 ON g2.nguoi_giao_hang_id = ngh2.id
                     WHERE g2.don_hang_id = dh.id LIMIT 1) AS assigned_shipper_id,
                    (SELECT nd2.ho_ten FROM giao_hang_tan_noi g3
                     JOIN nguoi_giao_hang ngh3 ON g3.nguoi_giao_hang_id = ngh3.id
                     JOIN nguoi_dung nd2 ON ngh3.nguoi_dung_id = nd2.id
                     WHERE g3.don_hang_id = dh.id LIMIT 1) AS assigned_shipper_name
             FROM don_hang dh
             LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
             LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
             WHERE dh.trang_thai_don_hang IN ('da_nhap_kho','dang_van_chuyen')
             ORDER BY dh.ngay_tao ASC"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function autoUpdateShipments(): void {
        // Lấy tất cả các đợt vận chuyển 'cho_khoi_hanh' đã quá giờ khởi hành (so với NOW() của DB)
        $res = $this->db->query(
            "SELECT id, ma_dot_van_chuyen 
             FROM dot_van_chuyen 
             WHERE trang_thai_dot_van_chuyen = 'cho_khoi_hanh' AND ngay_gio_khoi_hanh <= NOW()"
        );
        if ($res && $res->num_rows > 0) {
            while ($dot = $res->fetch_assoc()) {
                $dotId = (int)$dot['id'];
                $maDot = $dot['ma_dot_van_chuyen'];
                
                // 1. Cập nhật trạng thái đợt vận chuyển thành 'dang_di_chuyen'
                $this->db->query("UPDATE dot_van_chuyen SET trang_thai_dot_van_chuyen = 'dang_di_chuyen' WHERE id = $dotId");
                
                // 2. Cập nhật trạng thái chi tiết đợt vận chuyển thành 'dang_van_chuyen'
                $this->db->query("UPDATE chi_tiet_dot_van_chuyen SET trang_thai_trong_dot = 'dang_van_chuyen' WHERE dot_van_chuyen_id = $dotId");
                
                // 3. Cập nhật trạng thái đơn hàng trong đợt đó thành 'dang_van_chuyen'
                // Chỉ cập nhật các đơn hàng đang ở trạng thái 'da_nhap_kho'
                $resDon = $this->db->query("SELECT don_hang_id FROM chi_tiet_dot_van_chuyen WHERE dot_van_chuyen_id = $dotId");
                if ($resDon) {
                    while ($rowDon = $resDon->fetch_assoc()) {
                        $dhId = (int)$rowDon['don_hang_id'];
                        
                        // Kiểm tra trạng thái đơn hàng hiện tại
                        $checkDh = $this->db->query("SELECT trang_thai_don_hang FROM don_hang WHERE id = $dhId LIMIT 1");
                        if ($checkDh && $dh = $checkDh->fetch_assoc()) {
                            if ($dh['trang_thai_don_hang'] === 'da_nhap_kho') {
                                // Cập nhật trạng thái đơn
                                $this->db->query("UPDATE don_hang SET trang_thai_don_hang = 'dang_van_chuyen' WHERE id = $dhId");
                                
                                // Ghi lịch sử trạng thái
                                $this->db->query(
                                    "INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu)
                                     VALUES ($dhId, 'dang_van_chuyen', 'Hệ thống', 'Tự động xuất kho và vận chuyển theo lịch khởi hành đợt $maDot')"
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    public function reportIncident(int $userId, string $maDon, string $maDot, string $loaiSuCo, string $moTa, string $viTri, string $mucDo): bool {
        $donHangId = null;
        if (!empty($maDon)) {
            $stmt = $this->db->prepare("SELECT id FROM don_hang WHERE ma_don_hang = ? LIMIT 1");
            $stmt->bind_param("s", $maDon);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res) {
                $donHangId = (int)$res['id'];
            }
        }

        $dotId = null;
        if (!empty($maDot)) {
            $stmt = $this->db->prepare("SELECT id FROM dot_van_chuyen WHERE ma_dot_van_chuyen = ? LIMIT 1");
            $stmt->bind_param("s", $maDot);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res) {
                $dotId = (int)$res['id'];
            }
        }

        // Map enum
        $mappedType = 'khac';
        if ($loaiSuCo === 'xe_hong') {
            $mappedType = 'hong_xe';
        } elseif ($loaiSuCo === 'thoi_tiet') {
            $mappedType = 'thoi_tiet';
        } elseif ($loaiSuCo === 'khong_lien_lac') {
            $mappedType = 'khach_hen_lai';
        } elseif ($loaiSuCo === 'hang_hu_hong') {
            $mappedType = 'hang_hu_hong';
        }

        // Build details
        $fullDetails = "";
        if (!empty($viTri)) {
            $fullDetails .= "Vị trí: " . $viTri . "\n";
        }
        if (!empty($mucDo)) {
            $levels = [
                'thap' => 'Thấp (Tự xử lý được)',
                'trung_binh' => 'Trung bình (Cần hỗ trợ)',
                'cao' => 'Cao (Cần can thiệp ngay)'
            ];
            $levelName = $levels[$mucDo] ?? $mucDo;
            $fullDetails .= "Mức độ: " . $levelName . "\n";
        }
        $fullDetails .= "Chi tiết sự cố: " . $moTa;

        $stmt = $this->db->prepare(
            "INSERT INTO bao_cao_su_co (don_hang_id, dot_van_chuyen_id, nguoi_bao_cao_id, loai_su_co, mo_ta_chi_tiet, trang_thai_xu_ly)
             VALUES (?, ?, ?, ?, ?, 'cho_duyet')"
        );
        $stmt->bind_param("iiiss", $donHangId, $dotId, $userId, $mappedType, $fullDetails);
        return $stmt->execute();
    }
}
