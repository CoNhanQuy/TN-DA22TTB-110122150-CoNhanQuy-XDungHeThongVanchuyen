<?php
/**
 * API module: Giao hàng tận nơi (shipper)
 * Schema mới: giao_hang_tan_noi, nguoi_giao_hang, don_hang, lich_su_trang_thai
 */

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // ----------------------------------------------------------------
    // Shipper xem danh sách đơn được phân công
    // ----------------------------------------------------------------
    case 'driver_orders':
        if ($method === 'GET') {
            $user_id = (int)($_SESSION['user_id'] ?? 0);

            // Lấy nguoi_giao_hang.id từ nguoi_dung_id
            $res = $conn->query("SELECT id FROM nguoi_giao_hang WHERE nguoi_dung_id = $user_id LIMIT 1");
            $ngh = $res->fetch_assoc();
            if (!$ngh) {
                response(true, []);
            }
            $ngh_id = $ngh['id'];

            $sql = "SELECT gh.id as giao_hang_id, gh.trang_thai_giao_hang, gh.nguoi_nhan_thuc_te, gh.ngay_gio_giao,
                           dh.id as don_hang_id, dh.ma_don_hang as ma_don,
                           dh.tong_khoi_luong_kg as khoi_luong_kg, dh.phi_van_chuyen,
                           dh.trang_thai_don_hang as trang_thai,
                           kg.ho_ten as ng_gui, kg.so_dien_thoai as sdt_gui,
                           kn.ho_ten as ng_nhan, kn.so_dien_thoai as sdt_nhan, kn.dia_chi as dia_chi_nhan
                    FROM giao_hang_tan_noi gh
                    JOIN don_hang dh ON gh.don_hang_id = dh.id
                    LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
                    LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
                    WHERE gh.nguoi_giao_hang_id = $ngh_id
                      AND gh.trang_thai_giao_hang IN ('cho_lay_hang','dang_giao')
                    ORDER BY gh.id DESC";
            response(true, $conn->query($sql)->fetch_all(MYSQLI_ASSOC));
        }
        break;

    // ----------------------------------------------------------------
    // Shipper cập nhật trạng thái giao hàng
    // ----------------------------------------------------------------
    case 'driver_update_status':
        if ($method === 'POST') {
            $user_id            = (int)($_SESSION['user_id'] ?? 0);
            $ho_ten             = $conn->real_escape_string($_SESSION['ho_ten'] ?? 'Shipper');
            $dh_id              = (int)($_POST['don_hang_id'] ?? 0);
            $trang_thai         = $_POST['trang_thai'] ?? '';
            $ghi_chu            = $conn->real_escape_string($_POST['ghi_chu'] ?? '');
            $nguoi_nhan_thuc_te = $conn->real_escape_string($_POST['nguoi_nhan_thuc_te'] ?? '');

            // Kiểm tra đơn tồn tại
            $stmt = $conn->prepare("SELECT trang_thai_don_hang FROM don_hang WHERE id = ?");
            $stmt->bind_param("i", $dh_id);
            $stmt->execute();
            $dh_row = $stmt->get_result()->fetch_assoc();
            if (!$dh_row) response(false, null, 'Đơn hàng không tồn tại');

            // Map trạng thái shipper → don_hang
            $map_trang_thai_don = [
                'cho_lay_hang' => 'dang_giao_hang',
                'dang_giao'    => 'dang_giao_hang',
                'thanh_cong'   => 'hoan_tat',
                'that_bai'     => 'da_nhap_kho', // trả lại kho
            ];
            $tt_don = $map_trang_thai_don[$trang_thai] ?? $dh_row['trang_thai_don_hang'];

            // Cập nhật giao_hang_tan_noi
            $res = $conn->query(
                "SELECT id FROM nguoi_giao_hang WHERE nguoi_dung_id = $user_id LIMIT 1"
            );
            $ngh = $res->fetch_assoc();
            if ($ngh) {
                $ngh_id = $ngh['id'];
                $ngay_giao = ($trang_thai === 'thanh_cong') ? "'" . date('Y-m-d H:i:s') . "'" : "NULL";
                $nguoi_nhan_sql = $nguoi_nhan_thuc_te ? "'$nguoi_nhan_thuc_te'" : "NULL";
                $conn->query(
                    "UPDATE giao_hang_tan_noi
                     SET trang_thai_giao_hang = '$trang_thai',
                         nguoi_nhan_thuc_te   = $nguoi_nhan_sql,
                         ngay_gio_giao        = $ngay_giao
                     WHERE don_hang_id = $dh_id AND nguoi_giao_hang_id = $ngh_id"
                );
            }

            // Cập nhật trạng thái đơn hàng
            $conn->query("UPDATE don_hang SET trang_thai_don_hang = '$tt_don' WHERE id = $dh_id");

            // Lịch sử
            $conn->query(
                "INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu)
                 VALUES ($dh_id, '$tt_don', 'Shipper: $ho_ten', '$ghi_chu')"
            );

            response(true, null, 'Cập nhật trạng thái thành công');
        }
        break;

    // ----------------------------------------------------------------
    // Lịch sử giao hàng của shipper
    // ----------------------------------------------------------------
    case 'driver_delivery_log':
        if ($method === 'GET') {
            $user_id = (int)($_SESSION['user_id'] ?? 0);
            $ho_ten  = $conn->real_escape_string($_SESSION['ho_ten'] ?? '');

            $like_name = "Shipper: $ho_ten%";
            $stmt      = $conn->prepare(
                "SELECT ls.*, dh.ma_don_hang as ma_don,
                        kn.ho_ten as ng_nhan, kn.dia_chi as dia_chi_nhan
                 FROM lich_su_trang_thai ls
                 JOIN don_hang dh ON ls.don_hang_id = dh.id
                 LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
                 WHERE ls.nguoi_thuc_hien LIKE ?
                 ORDER BY ls.thoi_gian_cap_nhat DESC LIMIT 100"
            );
            $stmt->bind_param("s", $like_name);
            $stmt->execute();
            response(true, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        }
        break;
}
