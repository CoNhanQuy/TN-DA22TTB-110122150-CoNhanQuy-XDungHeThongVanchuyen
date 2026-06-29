<?php
require_once __DIR__ . '/../models/ThongKe.php';

/**
 * ThongKeController
 * Thống kê, báo giá, loại hàng hóa.
 */
class ThongKeController {
    private ThongKe $model;

    public function __construct(mysqli $db) {
        $this->model = new ThongKe($db);
    }

    public function getStatistics(string $from = '', string $to = ''): array {
        return ['success' => true, 'data' => $this->model->getStatistics($from, $to)];
    }

    public function getDetailedStatistics(string $interval = 'day', string $from = '', string $to = ''): array {
        return ['success' => true, 'data' => $this->model->getDetailedStatistics($interval, $from, $to)];
    }

    public function getQuote(float $weight): array {
        $fee = $this->model->getQuote($weight);
        if ($fee === null) {
            return ['success' => false, 'message' => 'Không tìm thấy bảng giá phù hợp'];
        }
        return ['success' => true, 'data' => ['estimated_fee' => $fee]];
    }

    public function getGoodsTypes(): array {
        return ['success' => true, 'data' => $this->model->getGoodsTypes()];
    }
}
