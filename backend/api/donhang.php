<?php
/**
 * API module: Đơn hàng
 * Schema mới: don_hang, khach_hang, chi_tiet_hang_hoa, hoa_don, lich_su_trang_thai
 *
 * Tên cột quan trọng đã đổi:
 *   ma_don        → ma_don_hang
 *   khach_gui_id  → khach_hang_gui_id
 *   khach_nhan_id → khach_hang_nhan_id
 *   trang_thai    → trang_thai_don_hang
 *   ten_hang_hoa  → (trong chi_tiet_hang_hoa.ten_mat_hang)
 *   khoi_luong_kg → tong_khoi_luong_kg
 *   ngay_tao      → ngay_tao (vẫn giữ)
 */

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ----------------------------------------------------------------
// Tra cứu đơn hàng (public — không cần đăng nhập)
// ----------------------------------------------------------------
if ($action === 'track') {
    $code = '';
    if ($method === 'POST') {
        $code = trim($_POST['ma_don'] ?? $_POST['code'] ?? '');
    } else {
        $code = trim($_GET['ma_don'] ?? $_GET['code'] ?? '');
    }

    if (!$code) response(false, null, 'Vui lòng nhập mã đơn hàng');

    $stmt = $conn->prepare(
        "SELECT dh.*,
                kg.ho_ten   as ng_gui_ten, kg.so_dien_thoai as ng_gui_sdt, kg.so_cccd as ng_gui_cccd,
                kn.ho_ten   as ng_nhan_ten, kn.so_dien_thoai as ng_nhan_sdt, kn.dia_chi as ng_nhan_dc,
                cn_gui.ten_chi_nhanh as ten_chi_nhanh_gui,
                cn_nhan.ten_chi_nhanh as ten_chi_nhanh_nhan
         FROM don_hang dh
         LEFT JOIN khach_hang kg  ON dh.khach_hang_gui_id  = kg.id
         LEFT JOIN khach_hang kn  ON dh.khach_hang_nhan_id = kn.id
         LEFT JOIN chi_nhanh cn_gui  ON dh.chi_nhanh_gui_id  = cn_gui.id
         LEFT JOIN chi_nhanh cn_nhan ON dh.chi_nhanh_nhan_id = cn_nhan.id
         WHERE dh.ma_don_hang = ?"
    );
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        response(false, null, 'Không tìm thấy đơn hàng với mã: ' . htmlspecialchars($code));
    }

    $row = $res->fetch_assoc();

    // Lấy chi tiết hàng hóa
    $stmtHH = $conn->prepare("SELECT * FROM chi_tiet_hang_hoa WHERE don_hang_id = ?");
    $stmtHH->bind_param("i", $row['id']);
    $stmtHH->execute();
    $hang_hoa = $stmtHH->get_result()->fetch_all(MYSQLI_ASSOC);

    // Lịch sử trạng thái
    $lsRes = $conn->query(
        "SELECT trang_thai_moi as status, thoi_gian_cap_nhat as time, ghi_chu as note, nguoi_thuc_hien as actor
         FROM lich_su_trang_thai WHERE don_hang_id = {$row['id']} ORDER BY thoi_gian_cap_nhat ASC"
    );
    $timeline = $lsRes ? $lsRes->fetch_all(MYSQLI_ASSOC) : [];

    if (empty($timeline)) {
        $timeline = [[
            'status' => $row['trang_thai_don_hang'],
            'time'   => $row['ngay_tao'] ?? '',
            'note'   => '',
            'actor'  => '',
        ]];
    }

    // Tính tiến độ
    $progress_map = [
        'cho_tiep_nhan'  => 10,
        'da_nhap_kho'    => 25,
        'dang_van_chuyen'=> 50,
        'da_den_kho_dich'=> 70,
        'dang_giao_hang' => 85,
        'hoan_tat'       => 100,
        'da_huy'         => 0,
    ];
    $progress = $progress_map[$row['trang_thai_don_hang']] ?? 0;

    $order = [
        'ma_don'            => $row['ma_don_hang'],
        'tong_khoi_luong_kg'=> $row['tong_khoi_luong_kg'],
        'phi_van_chuyen'    => $row['phi_van_chuyen'],
        'tien_tra_truoc'    => $row['tien_tra_truoc'] ?? 0,
        'trang_thai'        => $row['trang_thai_don_hang'],
        'ngay_tao'          => $row['ngay_tao'] ?? '',
        'chi_nhanh_gui'     => $row['ten_chi_nhanh_gui'] ?? '',
        'chi_nhanh_nhan'    => $row['ten_chi_nhanh_nhan'] ?? '',
        'progress'          => $progress,
        'hang_hoa'          => $hang_hoa,
        'nguoi_gui'  => [
            'ho_ten'        => $row['ng_gui_ten'] ?? '',
            'so_dien_thoai' => $row['ng_gui_sdt'] ?? '',
            'so_cccd'       => $row['ng_gui_cccd'] ?? '',
        ],
        'nguoi_nhan' => [
            'ho_ten'        => $row['ng_nhan_ten'] ?? '',
            'so_dien_thoai' => $row['ng_nhan_sdt'] ?? '',
            'dia_chi'       => $row['ng_nhan_dc'] ?? '',
        ],
    ];

    response(true, ['order' => $order, 'timeline' => $timeline]);
}

switch ($action) {

    // ----------------------------------------------------------------
    // Đơn hàng chờ điều phối
    // ----------------------------------------------------------------
    case 'pending_orders':
        if ($method === 'GET') {
            $sql = "SELECT dh.id, dh.ma_don_hang as ma_don,
                           dh.tong_khoi_luong_kg as khoi_luong_kg, dh.phi_van_chuyen,
                           dh.trang_thai_don_hang as trang_thai,
                           kg.ho_ten as sender_name, kg.so_dien_thoai as sender_phone,
                           kn.ho_ten as receiver_name, kn.dia_chi as receiver_address,
                           kn.so_dien_thoai as receiver_phone
                    FROM don_hang dh
                    LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
                    LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
                    WHERE dh.trang_thai_don_hang IN ('cho_tiep_nhan','da_nhap_kho')
                    ORDER BY dh.ngay_tao ASC";
            $res = $conn->query($sql);
            response(true, $res ? $res->fetch_all(MYSQLI_ASSOC) : []);
        }
        break;

    // ----------------------------------------------------------------
    // Tất cả đơn hàng (admin/tiếp nhận)
    // ----------------------------------------------------------------
    case 'orders':
        if ($method === 'GET') {
            $res = $conn->query(
                "SELECT dh.id, dh.ma_don_hang as ma_don,
                        dh.tong_khoi_luong_kg as khoi_luong_kg, dh.phi_van_chuyen,
                        dh.tien_tra_truoc, dh.trang_thai_don_hang as trang_thai,
                        dh.ngay_tao,
                        kg.ho_ten as sender_name
                 FROM don_hang dh
                 LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id = kg.id
                 ORDER BY dh.id DESC"
            );
            response(true, ['orders' => $res->fetch_all(MYSQLI_ASSOC)]);
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'update_status') {
                $id         = (int)($_POST['id'] ?? 0);
                $trang_thai = $_POST['trang_thai'] ?? '';
                $ghi_chu    = $_POST['ghi_chu'] ?? '';
                $stmt = $conn->prepare("UPDATE don_hang SET trang_thai_don_hang = ? WHERE id = ?");
                $stmt->bind_param("si", $trang_thai, $id);
                if ($stmt->execute()) {
                    // Ghi lịch sử
                    $actor = $_SESSION['ho_ten'] ?? 'Hệ thống';
                    $conn->query("INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu)
                                  VALUES ($id, '$trang_thai', '$actor', '$ghi_chu')");
                    response(true, null, 'Cập nhật trạng thái thành công');
                }
                response(false, null, $conn->error);
            } elseif ($op === 'cancel') {
                $id     = (int)($_POST['id'] ?? 0);
                $reason = $conn->real_escape_string($_POST['reason'] ?? '');
                $conn->query("UPDATE don_hang SET trang_thai_don_hang = 'da_huy' WHERE id = $id");
                $conn->query("INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu)
                              VALUES ($id, 'da_huy', '{$_SESSION['ho_ten']}', '$reason')");
                response(true, null, 'Hủy đơn hàng thành công');
            }
        }
        break;

    // ----------------------------------------------------------------
    // Đơn hàng — nhân viên tiếp nhận
    // ----------------------------------------------------------------
    case 'receptionist_orders':
        if ($method === 'GET') {
            $sql = "SELECT dh.id, dh.ma_don_hang as ma_don,
                           dh.tong_khoi_luong_kg as khoi_luong_kg, dh.phi_van_chuyen,
                           dh.tien_tra_truoc, dh.trang_thai_don_hang as trang_thai,
                           dh.ngay_tao,
                           kg.ho_ten as sender_name,
                           hd.trang_thai_thanh_toan as invoice_status
                    FROM don_hang dh
                    LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id = kg.id
                    LEFT JOIN hoa_don hd    ON dh.id = hd.don_hang_id
                    ORDER BY dh.id DESC";
            $res = $conn->query($sql);
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'update_status') {
                $id         = (int)($_POST['id'] ?? 0);
                $trang_thai = $_POST['trang_thai'] ?? '';
                $ghi_chu    = $conn->real_escape_string($_POST['ghi_chu'] ?? '');
                $actor      = $conn->real_escape_string($_SESSION['ho_ten'] ?? 'NV tiếp nhận');
                $conn->query("UPDATE don_hang SET trang_thai_don_hang = '$trang_thai' WHERE id = $id");
                $conn->query("INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu)
                              VALUES ($id, '$trang_thai', '$actor', '$ghi_chu')");
                response(true, null, 'Cập nhật thành công');
            } elseif ($op === 'cancel') {
                $id     = (int)($_POST['id'] ?? 0);
                $reason = $conn->real_escape_string($_POST['reason'] ?? '');
                $actor  = $conn->real_escape_string($_SESSION['ho_ten'] ?? 'NV tiếp nhận');
                $conn->query("UPDATE don_hang SET trang_thai_don_hang = 'da_huy' WHERE id = $id");
                $conn->query("INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu)
                              VALUES ($id, 'da_huy', '$actor', '$reason')");
                response(true, null, 'Hủy đơn thành công');
            } else {
                // Tạo đơn hàng mới
                $conn->begin_transaction();
                try {
                    $actor = $conn->real_escape_string($_SESSION['ho_ten'] ?? 'NV tiếp nhận');

                    // Upsert người gửi
                    $s_name  = $_POST['sender_name'] ?? '';
                    $s_phone = $_POST['sender_phone'] ?? '';
                    $s_cccd  = $_POST['sender_cccd'] ?? null;
                    $s_addr  = $_POST['sender_address'] ?? '';
                    $stmt = $conn->prepare(
                        "INSERT INTO khach_hang (ho_ten, so_dien_thoai, so_can_cuoc_cong_dan, dia_chi)
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
                    );
                    $stmt->bind_param("ssss", $s_name, $s_phone, $s_cccd, $s_addr);
                    $stmt->execute();
                    $sender_id = $conn->insert_id;

                    // Upsert người nhận
                    $r_name  = $_POST['receiver_name'] ?? '';
                    $r_phone = $_POST['receiver_phone'] ?? '';
                    $r_cccd  = $_POST['receiver_cccd'] ?? null;
                    $r_addr  = $_POST['receiver_address'] ?? '';
                    $stmt2 = $conn->prepare(
                        "INSERT INTO khach_hang (ho_ten, so_dien_thoai, so_can_cuoc_cong_dan, dia_chi)
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
                    );
                    $stmt2->bind_param("ssss", $r_name, $r_phone, $r_cccd, $r_addr);
                    $stmt2->execute();
                    $receiver_id = $conn->insert_id;

                    // Tạo đơn hàng
                    $ma_don     = "DH" . date("YmdHis") . rand(100, 999);
                    $ten_hang   = $_POST['ten_hang_hoa'] ?? '';
                    $kg         = (float)($_POST['khoi_luong_kg'] ?? 0);
                    $phi        = (float)($_POST['phi_van_chuyen'] ?? 0);
                    $kieu       = $_POST['kieu_thanh_toan'] ?? 'prepaid';
                    $tien_tt    = ($kieu === 'prepaid') ? $phi : (float)($_POST['tien_tra_truoc'] ?? 0);
                    $pttt       = $_POST['phuong_thuc_thanh_toan'] ?? 'tien_mat';

                    $stmt3 = $conn->prepare(
                        "INSERT INTO don_hang
                            (ma_don_hang, khach_hang_gui_id, khach_hang_nhan_id,
                             tong_khoi_luong_kg, phi_van_chuyen, tien_tra_truoc,
                             trang_thai_don_hang)
                         VALUES (?, ?, ?, ?, ?, ?, 'da_nhap_kho')"
                    );
                    $stmt3->bind_param("siiddd", $ma_don, $sender_id, $receiver_id, $kg, $phi, $tien_tt);
                    $stmt3->execute();
                    $don_hang_id = $conn->insert_id;

                    // Chi tiết hàng hóa
                    if ($ten_hang) {
                        $stmt4 = $conn->prepare(
                            "INSERT INTO chi_tiet_hang_hoa (don_hang_id, ten_mat_hang, khoi_luong_uoc_tinh_kg, ghi_chu)
                             VALUES (?, ?, ?, ?)"
                        );
                        $ghi_chu = $_POST['ghi_chu'] ?? '';
                        $stmt4->bind_param("isds", $don_hang_id, $ten_hang, $kg, $ghi_chu);
                        $stmt4->execute();
                    }

                    // Hóa đơn
                    $invoice_status = ($kieu === 'prepaid') ? 'da_thanh_toan' : 'chua_thanh_toan';
                    $pttt_enum = ($pttt === 'qr_code') ? 'qr_code' : 'tien_mat';
                    $con_lai = max(0, $phi - $tien_tt);
                    $stmt5 = $conn->prepare(
                        "INSERT INTO hoa_don (don_hang_id, so_tien_thu_ho, hinh_thuc_thanh_toan, trang_thai_thanh_toan)
                         VALUES (?, ?, ?, ?)"
                    );
                    $stmt5->bind_param("idss", $don_hang_id, $con_lai, $pttt_enum, $invoice_status);
                    $stmt5->execute();

                    // Lịch sử
                    $conn->query("INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu)
                                  VALUES ($don_hang_id, 'da_nhap_kho', '$actor', 'Tiếp nhận và nhập kho tại quầy')");

                    $conn->commit();
                    response(true, ['ma_don' => $ma_don, 'invoice_status' => $invoice_status], 'Tạo đơn hàng thành công');
                } catch (Exception $e) {
                    $conn->rollback();
                    response(false, null, 'Lỗi tạo đơn: ' . $e->getMessage());
                }
            }
        }
        break;

    // ----------------------------------------------------------------
    // Cập nhật trạng thái (generic)
    // ----------------------------------------------------------------
    case 'order_status':
        if ($method === 'POST') {
            $dh_id      = (int)($_POST['don_hang_id'] ?? 0);
            $trang_thai = $_POST['trang_thai'] ?? '';
            $ghi_chu    = $conn->real_escape_string($_POST['ghi_chu'] ?? '');
            $actor      = $conn->real_escape_string($_SESSION['ho_ten'] ?? 'Hệ thống');
            $stmt = $conn->prepare("UPDATE don_hang SET trang_thai_don_hang = ? WHERE id = ?");
            $stmt->bind_param("si", $trang_thai, $dh_id);
            if ($stmt->execute()) {
                $conn->query("INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_moi, nguoi_thuc_hien, ghi_chu)
                              VALUES ($dh_id, '$trang_thai', '$actor', '$ghi_chu')");
                response(true, ['id' => $dh_id]);
            }
            response(false, null, $conn->error);
        }
        break;

    // ----------------------------------------------------------------
    // Gán đơn vào đợt vận chuyển
    // ----------------------------------------------------------------
    case 'add_orders_to_shipment':
        if ($method === 'POST') {
            $dot_id       = (int)($_POST['dot_id'] ?? 0);
            $don_hang_ids = isset($_POST['don_hang_ids']) ? (array)$_POST['don_hang_ids'] : [];

            if (!$dot_id || empty($don_hang_ids)) {
                response(false, null, 'Thiếu thông tin bắt buộc');
            }

            $stmt = $conn->prepare("SELECT trang_thai_dot_van_chuyen FROM dot_van_chuyen WHERE id = ?");
            $stmt->bind_param("i", $dot_id);
            $stmt->execute();
            $dot = $stmt->get_result()->fetch_assoc();

            if (!$dot) response(false, null, 'Không tìm thấy đợt vận chuyển');
            if ($dot['trang_thai_dot_van_chuyen'] !== 'cho_khoi_hanh') {
                response(false, null, 'Chỉ có thể gán đơn vào đợt chưa khởi hành');
            }

            foreach ($don_hang_ids as $dh_id) {
                $dh_id = (int)$dh_id;
                $conn->query("INSERT INTO chi_tiet_dot_van_chuyen (dot_van_chuyen_id, don_hang_id, trang_thai_trong_dot)
                              VALUES ($dot_id, $dh_id, 'da_xep_len_xe')");
                $conn->query("UPDATE don_hang SET trang_thai_don_hang = 'dang_van_chuyen' WHERE id = $dh_id");
            }
            response(true, null, 'Gán đơn hàng thành công');
        }
        break;

    // ----------------------------------------------------------------
    // Hồ sơ user đang đăng nhập
    // ----------------------------------------------------------------
    case 'my_profile':
        if ($method === 'GET') {
            requireLogin();
            $uid  = (int)$_SESSION['user_id'];
            $sdt  = $_SESSION['so_dien_thoai'] ?? '';

            // Lấy thông tin từ nguoi_dung
            $stmt = $conn->prepare(
                "SELECT id, ho_ten, so_dien_thoai, trang_thai, ngay_tao as created_at FROM nguoi_dung WHERE id = ? LIMIT 1"
            );
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            if (!$row) {
                response(false, null, 'Không tìm thấy thông tin người dùng');
            }

            // Lấy thêm so_cccd, dia_chi từ bảng khach_hang
            if ($sdt) {
                $stmt2 = $conn->prepare(
                    "SELECT so_cccd, dia_chi FROM khach_hang WHERE so_dien_thoai = ? LIMIT 1"
                );
                $stmt2->bind_param("s", $sdt);
                $stmt2->execute();
                $kh = $stmt2->get_result()->fetch_assoc();
                if ($kh) {
                    $row['so_cccd'] = $kh['so_cccd'];
                    $row['dia_chi'] = $kh['dia_chi'];
                }
            }

            response(true, $row);
        }
        break;

    // ----------------------------------------------------------------
    // Đơn hàng của user đang đăng nhập (theo SĐT)
    // ----------------------------------------------------------------
    case 'my_orders':
        if ($method === 'GET') {
            requireLogin();
            $sdt = $_SESSION['so_dien_thoai'] ?? '';
            if (!$sdt) response(false, null, 'Không xác định được số điện thoại');

            $limit  = max(1, min(100, (int)($_GET['limit']  ?? 50)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));

            $stmt = $conn->prepare(
                "SELECT dh.id, dh.ma_don_hang as ma_don,
                        dh.tong_khoi_luong_kg as khoi_luong_kg, dh.phi_van_chuyen, dh.tien_tra_truoc,
                        dh.trang_thai_don_hang as trang_thai,
                        DATE_FORMAT(dh.ngay_tao,'%d/%m/%Y %H:%i') as ngay_tao,
                        kg.ho_ten as nguoi_gui, kn.ho_ten as nguoi_nhan,
                        kn.so_dien_thoai as sdt_nhan, kn.dia_chi as dia_chi_nhan
                 FROM don_hang dh
                 LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
                 LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
                 WHERE kg.so_dien_thoai = ? OR kn.so_dien_thoai = ?
                 ORDER BY dh.id DESC LIMIT ? OFFSET ?"
            );
            $stmt->bind_param("ssii", $sdt, $sdt, $limit, $offset);
            $stmt->execute();
            $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $stmtC = $conn->prepare(
                "SELECT COUNT(*) as total FROM don_hang dh
                 LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id  = kg.id
                 LEFT JOIN khach_hang kn ON dh.khach_hang_nhan_id = kn.id
                 WHERE kg.so_dien_thoai = ? OR kn.so_dien_thoai = ?"
            );
            $stmtC->bind_param("ss", $sdt, $sdt);
            $stmtC->execute();
            $total = (int)$stmtC->get_result()->fetch_assoc()['total'];

            response(true, ['orders' => $orders, 'total' => $total]);
        }
        break;

    // ----------------------------------------------------------------
    // Cập nhật hồ sơ khách hàng đang đăng nhập
    // ----------------------------------------------------------------
    case 'update_profile':
        if ($method === 'POST') {
            requireLogin();
            $uid     = (int)$_SESSION['user_id'];
            $ho_ten  = trim($_POST['ho_ten'] ?? '');
            $so_cccd = trim($_POST['so_cccd'] ?? '');
            $dia_chi = trim($_POST['dia_chi'] ?? '');

            if ($ho_ten === '') {
                response(false, null, 'Họ tên không được để trống');
            }

            // Cập nhật bảng nguoi_dung
            $stmt = $conn->prepare("UPDATE nguoi_dung SET ho_ten = ? WHERE id = ?");
            $stmt->bind_param("si", $ho_ten, $uid);
            if (!$stmt->execute()) {
                response(false, null, 'Lỗi cập nhật: ' . $conn->error);
            }

            // Cập nhật bảng khach_hang theo so_dien_thoai
            $sdt = $_SESSION['so_dien_thoai'] ?? '';
            if ($sdt) {
                $stmt2 = $conn->prepare(
                    "UPDATE khach_hang SET ho_ten = ?, so_cccd = ?, dia_chi = ? WHERE so_dien_thoai = ?"
                );
                $stmt2->bind_param("ssss", $ho_ten, $so_cccd, $dia_chi, $sdt);
                $stmt2->execute();
            }

            // Cập nhật session
            $_SESSION['ho_ten'] = $ho_ten;

            response(true, ['ho_ten' => $ho_ten], 'Cập nhật hồ sơ thành công');
        }
        break;

    // ----------------------------------------------------------------
    // Đổi mật khẩu
    // ----------------------------------------------------------------
    case 'change_password':
        if ($method === 'POST') {
            requireLogin();
            $uid      = (int)$_SESSION['user_id'];
            $mat_khau_cu  = $_POST['mat_khau_cu']  ?? '';
            $mat_khau_moi = $_POST['mat_khau_moi']  ?? '';
            $xac_nhan     = $_POST['xac_nhan']       ?? '';

            if ($mat_khau_cu === '' || $mat_khau_moi === '' || $xac_nhan === '') {
                response(false, null, 'Vui lòng điền đầy đủ thông tin');
            }
            if (strlen($mat_khau_moi) < 6) {
                response(false, null, 'Mật khẩu mới phải có ít nhất 6 ký tự');
            }
            if ($mat_khau_moi !== $xac_nhan) {
                response(false, null, 'Xác nhận mật khẩu không khớp');
            }

            // Lấy mật khẩu hiện tại
            $stmt = $conn->prepare("SELECT mat_khau FROM nguoi_dung WHERE id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) response(false, null, 'Không tìm thấy tài khoản');

            $dbPass = (string)($row['mat_khau'] ?? '');
            $valid  = false;
            if ($dbPass !== '') {
                if (password_verify($mat_khau_cu, $dbPass))              $valid = true;
                elseif ($dbPass === md5($mat_khau_cu))                    $valid = true;
                elseif (hash_equals($dbPass, (string)$mat_khau_cu))      $valid = true;
            }
            if (!$valid) {
                response(false, null, 'Mật khẩu hiện tại không đúng');
            }

            $newHash = password_hash($mat_khau_moi, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("UPDATE nguoi_dung SET mat_khau = ? WHERE id = ?");
            $stmt2->bind_param("si", $newHash, $uid);
            $stmt2->execute() ? response(true, null, 'Đổi mật khẩu thành công') : response(false, null, $conn->error);
        }
        break;
}
