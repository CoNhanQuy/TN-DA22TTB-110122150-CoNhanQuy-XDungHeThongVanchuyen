<?php
/**
 * Route group: /api/admin/
 * Quản trị hệ thống — gọi AdminController (wrapper của Admin model)
 */
require_once __DIR__ . '/../../models/admin.php';

$admin  = new Admin($conn);
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    case 'users':
        if ($method === 'GET') {
            response(true, $admin->getUsers());
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $admin->deleteUser((int)($_POST['id'] ?? 0));
                response(true);
            } elseif ($op === 'update') {
                $admin->updateUser(
                    (int)($_POST['id'] ?? 0),
                    $_POST['ho_ten'] ?? '',
                    $_POST['so_dien_thoai'] ?? '',
                    $_POST['vai_tro'] ?? 'khach_hang',
                    (int)($_POST['trang_thai'] ?? 1),
                    $_POST['mat_khau'] ?? ''
                );
                response(true);
            } else {
                $id = $admin->createUser(
                    $_POST['ho_ten'] ?? '',
                    $_POST['so_dien_thoai'] ?? '',
                    $_POST['vai_tro'] ?? 'nhan_vien_tiep_nhan',
                    $_POST['mat_khau'] ?? ''
                );
                response(true, ['id' => $id]);
            }
        }
        break;

    case 'vehicles':
        if ($method === 'GET') {
            response(true, $admin->getVehicles());
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $admin->deleteVehicle((int)($_POST['id'] ?? 0));
                response(true);
            } elseif ($op === 'update') {
                $admin->updateVehicle(
                    (int)($_POST['id'] ?? 0),
                    $_POST['bien_so'] ?? '',
                    (float)($_POST['trong_tai_kg'] ?? 0),
                    (int)($_POST['trang_thai'] ?? 1)
                );
                response(true);
            } else {
                $id = $admin->createVehicle(
                    $_POST['bien_so'] ?? '',
                    (float)($_POST['trong_tai_kg'] ?? 0),
                    $_POST['loai_xe'] ?? 'xe_tai_nho'
                );
                response(true, ['id' => $id]);
            }
        }
        break;

    case 'routes':
        if ($method === 'GET') {
            response(true, $admin->getRoutes());
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $admin->deleteRoute((int)($_POST['id'] ?? 0));
                response(true);
            } else {
                $id = $admin->createRoute(
                    (int)($_POST['chi_nhanh_di_id'] ?? 0),
                    (int)($_POST['chi_nhanh_den_id'] ?? 0),
                    (float)($_POST['quang_duong_km'] ?? 0),
                    (int)($_POST['thoi_gian_phut'] ?? 0)
                );
                response(true, ['id' => $id]);
            }
        }
        break;

    case 'branches':
        if ($method === 'GET') {
            response(true, $admin->getBranches());
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $admin->deleteBranch((int)($_POST['id'] ?? 0));
                response(true);
            } else {
                $id = $admin->createBranch(
                    $_POST['ma_chi_nhanh'] ?? '',
                    $_POST['ten_chi_nhanh'] ?? '',
                    $_POST['dia_chi'] ?? '',
                    $_POST['so_dien_thoai'] ?? ''
                );
                response(true, ['id' => $id]);
            }
        }
        break;

    case 'delivery_persons':
        if ($method === 'GET') {
            response(true, $admin->getDeliveryPersons());
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $admin->deleteDeliveryPerson((int)($_POST['id'] ?? 0));
                response(true);
            } else {
                $id = $admin->createDeliveryPerson(
                    $_POST['ho_ten'] ?? '',
                    $_POST['so_dien_thoai'] ?? '',
                    (int)($_POST['chi_nhanh_id'] ?? 0),
                    $_POST['khu_vuc_phu_trach'] ?? ''
                );
                response(true, ['id' => $id]);
            }
        }
        break;

    case 'pricing':
        if ($method === 'GET') {
            response(true, $admin->getPricing());
        } elseif ($method === 'POST') {
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') {
                $admin->deletePricing((int)($_POST['id'] ?? 0));
                response(true);
            } else {
                $id = $admin->createPricing(
                    (float)($_POST['tu_kg'] ?? 0),
                    (float)($_POST['den_kg'] ?? 0),
                    (float)($_POST['phi_co_ban'] ?? 0),
                    (float)($_POST['phi_per_km'] ?? 0)
                );
                response(true, ['id' => $id]);
            }
        }
        break;

    case 'customers':
        response(true, $admin->getCustomers());
        break;
}
