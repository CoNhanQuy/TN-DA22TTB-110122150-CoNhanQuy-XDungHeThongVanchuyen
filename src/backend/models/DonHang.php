<?php
/**
 * Model: DonHang
 * Xử lý tất cả truy vấn SQL liên quan đến đơn hàng.
 */
class DonHang {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    /** Tra cứu đơn hàng theo mã (public) */
    public function findByCode(string $code): ?array {
        $stmt = $this->db->prepare(
            "SELECT dh.*,
                    kg.ho_ten   as ng_gui_ten, kg.so_dien_thoai as ng_gui_sdt, kg.so_cccd as ng_gui_cccd,
                    kn.ho_ten   as ng_nhan_ten, kn.so_dien_thoai as ng_nhan_sdt, kn.dia_chi as ng_nhan_dc,
                    cn_gui.ten_chi_nhanh as ten_chi_nhanh_gui,
                    cn_nhan.ten_chi_nhanh as ten_chi_nhanh_nhan
             FROM don_hang dh
             LEFT JOIN khach_hang kg      ON dh.khach_hang_gui_id  = kg.id
             LEFT JOIN khach_hang kn      ON dh.khach_hang_nhan_id = kn.id
             LEFT JOIN chi_nhanh cn_gui   ON dh.chi_nhanh_gui_id   = cn_gui.id
             LEFT JOIN chi_nhanh cn_nhan  ON dh.chi_nhanh_nhan_id  = cn_nhan.id
             WHERE dh.ma_don_hang = ?"
        );
        $stmt->bind_param("s", $code);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /** Chi tiết hàng hóa của đơn */
    public function getHangHoa(int $donHangId): array {
        $stmt = $this->db->prepare("SELECT * FROM chi_tiet_hang_hoa WHERE don_hang_id = ?");
        $stmt->bind_param("i", $donHangId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Lịch sử trạng thái của đơn */
    public function getTimeline(int $donHangId): array {
        // Tự động kiểm tra và sửa đổi/bù đắp dữ liệu nhật ký vận chuyển nếu thiếu
        $stmtShip = $this->db->prepare(
            "SELECT cdvc.dot_van_chuyen_id, dvc.ma_dot_van_chuyen, dvc.trang_thai_dot_van_chuyen,
                    nd.ho_ten as tai_xe_ten, dvc.ngay_gio_khoi_hanh
             FROM chi_tiet_dot_van_chuyen cdvc
             JOIN dot_van_chuyen dvc ON cdvc.dot_van_chuyen_id = dvc.id
             LEFT JOIN tai_xe tx ON dvc.tai_xe_id = tx.id
             LEFT JOIN nguoi_dung nd ON tx.nguoi_dung_id = nd.id
             WHERE cdvc.don_hang_id = ?
             LIMIT 1"
        );
        
        if ($stmtShip) {
            $stmtShip->bind_param("i", $donHangId);
            $stmtShip->execute();
            $qShip = $stmtShip->get_result();
            $stmtShip->close();
            
            if ($qShip && $ship = $qShip->fetch_assoc()) {
                $maDot = $ship['ma_dot_van_chuyen'];
                $taiXe = $ship['tai_xe_ten'] ?? 'Tài xế';
                $trangThaiDot = $ship['trang_thai_dot_van_chuyen'];
                $timeStart = $ship['ngay_gio_khoi_hanh'] ?? date('Y-m-d H:i:s');

                // 1. Kiểm tra trạng thái vận chuyển
                if ($trangThaiDot === 'dang_di_chuyen' || $trangThaiDot === 'da_den_kho_nhan') {
                    $stmtLog1 = $this->db->prepare("SELECT id FROM lich_su_trang_thai WHERE don_hang_id = ? AND trang_thai_moi = 'dang_van_chuyen' LIMIT 1");
                    if ($stmtLog1) {
                        $stmtLog1->bind_param("i", $donHangId);
                        $stmtLog1->execute();
                        $qLog1 = $stmtLog1->get_result();
                        $stmtLog1->close();
                        
                        if ($qLog1 && $qLog1->num_rows === 0) {
                            // Chưa có, tiến hành ghi bù đắp vào db
                            $stmtInsert1 = $this->db->prepare(
                                "INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu, thoi_gian_cap_nhat)
                                 VALUES (?, 'dang_van_chuyen', ?, ?, ?)"
                            );
                            if ($stmtInsert1) {
                                $actor1 = "Tài xế: $taiXe";
                                $note1 = "Bắt đầu vận chuyển theo đợt $maDot";
                                $stmtInsert1->bind_param("isss", $donHangId, $actor1, $note1, $timeStart);
                                $stmtInsert1->execute();
                                $stmtInsert1->close();
                            }
                        }
                    }
                }

                // 2. Kiểm tra trạng thái đã đến kho đích
                if ($trangThaiDot === 'da_den_kho_nhan') {
                    $stmtLog2 = $this->db->prepare("SELECT id FROM lich_su_trang_thai WHERE don_hang_id = ? AND trang_thai_moi = 'da_den_kho_dich' LIMIT 1");
                    if ($stmtLog2) {
                        $stmtLog2->bind_param("i", $donHangId);
                        $stmtLog2->execute();
                        $qLog2 = $stmtLog2->get_result();
                        $stmtLog2->close();
                        
                        if ($qLog2 && $qLog2->num_rows === 0) {
                            $timeArrival = date('Y-m-d H:i:s', strtotime($timeStart) + 1800); // 30 phút sau khi xuất phát
                            
                            // Thử lấy mốc thời gian của trạng thái sau đó là 'dang_giao_hang'
                            $stmtGiao = $this->db->prepare("SELECT thoi_gian_cap_nhat FROM lich_su_trang_thai WHERE don_hang_id = ? AND trang_thai_moi = 'dang_giao_hang' LIMIT 1");
                            if ($stmtGiao) {
                                $stmtGiao->bind_param("i", $donHangId);
                                $stmtGiao->execute();
                                $qGiao = $stmtGiao->get_result();
                                $stmtGiao->close();
                                
                                if ($qGiao && $rowGiao = $qGiao->fetch_assoc()) {
                                    $timeArrival = date('Y-m-d H:i:s', strtotime($rowGiao['thoi_gian_cap_nhat']) - 300); // 5 phút trước khi đi giao
                                }
                            }
                            
                            $stmtInsert2 = $this->db->prepare(
                                "INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu, thoi_gian_cap_nhat)
                                 VALUES (?, 'da_den_kho_dich', ?, ?, ?)"
                            );
                            if ($stmtInsert2) {
                                $actor2 = "Tài xế: $taiXe";
                                $note2 = "Đã giao đến kho đích từ đợt $maDot";
                                $stmtInsert2->bind_param("isss", $donHangId, $actor2, $note2, $timeArrival);
                                $stmtInsert2->execute();
                                $stmtInsert2->close();
                            }
                        }
                    }
                }
            }
        }

        $stmtTimeline = $this->db->prepare(
            "SELECT ls.trang_thai_moi AS status,
                    ls.thoi_gian_cap_nhat AS time,
                    ls.ghi_chu AS note,
                    ls.nguoi_thuc_hien AS actor
             FROM lich_su_trang_thai ls
             INNER JOIN (
                 SELECT trang_thai_moi, MIN(id) AS min_id
                 FROM lich_su_trang_thai
                 WHERE don_hang_id = ?
                 GROUP BY trang_thai_moi
             ) dedup ON ls.id = dedup.min_id
             ORDER BY ls.id ASC"
        );
        if ($stmtTimeline) {
            $stmtTimeline->bind_param("i", $donHangId);
            $stmtTimeline->execute();
            $res = $stmtTimeline->get_result();
            $stmtTimeline->close();
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }
        return [];
    }

    /** Đơn hàng chờ điều phối — chỉ đơn đã nhập kho (da_nhap_kho)
     *  'cho_tiep_nhan' thuộc quyền nhân viên tiếp nhận, không phải điều phối.
     */
    public function getPendingOrders(): array {
        $res = $this->db->query(
            "SELECT dh.id, dh.ma_don_hang as ma_don,
                    dh.tong_khoi_luong_kg as khoi_luong_kg, dh.phi_van_chuyen,
                    dh.trang_thai_don_hang as trang_thai,
                    kg.ho_ten as sender_name, kg.so_dien_thoai as sender_phone,
                    kn.ho_ten as receiver_name, kn.dia_chi as receiver_address,
                    kn.so_dien_thoai as receiver_phone
             FROM don_hang dh
             LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
             LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
             WHERE dh.trang_thai_don_hang = 'da_nhap_kho'
             ORDER BY dh.ngay_tao ASC"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Tất cả đơn hàng (admin) */
    public function getAll(): array {
        $res = $this->db->query(
            "SELECT dh.id, dh.ma_don_hang as ma_don,
                    dh.tong_khoi_luong_kg as khoi_luong_kg, dh.phi_van_chuyen,
                    dh.tien_tra_truoc, dh.trang_thai_don_hang as trang_thai,
                    dh.ngay_tao, kg.ho_ten as sender_name,
                    (SELECT cthh.ten_mat_hang FROM chi_tiet_hang_hoa cthh
                     WHERE cthh.don_hang_id = dh.id LIMIT 1) as ten_hang_hoa
             FROM don_hang dh
             LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id = kg.id
             ORDER BY dh.id DESC"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Đơn hàng cho nhân viên tiếp nhận */
    public function getReceptionistOrders(): array {
        $res = $this->db->query(
            "SELECT dh.id, dh.ma_don_hang as ma_don,
                    dh.tong_khoi_luong_kg as khoi_luong_kg, dh.phi_van_chuyen,
                    dh.tien_tra_truoc, dh.trang_thai_don_hang as trang_thai,
                    dh.ngay_tao, kg.ho_ten as sender_name,
                    hd.trang_thai_thanh_toan as invoice_status,
                    (SELECT cthh.ten_mat_hang FROM chi_tiet_hang_hoa cthh
                     WHERE cthh.don_hang_id = dh.id LIMIT 1) as ten_hang_hoa
             FROM don_hang dh
             LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id = kg.id
             LEFT JOIN hoa_don hd    ON dh.id = hd.don_hang_id
             ORDER BY dh.id DESC"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Chi tiết đầy đủ một đơn hàng theo ID (dùng xuất phiếu in) */
    public function getOrderDetail(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT dh.id, dh.ma_don_hang,
                    dh.tong_khoi_luong_kg, dh.phi_van_chuyen, dh.tien_tra_truoc,
                    dh.trang_thai_don_hang, dh.ngay_tao,
                    kg.ho_ten   AS nguoi_gui,  kg.so_dien_thoai AS sdt_gui,
                    kg.dia_chi  AS dia_chi_gui, kg.so_cccd      AS cccd_gui,
                    kn.ho_ten   AS nguoi_nhan, kn.so_dien_thoai AS sdt_nhan,
                    kn.dia_chi  AS dia_chi_nhan, kn.so_cccd     AS cccd_nhan,
                    hd.trang_thai_thanh_toan AS invoice_status,
                    hd.hinh_thuc_thanh_toan  AS payment_method,
                    hd.so_tien_thu_ho        AS tien_thu_ho,
                    (SELECT nd_shipper.ho_ten 
                     FROM giao_hang_tan_noi gh_tan_noi
                     JOIN nguoi_giao_hang ngh_giao ON gh_tan_noi.nguoi_giao_hang_id = ngh_giao.id
                     JOIN nguoi_dung nd_shipper ON ngh_giao.nguoi_dung_id = nd_shipper.id
                     WHERE gh_tan_noi.don_hang_id = dh.id LIMIT 1) AS ten_shipper
             FROM don_hang dh
             LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
             LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
             LEFT JOIN hoa_don   hd ON dh.id = hd.don_hang_id
             WHERE dh.id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) return null;
        $row['hang_hoa'] = $this->getHangHoa($id);
        $row['timeline'] = $this->getTimeline($id);
        return $row;
    }

    /** Đơn hàng theo số điện thoại */
    public function getByPhone(string $sdt, int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare(
            "SELECT dh.id, dh.ma_don_hang as ma_don,
                    dh.tong_khoi_luong_kg as khoi_luong_kg, dh.phi_van_chuyen, dh.tien_tra_truoc,
                    dh.trang_thai_don_hang as trang_thai,
                    DATE_FORMAT(dh.ngay_tao,'%d/%m/%Y %H:%i') as ngay_tao,
                    kg.ho_ten as nguoi_gui, kn.ho_ten as nguoi_nhan,
                    kn.so_dien_thoai as sdt_nhan, kn.dia_chi as dia_chi_nhan,
                    hd.trang_thai_thanh_toan as invoice_status,
                    hd.so_tien_thu_ho as tien_thu_ho,
                    (SELECT cthh.ten_mat_hang FROM chi_tiet_hang_hoa cthh
                     WHERE cthh.don_hang_id = dh.id LIMIT 1) as ten_hang_hoa
             FROM don_hang dh
             LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
             LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
             LEFT JOIN hoa_don hd   ON dh.id = hd.don_hang_id
             WHERE kg.so_dien_thoai = ? OR kn.so_dien_thoai = ?
             ORDER BY dh.id DESC LIMIT ? OFFSET ?"
        );
        $stmt->bind_param("ssii", $sdt, $sdt, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Đếm tổng đơn theo SĐT */
    public function countByPhone(string $sdt): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as total FROM don_hang dh
             LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
             LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
             WHERE kg.so_dien_thoai = ? OR kn.so_dien_thoai = ?"
        );
        $stmt->bind_param("ss", $sdt, $sdt);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['total'];
    }

    /** Cập nhật trạng thái đơn + ghi lịch sử */
    public function updateStatus(int $id, string $trangThai, string $actor, string $ghiChu = ''): bool {
        $stmt = $this->db->prepare("UPDATE don_hang SET trang_thai_don_hang = ? WHERE id = ?");
        $stmt->bind_param("si", $trangThai, $id);
        if (!$stmt->execute()) return false;

        $actor   = $this->db->real_escape_string($actor);
        $ghiChu  = $this->db->real_escape_string($ghiChu);
        $trangThai = $this->db->real_escape_string($trangThai);
        $this->db->query(
            "INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu)
             VALUES ($id, '$trangThai', '$actor', '$ghiChu')"
        );
        return true;
    }

    /** Upsert khách hàng, trả về id */
    public function upsertKhachHang(string $hoTen, string $sdt, ?string $cccd, string $diaChi): int {
        $stmt = $this->db->prepare(
            "INSERT INTO khach_hang (ho_ten, so_dien_thoai, so_cccd, dia_chi)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
        );
        $stmt->bind_param("ssss", $hoTen, $sdt, $cccd, $diaChi);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    /** Tạo đơn hàng đầy đủ (transaction) */
    public function create($data) {
        $this->db->begin_transaction();
        try {
            $senderId   = $this->upsertKhachHang($data['sender_name'], $data['sender_phone'], $data['sender_cccd'] ?? null, $data['sender_address'] ?? '');
            $receiverId = $this->upsertKhachHang($data['receiver_name'], $data['receiver_phone'], $data['receiver_cccd'] ?? null, $data['receiver_address'] ?? '');

            $maDon   = "DH" . date("YmdHis") . rand(100, 999);
            $kg      = (float)($data['khoi_luong_kg'] ?? 0);
            $phi     = (float)($data['phi_van_chuyen'] ?? 0);
            $kieu    = $data['kieu_thanh_toan'] ?? 'prepaid';
            $tienTT  = ($kieu === 'prepaid') ? $phi : (float)($data['tien_tra_truoc'] ?? 0);

            $chiNhanhNhanId = !empty($data['chi_nhanh_nhan_id']) ? (int)$data['chi_nhanh_nhan_id'] : null;
            $chiNhanhGuiId  = !empty($data['chi_nhanh_gui_id']) ? (int)$data['chi_nhanh_gui_id'] : null;

            $stmt = $this->db->prepare(
                "INSERT INTO don_hang (ma_don_hang, khach_hang_gui_id, khach_hang_nhan_id, chi_nhanh_gui_id, chi_nhanh_nhan_id,
                 tong_khoi_luong_kg, phi_van_chuyen, tien_tra_truoc, trang_thai_don_hang)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'da_nhap_kho')"
            );
            $stmt->bind_param("siiiiddd", $maDon, $senderId, $receiverId, $chiNhanhGuiId, $chiNhanhNhanId, $kg, $phi, $tienTT);
            $stmt->execute();
            $donHangId = (int)$this->db->insert_id;

            if (!empty($data['ten_hang_hoa'])) {
                $tenHang    = $data['ten_hang_hoa'];
                $ghiChu     = $data['ghi_chu'] ?? '';
                $loaiHangId = (int)($data['loai_hang_id'] ?? 6);
                if ($loaiHangId <= 0) {
                    $loaiHangId = 6;
                }
                $dai  = (float)($data['chieu_dai_cm'] ?? 0);
                $rong = (float)($data['chieu_rong_cm'] ?? 0);
                $cao  = (float)($data['chieu_cao_cm'] ?? 0);

                $s4 = $this->db->prepare(
                    "INSERT INTO chi_tiet_hang_hoa (don_hang_id, ten_mat_hang, khoi_luong_uoc_tinh_kg, chieu_dai_cm, chieu_rong_cm, chieu_cao_cm, loai_hang_hoa_id, ghi_chu)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $s4->bind_param("isddddis", $donHangId, $tenHang, $kg, $dai, $rong, $cao, $loaiHangId, $ghiChu);
                $s4->execute();
            }

            $invoiceStatus = ($kieu === 'prepaid') ? 'da_thanh_toan' : 'chua_thanh_toan';
            $ptttEnum      = ($data['phuong_thuc_thanh_toan'] ?? 'tien_mat') === 'qr_code' ? 'qr_code' : 'tien_mat';
            $conLai        = max(0, $phi - $tienTT);
            $s5 = $this->db->prepare(
                "INSERT INTO hoa_don (don_hang_id, so_tien_thu_ho, hinh_thuc_thanh_toan, trang_thai_thanh_toan)
                 VALUES (?, ?, ?, ?)"
            );
            $s5->bind_param("idss", $donHangId, $conLai, $ptttEnum, $invoiceStatus);
            $s5->execute();

            $actor = $this->db->real_escape_string($data['actor'] ?? 'Hệ thống');
            $this->db->query(
                "INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu)
                 VALUES ($donHangId, 'da_nhap_kho', '$actor', 'Tiếp nhận và nhập kho tại quầy')"
            );

            $this->db->commit();
            return ['id' => $donHangId, 'ma_don' => $maDon, 'invoice_status' => $invoiceStatus];
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
