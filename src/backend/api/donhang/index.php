<?php
/**
 * Route group: /api/donhang/
 * Tất cả action liên quan đến đơn hàng — gọi DonHangController
 */
require_once __DIR__ . '/../../controllers/donhangcontroller.php';

$ctrl   = new DonHangController($conn);
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    case 'track':
        $code = trim($method === 'POST' ? ($_POST['ma_don'] ?? $_POST['code'] ?? '') : ($_GET['ma_don'] ?? $_GET['code'] ?? ''));
        $result = $ctrl->track($code);
        response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        break;

    case 'pending_orders':
        if ($method === 'GET') {
            $result = $ctrl->getPendingOrders();
            response(true, $result['data']);
        }
        break;

    case 'orders':
        if ($method === 'GET') {
            $result = $ctrl->getOrders();
            response(true, $result['data']);
        } elseif ($method === 'POST') {
            $op     = $_POST['op'] ?? '';
            $id     = (int)($_POST['id'] ?? 0);
            $actor  = $_SESSION['ho_ten'] ?? 'Hệ thống';
            if ($op === 'update_status') {
                $result = $ctrl->updateStatus($id, $_POST['trang_thai'] ?? '', $actor, $_POST['ghi_chu'] ?? '');
            } elseif ($op === 'cancel') {
                $result = $ctrl->cancelOrder($id, $actor, $_POST['reason'] ?? '');
            } else {
                $result = $ctrl->createOrder($_POST, $actor);
            }
            response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        }
        break;

    case 'receptionist_orders':
        if ($method === 'GET') {
            $result = $ctrl->getReceptionistOrders();
            response(true, $result['data']);
        } elseif ($method === 'POST') {
            $op    = $_POST['op'] ?? '';
            $id    = (int)($_POST['id'] ?? 0);
            $actor = $_SESSION['ho_ten'] ?? 'NV tiếp nhận';
            if ($op === 'update_status') {
                $result = $ctrl->updateStatus($id, $_POST['trang_thai'] ?? '', $actor, $_POST['ghi_chu'] ?? '');
            } elseif ($op === 'cancel') {
                $result = $ctrl->cancelOrder($id, $actor, $_POST['reason'] ?? '');
            } else {
                $result = $ctrl->createOrder($_POST, $actor);
            }
            response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        }
        break;

    case 'order_status':
        if ($method === 'POST') {
            $result = $ctrl->updateStatus(
                (int)($_POST['don_hang_id'] ?? 0),
                $_POST['trang_thai'] ?? '',
                $_SESSION['ho_ten'] ?? 'Hệ thống',
                $_POST['ghi_chu'] ?? ''
            );
            response($result['success'], ['id' => (int)($_POST['don_hang_id'] ?? 0)], $result['message'] ?? '');
        }
        break;

    case 'my_profile':
        requireLogin();
        $result = $ctrl->getProfile((int)$_SESSION['user_id'], $_SESSION['so_dien_thoai'] ?? '');
        response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        break;

    case 'my_orders':
        requireLogin();
        $limit  = max(1, min(100, (int)($_GET['limit']  ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $result = $ctrl->getMyOrders($_SESSION['so_dien_thoai'] ?? '', $limit, $offset);
        response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        break;

    case 'update_profile':
        requireLogin();
        $result = $ctrl->updateProfile(
            (int)$_SESSION['user_id'],
            $_SESSION['so_dien_thoai'] ?? '',
            trim($_POST['ho_ten'] ?? ''),
            trim($_POST['so_cccd'] ?? ''),
            trim($_POST['dia_chi'] ?? '')
        );
        response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        break;

    case 'change_password':
        requireLogin();
        $result = $ctrl->changePassword(
            (int)$_SESSION['user_id'],
            $_POST['mat_khau_cu'] ?? '',
            $_POST['mat_khau_moi'] ?? '',
            $_POST['xac_nhan'] ?? ''
        );
        response($result['success'], null, $result['message']);
        break;

    case 'add_orders_to_shipment':
        if ($method === 'POST') {
            // Delegated to taixe module — forward $conn trực tiếp
            require_once __DIR__ . '/../../controllers/giaohangcontroller.php';
            $dotId      = (int)($_POST['dot_id'] ?? 0);
            $donHangIds = isset($_POST['don_hang_ids']) ? (array)$_POST['don_hang_ids'] : [];
            if (!$dotId || empty($donHangIds)) response(false, null, 'Thiếu thông tin bắt buộc');

            $stmt = $conn->prepare("SELECT trang_thai_dot_van_chuyen FROM dot_van_chuyen WHERE id = ?");
            $stmt->bind_param("i", $dotId);
            $stmt->execute();
            $dot = $stmt->get_result()->fetch_assoc();
            if (!$dot) response(false, null, 'Không tìm thấy đợt vận chuyển');
            if ($dot['trang_thai_dot_van_chuyen'] !== 'cho_khoi_hanh') {
                response(false, null, 'Chỉ có thể gán đơn vào đợt chưa khởi hành');
            }
            foreach ($donHangIds as $dhId) {
                $dhId = (int)$dhId;
                $conn->query("INSERT INTO chi_tiet_dot_van_chuyen (dot_van_chuyen_id, don_hang_id, trang_thai_trong_dot) VALUES ($dotId, $dhId, 'da_xep_len_xe')");
                $conn->query("UPDATE don_hang SET trang_thai_don_hang = 'dang_van_chuyen' WHERE id = $dhId");
            }
            response(true, null, 'Gán đơn hàng thành công');
        }
        break;

    case 'order_detail':
        if ($method === 'GET') {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) response(false, null, 'Thiếu id đơn hàng');
            $result = $ctrl->getOrderDetail($id);
            response($result['success'], $result['data'] ?? null, $result['message'] ?? '');
        }
        break;
}
