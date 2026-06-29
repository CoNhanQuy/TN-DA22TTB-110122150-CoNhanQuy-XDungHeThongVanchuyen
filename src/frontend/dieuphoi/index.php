<?php
require_once __DIR__ . '/../../backend/config/cauhinh.php';
require_once __DIR__ . '/../../backend/core/helpers.php';
requireRole('nhan_vien_dieu_phoi');
$pageTitle = 'Nhân viên Điều phối';
$moduleCSS = '/DATN/frontend/assets/css/dieuphoi.css?v=2';
$moduleJS  = '/DATN/frontend/assets/js/dieuphoi.js';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="header">
        <div>
            <h1>🚚 Điều phối vận chuyển</h1>
            <div class="header-meta">
                <strong>Họ tên:</strong> <?php echo htmlspecialchars($_SESSION['ho_ten']); ?> &nbsp;|&nbsp;
                <strong>SĐT:</strong> <?php echo htmlspecialchars($_SESSION['so_dien_thoai'] ?? ''); ?> &nbsp;|&nbsp;
                <strong>Vai trò:</strong> Nhân viên điều phối
                <?php if (isset($_SESSION['co_so'])): ?> &nbsp;|&nbsp;
                    <strong>Cơ sở:</strong> <?php echo htmlspecialchars($_SESSION['co_so']); ?>
                <?php endif; ?>
            </div>
        </div>
        <a href="/DATN/backend/api/auth/logout.php" class="logout-btn">🚪 Đăng xuất</a>
    </div>

    <!-- Thống kê -->
    <div class="grid">
        <div class="stat-box">
            <div class="stat-box-icon blue">📦</div>
            <div>
                <h3>Đơn chờ điều phối</h3>
                <div class="value" id="pendingOrders">—</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon green">🚚</div>
            <div>
                <h3>Đợt vận chuyển hôm nay</h3>
                <div class="value" id="todayShipments">—</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon orange">👨‍✈️</div>
            <div>
                <h3>Tài xế có sẵn</h3>
                <div class="value" id="availableDrivers">—</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon purple">🚗</div>
            <div>
                <h3>Xe sẵn sàng</h3>
                <div class="value" id="availableVehicles">—</div>
            </div>
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
                <tr><td colspan="7" style="text-align: center; color: #999;">Đang tải...</td></tr>
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
                <tr><td colspan="8" style="text-align: center; color: #999;">Không có đợt vận chuyển</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Phân công Shipper -->
    <div class="card" style="margin-top: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
            <h2>🚚 Phân công người giao hàng (Shipper)</h2>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <label for="shipperSelect" style="font-weight: 600; margin-right: 0.25rem;">Chọn Shipper:</label>
                <select id="shipperSelect" style="padding: 0.4rem 0.8rem; border: 1px solid #ccc; border-radius: 4px; outline: none; font-size: 0.9rem;">
                    <option value="">-- Chọn shipper --</option>
                </select>
                <button class="btn btn-primary" onclick="assignSelectedToShipper()">✓ Xác nhận phân công</button>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 36px; text-align: center;">
                        <input type="checkbox" id="checkAllShipper" onchange="toggleCheckAllShipper(this)">
                    </th>
                    <th>Mã đơn</th>
                    <th>Trạng thái đơn hàng</th>
                    <th>Người nhận</th>
                    <th>Địa chỉ nhận</th>
                    <th>Hàng hóa</th>
                    <th>Shipper hiện tại</th>
                </tr>
            </thead>
            <tbody id="shipperOrdersList">
                <tr><td colspan="7" style="text-align: center; color: #999; padding: 2rem;">Đang tải danh sách đơn hàng...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Chi tiết đợt vận chuyển -->
<div id="detailModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2>Chi tiết đợt vận chuyển</h2>
            <button class="close-modal" type="button" onclick="closeDetailModal()" title="Đóng">&#x2715;</button>
        </div>
        <div style="padding: 1.25rem 1.5rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; background: #f9f9f9; padding: 1rem; border-radius: 8px;">
                <div><strong>Mã đợt:</strong> <span id="detailMaDot">-</span></div>
                <div><strong>Tuyến:</strong> <span id="detailTuyen">-</span></div>
                <div><strong>Tài xế:</strong> <span id="detailTaiXe">-</span></div>
                <div><strong>Xe:</strong> <span id="detailXe">-</span></div>
                <div><strong>Số đơn hàng:</strong> <span id="detailSoDon">0</span></div>
                <div><strong>Tổng khối lượng:</strong> <span id="detailTongKhoiLuong">0</span> kg</div>
                <div><strong>Trạng thái:</strong> <span id="detailTrangThai">-</span></div>
                <div><strong>Giờ khởi hành:</strong> <span id="detailGioKhoiHanh">-</span></div>
            </div>
            <h3 style="margin-bottom: 1rem; border-bottom: 2px solid #667eea; padding-bottom: 0.5rem;">Danh sách hàng hóa</h3>
            <table style="margin-bottom: 1rem;">
                <thead>
                    <tr>
                        <th>Mã đơn</th><th>Hàng hóa</th><th>Khối lượng (kg)</th>
                        <th>Người gửi</th><th>Người nhận</th><th>Địa chỉ giao</th>
                    </tr>
                </thead>
                <tbody id="detailOrdersList">
                    <tr><td colspan="6" style="text-align: center; color: #999;">Không có đơn hàng</td></tr>
                </tbody>
            </table>
            <button class="btn btn-primary" onclick="closeDetailModal()" style="width: 100%;">Đóng</button>
        </div>
    </div>
</div>

<!-- Modal Tạo đợt vận chuyển -->
<div id="assignModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Tạo đợt vận chuyển mới</h2>
            <button class="close-modal" type="button" onclick="closeAssignModal()" title="Đóng">&#x2715;</button>
        </div>
        <form id="assignForm">
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
            <div id="orderSelectionArea" style="margin-top: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <label style="font-weight: 600; color: #333;">
                        Đơn hàng <span id="orderAreaTitle" style="color: #667eea; font-weight: normal;">(Chọn tuyến để lọc đơn)</span>
                    </label>
                    <div id="bulkActions" style="display: none; gap: 0.5rem; display: flex;">
                        <button type="button" class="btn btn-primary btn-small" onclick="selectAllMatchedOrders()">✓ Chọn tất cả khớp</button>
                        <button type="button" class="btn btn-small" style="background:#eee; color:#555;" onclick="clearAllOrders()">✗ Bỏ chọn tất cả</button>
                    </div>
                </div>
                <div style="max-height: 300px; overflow-y: auto; border: 2px solid #e0e0e0; border-radius: 5px;">
                    <table id="orderCheckTable" style="margin: 0; font-size: 0.85rem;">
                        <thead style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th style="width: 36px; text-align: center;">
                                    <input type="checkbox" id="checkAll" onchange="toggleCheckAll(this)" title="Chọn tất cả">
                                </th>
                                <th>Mã đơn</th><th>Người nhận</th><th>Địa chỉ nhận</th><th>KL (kg)</th>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
