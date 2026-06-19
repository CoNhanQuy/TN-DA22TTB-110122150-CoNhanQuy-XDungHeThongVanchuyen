<?php
// File này đã deprecated — router chính là backend/index.php
// Redirect về index.php để tránh nhầm lẫn
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action) {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header("Location: /DATN/backend/index.php" . ($qs ? "?$qs" : ''));
    exit();
}
http_response_code(410);
die(json_encode(['success' => false, 'message' => 'Endpoint này đã deprecated. Dùng /backend/index.php']));


function response($success, $data = null, $message = '') {
    // Xóa tất cả output buffers đang mở
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'track':
        if ($method === 'GET') {
            $code = $_GET['code'] ?? '';
            $stmt = $conn->prepare("SELECT dh.*, kg.ho_ten as ng_gui, kn.ho_ten as ng_nhan, kn.so_dien_thoai, kn.dia_chi FROM don_hang dh LEFT JOIN khach_hang kg ON dh.khach_gui_id = kg.id LEFT JOIN khach_hang kn ON dh.khach_nhan_id = kn.id WHERE dh.ma_don = ?");
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $order = $res->fetch_assoc();
                $order['code'] = $order['ma_don'];
                $order['product'] = $order['ten_hang_hoa'];
                $order['weight'] = $order['khoi_luong_kg'];
                $order['fee'] = $order['phi_van_chuyen'];
                $order['prepaid'] = $order['tien_tra_truoc'];
                $order['remaining'] = max(0, $order['phi_van_chuyen'] - $order['tien_tra_truoc']);
                $order['receiver'] = $order['ng_nhan'];
                $order['phone'] = $order['so_dien_thoai'];
                $order['address'] = $order['dia_chi'];
                $order['status'] = $order['trang_thai'];
                response(true, $order);
            } else {
                response(false, null, 'Không tìm thấy đơn hàng');
            }
        }
        break;

    case 'users':
        if ($method === 'GET') {
            $res = $conn->query("SELECT u.id, u.ho_ten, u.so_dien_thoai, u.trang_thai, u.created_at, r.ma_vai_tro as vai_tro, NULL as email FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.id");
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $conn->query("DELETE FROM users WHERE id = $id");
                response(true);
            } else {
                $ho_ten = $_POST['ho_ten'] ?? '';
                $sdt = $_POST['so_dien_thoai'] ?? '';
                $pass = password_hash($_POST['mat_khau'] ?? '123456', PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (ho_ten, so_dien_thoai, mat_khau) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $ho_ten, $sdt, $pass);
                if ($stmt->execute()) {
                    response(true, ['id' => $conn->insert_id]);
                } else {
                    response(false, null, $conn->error);
                }
            }
        }
        break;

    case 'vehicles':
        if ($method === 'GET') {
            $res = $conn->query("SELECT * FROM xe");
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $conn->query("DELETE FROM xe WHERE id = $id");
                response(true);
            } else {
                $bs = $_POST['bien_so'] ?? '';
                $tt = $_POST['trong_tai_kg'] ?? 0;
                $stmt = $conn->prepare("INSERT INTO xe (bien_so, trong_tai_kg) VALUES (?, ?)");
                $stmt->bind_param("sd", $bs, $tt);
                $stmt->execute() ? response(true, ['id' => $conn->insert_id]) : response(false, null, $conn->error);
            }
        }
        break;

    case 'routes':
        if ($method === 'GET') {
            $res = $conn->query("SELECT * FROM tuyen_duong");
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $conn->query("DELETE FROM tuyen_duong WHERE id = $id");
                response(true);
            } else {
                $ten = $_POST['ten_tuyen'] ?? '';
                $di = $_POST['diem_di'] ?? '';
                $den = $_POST['diem_den'] ?? '';
                $km = $_POST['quang_duong_km'] ?? 0;
                $phut = $_POST['thoi_gian_du_kien_phut'] ?? 0;
                $stmt = $conn->prepare("INSERT INTO tuyen_duong (ten_tuyen, diem_di, diem_den, quang_duong_km, thoi_gian_du_kien_phut) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdi", $ten, $di, $den, $km, $phut);
                $stmt->execute() ? response(true, ['id' => $conn->insert_id]) : response(false, null, $conn->error);
            }
        }
        break;

    case 'delivery_persons':
        if ($method === 'GET') {
            $sql = "SELECT n.id, n.ma_shipper as ma_nguoi_giao, u.ho_ten, u.so_dien_thoai, n.so_cccd, n.chi_nhanh_id, n.trang_thai 
                    FROM nguoi_giao_hang n 
                    JOIN users u ON n.user_id = u.id";
            $res = $conn->query($sql);
            if ($res === false) {
                response(false, null, $conn->error);
            }
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $res = $conn->query("SELECT user_id FROM nguoi_giao_hang WHERE id = $id");
                if ($row = $res->fetch_assoc()) {
                    $user_id = (int)$row['user_id'];
                    $conn->query("DELETE FROM nguoi_giao_hang WHERE id = $id");
                    $conn->query("DELETE FROM users WHERE id = $user_id");
                }
                response(true);
            } elseif ($op === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $ma = $_POST['ma_nguoi_giao'] ?? '';
                $ten = $_POST['ho_ten'] ?? '';
                $sdt = $_POST['so_dien_thoai'] ?? '';
                $cccd = $_POST['so_cccd'] ?? '';
                $chi_nhanh_id = empty($_POST['chi_nhanh_id']) ? null : (int)$_POST['chi_nhanh_id'];
                $trang_thai = (int)($_POST['trang_thai'] ?? 1);

                $res = $conn->query("SELECT user_id FROM nguoi_giao_hang WHERE id = $id");
                if ($row = $res->fetch_assoc()) {
                    $user_id = $row['user_id'];
                    $stmtUser = $conn->prepare("UPDATE users SET ho_ten=?, so_dien_thoai=?, trang_thai=? WHERE id=?");
                    $stmtUser->bind_param("ssii", $ten, $sdt, $trang_thai, $user_id);
                    $stmtUser->execute();

                    $stmtNgh = $conn->prepare("UPDATE nguoi_giao_hang SET ma_shipper=?, so_cccd=?, chi_nhanh_id=?, trang_thai=? WHERE id=?");
                    $stmtNgh->bind_param("ssiii", $ma, $cccd, $chi_nhanh_id, $trang_thai, $id);
                    $stmtNgh->execute();
                    response(true);
                } else {
                    response(false, null, 'Không tìm thấy người giao hàng');
                }
            } else {
                $ma = $_POST['ma_nguoi_giao'] ?? '';
                $ten = $_POST['ho_ten'] ?? '';
                $sdt = $_POST['so_dien_thoai'] ?? '';
                $cccd = $_POST['so_cccd'] ?? '';
                $chi_nhanh_id = empty($_POST['chi_nhanh_id']) ? null : (int)$_POST['chi_nhanh_id'];
                $trang_thai = (int)($_POST['trang_thai'] ?? 1);

                $pass = password_hash('123456', PASSWORD_DEFAULT);
                $stmtUser = $conn->prepare("INSERT INTO users (ho_ten, so_dien_thoai, mat_khau, trang_thai) VALUES (?, ?, ?, ?)");
                $stmtUser->bind_param("sssi", $ten, $sdt, $pass, $trang_thai);

                if ($stmtUser->execute()) {
                    $user_id = $conn->insert_id;
                    $stmtRole = $conn->query("SELECT id FROM roles WHERE ma_vai_tro = 'shipper'");
                    if ($roleRow = $stmtRole->fetch_assoc()) {
                        $role_id = $roleRow['id'];
                        $conn->query("INSERT INTO user_roles (user_id, role_id) VALUES ($user_id, $role_id)");
                    }

                    $stmtNgh = $conn->prepare("INSERT INTO nguoi_giao_hang (user_id, ma_shipper, so_cccd, chi_nhanh_id, trang_thai) VALUES (?, ?, ?, ?, ?)");
                    $stmtNgh->bind_param("issii", $user_id, $ma, $cccd, $chi_nhanh_id, $trang_thai);
                    $stmtNgh->execute();
                    response(true, ['id' => $conn->insert_id]);
                } else {
                    response(false, null, $conn->error);
                }
            }
        }
        break;

    case 'pricing':
        if ($method === 'GET') {
            $res = $conn->query("SELECT * FROM danh_muc WHERE loai_danh_muc = 'bang_phi'");
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $val = json_decode($row['gia_tri'], true) ?? [];
                $val['id'] = $row['id'];
                $data[] = $val;
            }
            response(true, $data);
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $conn->query("DELETE FROM danh_muc WHERE id = $id");
                response(true);
            } else {
                $tu = $_POST['tu_kg'] ?? 0;
                $den = $_POST['den_kg'] ?? 0;
                $phi = $_POST['phi_co_ban'] ?? 0;
                $val = json_encode(['tu_kg' => $tu, 'den_kg' => $den, 'phi_co_ban' => $phi]);
                $stmt = $conn->prepare("INSERT INTO danh_muc (loai_danh_muc, ten_danh_muc, gia_tri) VALUES ('bang_phi', 'Bảng phí', ?)");
                $stmt->bind_param("s", $val);
                $stmt->execute() ? response(true, ['id' => $conn->insert_id]) : response(false, null, $conn->error);
            }
        }
        break;

    case 'quote':
        if ($method === 'GET') {
            $weight = (float)($_GET['weight'] ?? 0);
            $res = $conn->query("SELECT * FROM danh_muc WHERE loai_danh_muc = 'bang_phi'");
            $fee = 0;
            while ($row = $res->fetch_assoc()) {
                $val = json_decode($row['gia_tri'], true) ?? [];
                if ($weight > ($val['tu_kg'] ?? 0) && $weight <= ($val['den_kg'] ?? INF)) {
                    $fee = $val['phi_co_ban'] ?? 0;
                    break;
                }
            }
            if ($fee == 0 && $res->num_rows > 0) {
                 $fee = 30000; // default minimum fee if not matched but pricing exists
            }
            if ($fee > 0) {
                response(true, ['estimated_fee' => $fee]);
            } else {
                response(false, null, 'Không tìm thấy bảng phí phù hợp');
            }
        }
        break;

    case 'goods_types':
        if ($method === 'GET') {
            $res = $conn->query("SELECT * FROM danh_muc WHERE loai_danh_muc = 'loai_hang'");
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $conn->query("DELETE FROM danh_muc WHERE id = $id AND loai_danh_muc = 'loai_hang'");
                response(true, null, 'Xóa loại hàng thành công');
            } elseif ($op === 'create') {
                $ten = $_POST['ten_danh_muc'] ?? '';
                $mo_ta = $_POST['mo_ta'] ?? '';
                $stmt = $conn->prepare("INSERT INTO danh_muc (loai_danh_muc, ten_danh_muc, mo_ta, trang_thai) VALUES ('loai_hang', ?, ?, 1)");
                $stmt->bind_param("ss", $ten, $mo_ta);
                if ($stmt->execute()) {
                    response(true, ['id' => $conn->insert_id], 'Thêm loại hàng thành công');
                } else {
                    response(false, null, $conn->error);
                }
            }
        }
        break;

    case 'receptionist_orders':
        if ($method === 'GET') {
            $sql = "SELECT dh.*, kg.ho_ten as sender_name, hd.trang_thai as invoice_status 
                    FROM don_hang dh 
                    LEFT JOIN khach_hang kg ON dh.khach_gui_id = kg.id
                    LEFT JOIN hoa_don hd ON dh.id = hd.don_hang_id 
                    ORDER BY dh.id DESC";
            $res = $conn->query($sql);
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'update_status') {
                $id = (int)($_POST['id'] ?? 0);
                $trang_thai = $_POST['trang_thai'] ?? '';
                $ghi_chu = $_POST['ghi_chu'] ?? '';
                $stmt = $conn->prepare("UPDATE don_hang SET trang_thai = ?, ghi_chu = ? WHERE id = ?");
                $stmt->bind_param("ssi", $trang_thai, $ghi_chu, $id);
                $stmt->execute() ? response(true, null, 'Cập nhật trạng thái thành công') : response(false, null, $conn->error);
            } elseif ($op === 'cancel') {
                $id = (int)($_POST['id'] ?? 0);
                $reason = $_POST['reason'] ?? '';
                $stmt = $conn->prepare("UPDATE don_hang SET trang_thai = 'da_huy', ghi_chu = ? WHERE id = ?");
                $stmt->bind_param("si", $reason, $id);
                $stmt->execute() ? response(true, null, 'Hủy đơn hàng thành công') : response(false, null, $conn->error);
            } else {
                // Create order logic
                $conn->begin_transaction();
                try {
                    // Insert or update sender
                    $sender_name = $_POST['sender_name'] ?? '';
                    $sender_phone = $_POST['sender_phone'] ?? '';
                    $sender_cccd = $_POST['sender_cccd'] ?? null;
                    $sender_address = $_POST['sender_address'] ?? '';
                    
                    $stmt = $conn->prepare("INSERT INTO khach_hang (ho_ten, so_dien_thoai, so_cccd, dia_chi) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                    $stmt->bind_param("ssss", $sender_name, $sender_phone, $sender_cccd, $sender_address);
                    $stmt->execute();
                    $sender_id = $conn->insert_id;

                    // Insert or update receiver
                    $receiver_name = $_POST['receiver_name'] ?? '';
                    $receiver_phone = $_POST['receiver_phone'] ?? '';
                    $receiver_cccd = $_POST['receiver_cccd'] ?? null;
                    $receiver_address = $_POST['receiver_address'] ?? '';
                    
                    $stmt = $conn->prepare("INSERT INTO khach_hang (ho_ten, so_dien_thoai, so_cccd, dia_chi) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                    $stmt->bind_param("ssss", $receiver_name, $receiver_phone, $receiver_cccd, $receiver_address);
                    $stmt->execute();
                    $receiver_id = $conn->insert_id;

                    // Insert order
                    $ma_don = "DH" . date("YmdHis") . rand(100, 999);
                    $ten_hang = $_POST['ten_hang_hoa'] ?? '';
                    $kg = (float)($_POST['khoi_luong_kg'] ?? 0);
                    $phi = (float)($_POST['phi_van_chuyen'] ?? 0);
                    $pttt = $_POST['phuong_thuc_thanh_toan'] ?? 'tien_mat';
                    $trang_thai = 'da_nhap_kho'; // Trạng thái mặc định khi tiếp nhận tại quầy
                    
                    $kieu = $_POST['kieu_thanh_toan'] ?? 'prepaid';
                    $tien_tra_truoc = 0;
                    if ($kieu === 'prepaid') {
                        $tien_tra_truoc = $phi;
                    } elseif ($kieu === 'partial') {
                        $tien_tra_truoc = (float)($_POST['tien_tra_truoc'] ?? 0);
                    }
                    
                    $stmt = $conn->prepare("INSERT INTO don_hang (ma_don, ten_hang_hoa, khoi_luong_kg, phi_van_chuyen, tien_tra_truoc, khach_gui_id, khach_nhan_id, phuong_thuc_thanh_toan, trang_thai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssdddiiss", $ma_don, $ten_hang, $kg, $phi, $tien_tra_truoc, $sender_id, $receiver_id, $pttt, $trang_thai);
                    $stmt->execute();
                    $don_hang_id = $conn->insert_id;

                    // Handle payment / invoice
                    $invoice_status = ($kieu === 'prepaid') ? 'da_thanh_toan' : 'chua_thanh_toan';
                    
                    $stmt = $conn->prepare("INSERT INTO hoa_don (don_hang_id, so_tien, phuong_thuc, trang_thai) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("idss", $don_hang_id, $phi, $pttt, $invoice_status);
                    $stmt->execute();

                    $conn->commit();
                    response(true, ['ma_don' => $ma_don, 'invoice_status' => $invoice_status], 'Tạo đơn hàng thành công');
                } catch (Exception $e) {
                    $conn->rollback();
                    response(false, null, 'Lỗi tạo đơn: ' . $e->getMessage());
                }
            }
        }
        break;

    case 'orders':
        if ($method === 'GET') {
            $res = $conn->query("SELECT * FROM don_hang ORDER BY id DESC");
            response(true, ['orders' => $res->fetch_all(MYSQLI_ASSOC)]);
        } elseif ($method === 'POST') {
            $ten = $_POST['ten_hang_hoa'] ?? '';
            $kg = $_POST['khoi_luong_kg'] ?? 0;
            $phi = $_POST['phi_van_chuyen'] ?? 0;
            $ng_gui = $_POST['nguoi_gui_id'] ?? 1;
            $ng_nhan = $_POST['nguoi_nhan_id'] ?? 1;
            $pttt = $_POST['phuong_thuc_thanh_toan'] ?? 'tien_mat';
            $ma_don = "DH" . date("YmdHis");
            
            $stmt = $conn->prepare("INSERT INTO don_hang (ma_don, ten_hang_hoa, khoi_luong_kg, phi_van_chuyen, khach_gui_id, khach_nhan_id, phuong_thuc_thanh_toan) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddiis", $ma_don, $ten, $kg, $phi, $ng_gui, $ng_nhan, $pttt);
            if ($stmt->execute()) {
                response(true, ['id' => $conn->insert_id, 'ma_don' => $ma_don], "Tạo đơn hàng thành công");
            } else {
                response(false, null, $conn->error);
            }
        }
        break;

    case 'customers':
        if ($method === 'GET') {
            $res = $conn->query("SELECT * FROM khach_hang");
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        }
        break;

    case 'statistics':
        if ($method === 'GET') {
            $from = $_GET['from'] ?? '';
            $to   = $_GET['to']   ?? '';

            $where_date = '';
            if ($from && $to) {
                $from_safe = $conn->real_escape_string($from);
                $to_safe   = $conn->real_escape_string($to);
                $where_date = "WHERE DATE(ngay_tao) BETWEEN '$from_safe' AND '$to_safe'";
            } elseif ($from) {
                $from_safe = $conn->real_escape_string($from);
                $where_date = "WHERE DATE(ngay_tao) >= '$from_safe'";
            } elseif ($to) {
                $to_safe = $conn->real_escape_string($to);
                $where_date = "WHERE DATE(ngay_tao) <= '$to_safe'";
            }

            $stats = [
                'total_orders'    => $conn->query("SELECT COUNT(*) as c FROM don_hang $where_date")->fetch_assoc()['c'],
                'success_orders'  => $conn->query("SELECT COUNT(*) as c FROM don_hang " . ($where_date ? $where_date . " AND" : "WHERE") . " trang_thai IN ('hoan_tat','da_giao_hang')")->fetch_assoc()['c'],
                'total_revenue'   => $conn->query("SELECT COALESCE(SUM(phi_van_chuyen),0) as s FROM don_hang " . ($where_date ? $where_date . " AND" : "WHERE") . " trang_thai IN ('hoan_tat','da_giao_hang')")->fetch_assoc()['s'],
                'total_drivers'   => $conn->query("SELECT COUNT(*) as c FROM tai_xe")->fetch_assoc()['c'],
                'total_vehicles'  => $conn->query("SELECT COUNT(*) as c FROM xe")->fetch_assoc()['c'],
                'total_shipments' => $conn->query("SELECT COUNT(*) as c FROM dot_van_chuyen")->fetch_assoc()['c'],
            ];

            // Orders by day
            $res_obd = $conn->query("SELECT DATE(ngay_tao) as date, COUNT(*) as total_orders, SUM(trang_thai IN ('hoan_tat','da_giao_hang')) as success_orders FROM don_hang $where_date GROUP BY DATE(ngay_tao) ORDER BY date DESC LIMIT 30");
            $stats['orders_by_day'] = $res_obd ? $res_obd->fetch_all(MYSQLI_ASSOC) : [];

            // Revenue by day
            $status_filter = "trang_thai IN ('hoan_tat','da_giao_hang')";
            if ($where_date) {
                $where_rev = $where_date . " AND $status_filter";
            } else {
                $where_rev = "WHERE $status_filter";
            }
            $res_rbd = $conn->query("SELECT DATE(ngay_tao) as date, COALESCE(SUM(phi_van_chuyen),0) as revenue FROM don_hang $where_rev GROUP BY DATE(ngay_tao) ORDER BY date DESC LIMIT 30");
            $stats['revenue_by_day'] = $res_rbd ? $res_rbd->fetch_all(MYSQLI_ASSOC) : [];

            response(true, $stats);
        }
        break;

    case 'pending_orders':
        if ($method === 'GET') {
            $sql = "SELECT dh.id, dh.ma_don, dh.ten_hang_hoa, dh.khoi_luong_kg, dh.trang_thai,
                           kg.ho_ten as sender_name, kn.ho_ten as receiver_name,
                           kn.dia_chi as receiver_address, kn.so_dien_thoai as receiver_phone
                    FROM don_hang dh
                    LEFT JOIN khach_hang kg ON dh.khach_gui_id = kg.id
                    LEFT JOIN khach_hang kn ON dh.khach_nhan_id = kn.id
                    WHERE dh.trang_thai IN ('cho_tiep_nhan', 'da_nhap_kho')
                    ORDER BY dh.ngay_tao ASC";
            $res = $conn->query($sql);
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        }
        break;

    case 'orders_by_destination':
        // Lấy đơn hàng theo điểm đến của tuyến đường (khớp địa chỉ người nhận)
        if ($method === 'GET') {
            $tuyen_id = (int)($_GET['tuyen_id'] ?? 0);
            if (!$tuyen_id) response(false, null, 'Thiếu tuyen_id');

            $stmt = $conn->prepare("SELECT diem_den FROM tuyen_duong WHERE id = ?");
            $stmt->bind_param("i", $tuyen_id);
            $stmt->execute();
            $tuyen = $stmt->get_result()->fetch_assoc();
            if (!$tuyen) response(false, null, 'Không tìm thấy tuyến đường');

            $diem_den = $tuyen['diem_den'];
            $like_pattern = '%' . $diem_den . '%';

            $sql = "SELECT dh.id, dh.ma_don, dh.ten_hang_hoa, dh.khoi_luong_kg, dh.trang_thai,
                           kg.ho_ten as sender_name, kn.ho_ten as receiver_name,
                           kn.dia_chi as receiver_address, kn.so_dien_thoai as receiver_phone
                    FROM don_hang dh
                    LEFT JOIN khach_hang kg ON dh.khach_gui_id = kg.id
                    LEFT JOIN khach_hang kn ON dh.khach_nhan_id = kn.id
                    WHERE dh.trang_thai IN ('cho_tiep_nhan', 'da_nhap_kho')
                      AND kn.dia_chi LIKE ?
                    ORDER BY dh.ngay_tao ASC";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("s", $like_pattern);
            $stmt2->execute();
            $matched = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

            // Trả về cả danh sách tất cả đơn để hiển thị phần không khớp
            $sql_all = "SELECT dh.id, dh.ma_don, dh.ten_hang_hoa, dh.khoi_luong_kg, dh.trang_thai,
                               kg.ho_ten as sender_name, kn.ho_ten as receiver_name,
                               kn.dia_chi as receiver_address, kn.so_dien_thoai as receiver_phone
                        FROM don_hang dh
                        LEFT JOIN khach_hang kg ON dh.khach_gui_id = kg.id
                        LEFT JOIN khach_hang kn ON dh.khach_nhan_id = kn.id
                        WHERE dh.trang_thai IN ('cho_tiep_nhan', 'da_nhap_kho')
                        ORDER BY dh.ngay_tao ASC";
            $all = $conn->query($sql_all)->fetch_all(MYSQLI_ASSOC);

            response(true, [
                'diem_den' => $diem_den,
                'matched' => $matched,
                'all' => $all
            ]);
        }
        break;

    case 'shipment_details':
        if ($method === 'GET') {
            $dot_id = (int)($_GET['id'] ?? 0);
            if (!$dot_id) response(false, null, 'Thiếu id đợt vận chuyển');

            $stmt = $conn->prepare(
                "SELECT d.*, t.ten_tuyen, t.diem_den, u.ho_ten as tai_xe, x.bien_so,
                        (SELECT COUNT(*) FROM chi_tiet_dot cd WHERE cd.dot_id = d.id) as so_don,
                        (SELECT COALESCE(SUM(dh2.khoi_luong_kg),0) FROM chi_tiet_dot cd2 JOIN don_hang dh2 ON cd2.don_hang_id = dh2.id WHERE cd2.dot_id = d.id) as tong_khoi_luong
                 FROM dot_van_chuyen d
                 LEFT JOIN tuyen_duong t ON d.tuyen_id = t.id
                 LEFT JOIN tai_xe tx ON d.tai_xe_id = tx.id
                 LEFT JOIN users u ON tx.user_id = u.id
                 LEFT JOIN xe x ON d.xe_id = x.id
                 WHERE d.id = ?"
            );
            $stmt->bind_param("i", $dot_id);
            $stmt->execute();
            $shipment = $stmt->get_result()->fetch_assoc();
            if (!$shipment) response(false, null, 'Không tìm thấy đợt vận chuyển');

            $stmt2 = $conn->prepare(
                "SELECT dh.id, dh.ma_don, dh.ten_hang_hoa, dh.khoi_luong_kg,
                        kg.ho_ten as sender_name, kn.ho_ten as receiver_name, kn.dia_chi as receiver_address
                 FROM chi_tiet_dot cd
                 JOIN don_hang dh ON cd.don_hang_id = dh.id
                 LEFT JOIN khach_hang kg ON dh.khach_gui_id = kg.id
                 LEFT JOIN khach_hang kn ON dh.khach_nhan_id = kn.id
                 WHERE cd.dot_id = ?"
            );
            $stmt2->bind_param("i", $dot_id);
            $stmt2->execute();
            $orders = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

            response(true, ['shipment' => $shipment, 'orders' => $orders]);
        }
        break;

    case 'defer_expired_shipments':
        // Dời các đơn hàng của đợt đã quá giờ sang đợt mới hoặc trả về trạng thái chờ
        if ($method === 'POST') {
            $dot_id = (int)($_POST['dot_id'] ?? 0);
            if (!$dot_id) response(false, null, 'Thiếu dot_id');

            // Kiểm tra đợt tồn tại và đã quá giờ
            $stmt = $conn->prepare("SELECT * FROM dot_van_chuyen WHERE id = ?");
            $stmt->bind_param("i", $dot_id);
            $stmt->execute();
            $dot = $stmt->get_result()->fetch_assoc();
            if (!$dot) response(false, null, 'Không tìm thấy đợt vận chuyển');

            $ngay_gio = new DateTime($dot['ngay_gio_bat_dau']);
            $now = new DateTime();
            if ($ngay_gio > $now && $dot['trang_thai'] === 'chua_khoi_hanh') {
                response(false, null, 'Đợt vận chuyển chưa đến giờ khởi hành');
            }

            // Lấy các đơn chưa giao trong đợt (còn trong trạng thái chờ/đã xếp)
            $stmt2 = $conn->prepare(
                "SELECT cd.don_hang_id FROM chi_tiet_dot cd
                 WHERE cd.dot_id = ? AND cd.trang_thai_trong_dot IN ('da_xep_len_xe', 'dang_van_chuyen')"
            );
            $stmt2->bind_param("i", $dot_id);
            $stmt2->execute();
            $rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            $don_ids = array_column($rows, 'don_hang_id');

            if (empty($don_ids)) {
                response(true, ['deferred' => 0], 'Không có đơn nào cần dời');
            }

            // Đặt đợt hiện tại thành hủy/hoàn thành nếu chưa chạy
            if ($dot['trang_thai'] === 'chua_khoi_hanh') {
                $conn->query("UPDATE dot_van_chuyen SET trang_thai = 'huy' WHERE id = $dot_id");
            }

            // Trả các đơn về trạng thái chờ điều phối
            foreach ($don_ids as $dh_id) {
                $dh_id = (int)$dh_id;
                $conn->query("UPDATE don_hang SET trang_thai = 'da_nhap_kho' WHERE id = $dh_id");
                $conn->query("UPDATE chi_tiet_dot SET trang_thai_trong_dot = 'tra_lai' WHERE dot_id = $dot_id AND don_hang_id = $dh_id");
            }

            response(true, ['deferred' => count($don_ids), 'don_ids' => $don_ids],
                count($don_ids) . ' đơn hàng đã được dời về hàng chờ điều phối');
        }
        break;

    case 'shipments':
        if ($method === 'GET') {
            $sql = "SELECT d.*, t.ten_tuyen, t.diem_den, tx.user_id, u.ho_ten as tai_xe, x.bien_so,
                           (SELECT COUNT(*) FROM chi_tiet_dot cd WHERE cd.dot_id = d.id) as so_don
                    FROM dot_van_chuyen d
                    LEFT JOIN tuyen_duong t ON d.tuyen_id = t.id
                    LEFT JOIN tai_xe tx ON d.tai_xe_id = tx.id
                    LEFT JOIN users u ON tx.user_id = u.id
                    LEFT JOIN xe x ON d.xe_id = x.id
                    ORDER BY d.ngay_gio_bat_dau DESC";
            $res = $conn->query($sql);
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $tuyen = (int)($_POST['tuyen_id'] ?? 0);
            $tx = (int)($_POST['tai_xe_id'] ?? 0);
            $xe = (int)($_POST['xe_id'] ?? 0);
            $ngay = $_POST['ngay_gio_bat_dau'] ?? date('Y-m-d H:i:s');
            $ghi_chu = $_POST['ghi_chu'] ?? '';
            $ma_dot = "DVC" . date("YmdHis");
            $don_hang_ids = isset($_POST['don_hang_ids']) ? (array)$_POST['don_hang_ids'] : [];

            if (!$tuyen || !$tx || !$xe) {
                response(false, null, 'Thiếu thông tin bắt buộc (tuyến, tài xế, xe)');
            }

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO dot_van_chuyen (ma_dot, tuyen_id, tai_xe_id, xe_id, ngay_gio_bat_dau, ghi_chu) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siiiss", $ma_dot, $tuyen, $tx, $xe, $ngay, $ghi_chu);
                if (!$stmt->execute()) throw new Exception($conn->error);
                $dot_id = $conn->insert_id;

                // Gán đơn hàng vào đợt
                foreach ($don_hang_ids as $dh_id) {
                    $dh_id = (int)$dh_id;
                    if ($dh_id > 0) {
                        $conn->query("INSERT INTO chi_tiet_dot (dot_id, don_hang_id, trang_thai_trong_dot) VALUES ($dot_id, $dh_id, 'da_xep_len_xe')");
                        $conn->query("UPDATE don_hang SET trang_thai = 'dang_van_chuyen' WHERE id = $dh_id AND trang_thai IN ('cho_tiep_nhan','da_nhap_kho')");
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

    case 'available_drivers':
        if ($method === 'GET') {
            $res = $conn->query("SELECT tx.id, u.ho_ten FROM tai_xe tx JOIN users u ON tx.user_id = u.id WHERE tx.trang_thai = 1");
            $drivers = $res->fetch_all(MYSQLI_ASSOC);
            response(true, ['count' => count($drivers), 'drivers' => $drivers]);
        }
        break;

    case 'available_vehicles':
        if ($method === 'GET') {
            $res = $conn->query("SELECT id, bien_so, trong_tai_kg FROM xe WHERE trang_thai = 1");
            $vehicles = $res->fetch_all(MYSQLI_ASSOC);
            response(true, ['count' => count($vehicles), 'vehicles' => $vehicles]);
        }
        break;
        
    case 'dispatcher_stats':
        if ($method === 'GET') {
            response(true, [
                'pending_orders'    => $conn->query("SELECT COUNT(*) as c FROM don_hang WHERE trang_thai IN ('cho_tiep_nhan','da_nhap_kho')")->fetch_assoc()['c'],
                'today_shipments'   => $conn->query("SELECT COUNT(*) as c FROM dot_van_chuyen WHERE DATE(ngay_gio_bat_dau) = CURDATE()")->fetch_assoc()['c'],
                'available_drivers' => $conn->query("SELECT COUNT(*) as c FROM tai_xe WHERE trang_thai = 1")->fetch_assoc()['c'],
                'available_vehicles'=> $conn->query("SELECT COUNT(*) as c FROM xe WHERE trang_thai = 1")->fetch_assoc()['c']
            ]);
        }
        break;

    case 'order_status':
        if ($method === 'POST') {
            $dh_id = $_POST['don_hang_id'] ?? 0;
            $tt = $_POST['trang_thai'] ?? '';
            $gc = $_POST['ghi_chu'] ?? '';
            $stmt = $conn->prepare("UPDATE don_hang SET trang_thai = ?, ghi_chu = ? WHERE id = ?");
            $stmt->bind_param("ssi", $tt, $gc, $dh_id);
            $stmt->execute() ? response(true, ['id' => $dh_id]) : response(false, null, $conn->error);
        }
        break;

    case 'add_orders_to_shipment':
        if ($method === 'POST') {
            // requireRole('nhan_vien_dieu_phoi'); // Assuming auth logic allows
            $dot_id = (int)($_POST['dot_id'] ?? 0);
            $don_hang_ids = isset($_POST['don_hang_ids']) ? $_POST['don_hang_ids'] : null;
            if (!$dot_id || !is_array($don_hang_ids) || count($don_hang_ids) === 0) response(false, null, 'Thiếu thông tin bắt buộc hoặc danh sách đơn hàng không hợp lệ');
            
            // Check status of shipment
            $stmt_check = $conn->prepare("SELECT trang_thai FROM dot_van_chuyen WHERE id = ?");
            $stmt_check->bind_param("i", $dot_id);
            $stmt_check->execute();
            $shipment = $stmt_check->get_result()->fetch_assoc();
            
            if (!$shipment) response(false, null, 'Không tìm thấy đợt vận chuyển');
            if ($shipment['trang_thai'] !== 'chua_khoi_hanh') response(false, null, 'Chỉ có thể gán đơn vào đợt chưa khởi hành');

            foreach ($don_hang_ids as $dh_id) {
                $dh_id = (int)$dh_id;
                $conn->query("INSERT INTO chi_tiet_dot (dot_id, don_hang_id, trang_thai_trong_dot) VALUES ($dot_id, $dh_id, 'da_xep_len_xe')");
                $conn->query("UPDATE don_hang SET trang_thai = 'dang_van_chuyen' WHERE id = $dh_id");
            }
            response(true, null, 'Gán đơn hàng vào đợt vận chuyển thành công');
        }
        break;

    case 'my_profile':
        // Thông tin user đang đăng nhập (từ bảng users)
        if ($method === 'GET') {
            requireLogin();
            $uid = (int)$_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT id, ho_ten, so_dien_thoai, trang_thai, created_at FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                response(true, $row);
            } else {
                response(false, null, 'Không tìm thấy thông tin người dùng');
            }
        }
        break;

    case 'my_orders':
        // Đơn hàng liên quan đến SĐT của user đăng nhập (trong bảng khach_hang)
        if ($method === 'GET') {
            requireLogin();
            $sdt = $_SESSION['so_dien_thoai'] ?? '';
            if (!$sdt) {
                response(false, null, 'Không xác định được số điện thoại đăng nhập');
            }

            $limit  = max(1, min(100, (int)($_GET['limit']  ?? 50)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));

            // Lấy đơn mà user là người gửi hoặc người nhận (dựa theo SĐT trong bảng khach_hang)
            $sql = "SELECT dh.id, dh.ma_don, dh.ten_hang_hoa, dh.khoi_luong_kg,
                           dh.phi_van_chuyen, dh.tien_tra_truoc, dh.trang_thai,
                           dh.phuong_thuc_thanh_toan, dh.ghi_chu,
                           DATE_FORMAT(dh.ngay_tao, '%d/%m/%Y %H:%i') as ngay_tao,
                           kg.ho_ten as nguoi_gui, kn.ho_ten as nguoi_nhan,
                           kn.so_dien_thoai as sdt_nhan, kn.dia_chi as dia_chi_nhan
                    FROM don_hang dh
                    LEFT JOIN khach_hang kg ON dh.khach_gui_id = kg.id
                    LEFT JOIN khach_hang kn ON dh.khach_nhan_id = kn.id
                    WHERE kg.so_dien_thoai = ? OR kn.so_dien_thoai = ?
                    ORDER BY dh.id DESC
                    LIMIT ? OFFSET ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $sdt, $sdt, $limit, $offset);
            $stmt->execute();
            $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Đếm tổng
            $stmtCount = $conn->prepare(
                "SELECT COUNT(*) as total FROM don_hang dh
                 LEFT JOIN khach_hang kg ON dh.khach_gui_id = kg.id
                 LEFT JOIN khach_hang kn ON dh.khach_nhan_id = kn.id
                 WHERE kg.so_dien_thoai = ? OR kn.so_dien_thoai = ?"
            );
            $stmtCount->bind_param("ss", $sdt, $sdt);
            $stmtCount->execute();
            $total = (int)$stmtCount->get_result()->fetch_assoc()['total'];

            response(true, ['orders' => $orders, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
        }
        break;

    case 'my_shipments':
        if ($method === 'GET') {
            $user_id = $_SESSION['user_id'] ?? 0;
            $res = $conn->query("SELECT id FROM tai_xe WHERE user_id = $user_id");
            $tai_xe = $res->fetch_assoc();
            if (!$tai_xe) {
                response(true, []);
            } else {
                $tai_xe_id = $tai_xe['id'];
                $sql = "SELECT d.*, t.ten_tuyen, x.bien_so 
                        FROM dot_van_chuyen d 
                        LEFT JOIN tuyen_duong t ON d.tuyen_id = t.id 
                        LEFT JOIN xe x ON d.xe_id = x.id
                        WHERE d.tai_xe_id = $tai_xe_id
                        ORDER BY d.ngay_gio_bat_dau DESC";
                $shipments = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
                response(true, $shipments);
            }
        }
        break;

    case 'update_shipment_status':
        if ($method === 'POST') {
            $dot_id = (int)($_POST['dot_id'] ?? 0);
            $tt = $_POST['trang_thai'] ?? '';
            $user_id = $_SESSION['user_id'] ?? 0;
            
            // Verify driver owns this shipment
            $res = $conn->query("SELECT tx.id FROM tai_xe tx JOIN dot_van_chuyen d ON tx.id = d.tai_xe_id WHERE tx.user_id = $user_id AND d.id = $dot_id");
            if ($res->num_rows === 0) {
                response(false, null, 'Không có quyền cập nhật đợt vận chuyển này');
            }

            $stmt = $conn->prepare("UPDATE dot_van_chuyen SET trang_thai = ? WHERE id = ?");
            $stmt->bind_param("si", $tt, $dot_id);
            if ($stmt->execute()) {
                if ($tt === 'dang_chay') {
                    $conn->query("UPDATE chi_tiet_dot SET trang_thai_trong_dot = 'dang_van_chuyen' WHERE dot_id = $dot_id");
                    $conn->query("UPDATE don_hang dh JOIN chi_tiet_dot cd ON dh.id = cd.don_hang_id SET dh.trang_thai = 'dang_van_chuyen' WHERE cd.dot_id = $dot_id AND dh.trang_thai = 'da_nhap_kho'");
                }
                response(true, null, 'Cập nhật đợt vận chuyển thành công');
            } else {
                response(false, null, $conn->error);
            }
        }
        break;

    case 'driver_orders':
        if ($method === 'GET') {
            $user_id = $_SESSION['user_id'] ?? 0;
            $res = $conn->query("SELECT id FROM tai_xe WHERE user_id = $user_id");
            $tai_xe = $res->fetch_assoc();
            if (!$tai_xe) {
                response(true, []); // Mặc định trả về rỗng nếu không tìm thấy
            } else {
                $tai_xe_id = $tai_xe['id'];
                $sql = "SELECT dh.*, kg.ho_ten as ng_gui, kn.ho_ten as ng_nhan, kn.so_dien_thoai as sdt_nhan, kn.dia_chi as dia_chi_nhan,
                        cd.trang_thai_trong_dot, dv.ma_dot
                        FROM chi_tiet_dot cd
                        JOIN don_hang dh ON cd.don_hang_id = dh.id
                        JOIN dot_van_chuyen dv ON cd.dot_id = dv.id
                        LEFT JOIN khach_hang kg ON dh.khach_gui_id = kg.id
                        LEFT JOIN khach_hang kn ON dh.khach_nhan_id = kn.id
                        WHERE dv.tai_xe_id = $tai_xe_id AND dv.trang_thai IN ('dang_chay', 'chua_khoi_hanh')
                        ORDER BY dv.ngay_gio_bat_dau DESC";
                $orders = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
                response(true, $orders);
            }
        }
        break;

    case 'driver_update_status':
        if ($method === 'POST') {
            $user_id = $_SESSION['user_id'] ?? 0;
            $ho_ten = $_SESSION['ho_ten'] ?? 'Unknown';
            
            $dh_id = (int)($_POST['don_hang_id'] ?? 0);
            $trang_thai = $_POST['trang_thai'] ?? '';
            $ghi_chu = $_POST['ghi_chu'] ?? '';
            $nguoi_nhan_thuc_te = $_POST['nguoi_nhan_thuc_te'] ?? '';
            
            $stmt = $conn->prepare("SELECT trang_thai FROM don_hang WHERE id = ?");
            $stmt->bind_param("i", $dh_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                response(false, null, 'Đơn hàng không tồn tại');
            }
            $old_status = $result->fetch_assoc()['trang_thai'];
            
            $full_ghi_chu = trim($ghi_chu);
            if ($trang_thai === 'da_giao_hang' && $nguoi_nhan_thuc_te) {
                $full_ghi_chu = "Người nhận thực tế: $nguoi_nhan_thuc_te." . ($full_ghi_chu ? "\nGhi chú: $full_ghi_chu" : "");
            }

            $stmt = $conn->prepare("UPDATE don_hang SET trang_thai = ?, ghi_chu = CONCAT(IFNULL(ghi_chu, ''), '\n', ?) WHERE id = ?");
            $stmt->bind_param("ssi", $trang_thai, $full_ghi_chu, $dh_id);
            if ($stmt->execute()) {
                $nguoi_thay_doi = "Tài xế: " . $ho_ten;
                $stmt_ls = $conn->prepare("INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai_cu, trang_thai_moi, nguoi_thay_doi, ghi_chu) VALUES (?, ?, ?, ?, ?)");
                $stmt_ls->bind_param("issss", $dh_id, $old_status, $trang_thai, $nguoi_thay_doi, $full_ghi_chu);
                $stmt_ls->execute();
                
                if ($trang_thai === 'da_giao_hang') {
                    $conn->query("UPDATE chi_tiet_dot SET trang_thai_trong_dot = 'da_giao' WHERE don_hang_id = $dh_id");
                } elseif ($trang_thai === 'tra_lai') {
                    $conn->query("UPDATE chi_tiet_dot SET trang_thai_trong_dot = 'tra_lai' WHERE don_hang_id = $dh_id");
                }
                response(true, null, 'Cập nhật trạng thái thành công');
            } else {
                response(false, null, $conn->error);
            }
        }
        break;

    case 'driver_delivery_log':
        if ($method === 'GET') {
            $user_id = $_SESSION['user_id'] ?? 0;
            $ho_ten = $_SESSION['ho_ten'] ?? '';
            
            $like_name = "Tài xế: " . $ho_ten . "%";
            $sql = "SELECT ls.*, dh.ma_don, dh.ten_hang_hoa, kn.ho_ten as ng_nhan, kn.dia_chi as dia_chi_nhan
                    FROM lich_su_trang_thai ls
                    JOIN don_hang dh ON ls.don_hang_id = dh.id
                    LEFT JOIN khach_hang kn ON dh.khach_nhan_id = kn.id
                    WHERE ls.nguoi_thay_doi LIKE ?
                    ORDER BY ls.thoi_gian DESC LIMIT 100";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $like_name);
            $stmt->execute();
            response(true, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        }
        break;

    default:
        response(false, null, 'Endpoint không tồn tại');
}
?>