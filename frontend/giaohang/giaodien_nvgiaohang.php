<?php
require_once __DIR__ . '/../../backend/xacthuc_dangnhap.php';
requireRole('shipper');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Người giao hàng - Vận Tải Xanh</title>
    <link rel="stylesheet" href="giaodien_nvgiaohang.css">
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1>🚗 Dashboard Tài xế</h1>
            <div style="margin-top: 0.5rem; font-size: 0.95rem; color: #555;">
                <strong>Họ tên:</strong> <?php echo htmlspecialchars($_SESSION['ho_ten']); ?> |
                <strong>SĐT:</strong> <?php echo htmlspecialchars($_SESSION['so_dien_thoai'] ?? ''); ?> |
                <strong>Vai trò:</strong> Tài xế
                <?php if (isset($_SESSION['co_so'])): ?> |
                    <strong>Cơ sở:</strong> <?php echo htmlspecialchars($_SESSION['co_so']); ?>
                <?php endif; ?>
            </div>
        </div>
        <a href="../../backend/dangxuat.php" class="logout-btn">Đăng xuất</a>
    </div>

    <!-- Tra cứu đơn hàng nhanh -->
    <div class="card">
        <h2>🔍 Tra cứu đơn hàng</h2>
        <p style="margin-bottom: 1rem; color: #666;">Nhập mã đơn hàng hoặc SĐT/CCCD người gửi để kiểm tra chi tiết thông tin giao hàng.</p>
        <div class="tracking-input">
            <input type="text" id="orderCode" placeholder="Nhập mã đơn hàng (VD: DH123...)">
            <input type="text" id="orderPhone" placeholder="Hoặc SĐT người gửi">
            <button class="btn btn-primary" onclick="trackOrder()" style="padding: 0.8rem 1.5rem;">Tra cứu ngay</button>
        </div>
        <div id="trackError" style="color: red; margin-top: 1rem; display: none;"></div>
        <div id="trackResult" style="margin-top: 1.5rem; display: none; background: #f9f9f9; padding: 1.5rem; border-radius: 8px;">
            <div id="trackSummary" style="margin-bottom: 1.5rem;"></div>
            <div id="trackTimeline"></div>
        </div>
    </div>

    <!-- Danh sách đơn hàng đang vận chuyển -->
    <div class="card">
        <h2>📦 Danh sách đơn hàng được phân công</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Người nhận</th>
                        <th>SĐT Nhận</th>
                        <th>Địa chỉ giao</th>
                        <th>Mã đợt</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="ordersList">
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999;">Đang tải dữ liệu...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Nhật ký giao hàng -->
    <div class="card">
        <h2>📒 Nhật ký giao hàng của tôi</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Mã đơn</th>
                        <th>Trạng thái mới</th>
                        <th>Người nhận</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody id="logList">
                    <tr>
                        <td colspan="5" style="text-align: center; color: #999;">Đang tải dữ liệu...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Cập nhật trạng thái -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeStatusModal()">&times;</span>
        <h2 style="margin-bottom: 1.5rem;">Cập nhật trạng thái đơn hàng</h2>
        <form id="statusForm">
            <input type="hidden" id="updateOrderId">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600;">Mã đơn</label>
                <input type="text" id="displayOrderCode" disabled style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; background: #f5f5f5; border-radius: 5px;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600;">Trạng thái mới</label>
                <select id="updateStatus" style="width: 100%; padding: 0.8rem; border: 2px solid #e0e0e0; border-radius: 5px;" required onchange="toggleReceiverField()">
                    <option value="">-- Chọn trạng thái --</option>
                    <option value="dang_van_chuyen">Đang vận chuyển</option>
                    <option value="da_giao_hang">Đã giao hàng (Thành công)</option>
                    <option value="tra_lai">Trả lại (Không giao được)</option>
                </select>
            </div>
            <div style="margin-bottom: 1rem; display: none;" id="receiverFieldGroup">
                <label style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600;">Tên người nhận thực tế <span style="color:red;">*</span></label>
                <input type="text" id="actualReceiver" placeholder="Nhập tên người trực tiếp nhận hàng" style="width: 100%; padding: 0.8rem; border: 2px solid #e0e0e0; border-radius: 5px;">
                <small style="color: #666; display: block; margin-top: 0.3rem;">Xác nhận thông tin khi khách nhận hàng.</small>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600;">Ghi chú (Tùy chọn)</label>
                <textarea id="updateNote" placeholder="Ví dụ: Đã gọi khách 3 lần không nghe máy, hoặc Khách hẹn giao lại..." style="width: 100%; padding: 0.8rem; border: 2px solid #e0e0e0; border-radius: 5px; min-height: 80px; resize: vertical;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;" id="btnUpdateStatus">✓ Cập nhật trạng thái</button>
        </form>
    </div>
</div>

<script src="../scripts.js"></script>
<script src="giaodien_nvgiaohang.js"></script>
</body>
</html>
