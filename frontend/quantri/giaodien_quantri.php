<?php
require_once __DIR__ . '/../../backend/cauhinh.php';
require_once __DIR__ . '/../../backend/xacthuc_dangnhap.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Vận Tải Xanh</title>
    <link rel="stylesheet" href="giaodien_quantri.css">
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">🚚 Admin</div>
        <ul class="nav-menu">
            <li><a href="#" class="nav-link active" data-tab="dashboard">📊 Dashboard</a></li>
            <li><a href="#" class="nav-link" data-tab="users">👥 Quản lý Users</a></li>
            <li><a href="#" class="nav-link" data-tab="vehicles">🚗 Quản lý Xe</a></li>
            <li><a href="#" class="nav-link" data-tab="routes">📍 Quản lý Tuyến</a></li>
            <li><a href="#" class="nav-link" data-tab="delivery_persons">🧑‍🚚 Người giao hàng</a></li>
            <li><a href="#" class="nav-link" data-tab="orders">📦 Quản lý Đơn hàng</a></li>
            <li><a href="#" class="nav-link" data-tab="pricing">💰 Bảng giá</a></li>
            <li><a href="#" class="nav-link" data-tab="customers">🧾 Khách hàng</a></li>
            <li><a href="#" class="nav-link" data-tab="reports">📈 Báo cáo</a></li>
            <li><a href="../../backend/dangxuat.php" style="color: #ffcccc;">🚪 Đăng xuất</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Quản lý hệ thống Vận Tải Xanh</h1>
                <div style="margin-top: 0.5rem; color: #555; font-size: 1rem;">
                    <strong>Họ tên:</strong> <?php echo htmlspecialchars($_SESSION['ho_ten']); ?> |
                    <strong>SĐT:</strong> <?php echo htmlspecialchars($_SESSION['so_dien_thoai'] ?? ''); ?> |
                    <strong>Vai trò:</strong> <?php echo htmlspecialchars($_SESSION['role']); ?>
                    <?php if (isset($_SESSION['co_so'])): ?> |
                        <strong>Cơ sở:</strong> <?php echo htmlspecialchars($_SESSION['co_so']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="user-info">
                <a href="../../backend/dangxuat.php" style="color: #ff6b6b; text-decoration: none;">Đăng xuất</a>
            </div>
        </div>

        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content active">
            <h2 style="margin-bottom: 1.5rem; color: #333;">Thống kê chung</h2>
            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Tổng đơn hàng</h3>
                    <div class="value" id="dashTotalOrders">0</div>
                    <div class="icon">📦</div>
                </div>
                <div class="stat-card">
                    <h3>Đợt vận chuyển</h3>
                    <div class="value" id="dashTotalShipments">0</div>
                    <div class="icon">🚚</div>
                </div>
                <div class="stat-card">
                    <h3>Tài xế hoạt động</h3>
                    <div class="value" id="dashTotalDrivers">0</div>
                    <div class="icon">👨‍✈️</div>
                </div>
                <div class="stat-card">
                    <h3>Xe sẵn sàng</h3>
                    <div class="value" id="dashTotalVehicles">0</div>
                    <div class="icon">🚗</div>
                </div>
            </div>
        </div>

        <!-- Users Management -->
        <div id="users" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="color: #333;">Quản lý Users</h2>
                <button class="btn btn-primary" onclick="openUserModal()">+ Thêm mới</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="usersList">
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999;">Đang tải...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Vehicles Management -->
        <div id="vehicles" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="color: #333;">Quản lý Xe</h2>
                <button class="btn btn-primary" onclick="openVehicleModal()">+ Thêm mới</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Biển số</th>
                        <th>Loại xe</th>
                        <th>Trọng tải (kg)</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="vehiclesList">
                    <tr>
                        <td colspan="6" style="text-align: center; color: #999;">Đang tải...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Routes Management -->
        <div id="routes" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="color: #333;">Quản lý Tuyến đường</h2>
                <button class="btn btn-primary" onclick="openRouteModal()">+ Thêm mới</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên tuyến</th>
                        <th>Điểm đi</th>
                        <th>Điểm đến</th>
                        <th>Quãng đường (km)</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="routesList">
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999;">Đang tải...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Delivery Persons Management -->
        <div id="delivery_persons" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="color: #333;">Quản lý Người giao hàng</h2>
                <button class="btn btn-primary" onclick="openDeliveryPersonModal()">+ Thêm mới</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã</th>
                        <th>Họ tên</th>
                        <th>Số điện thoại</th>
                        <th>CCCD</th>
                        <th>Chi nhánh</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="deliveryPersonsList">
                    <tr>
                        <td colspan="8" style="text-align: center; color: #999;">Đang tải...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Orders Management -->
        <div id="orders" class="tab-content">
            <h2 style="margin-bottom: 1.5rem; color: #333;">Quản lý Đơn hàng</h2>
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Hàng hóa</th>
                        <th>Khối lượng (kg)</th>
                        <th>Phí vận chuyển</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="ordersList">
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999;">Đang tải...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pricing Management -->
        <div id="pricing" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="color: #333;">Bảng giá vận chuyển</h2>
                <button class="btn btn-primary" onclick="openPricingModal()">+ Thêm mới</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Từ KL (kg)</th>
                        <th>Đến KL (kg)</th>
                        <th>Phí cơ bản</th>
                        <th>Phí/km</th>
                        <th>Áp dụng từ</th>
                        <th>Áp dụng đến</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="pricingList">
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999;">Đang tải...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Customers Management -->
        <div id="customers" class="tab-content">
            <h2 style="margin-bottom: 1.5rem; color: #333;">Quản lý Khách hàng</h2>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Họ tên</th>
                        <th>Số điện thoại</th>
                        <th>CCCD</th>
                        <th>Email</th>
                        <th>Địa chỉ</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody id="customersList">
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999;">Đang tải...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Reports -->
        <div id="reports" class="tab-content">
            <h2 style="margin-bottom: 1.5rem; color: #333;">Báo cáo thống kê</h2>

            <!-- Filter -->
            <div style="display:flex; gap: 1rem; flex-wrap:wrap; align-items:end; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin:0; min-width: 220px;">
                    <label>Từ ngày</label>
                    <input type="date" id="reportFromDate">
                </div>
                <div class="form-group" style="margin:0; min-width: 220px;">
                    <label>Đến ngày</label>
                    <input type="date" id="reportToDate">
                </div>
                <button class="btn btn-primary" onclick="loadStatistics()">Lọc</button>
                <button class="btn" style="background:#eee;" onclick="resetReportFilter()">Reset</button>
            </div>

            <!-- Summary cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                <div class="stat-card">
                    <h3>Tổng đơn hàng</h3>
                    <div class="value" id="totalOrders">0</div>
                </div>
                <div class="stat-card">
                    <h3>Đơn thành công</h3>
                    <div class="value" id="successOrders">0</div>
                </div>
                <div class="stat-card">
                    <h3>Tổng doanh thu</h3>
                    <div class="value" id="totalRevenue">0đ</div>
                </div>
                <div class="stat-card">
                    <h3>Tài xế</h3>
                    <div class="value" id="totalDrivers">0</div>
                </div>
            </div>

            <!-- Daily stats tables -->
            <div style="display:grid; grid-template-columns: 1fr; gap: 1.25rem;">
                <div>
                    <h3 style="margin-bottom: 0.75rem; color:#333;">Số lượng đơn theo ngày</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Tổng đơn</th>
                                <th>Đơn hoàn tất</th>
                            </tr>
                        </thead>
                        <tbody id="ordersByDayBody">
                            <tr><td colspan="3" style="text-align:center; color:#999;">Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div>
                    <h3 style="margin-bottom: 0.75rem; color:#333;">Doanh thu theo ngày</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody id="revenueByDayBody">
                            <tr><td colspan="2" style="text-align:center; color:#999;">Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeUserModal()">&times;</span>
        <h2 style="margin-bottom: 1.5rem;">Thêm/Sửa User</h2>
        <form id="userForm">
            <input type="hidden" id="userId" value="">
            <div class="form-group">
                <label>Họ tên</label>
                <input type="text" id="userName" required>
            </div>
            <div class="form-group" style="display:none;">
                <label>Email (chưa dùng)</label>
                <input type="email" id="userEmail">
            </div>
            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="tel" id="userPhone" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu (để trống nếu không đổi)</label>
                <input type="password" id="userPassword">
            </div>
            <div class="form-group">
                <label>Vai trò</label>
                <select id="userRole" required>
                    <option value="admin">Admin</option>
                    <option value="nhan_vien_tiep_nhan">Nhân viên tiếp nhận</option>
                    <option value="nhan_vien_dieu_phoi">Nhân viên điều phối</option>
                    <option value="tai_xe">Tài xế</option>
                    <option value="khach_hang">Khách hàng</option>
                </select>
            </div>
            <div class="form-group">
                <label>Trạng thái</label>
                <select id="userStatus">
                    <option value="1">Hoạt động</option>
                    <option value="0">Khóa</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Lưu</button>
        </form>
    </div>
</div>

<!-- Vehicle Modal -->
<div id="vehicleModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeVehicleModal()">&times;</span>
        <h2 style="margin-bottom: 1.5rem;">Thêm/Sửa Xe</h2>
        <form id="vehicleForm">
            <input type="hidden" id="vehicleId" value="">
            <div class="form-group">
                <label>Biển số</label>
                <input type="text" id="vehiclePlate" required>
            </div>
            <div class="form-group">
                <label>Trọng tải (kg)</label>
                <input type="number" step="0.01" id="vehicleCapacity" required>
            </div>
            <div class="form-group">
                <label>Trạng thái</label>
                <select id="vehicleStatus">
                    <option value="1">Sẵn sàng</option>
                    <option value="0">Không hoạt động</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Lưu</button>
        </form>
    </div>
</div>

<!-- Route Modal -->
<div id="routeModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeRouteModal()">&times;</span>
        <h2 style="margin-bottom: 1.5rem;">Thêm/Sửa Tuyến</h2>
        <form id="routeForm">
            <input type="hidden" id="routeId" value="">
            <div class="form-group">
                <label>Tên tuyến</label>
                <input type="text" id="routeName" required>
            </div>
            <div class="form-group">
                <label>Điểm đi</label>
                <input type="text" id="routeFrom" required>
            </div>
            <div class="form-group">
                <label>Điểm đến</label>
                <input type="text" id="routeTo" required>
            </div>
            <div class="form-group">
                <label>Quãng đường (km)</label>
                <input type="number" step="0.01" id="routeDistance" required>
            </div>
            <div class="form-group">
                <label>Thời gian dự kiến (phút)</label>
                <input type="number" step="1" id="routeTime" value="0">
            </div>
            <div class="form-group">
                <label>Trạng thái</label>
                <select id="routeStatus">
                    <option value="1">Hoạt động</option>
                    <option value="0">Tạm dừng</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Lưu</button>
        </form>
    </div>
</div>

<!-- Delivery Person Modal -->
<div id="deliveryPersonModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeDeliveryPersonModal()">&times;</span>
        <h2 style="margin-bottom: 1.5rem;">Thêm/Sửa Người giao hàng</h2>
        <form id="deliveryPersonForm">
            <input type="hidden" id="deliveryPersonId" value="">
            <div class="form-group">
                <label>Mã người giao (tùy chọn)</label>
                <input type="text" id="deliveryPersonCode">
            </div>
            <div class="form-group">
                <label>Họ tên</label>
                <input type="text" id="deliveryPersonName" required>
            </div>
            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="tel" id="deliveryPersonPhone" required>
            </div>
            <div class="form-group">
                <label>CCCD</label>
                <input type="text" id="deliveryPersonCccd">
            </div>
            <div class="form-group">
                <label>Chi nhánh ID (tùy chọn)</label>
                <input type="number" step="1" id="deliveryPersonBranchId">
            </div>
            <div class="form-group">
                <label>Trạng thái</label>
                <select id="deliveryPersonStatus">
                    <option value="1">Hoạt động</option>
                    <option value="0">Tạm dừng</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Lưu</button>
        </form>
    </div>
</div>

<!-- Pricing Modal -->
<div id="pricingModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closePricingModal()">&times;</span>
        <h2 style="margin-bottom: 1.5rem;">Thêm/Sửa Bảng giá</h2>
        <form id="pricingForm">
            <input type="hidden" id="pricingId" value="">
            <div class="form-group">
                <label>Từ KL (kg)</label>
                <input type="number" step="0.01" id="pricingFromKg" required>
            </div>
            <div class="form-group">
                <label>Đến KL (kg)</label>
                <input type="number" step="0.01" id="pricingToKg" required>
            </div>
            <div class="form-group">
                <label>Phí cơ bản</label>
                <input type="number" step="1" id="pricingBaseFee" required>
            </div>
            <div class="form-group">
                <label>Phí/km</label>
                <input type="number" step="1" id="pricingPerKm">
            </div>
            <div class="form-group">
                <label>Áp dụng từ (YYYY-MM-DD)</label>
                <input type="date" id="pricingApplyFrom">
            </div>
            <div class="form-group">
                <label>Áp dụng đến (YYYY-MM-DD)</label>
                <input type="date" id="pricingApplyTo">
            </div>
            <div class="form-group">
                <label>Trạng thái</label>
                <select id="pricingStatus">
                    <option value="1">Hoạt động</option>
                    <option value="0">Tạm dừng</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Lưu</button>
        </form>
    </div>
</div>

<script src="giaodien_quantri.js"></script>
</body>
</html>
