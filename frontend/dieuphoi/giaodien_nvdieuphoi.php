<?php
require_once __DIR__ . '/../../backend/xacthuc_dangnhap.php';
requireRole('nhan_vien_dieu_phoi');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhân viên Điều phối - Vận Tải Xanh</title>
    <link rel="stylesheet" href="giaodien_nvdieuphoi.css">
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1>🚚 Điều phối vận chuyển</h1>
            <div style="margin-top: 0.5rem; font-size: 0.95rem; color: #555;">
                <strong>Họ tên:</strong> <?php echo htmlspecialchars($_SESSION['ho_ten']); ?> |
                <strong>SĐT:</strong> <?php echo htmlspecialchars($_SESSION['so_dien_thoai'] ?? ''); ?> |
                <strong>Vai trò:</strong> Nhân viên điều phối
                <?php if (isset($_SESSION['co_so'])): ?> |
                    <strong>Cơ sở:</strong> <?php echo htmlspecialchars($_SESSION['co_so']); ?>
                <?php endif; ?>
            </div>
        </div>
        <a href="../../backend/dangxuat.php" class="logout-btn">Đăng xuất</a>
    </div>

    <!-- Thống kê -->
    <div class="grid">
        <div class="stat-box">
            <h3>Đơn chờ điều phối</h3>
            <div class="value" id="pendingOrders">0</div>
        </div>
        <div class="stat-box">
            <h3>Đợt vận chuyển hôm nay</h3>
            <div class="value" id="todayShipments">0</div>
        </div>
        <div class="stat-box">
            <h3>Tài xế có sẵn</h3>
            <div class="value" id="availableDrivers">0</div>
        </div>
        <div class="stat-box">
            <h3>Xe sẵn sàng</h3>
            <div class="value" id="availableVehicles">0</div>
        </div>
    </div>

    <!-- Đơn hàng chờ điều phối -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>📦 Đơn hàng chờ điều phối</h2>
            <button class="btn btn-primary" onclick="openAssignModal()">+ Tạo đợt vận chuyển</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Người gửi</th>
                    <th>Người nhận</th>
                    <th>Khối lượng (kg)</th>
                    <th>Địa chỉ nhận</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="pendingOrdersList">
                <tr>
                    <td colspan="7" style="text-align: center; color: #999;">Đang tải...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Đợt vận chuyển -->
    <div class="card">
        <h2>🚚 Danh sách đợt vận chuyển</h2>
        <table>
            <thead>
                <tr>
                    <th>Mã đợt</th>
                    <th>Tuyến</th>
                    <th>Tài xế</th>
                    <th>Xe</th>
                    <th>Số đơn</th>
                    <th>Trạng thái</th>
                    <th>Giờ khởi hành</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="shipmentsList">
                <tr>
                    <td colspan="8" style="text-align: center; color: #999;">Không có đợt vận chuyển</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Chi tiết đợt vận chuyển -->
<div id="detailModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <span class="close-modal" onclick="closeDetailModal()">&times;</span>
        <h2 style="margin-bottom: 1.5rem;">Chi tiết đợt vận chuyển</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem; background: #f9f9f9; padding: 1rem; border-radius: 5px;">
            <div>
                <strong>Mã đợt:</strong> <span id="detailMaDot">-</span>
            </div>
            <div>
                <strong>Tuyến:</strong> <span id="detailTuyen">-</span>
            </div>
            <div>
                <strong>Tài xế:</strong> <span id="detailTaiXe">-</span>
            </div>
            <div>
                <strong>Xe:</strong> <span id="detailXe">-</span>
            </div>
            <div>
                <strong>Số đơn hàng:</strong> <span id="detailSoDon">0</span>
            </div>
            <div>
                <strong>Tổng khối lượng:</strong> <span id="detailTongKhoiLuong">0</span> kg
            </div>
            <div>
                <strong>Trạng thái:</strong> <span id="detailTrangThai">-</span>
            </div>
            <div>
                <strong>Giờ khởi hành:</strong> <span id="detailGioKhoiHanh">-</span>
            </div>
        </div>

        <h3 style="margin-bottom: 1rem; border-bottom: 2px solid #667eea; padding-bottom: 0.5rem;">Danh sách hàng hóa</h3>
        <table style="margin-bottom: 1rem;">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Hàng hóa</th>
                    <th>Khối lượng (kg)</th>
                    <th>Người gửi</th>
                    <th>Người nhận</th>
                    <th>Địa chỉ giao</th>
                </tr>
            </thead>
            <tbody id="detailOrdersList">
                <tr>
                    <td colspan="6" style="text-align: center; color: #999;">Không có đơn hàng</td>
                </tr>
            </tbody>
        </table>

        <button class="btn btn-primary" onclick="closeDetailModal()" style="width: 100%;">Đóng</button>
    </div>
</div>

<!-- Modal Gán đơn hàng -->
<div id="assignModal" class="modal">
    <div class="modal-content modal-large">
        <span class="close-modal" onclick="closeAssignModal()">&times;</span>
        <h2 style="margin-bottom: 1.5rem;">Tạo đợt vận chuyển mới</h2>
        <form id="assignForm">

            <!-- Bước 1: Chọn tuyến → tự động lọc đơn -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Chọn tuyến đường <span style="color:red">*</span></label>
                    <select id="routeSelect" required onchange="onRouteChange()">
                        <option value="">-- Chọn tuyến --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Giờ khởi hành <span style="color:red">*</span></label>
                    <input type="datetime-local" id="departureTime" required>
                </div>
                <div class="form-group">
                    <label>Chọn tài xế <span style="color:red">*</span></label>
                    <select id="driverSelect" required>
                        <option value="">-- Chọn tài xế --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Chọn xe <span style="color:red">*</span></label>
                    <select id="vehicleSelect" required>
                        <option value="">-- Chọn xe --</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Ghi chú</label>
                <input type="text" id="notes" placeholder="Ghi chú thêm (nếu có)">
            </div>

            <!-- Khu vực chọn đơn hàng theo điểm đến -->
            <div id="orderSelectionArea" style="margin-top: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <label style="font-weight: 600; color: #333;">
                        Đơn hàng 
                        <span id="orderAreaTitle" style="color: #667eea; font-weight: normal;">(Chọn tuyến để lọc đơn)</span>
                    </label>
                    <div id="bulkActions" style="display: none; gap: 0.5rem; display: flex;">
                        <button type="button" class="btn btn-primary btn-small" onclick="selectAllMatchedOrders()">✓ Chọn tất cả khớp</button>
                        <button type="button" class="btn btn-small" style="background:#eee; color:#555;" onclick="clearAllOrders()">✗ Bỏ chọn tất cả</button>
                    </div>
                </div>

                <!-- Bảng đơn hàng với checkbox -->
                <div style="max-height: 300px; overflow-y: auto; border: 2px solid #e0e0e0; border-radius: 5px;">
                    <table id="orderCheckTable" style="margin: 0; font-size: 0.85rem;">
                        <thead style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th style="width: 36px; text-align: center;">
                                    <input type="checkbox" id="checkAll" onchange="toggleCheckAll(this)" title="Chọn tất cả">
                                </th>
                                <th>Mã đơn</th>
                                <th>Người nhận</th>
                                <th>Địa chỉ nhận</th>
                                <th>KL (kg)</th>
                                <th style="width: 80px; text-align: center;">Khớp</th>
                            </tr>
                        </thead>
                        <tbody id="orderCheckList">
                            <tr><td colspan="6" style="text-align: center; color: #999; padding: 2rem;">Chọn tuyến để hiển thị đơn hàng</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="orderSummary" style="margin-top: 0.5rem; font-size: 0.85rem; color: #667eea; font-weight: 600;"></div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">✓ Tạo đợt vận chuyển</button>
        </form>
    </div>
</div>

<script src="giaodien_nvdieuphoi.js"></script>
</body>
</html>