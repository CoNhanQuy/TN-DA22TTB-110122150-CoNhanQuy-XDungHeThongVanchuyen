<?php
/**
 * API module: Thống kê & Bảng giá
 * Schema mới: bang_gia_cuoc (thay danh_muc)
 */

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // ----------------------------------------------------------------
    // Thống kê tổng
    // ----------------------------------------------------------------
    case 'statistics':
        if ($method === 'GET') {
            $from = $_GET['from'] ?? '';
            $to   = $_GET['to']   ?? '';

            $where_date = '';
            if ($from && $to) {
                $f = $conn->real_escape_string($from);
                $t = $conn->real_escape_string($to);
                $where_date = "WHERE DATE(ngay_tao) BETWEEN '$f' AND '$t'";
            } elseif ($from) {
                $f = $conn->real_escape_string($from);
                $where_date = "WHERE DATE(ngay_tao) >= '$f'";
            } elseif ($to) {
                $t = $conn->real_escape_string($to);
                $where_date = "WHERE DATE(ngay_tao) <= '$t'";
            }

            $and_success = ($where_date ? $where_date . " AND" : "WHERE")
                         . " trang_thai_don_hang IN ('hoan_tat')";

            $stats = [
                'total_orders'    => $conn->query("SELECT COUNT(*) as c FROM don_hang $where_date")->fetch_assoc()['c'],
                'success_orders'  => $conn->query("SELECT COUNT(*) as c FROM don_hang $and_success")->fetch_assoc()['c'],
                'total_revenue'   => $conn->query("SELECT COALESCE(SUM(phi_van_chuyen),0) as s FROM don_hang $and_success")->fetch_assoc()['s'],
                'total_drivers'   => $conn->query("SELECT COUNT(*) as c FROM tai_xe")->fetch_assoc()['c'],
                'total_vehicles'  => $conn->query("SELECT COUNT(*) as c FROM xe_van_tai")->fetch_assoc()['c'],
                'total_shipments' => $conn->query("SELECT COUNT(*) as c FROM dot_van_chuyen")->fetch_assoc()['c'],
            ];

            $res_obd = $conn->query(
                "SELECT DATE(ngay_tao) as date, COUNT(*) as total_orders,
                        SUM(trang_thai_don_hang = 'hoan_tat') as success_orders
                 FROM don_hang $where_date
                 GROUP BY DATE(ngay_tao) ORDER BY date DESC LIMIT 30"
            );
            $stats['orders_by_day'] = $res_obd ? $res_obd->fetch_all(MYSQLI_ASSOC) : [];

            $res_rbd = $conn->query(
                "SELECT DATE(ngay_tao) as date, COALESCE(SUM(phi_van_chuyen),0) as revenue
                 FROM don_hang $and_success
                 GROUP BY DATE(ngay_tao) ORDER BY date DESC LIMIT 30"
            );
            $stats['revenue_by_day'] = $res_rbd ? $res_rbd->fetch_all(MYSQLI_ASSOC) : [];

            response(true, $stats);
        }
        break;

    // ----------------------------------------------------------------
    // Tính phí ước tính (bang_gia_cuoc)
    // ----------------------------------------------------------------
    case 'quote':
        if ($method === 'GET') {
            $weight = (float)($_GET['weight'] ?? 0);
            $res    = $conn->query(
                "SELECT * FROM bang_gia_cuoc
                 WHERE khoi_luong_tu_kg <= $weight AND khoi_luong_den_kg >= $weight
                 LIMIT 1"
            );
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $fee = (float)$row['gia_co_ban'];
                response(true, ['estimated_fee' => $fee]);
            } else {
                // Fallback: lấy bậc cao nhất
                $fallback = $conn->query("SELECT gia_co_ban FROM bang_gia_cuoc ORDER BY khoi_luong_den_kg DESC LIMIT 1")->fetch_assoc();
                $fee = $fallback ? (float)$fallback['gia_co_ban'] : 0;
                $fee > 0
                    ? response(true, ['estimated_fee' => $fee])
                    : response(false, null, 'Không tìm thấy bảng giá phù hợp');
            }
        }
        break;

    // ----------------------------------------------------------------
    // Loại hàng hóa — schema mới không có bảng danh_muc
    // Trả về danh sách tĩnh để frontend không bị lỗi
    // ----------------------------------------------------------------
    case 'goods_types':
        if ($method === 'GET') {
            response(true, [
                ['id' => 1, 'ten_danh_muc' => 'Hồ sơ, tài liệu',    'mo_ta' => '', 'trang_thai' => 1],
                ['id' => 2, 'ten_danh_muc' => 'Thực phẩm khô',       'mo_ta' => '', 'trang_thai' => 1],
                ['id' => 3, 'ten_danh_muc' => 'Điện tử',             'mo_ta' => '', 'trang_thai' => 1],
                ['id' => 4, 'ten_danh_muc' => 'Quần áo, giày dép',   'mo_ta' => '', 'trang_thai' => 1],
                ['id' => 5, 'ten_danh_muc' => 'Hàng dễ vỡ',         'mo_ta' => '', 'trang_thai' => 1],
                ['id' => 6, 'ten_danh_muc' => 'Hàng hóa thông thường','mo_ta'=> '', 'trang_thai' => 1],
            ]);
        } elseif ($method === 'POST') {
            // Schema mới không có bảng danh_muc, trả về success để UI không lỗi
            response(true, null, 'Chức năng quản lý loại hàng chưa được hỗ trợ trong schema mới');
        }
        break;
}
