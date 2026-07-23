<?php
require_once __DIR__ . '/../models/thongke.php';

/**
 * ThongKeController
 * Thống kê, báo giá, loại hàng hóa.
 */
class ThongKeController {
    private $model;

    public function __construct($db) {
        $this->model = new ThongKe($db);
    }

    public function getStatistics($from = '', $to = '') {
        return ['success' => true, 'data' => $this->model->getStatistics($from, $to)];
    }

    public function getDetailedStatistics($interval = 'day', $from = '', $to = '') {
        return ['success' => true, 'data' => $this->model->getDetailedStatistics($interval, $from, $to)];
    }

    public function getQuote($weight, $km = 0, $loaiHangId = 0) {
        $fee = $this->model->getQuote($weight, $km, $loaiHangId);
        if ($fee === null) {
            return ['success' => false, 'message' => 'Không tìm thấy bảng giá phù hợp'];
        }
        return ['success' => true, 'data' => ['estimated_fee' => $fee]];
    }

    public function getGoodsTypes() {
        return ['success' => true, 'data' => $this->model->getGoodsTypes()];
    }
}
