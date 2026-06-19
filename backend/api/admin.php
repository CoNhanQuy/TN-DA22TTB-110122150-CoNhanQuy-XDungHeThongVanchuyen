<?php
/**
 * API module: Quản trị (Admin)
 * Schema mới: nguoi_dung, vai_tro_nguoi_dung, vai_tro, xe_van_tai, tuyen_duong, nguoi_giao_hang, bang_gia_cuoc, khach_hang
 */

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // ----------------------------------------------------------------
    // Người dùng (nguoi_dung + vai_tro)
    // ----------------------------------------------------------------
    case 'users':
        if ($method === 'GET') {
            $res = $conn->query(
                "SELECT nd.id, nd.ho_ten, nd.so_dien_thoai, nd.trang_thai, nd.ngay_tao as created_at,
                        vt.ten_vai_tro as vai_tro
                 FROM nguoi_dung nd
                 LEFT JOIN vai_tro_nguoi_dung vtnd ON vtnd.nguoi_dung_id = nd.id
                 LEFT JOIN vai_tro vt ON vt.id = vtnd.vai_tro_id"
            );
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $conn->query("DELETE FROM nguoi_dung WHERE id = $id");
                response(true);
            } else {
                $ho_ten  = $_POST['ho_ten'] ?? '';
                $sdt     = $_POST['so_dien_thoai'] ?? '';
                $vai_tro = $_POST['vai_tro'] ?? 'nhan_vien_tiep_nhan';
                $pass    = password_hash($_POST['mat_khau'] ?? '123456', PASSWORD_DEFAULT);
                $stmt    = $conn->prepare(
                    "INSERT INTO nguoi_dung (ho_ten, so_dien_thoai, mat_khau, trang_thai) VALUES (?, ?, ?, 1)"
                );
                $stmt->bind_param("sss", $ho_ten, $sdt, $pass);
                if ($stmt->execute()) {
                    $uid     = $conn->insert_id;
                    $vtRow   = $conn->query("SELECT id FROM vai_tro WHERE ten_vai_tro = '$vai_tro' LIMIT 1")->fetch_assoc();
                    if ($vtRow) {
                        $conn->query("INSERT INTO vai_tro_nguoi_dung (nguoi_dung_id, vai_tro_id) VALUES ($uid, {$vtRow['id']})");
                    }
                    response(true, ['id' => $uid]);
                }
                response(false, null, $conn->error);
            }
        }
        break;

    // ----------------------------------------------------------------
    // Xe vận tải (xe_van_tai)
    // ----------------------------------------------------------------
    case 'vehicles':
        if ($method === 'GET') {
            $res = $conn->query("SELECT id, bien_so_xe as bien_so, trong_tai_toi_da_kg as trong_tai_kg, loai_xe, trang_thai_hoat_dong as trang_thai FROM xe_van_tai");
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $conn->query("DELETE FROM xe_van_tai WHERE id = $id");
                response(true);
            } else {
                $bs     = $_POST['bien_so'] ?? '';
                $tt     = (float)($_POST['trong_tai_kg'] ?? 0);
                $loai   = $_POST['loai_xe'] ?? 'xe_tai_nho';
                $stmt   = $conn->prepare(
                    "INSERT INTO xe_van_tai (bien_so_xe, trong_tai_toi_da_kg, loai_xe) VALUES (?, ?, ?)"
                );
                $stmt->bind_param("sds", $bs, $tt, $loai);
                $stmt->execute()
                    ? response(true, ['id' => $conn->insert_id])
                    : response(false, null, $conn->error);
            }
        }
        break;

    // ----------------------------------------------------------------
    // Tuyến đường (tuyen_duong — nối theo chi_nhanh)
    // ----------------------------------------------------------------
    case 'routes':
        if ($method === 'GET') {
            $res = $conn->query(
                "SELECT td.id, td.khoang_cach_ki_lo_met as quang_duong_km,
                        td.thoi_gian_di_chuyen_uoc_tinh_phut as thoi_gian_phut,
                        cn_di.ten_chi_nhanh as diem_di, cn_den.ten_chi_nhanh as diem_den,
                        cn_di.id as chi_nhanh_di_id, cn_den.id as chi_nhanh_den_id
                 FROM tuyen_duong td
                 LEFT JOIN chi_nhanh cn_di  ON td.chi_nhanh_di_id  = cn_di.id
                 LEFT JOIN chi_nhanh cn_den ON td.chi_nhanh_den_id = cn_den.id"
            );
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $conn->query("DELETE FROM tuyen_duong WHERE id = $id");
                response(true);
            } else {
                $di   = (int)($_POST['chi_nhanh_di_id'] ?? 0);
                $den  = (int)($_POST['chi_nhanh_den_id'] ?? 0);
                $km   = (float)($_POST['quang_duong_km'] ?? 0);
                $phut = (int)($_POST['thoi_gian_phut'] ?? 0);
                $stmt = $conn->prepare(
                    "INSERT INTO tuyen_duong (chi_nhanh_di_id, chi_nhanh_den_id, khoang_cach_ki_lo_met, thoi_gian_di_chuyen_uoc_tinh_phut)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param("iidi", $di, $den, $km, $phut);
                $stmt->execute()
                    ? response(true, ['id' => $conn->insert_id])
                    : response(false, null, $conn->error);
            }
        }
        break;

    // ----------------------------------------------------------------
    // Chi nhánh
    // ----------------------------------------------------------------
    case 'branches':
        if ($method === 'GET') {
            $res = $conn->query("SELECT * FROM chi_nhanh");
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $conn->query("DELETE FROM chi_nhanh WHERE id = $id");
                response(true);
            } else {
                $ma  = $_POST['ma_chi_nhanh'] ?? '';
                $ten = $_POST['ten_chi_nhanh'] ?? '';
                $dc  = $_POST['dia_chi'] ?? '';
                $sdt = $_POST['so_dien_thoai'] ?? '';
                $stmt = $conn->prepare(
                    "INSERT INTO chi_nhanh (ma_chi_nhanh, ten_chi_nhanh, dia_chi, so_dien_thoai) VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param("ssss", $ma, $ten, $dc, $sdt);
                $stmt->execute()
                    ? response(true, ['id' => $conn->insert_id])
                    : response(false, null, $conn->error);
            }
        }
        break;

    // ----------------------------------------------------------------
    // Người giao hàng (nguoi_giao_hang → nguoi_dung)
    // ----------------------------------------------------------------
    case 'delivery_persons':
        if ($method === 'GET') {
            $sql = "SELECT ngh.id, nd.ho_ten, nd.so_dien_thoai,
                           ngh.khu_vuc_phu_trach, cn.ten_chi_nhanh, ngh.chi_nhanh_id,
                           nd.trang_thai
                    FROM nguoi_giao_hang ngh
                    JOIN nguoi_dung nd ON ngh.nguoi_dung_id = nd.id
                    LEFT JOIN chi_nhanh cn ON ngh.chi_nhanh_id = cn.id";
            $res = $conn->query($sql);
            if (!$res) response(false, null, $conn->error);
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id  = (int)($_POST['id'] ?? 0);
                $res = $conn->query("SELECT nguoi_dung_id FROM nguoi_giao_hang WHERE id = $id");
                if ($row = $res->fetch_assoc()) {
                    $uid = (int)$row['nguoi_dung_id'];
                    $conn->query("DELETE FROM nguoi_giao_hang WHERE id = $id");
                    $conn->query("DELETE FROM nguoi_dung WHERE id = $uid");
                }
                response(true);
            } else {
                $ten          = $_POST['ho_ten'] ?? '';
                $sdt          = $_POST['so_dien_thoai'] ?? '';
                $chi_nhanh_id = (int)($_POST['chi_nhanh_id'] ?? 0);
                $khu_vuc      = $_POST['khu_vuc_phu_trach'] ?? '';
                $pass         = password_hash('123456', PASSWORD_DEFAULT);

                $sNd = $conn->prepare(
                    "INSERT INTO nguoi_dung (ho_ten, so_dien_thoai, mat_khau, trang_thai) VALUES (?, ?, ?, 1)"
                );
                $sNd->bind_param("sss", $ten, $sdt, $pass);
                if ($sNd->execute()) {
                    $uid    = $conn->insert_id;
                    $vtRow  = $conn->query("SELECT id FROM vai_tro WHERE ten_vai_tro = 'shipper' LIMIT 1")->fetch_assoc();
                    if ($vtRow) {
                        $conn->query("INSERT INTO vai_tro_nguoi_dung (nguoi_dung_id, vai_tro_id) VALUES ($uid, {$vtRow['id']})");
                    }
                    $sNgh = $conn->prepare(
                        "INSERT INTO nguoi_giao_hang (nguoi_dung_id, chi_nhanh_id, khu_vuc_phu_trach) VALUES (?, ?, ?)"
                    );
                    $sNgh->bind_param("iis", $uid, $chi_nhanh_id, $khu_vuc);
                    $sNgh->execute();
                    response(true, ['id' => $conn->insert_id]);
                }
                response(false, null, $conn->error);
            }
        }
        break;

    // ----------------------------------------------------------------
    // Bảng giá cước (bang_gia_cuoc)
    // ----------------------------------------------------------------
    case 'pricing':
        if ($method === 'GET') {
            $res  = $conn->query("SELECT * FROM bang_gia_cuoc ORDER BY khoi_luong_tu_kg");
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
            response(true, $data);
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $conn->query("DELETE FROM bang_gia_cuoc WHERE id = $id");
                response(true);
            } else {
                $tu  = (float)($_POST['tu_kg'] ?? 0);
                $den = (float)($_POST['den_kg'] ?? 0);
                $phi = (float)($_POST['phi_co_ban'] ?? 0);
                $pkm = (float)($_POST['phi_per_km'] ?? 0);
                $stmt = $conn->prepare(
                    "INSERT INTO bang_gia_cuoc (khoi_luong_tu_kg, khoi_luong_den_kg, gia_co_ban, gia_theo_moi_ki_lo_met)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param("dddd", $tu, $den, $phi, $pkm);
                $stmt->execute()
                    ? response(true, ['id' => $conn->insert_id])
                    : response(false, null, $conn->error);
            }
        }
        break;

    // ----------------------------------------------------------------
    // Khách hàng
    // ----------------------------------------------------------------
    case 'customers':
        if ($method === 'GET') {
            $res = $conn->query(
                "SELECT id, ho_ten, so_dien_thoai, so_can_cuoc_cong_dan as so_cccd, email, dia_chi FROM khach_hang"
            );
            response(true, $res->fetch_all(MYSQLI_ASSOC));
        }
        break;
}
