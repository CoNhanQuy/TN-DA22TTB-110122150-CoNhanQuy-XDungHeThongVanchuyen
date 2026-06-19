<?php
require_once __DIR__ . '/../../backend/xacthuc_dangnhap.php';
requireRole('tai_xe');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài xế - Vận Tải Xanh</title>
    <link rel="stylesheet" href="giaodien_taixe.css">
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1>🚚 Dashboard Tài xế - Quản lý Chuyến xe</h1>
            <div style="margin-top: 0.5rem; font-size: 0.95rem; color: #555;">
                <strong>Họ tên:</strong> <?php echo htmlspecialchars($_SESSION['ho_ten']); ?> |
                <strong>SĐT:</strong> <?php echo htmlspecialchars($_SESSION['so_dien_thoai'] ?? ''); ?> |
                <strong>Vai trò:</strong> Tài xế Lái xe
            </div>
        </div>
        <a href="../../backend/dangxuat.php" class="logout-btn">Đăng xuất</a>
    </div>

    <!-- Đợt vận chuyển -->
    <div class="card">
        <h2>🚚 Các chuyến xe (Đợt vận chuyển) của tôi</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Mã đợt</th>
                        <th>Tuyến đường</th>
                        <th>Biển số xe</th>
                        <th>Khởi hành</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="shipmentsList">
                    <tr>
                        <td colspan="6" style="text-align: center; color: #999;">Đang tải dữ liệu...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Cập nhật trạng thái chuyến xe -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeStatusModal()">&times;</span>
        <h2 style="margin-bottom: 1.5rem;">Cập nhật trạng thái chuyến xe</h2>
        <form id="statusForm">
            <input type="hidden" id="updateDotId">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600;">Mã chuyến (Đợt)</label>
                <input type="text" id="displayDotCode" disabled style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; background: #f5f5f5; border-radius: 5px;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600;">Trạng thái mới</label>
                <select id="updateStatus" style="width: 100%; padding: 0.8rem; border: 2px solid #e0e0e0; border-radius: 5px;" required>
                    <option value="">-- Chọn trạng thái --</option>
                    <option value="dang_chay">Đang chạy (Bắt đầu hành trình)</option>
                    <option value="hoan_thanh">Hoàn thành (Đã đến nơi)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;" id="btnUpdateStatus">✓ Cập nhật trạng thái</button>
        </form>
    </div>
</div>

<script src="../scripts.js"></script>
<script src="giaodien_taixe.js"></script>
</body>
</html>
