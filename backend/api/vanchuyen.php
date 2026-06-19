<?php
/**
 * API module: Vận chuyển (đợt vận chuyển trung chuyển kho-kho)
 * Schema mới: dot_van_chuyen, chi_tiet_dot_van_chuyen, tai_xe, xe_van_tai, tuyen_duong, chi_nhanh
 *
 * Tên cột đã đổi:
 *   trang_thai           → trang_thai_dot_van_chuyen
 *   ma_dot               → ma_dot_van_chuyen
 *   xe_id                → xe_van_tai_id
 *   ngay_gio_bat_dau     → ngay_gio_khoi_hanh
 *   chi_tiet_dot.dot_id  → chi_tiet_dot_van_chuyen.dot_van_chuyen_id
 *   tai_xe.user_id       → tai_xe.nguoi_dung_id
 *   xe.bien_so           → xe_van_tai.bien_so_xe
 */

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // ----------------------------------------------------------------
    // Danh sách đợt vận chuyển / Tạo đợt mới
    // ----------------------------------------------------------------
    case 'shipments':
        if ($method === 'GET') {
            $sql = "SELECT dvc.*, td.khoang_cach_ki_lo_met,
                           cn_di.ten_chi_nhanh as diem_di, cn_den.ten_chi_nhanh as diem_den,
                           nd.ho_ten as tai_xe, xvt.bien_so_xe as bien_so,
                           (SELECT COUNT(*) FROM chi_tiet_dot_van_chuyen c WHERE c.dot_van_chuyen_id = dvc.id) as so_don
                    FROM dot_van_chuyen dvc
                    LEFT JOIN tuyen_duong td    ON dvc.tuyen_duong_id  = td.id
                    LEFT JOIN chi_nhanh cn_di   ON td.chi_nhanh_di_id  = cn_di.id
                    LEFT JOIN chi_nhanh cn_den  ON td.chi_nhanh_den_id = cn_den.id
                    LEFT JOIN tai_xe tx         ON dvc.tai_xe_id        = tx.id
                    LEFT JOIN nguoi_dung nd     ON tx.nguoi_dung_id     = nd.id
                    LEFT JOIN xe_van_tai xvt    ON dvc.xe_van_tai_id    = xvt.id
                    ORDER BY dvc.ngay_gio_khoi_hanh DESC";
            $res = $conn->query($sql);
            response(true, $res->fetch_all(MYSQLI_ASSOC));

        } elseif ($method === 'POST') {
            $tuyen_id    = (int)($_POST['tuyen_id'] ?? 0);
            $tai_xe_id   = (int)($_POST['tai_xe_id'] ?? 0);
            $xe_id       = (int)($_POST['xe_id'] ?? 0);
            $ngay        = $_POST['ngay_gio_bat_dau'] ?? date('Y-m-d H:i:s');
            $don_hang_ids = isset($_POST['don_hang_ids']) ? (array)$_POST['don_hang_ids'] : [];
            $ma_dot      = "DOT_" . date("YmdHis");

            if (!$tuyen_id || !$tai_xe_id || !$xe_id) {
                response(false, null, 'Thiếu thông tin bắt buộc (tuyến, tài xế, xe)');
            }

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO dot_van_chuyen (ma_dot_van_chuyen, tuyen_duong_id, tai_xe_id, xe_van_tai_id, ngay_gio_khoi_hanh)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("siiis", $ma_dot, $tuyen_id, $tai_xe_id, $xe_id, $ngay);
                if (!$stmt->execute()) throw new Exception($conn->error);
                $dot_id = $conn->insert_id;

                foreach ($don_hang_ids as $dh_id) {
                    $dh_id = (int)$dh_id;
                    if ($dh_id > 0) {
                        $conn->query(
                            "INSERT INTO chi_tiet_dot_van_chuyen (dot_van_chuyen_id, don_hang_id, trang_thai_trong_dot)
                             VALUES ($dot_id, $dh_id, 'da_xep_len_xe')"
                        );
                        $conn->query(
                            "UPDATE don_hang SET trang_thai_don_hang = 'dang_van_chuyen'
                             WHERE id = $dh_id AND trang_thai_don_hang IN ('cho_tiep_nhan','da_nhap_kho')"
                        );
                    }
                }

                $conn->commit();
                response(true, ['id' => $dot_id, 'ma_dot' => $ma_dot, 'so_don' => count($don_hang_ids)]);
            } catch (Exception $e) {
                $conn->rollback();
                response(false, null, 'Lỗi tạo đợt: ' . $e->getMessage());
            }
        }
        break;

    // ----------------------------------------------------------------
    // Chi tiết đợt vận chuyển
    // ----------------------------------------------------------------
    case 'shipment_details':
        if ($method === 'GET') {
            $dot_id = (int)($_GET['id'] ?? 0);
            if (!$dot_id) response(false, null, 'Thiếu id đợt vận chuyển');

            $stmt = $conn->prepare(
                "SELECT dvc.*, cn_di.ten_chi_nhanh as diem_di, cn_den.ten_chi_nhanh as diem_den,
                        nd.ho_ten as tai_xe, xvt.bien_so_xe as bien_so,
                        (SELECT COUNT(*) FROM chi_tiet_dot_van_chuyen c WHERE c.dot_van_chuyen_id = dvc.id) as so_don,
                        (SELECT COALESCE(SUM(dh2.tong_khoi_luong_kg),0)
                         FROM chi_tiet_dot_van_chuyen c2
                         JOIN don_hang dh2 ON c2.don_hang_id = dh2.id
                         WHERE c2.dot_van_chuyen_id = dvc.id) as tong_khoi_luong
                 FROM dot_van_chuyen dvc
                 LEFT JOIN tuyen_duong td   ON dvc.tuyen_duong_id  = td.id
                 LEFT JOIN chi_nhanh cn_di  ON td.chi_nhanh_di_id  = cn_di.id
                 LEFT JOIN chi_nhanh cn_den ON td.chi_nhanh_den_id = cn_den.id
                 LEFT JOIN tai_xe tx        ON dvc.tai_xe_id        = tx.id
                 LEFT JOIN nguoi_dung nd    ON tx.nguoi_dung_id     = nd.id
                 LEFT JOIN xe_van_tai xvt   ON dvc.xe_van_tai_id    = xvt.id
                 WHERE dvc.id = ?"
            );
            $stmt->bind_param("i", $dot_id);
            $stmt->execute();
            $shipment = $stmt->get_result()->fetch_assoc();
            if (!$shipment) response(false, null, 'Không tìm thấy đợt vận chuyển');

            $stmt2 = $conn->prepare(
                "SELECT dh.id, dh.ma_don_hang as ma_don,
                        dh.tong_khoi_luong_kg as khoi_luong_kg,
                        kg.ho_ten as sender_name, kn.ho_ten as receiver_name,
                        kn.dia_chi as receiver_address
                 FROM chi_tiet_dot_van_chuyen cdvc
                 JOIN don_hang dh ON cdvc.don_hang_id = dh.id
                 LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
                 LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
                 WHERE cdvc.dot_van_chuyen_id = ?"
            );
            $stmt2->bind_param("i", $dot_id);
            $stmt2->execute();
            $orders = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

            response(true, ['shipment' => $shipment, 'orders' => $orders]);
        }
        break;

    // ----------------------------------------------------------------
    // Lọc đơn hàng theo điểm đến (dùng khi tạo đợt)
    // ----------------------------------------------------------------
    case 'orders_by_destination':
        if ($method === 'GET') {
            $tuyen_id = (int)($_GET['tuyen_id'] ?? 0);
            if (!$tuyen_id) response(false, null, 'Thiếu tuyen_id');

            $stmt = $conn->prepare(
                "SELECT cn_den.ten_chi_nhanh as diem_den, cn_den.dia_chi as dia_chi_den
                 FROM tuyen_duong td
                 JOIN chi_nhanh cn_den ON td.chi_nhanh_den_id = cn_den.id
                 WHERE td.id = ?"
            );
            $stmt->bind_param("i", $tuyen_id);
            $stmt->execute();
            $tuyen = $stmt->get_result()->fetch_assoc();
            if (!$tuyen) response(false, null, 'Không tìm thấy tuyến đường');

            $diem_den     = $tuyen['diem_den'];
            $dia_chi_den  = $tuyen['dia_chi_den'] ?? '';
            $like_pattern = '%' . $conn->real_escape_string($dia_chi_den) . '%';

            $base = "SELECT dh.id, dh.ma_don_hang as ma_don,
                            dh.tong_khoi_luong_kg as khoi_luong_kg, dh.trang_thai_don_hang as trang_thai,
                            kg.ho_ten as sender_name, kn.ho_ten as receiver_name,
                            kn.dia_chi as receiver_address, kn.so_dien_thoai as receiver_phone
                     FROM don_hang dh
                     LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
                     LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
                     WHERE dh.trang_thai_don_hang IN ('cho_tiep_nhan','da_nhap_kho')";

            $stmt2 = $conn->prepare($base . " AND kn.dia_chi LIKE ? ORDER BY dh.ngay_tao ASC");
            $stmt2->bind_param("s", $like_pattern);
            $stmt2->execute();
            $matched = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

            $all = $conn->query($base . " ORDER BY dh.ngay_tao ASC")->fetch_all(MYSQLI_ASSOC);

            response(true, ['diem_den' => $diem_den, 'matched' => $matched, 'all' => $all]);
        }
        break;

    // ----------------------------------------------------------------
    // Tài xế sẵn sàng (dropdown điều phối)
    // ----------------------------------------------------------------
    case 'available_drivers':
        if ($method === 'GET') {
            $res     = $conn->query(
                "SELECT tx.id, nd.ho_ten FROM tai_xe tx
                 JOIN nguoi_dung nd ON tx.nguoi_dung_id = nd.id
                 WHERE nd.trang_thai = 1"
            );
            $drivers = $res->fetch_all(MYSQLI_ASSOC);
            response(true, ['count' => count($drivers), 'drivers' => $drivers]);
        }
        break;

    // ----------------------------------------------------------------
    // Xe sẵn sàng (dropdown điều phối)
    // ----------------------------------------------------------------
    case 'available_vehicles':
        if ($method === 'GET') {
            $res      = $conn->query(
                "SELECT id, bien_so_xe as bien_so, trong_tai_toi_da_kg as trong_tai_kg
                 FROM xe_van_tai WHERE trang_thai_hoat_dong = 1"
            );
            $vehicles = $res->fetch_all(MYSQLI_ASSOC);
            response(true, ['count' => count($vehicles), 'vehicles' => $vehicles]);
        }
        break;

    // ----------------------------------------------------------------
    // Tài xế xem các đợt được phân công
    // ----------------------------------------------------------------
    case 'my_shipments':
        if ($method === 'GET') {
            $user_id = (int)($_SESSION['user_id'] ?? 0);
            $res     = $conn->query("SELECT id FROM tai_xe WHERE nguoi_dung_id = $user_id LIMIT 1");
            $tai_xe  = $res->fetch_assoc();
            if (!$tai_xe) response(true, []);

            $tai_xe_id = $tai_xe['id'];
            $sql = "SELECT dvc.*, cn_di.ten_chi_nhanh as diem_di, cn_den.ten_chi_nhanh as diem_den,
                           xvt.bien_so_xe as bien_so
                    FROM dot_van_chuyen dvc
                    LEFT JOIN tuyen_duong td   ON dvc.tuyen_duong_id  = td.id
                    LEFT JOIN chi_nhanh cn_di  ON td.chi_nhanh_di_id  = cn_di.id
                    LEFT JOIN chi_nhanh cn_den ON td.chi_nhanh_den_id = cn_den.id
                    LEFT JOIN xe_van_tai xvt   ON dvc.xe_van_tai_id   = xvt.id
                    WHERE dvc.tai_xe_id = $tai_xe_id
                    ORDER BY dvc.ngay_gio_khoi_hanh DESC";
            response(true, $conn->query($sql)->fetch_all(MYSQLI_ASSOC));
        }
        break;

    // ----------------------------------------------------------------
    // Tài xế cập nhật trạng thái đợt vận chuyển
    // ----------------------------------------------------------------
    case 'update_shipment_status':
        if ($method === 'POST') {
            $dot_id  = (int)($_POST['dot_id'] ?? 0);
            $tt      = $_POST['trang_thai'] ?? '';
            $user_id = (int)($_SESSION['user_id'] ?? 0);

            $res = $conn->query(
                "SELECT tx.id FROM tai_xe tx
                 JOIN dot_van_chuyen dvc ON tx.id = dvc.tai_xe_id
                 WHERE tx.nguoi_dung_id = $user_id AND dvc.id = $dot_id LIMIT 1"
            );
            if ($res->num_rows === 0) {
                response(false, null, 'Không có quyền cập nhật đợt vận chuyển này');
            }

            $stmt = $conn->prepare("UPDATE dot_van_chuyen SET trang_thai_dot_van_chuyen = ? WHERE id = ?");
            $stmt->bind_param("si", $tt, $dot_id);
            if ($stmt->execute()) {
                if ($tt === 'dang_di_chuyen') {
                    $conn->query(
                        "UPDATE chi_tiet_dot_van_chuyen SET trang_thai_trong_dot = 'dang_van_chuyen'
                         WHERE dot_van_chuyen_id = $dot_id"
                    );
                    $conn->query(
                        "UPDATE don_hang dh
                         JOIN chi_tiet_dot_van_chuyen cdvc ON dh.id = cdvc.don_hang_id
                         SET dh.trang_thai_don_hang = 'dang_van_chuyen'
                         WHERE cdvc.dot_van_chuyen_id = $dot_id
                           AND dh.trang_thai_don_hang = 'da_nhap_kho'"
                    );
                } elseif ($tt === 'da_den_kho_nhan') {
                    $conn->query(
                        "UPDATE chi_tiet_dot_van_chuyen SET trang_thai_trong_dot = 'da_giao_kho_dich'
                         WHERE dot_van_chuyen_id = $dot_id"
                    );
                    $conn->query(
                        "UPDATE don_hang dh
                         JOIN chi_tiet_dot_van_chuyen cdvc ON dh.id = cdvc.don_hang_id
                         SET dh.trang_thai_don_hang = 'da_den_kho_dich'
                         WHERE cdvc.dot_van_chuyen_id = $dot_id"
                    );
                }
                response(true, null, 'Cập nhật đợt vận chuyển thành công');
            }
            response(false, null, $conn->error);
        }
        break;

    // ----------------------------------------------------------------
    // Thống kê nhanh cho điều phối viên
    // ----------------------------------------------------------------
    case 'dispatcher_stats':
        if ($method === 'GET') {
            response(true, [
                'pending_orders'     => $conn->query("SELECT COUNT(*) as c FROM don_hang WHERE trang_thai_don_hang IN ('cho_tiep_nhan','da_nhap_kho')")->fetch_assoc()['c'],
                'today_shipments'    => $conn->query("SELECT COUNT(*) as c FROM dot_van_chuyen WHERE DATE(ngay_gio_khoi_hanh) = CURDATE()")->fetch_assoc()['c'],
                'available_drivers'  => $conn->query("SELECT COUNT(*) as c FROM tai_xe")->fetch_assoc()['c'],
                'available_vehicles' => $conn->query("SELECT COUNT(*) as c FROM xe_van_tai WHERE trang_thai_hoat_dong = 1")->fetch_assoc()['c'],
            ]);
        }
        break;

    // ----------------------------------------------------------------
    // Dời đơn của đợt quá giờ về hàng chờ
    // ----------------------------------------------------------------
    case 'defer_expired_shipments':
        if ($method === 'POST') {
            $dot_id = (int)($_POST['dot_id'] ?? 0);
            if (!$dot_id) response(false, null, 'Thiếu dot_id');

            $stmt = $conn->prepare("SELECT * FROM dot_van_chuyen WHERE id = ?");
            $stmt->bind_param("i", $dot_id);
            $stmt->execute();
            $dot = $stmt->get_result()->fetch_assoc();
            if (!$dot) response(false, null, 'Không tìm thấy đợt vận chuyển');

            $ngay_gio = new DateTime($dot['ngay_gio_khoi_hanh']);
            if ($ngay_gio > new DateTime() && $dot['trang_thai_dot_van_chuyen'] === 'cho_khoi_hanh') {
                response(false, null, 'Đợt vận chuyển chưa đến giờ khởi hành');
            }

            $stmt2 = $conn->prepare(
                "SELECT don_hang_id FROM chi_tiet_dot_van_chuyen
                 WHERE dot_van_chuyen_id = ? AND trang_thai_trong_dot IN ('da_xep_len_xe','dang_van_chuyen')"
            );
            $stmt2->bind_param("i", $dot_id);
            $stmt2->execute();
            $rows    = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            $don_ids = array_column($rows, 'don_hang_id');

            if (empty($don_ids)) {
                response(true, ['deferred' => 0], 'Không có đơn nào cần dời');
            }

            if ($dot['trang_thai_dot_van_chuyen'] === 'cho_khoi_hanh') {
                $conn->query("UPDATE dot_van_chuyen SET trang_thai_dot_van_chuyen = 'huy' WHERE id = $dot_id");
            }

            foreach ($don_ids as $dh_id) {
                $dh_id = (int)$dh_id;
                $conn->query("UPDATE don_hang SET trang_thai_don_hang = 'da_nhap_kho' WHERE id = $dh_id");
                $conn->query(
                    "UPDATE chi_tiet_dot_van_chuyen SET trang_thai_trong_dot = 'tra_lai'
                     WHERE dot_van_chuyen_id = $dot_id AND don_hang_id = $dh_id"
                );
            }

            response(true,
                ['deferred' => count($don_ids), 'don_ids' => $don_ids],
                count($don_ids) . ' đơn đã được dời về hàng chờ'
            );
        }
        break;
}
