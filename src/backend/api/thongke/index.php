<?php
/**
 * Route group: /api/thongke/
 * Thống kê, báo giá, loại hàng hóa — gọi ThongKeController
 */
require_once __DIR__ . '/../../controllers/ThongKeController.php';

$ctrl = new ThongKeController($conn);

switch ($action) {

    case 'statistics':
        if (isset($_GET['interval'])) {
            $result = $ctrl->getDetailedStatistics($_GET['interval'], $_GET['from'] ?? '', $_GET['to'] ?? '');
        } else {
            $result = $ctrl->getStatistics($_GET['from'] ?? '', $_GET['to'] ?? '');
        }
        response(true, $result['data']);
        break;

    case 'quote':
        $weight = (float)($_GET['weight'] ?? 0);
        $result = $ctrl->getQuote($weight);
        response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        break;

    case 'goods_types':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $result = $ctrl->getGoodsTypes();
            response(true, $result['data']);
        }
        response(true, null, 'Chức năng quản lý loại hàng chưa được hỗ trợ trong schema mới');
        break;
}
