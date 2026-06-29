<?php
/**
 * Model: ThongKe
 * Thống kê doanh thu, đơn hàng, bảng giá cước.
 */
class ThongKe {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function getStatistics(string $from = '', string $to = ''): array {
        $whereDate = '';
        if ($from && $to) {
            $f = $this->db->real_escape_string($from);
            $t = $this->db->real_escape_string($to);
            $whereDate = "WHERE DATE(ngay_tao) BETWEEN '$f' AND '$t'";
        } elseif ($from) {
            $f = $this->db->real_escape_string($from);
            $whereDate = "WHERE DATE(ngay_tao) >= '$f'";
        } elseif ($to) {
            $t = $this->db->real_escape_string($to);
            $whereDate = "WHERE DATE(ngay_tao) <= '$t'";
        }

        $andSuccess = ($whereDate ? $whereDate . " AND" : "WHERE") . " trang_thai_don_hang IN ('hoan_tat')";

        $stats = [
            'total_orders'    => (int)$this->db->query("SELECT COUNT(*) as c FROM don_hang $whereDate")->fetch_assoc()['c'],
            'success_orders'  => (int)$this->db->query("SELECT COUNT(*) as c FROM don_hang $andSuccess")->fetch_assoc()['c'],
            'total_revenue'   => (float)$this->db->query("SELECT COALESCE(SUM(phi_van_chuyen),0) as s FROM don_hang $andSuccess")->fetch_assoc()['s'],
            'total_drivers'   => (int)$this->db->query("SELECT COUNT(*) as c FROM tai_xe")->fetch_assoc()['c'],
            'total_vehicles'  => (int)$this->db->query("SELECT COUNT(*) as c FROM xe_van_tai")->fetch_assoc()['c'],
            'total_shipments' => (int)$this->db->query("SELECT COUNT(*) as c FROM dot_van_chuyen")->fetch_assoc()['c'],
        ];

        $resObd = $this->db->query(
            "SELECT DATE(ngay_tao) as date, COUNT(*) as total_orders,
                    SUM(trang_thai_don_hang = 'hoan_tat') as success_orders
             FROM don_hang $whereDate
             GROUP BY DATE(ngay_tao) ORDER BY date DESC LIMIT 30"
        );
        $stats['orders_by_day'] = $resObd ? $resObd->fetch_all(MYSQLI_ASSOC) : [];

        $resRbd = $this->db->query(
            "SELECT DATE(ngay_tao) as date, COALESCE(SUM(phi_van_chuyen),0) as revenue
             FROM don_hang $andSuccess
             GROUP BY DATE(ngay_tao) ORDER BY date DESC LIMIT 30"
        );
        $stats['revenue_by_day'] = $resRbd ? $resRbd->fetch_all(MYSQLI_ASSOC) : [];

        return $stats;
    }

    public function getDetailedStatistics(string $interval = 'day', string $from = '', string $to = ''): array {
        $where = "WHERE 1=1";
        if ($from) {
            $f = $this->db->real_escape_string($from);
            $where .= " AND DATE(dh.ngay_tao) >= '$f'";
        }
        if ($to) {
            $t = $this->db->real_escape_string($to);
            $where .= " AND DATE(dh.ngay_tao) <= '$t'";
        }

        // 1. Phân nhóm thời gian theo interval
        $selectLabel = "DATE_FORMAT(dh.ngay_tao, '%Y-%m-%d')";
        
        switch (strtolower($interval)) {
            case 'week':
                // Trả về ngày Thứ Hai đầu tuần làm label
                $selectLabel = "DATE_FORMAT(DATE_SUB(dh.ngay_tao, INTERVAL WEEKDAY(dh.ngay_tao) DAY), '%Y-%m-%d')";
                break;
            case 'month':
                $selectLabel = "DATE_FORMAT(dh.ngay_tao, '%Y-%m')";
                break;
            case 'quarter':
                $selectLabel = "CONCAT(YEAR(dh.ngay_tao), '-Q', QUARTER(dh.ngay_tao))";
                break;
            case 'year':
                $selectLabel = "YEAR(dh.ngay_tao)";
                break;
            case 'day':
            default:
                $selectLabel = "DATE_FORMAT(dh.ngay_tao, '%Y-%m-%d')";
                break;
        }

        // Lấy số liệu theo thời gian
        $qTime = "SELECT 
                    $selectLabel as time_label,
                    COUNT(dh.id) as total_orders,
                    SUM(CASE WHEN dh.trang_thai_don_hang = 'hoan_tat' THEN 1 ELSE 0 END) as success_orders,
                    SUM(CASE WHEN dh.trang_thai_don_hang = 'hoan_tat' THEN dh.phi_van_chuyen ELSE 0 END) as revenue
                  FROM don_hang dh
                  LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id = kg.id
                  LEFT JOIN chi_nhanh cn_gui ON dh.chi_nhanh_gui_id = cn_gui.id
                  $where
                  GROUP BY time_label
                  ORDER BY MIN(dh.ngay_tao) ASC";
        
        $resTime = $this->db->query($qTime);
        $timeData = $resTime ? $resTime->fetch_all(MYSQLI_ASSOC) : [];

        // 2. Lấy số liệu theo Tỉnh thành gửi
        $qProv = "SELECT 
                    CASE 
                        WHEN cn_gui.ten_chi_nhanh IS NOT NULL THEN 
                            CASE 
                                WHEN cn_gui.ten_chi_nhanh LIKE '%Tra Vinh%' OR cn_gui.ten_chi_nhanh LIKE '%Trà Vinh%' THEN 'Trà Vinh'
                                WHEN cn_gui.ten_chi_nhanh LIKE '%Vinh Long%' OR cn_gui.ten_chi_nhanh LIKE '%Vĩnh Long%' THEN 'Vĩnh Long'
                                ELSE cn_gui.ten_chi_nhanh
                            END
                        WHEN kg.dia_chi LIKE '%Trà Vinh%' OR kg.dia_chi LIKE '%Tra Vinh%' THEN 'Trà Vinh'
                        WHEN kg.dia_chi LIKE '%Vĩnh Long%' OR kg.dia_chi LIKE '%Vinh Long%' THEN 'Vĩnh Long'
                        ELSE 'Khác'
                    END AS tinh_thanh_gui,
                    COUNT(dh.id) as total_orders,
                    SUM(CASE WHEN dh.trang_thai_don_hang = 'hoan_tat' THEN 1 ELSE 0 END) as success_orders,
                    SUM(CASE WHEN dh.trang_thai_don_hang = 'hoan_tat' THEN dh.phi_van_chuyen ELSE 0 END) as revenue
                  FROM don_hang dh
                  LEFT JOIN khach_hang kg ON dh.khach_hang_gui_id = kg.id
                  LEFT JOIN chi_nhanh cn_gui ON dh.chi_nhanh_gui_id = cn_gui.id
                  $where
                  GROUP BY tinh_thanh_gui
                  ORDER BY revenue DESC";

        $resProv = $this->db->query($qProv);
        $provData = $resProv ? $resProv->fetch_all(MYSQLI_ASSOC) : [];

        // 3. Tổng hợp số liệu chung cho khoảng thời gian lọc
        $qSummary = "SELECT 
                        COUNT(dh.id) as total_orders,
                        SUM(CASE WHEN dh.trang_thai_don_hang = 'hoan_tat' THEN 1 ELSE 0 END) as success_orders,
                        SUM(CASE WHEN dh.trang_thai_don_hang = 'hoan_tat' THEN dh.phi_van_chuyen ELSE 0 END) as total_revenue
                     FROM don_hang dh
                     $where";
        $resSummary = $this->db->query($qSummary);
        $summary = $resSummary ? $resSummary->fetch_assoc() : ['total_orders' => 0, 'success_orders' => 0, 'total_revenue' => 0];

        return [
            'summary' => [
                'total_orders' => (int)($summary['total_orders'] ?? 0),
                'success_orders' => (int)($summary['success_orders'] ?? 0),
                'total_revenue' => (float)($summary['total_revenue'] ?? 0)
            ],
            'by_time' => $timeData,
            'by_province' => $provData
        ];
    }

    public function getQuote(float $weight): ?float {
        $res = $this->db->query(
            "SELECT gia_co_ban FROM bang_gia_cuoc
             WHERE khoi_luong_tu_kg <= $weight AND khoi_luong_den_kg >= $weight
             LIMIT 1"
        );
        if ($res && $res->num_rows > 0) {
            return (float)$res->fetch_assoc()['gia_co_ban'];
        }
        // Fallback: bậc cao nhất
        $fb = $this->db->query("SELECT gia_co_ban FROM bang_gia_cuoc ORDER BY khoi_luong_den_kg DESC LIMIT 1")->fetch_assoc();
        return $fb ? (float)$fb['gia_co_ban'] : null;
    }

    public function getGoodsTypes(): array {
        return [
            ['id' => 1, 'ten_danh_muc' => 'Hồ sơ, tài liệu',     'mo_ta' => '', 'trang_thai' => 1],
            ['id' => 2, 'ten_danh_muc' => 'Thực phẩm khô',        'mo_ta' => '', 'trang_thai' => 1],
            ['id' => 3, 'ten_danh_muc' => 'Điện tử',              'mo_ta' => '', 'trang_thai' => 1],
            ['id' => 4, 'ten_danh_muc' => 'Quần áo, giày dép',    'mo_ta' => '', 'trang_thai' => 1],
            ['id' => 5, 'ten_danh_muc' => 'Hàng dễ vỡ',          'mo_ta' => '', 'trang_thai' => 1],
            ['id' => 6, 'ten_danh_muc' => 'Hàng hóa thông thường','mo_ta' => '', 'trang_thai' => 1],
        ];
    }
}
