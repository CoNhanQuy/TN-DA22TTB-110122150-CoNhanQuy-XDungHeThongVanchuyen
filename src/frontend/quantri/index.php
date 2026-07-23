<?php
require_once __DIR__ . '/../../backend/config/cauhinh.php';
require_once __DIR__ . '/../../backend/core/helpers.php';
requireRole('admin');
$pageTitle = 'Quản trị hệ thống';
$moduleCSS = APP_BASE_URL . '/frontend/assets/css/quantri.css';
$moduleJS  = APP_BASE_URL . '/frontend/assets/js/quantri.js';
include __DIR__ . '/../includes/header.php';
$adminName = htmlspecialchars($_SESSION['ho_ten'] ?? 'Admin');
$adminPhone = htmlspecialchars($_SESSION['so_dien_thoai'] ?? '');
$adminRole  = htmlspecialchars($_SESSION['role'] ?? 'admin');
$adminInitial = mb_strtoupper(mb_substr($_SESSION['ho_ten'] ?? 'A', 0, 1, 'UTF-8'), 'UTF-8');
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<div class="container">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">🚚</div>
            <div>
                <div class="brand-name">Vận Tải Xanh</div>
                <div class="brand-sub">Admin Dashboard</div>
            </div>
        </div>

        <div class="nav-section-title">Tổng quan</div>
        <ul class="nav-menu">
            <li><a href="#" class="nav-link active" data-tab="dashboard">
                <span class="nav-icon">📊</span> Dashboard
            </a></li>
        </ul>

        <div class="nav-section-title">Quản lý</div>
        <ul class="nav-menu">
            <li><a href="#" class="nav-link" data-tab="users">
                <span class="nav-icon">👥</span> Người dùng
            </a></li>
            <li><a href="#" class="nav-link" data-tab="delivery_persons">
                <span class="nav-icon">🛵</span> Người giao hàng
            </a></li>
            <li><a href="#" class="nav-link" data-tab="customers">
                <span class="nav-icon">🧾</span> Khách hàng
            </a></li>
            <li><a href="#" class="nav-link" data-tab="orders">
                <span class="nav-icon">📦</span> Đơn hàng
            </a></li>
            <li><a href="#" class="nav-link" data-tab="vehicles">
                <span class="nav-icon">🚗</span> Phương tiện
            </a></li>
            <li><a href="#" class="nav-link" data-tab="routes">
                <span class="nav-icon">📍</span> Tuyến đường
            </a></li>
            <li><a href="#" class="nav-link" data-tab="pricing">
                <span class="nav-icon">💰</span> Bảng giá
            </a></li>
        </ul>

        <div class="nav-section-title">Phân tích</div>
        <ul class="nav-menu">
            <li><a href="#" class="nav-link" data-tab="reports">
                <span class="nav-icon">📈</span> Báo cáo
            </a></li>
            <li><a href="#" class="nav-link" data-tab="gpsmap" id="navGpsMap">
                <span class="nav-icon">🗺️</span> Bản đồ GPS
            </a></li>
        </ul>

        <div class="sidebar-footer">
            <a href="<?php echo APP_BASE_URL; ?>/backend/api/auth/logout.php">
                <span class="nav-icon">🚪</span> Đăng xuất
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1 id="topbarTitle">Dashboard</h1>
                <p id="topbarSub">Tổng quan hệ thống vận tải</p>
            </div>
            <div class="topbar-right">
                <div class="topbar-user">
                    <div class="topbar-avatar"><?= $adminInitial ?></div>
                    <div>
                        <div class="topbar-user-name"><?= $adminName ?></div>
                        <div class="topbar-user-role"><?= $adminRole ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Body -->
        <div class="page-body">

            <!-- ── DASHBOARD ── -->
            <div id="dashboard" class="tab-content active">
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">📦</div>
                        <div class="stat-info">
                            <h3>Tổng đơn hàng</h3>
                            <div class="value" id="dashTotalOrders">—</div>
                            <div class="trend">↑ Hôm nay</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">🚚</div>
                        <div class="stat-info">
                            <h3>Đợt vận chuyển</h3>
                            <div class="value" id="dashTotalShipments">—</div>
                            <div class="trend">Đang hoạt động</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">👨‍✈️</div>
                        <div class="stat-info">
                            <h3>Tài xế hoạt động</h3>
                            <div class="value" id="dashTotalDrivers">—</div>
                            <div class="trend">Sẵn sàng</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">🚗</div>
                        <div class="stat-info">
                            <h3>Xe sẵn sàng</h3>
                            <div class="value" id="dashTotalVehicles">—</div>
                            <div class="trend">Khả dụng</div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-header">
                        <h2>🕐 Đơn hàng gần đây</h2>
                        <button class="btn btn-ghost btn-sm" onclick="switchTab('orders')">Xem tất cả →</button>
                    </div>
                    <div class="content-card-table table-wrap">
                        <table>
                            <thead>
                                <tr><th>Mã đơn</th><th>Hàng hóa</th><th>Khối lượng</th><th>Phí</th><th>Trạng thái</th><th>Ngày tạo</th></tr>
                            </thead>
                            <tbody id="dashRecentOrders">
                                <tr class="empty-row"><td colspan="6">Đang tải dữ liệu...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── USERS ── -->
            <div id="users" class="tab-content">
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>👥 Danh sách người dùng</h2>
                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <select id="userRoleFilter" onchange="filterUserByRole()" style="padding: 0.4rem 0.75rem; border-radius: 6px; border: 1px solid var(--border); font-size: 0.85rem; font-weight: 500; outline: none; background-color: #fff; cursor: pointer;">
                                <option value="">Tất cả vai trò</option>
                                <option value="admin">Admin</option>
                                <option value="nhan_vien_tiep_nhan">Tiếp nhận</option>
                                <option value="nhan_vien_dieu_phoi">Điều phối</option>
                                <option value="tai_xe">Tài xế</option>
                                <option value="shipper">Người giao hàng</option>
                                <option value="khach_hang">Khách hàng</option>
                            </select>
                            <button class="btn btn-primary btn-sm" onclick="openUserModal()">+ Thêm mới</button>
                        </div>
                    </div>
                    <div class="content-card-table table-wrap">
                        <table>
                            <thead><tr><th>#</th><th class="sortable" id="userSortName" onclick="toggleUserSort('ho_ten')">Họ tên <span class="sort-icon">↕</span></th><th>Số điện thoại</th><th class="sortable" id="userSortRole" onclick="toggleUserSort('vai_tro')">Vai trò <span class="sort-icon">↕</span></th><th>Trạng thái</th><th>Hành động</th></tr></thead>
                            <tbody id="usersList"><tr class="empty-row"><td colspan="6">Đang tải...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── VEHICLES ── -->
            <div id="vehicles" class="tab-content">
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>🚗 Danh sách phương tiện</h2>
                        <button class="btn btn-primary btn-sm" onclick="openVehicleModal()">+ Thêm mới</button>
                    </div>
                    <div class="content-card-table table-wrap">
                        <table>
                            <thead><tr><th>#</th><th>Biển số</th><th>Loại xe</th><th>Trọng tải (kg)</th><th>Trạng thái</th><th>Hành động</th></tr></thead>
                            <tbody id="vehiclesList"><tr class="empty-row"><td colspan="6">Đang tải...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── ROUTES ── -->
            <div id="routes" class="tab-content">
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>📍 Danh sách tuyến đường</h2>
                        <button class="btn btn-primary btn-sm" onclick="openRouteModal()">+ Thêm mới</button>
                    </div>
                    <div class="content-card-table table-wrap">
                        <table>
                            <thead><tr><th>#</th><th>Tên tuyến</th><th>Điểm đi</th><th>Điểm đến</th><th>Quãng đường (km)</th><th>Trạng thái</th><th>Hành động</th></tr></thead>
                            <tbody id="routesList"><tr class="empty-row"><td colspan="7">Đang tải...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── DELIVERY PERSONS ── -->
            <div id="delivery_persons" class="tab-content">
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>🛵 Người giao hàng</h2>
                        <button class="btn btn-primary btn-sm" onclick="openDeliveryPersonModal()">+ Thêm mới</button>
                    </div>
                    <div class="content-card-table table-wrap">
                        <table>
                            <thead><tr><th>#</th><th class="sortable" id="deliverySortName" onclick="toggleDeliverySort('ho_ten')">Họ tên <span class="sort-icon">↕</span></th><th>Số điện thoại</th><th>Khu vực phụ trách</th><th>Chi nhánh</th><th>Trạng thái</th><th>Hành động</th></tr></thead>
                            <tbody id="deliveryPersonsList"><tr class="empty-row"><td colspan="7">Đang tải...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── ORDERS ── -->
            <div id="orders" class="tab-content">
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>📦 Quản lý đơn hàng</h2>
                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <select id="orderStatusFilter" onchange="filterOrderByStatus()" style="padding: 0.4rem 0.75rem; border-radius: 6px; border: 1px solid var(--border); font-size: 0.85rem; font-weight: 500; outline: none; background-color: #fff; cursor: pointer;">
                                <option value="">Tất cả trạng thái</option>
                                <option value="cho_tiep_nhan">Chờ tiếp nhận</option>
                                <option value="da_nhap_kho">Đã nhập kho</option>
                                <option value="dang_van_chuyen">Đang vận chuyển</option>
                                <option value="da_den_kho_dich">Đã đến kho</option>
                                <option value="dang_giao_hang">Đang giao hàng</option>
                                <option value="hoan_tat">Giao hàng thành công</option>
                            </select>
                        </div>
                    </div>
                    <div class="content-card-table table-wrap">
                        <table>
                            <thead><tr><th>Mã đơn</th><th>Hàng hóa</th><th>Khối lượng (kg)</th><th>Phí vận chuyển</th><th>Trạng thái</th><th>Ngày tạo</th><th>Hành động</th></tr></thead>
                            <tbody id="ordersList"><tr class="empty-row"><td colspan="7">Đang tải...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── PRICING ── -->
            <div id="pricing" class="tab-content">
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>💰 Bảng giá vận chuyển</h2>
                        <button class="btn btn-primary btn-sm" onclick="openPricingModal()">+ Thêm mới</button>
                    </div>
                    <div class="content-card-table table-wrap">
                        <table>
                            <thead><tr><th>Từ KL (kg)</th><th>Đến KL (kg)</th><th>Phí cơ bản</th><th>Phí/km</th><th>Hành động</th></tr></thead>
                            <tbody id="pricingList"><tr class="empty-row"><td colspan="5">Đang tải...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── CUSTOMERS ── -->
            <div id="customers" class="tab-content">
                <div class="content-card">
                    <div class="content-card-header">
                        <h2>🧾 Danh sách khách hàng</h2>
                    </div>
                    <div class="content-card-table table-wrap">
                        <table>
                            <thead><tr><th>#</th><th class="sortable" id="customerSortName" onclick="toggleCustomerSort('ho_ten')">Họ tên <span class="sort-icon">↕</span></th><th>Số điện thoại</th><th>CCCD</th><th>Địa chỉ</th><th>Ngày tạo</th></tr></thead>
                            <tbody id="customersList"><tr class="empty-row"><td colspan="6">Đang tải...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── REPORTS ── -->
            <div id="reports" class="tab-content">
                <div class="report-filters" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 1.5rem; background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                    <div class="form-group" style="margin: 0; min-width: 150px;">
                        <label style="font-weight: 600; font-size: 0.85rem; display: block; margin-bottom: 0.4rem;">Khoảng thời gian</label>
                        <select id="reportInterval" onchange="loadStatistics()" style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid var(--border);">
                            <option value="day">Theo ngày</option>
                            <option value="week">Theo tuần</option>
                            <option value="month">Theo tháng</option>
                            <option value="quarter">Theo quý</option>
                            <option value="year">Theo năm</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0; min-width: 140px;">
                        <label style="font-weight: 600; font-size: 0.85rem; display: block; margin-bottom: 0.4rem;">Từ ngày</label>
                        <input type="date" id="reportFromDate" style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid var(--border);">
                    </div>
                    <div class="form-group" style="margin: 0; min-width: 140px;">
                        <label style="font-weight: 600; font-size: 0.85rem; display: block; margin-bottom: 0.4rem;">Đến ngày</label>
                        <input type="date" id="reportToDate" style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid var(--border);">
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-primary" onclick="loadStatistics()" style="padding: 0.6rem 1.25rem;">Lọc dữ liệu</button>
                        <button class="btn btn-ghost" onclick="resetReportFilter()" style="padding: 0.6rem 1.25rem;">Đặt lại</button>
                    </div>
                </div>

                <div class="dashboard-grid" style="margin-bottom: 1.5rem;">
                    <div class="stat-card">
                        <div class="stat-icon blue">📦</div>
                        <div class="stat-info"><h3>Tổng đơn đã gửi</h3><div class="value" id="totalOrders">—</div></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">✅</div>
                        <div class="stat-info"><h3>Đơn hoàn tất</h3><div class="value" id="successOrders">—</div></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">💵</div>
                        <div class="stat-info"><h3>Doanh thu</h3><div class="value" id="totalRevenue">—</div></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">👨‍✈️</div>
                        <div class="stat-info"><h3>Tổng số tài xế</h3><div class="value" id="totalDrivers">—</div></div>
                    </div>
                </div>

                <!-- ── CHARTS ── -->
                <div class="reports-grid" style="margin-bottom: 1.5rem;">
                    <div class="content-card" style="padding: 1.25rem; background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                        <div class="content-card-header" style="border-bottom: none; padding: 0 0 1rem 0; margin-bottom: 0.5rem;">
                            <h2 style="font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">📈 Xu hướng Đơn hàng & Doanh thu</h2>
                        </div>
                        <div style="position: relative; height: 320px; width: 100%;">
                            <canvas id="chartTrend"></canvas>
                        </div>
                    </div>
                    <div class="content-card" style="padding: 1.25rem; background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                        <div class="content-card-header" style="border-bottom: none; padding: 0 0 1rem 0; margin-bottom: 0.5rem;">
                            <h2 style="font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">🗺️ Phân tích theo Tỉnh thành gửi</h2>
                        </div>
                        <div style="position: relative; height: 320px; width: 100%;">
                            <canvas id="chartProvince"></canvas>
                        </div>
                    </div>
                </div>

                <!-- ── TABLES ── -->
                <div class="reports-grid">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h2>📅 Thống kê chi tiết theo thời gian</h2>
                        </div>
                        <div class="content-card-table table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Đơn đã gửi</th>
                                        <th>Đơn hoàn tất</th>
                                        <th>Doanh thu</th>
                                    </tr>
                                </thead>
                                <tbody id="ordersByTimeBody">
                                    <tr class="empty-row"><td colspan="4">Đang tải dữ liệu...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="content-card">
                        <div class="content-card-header">
                            <h2>📍 Thống kê chi tiết theo tỉnh thành gửi</h2>
                        </div>
                        <div class="content-card-table table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tỉnh thành gửi</th>
                                        <th>Đơn đã gửi</th>
                                        <th>Đơn hoàn tất</th>
                                        <th>Doanh thu</th>
                                    </tr>
                                </thead>
                                <tbody id="ordersByProvinceBody">
                                    <tr class="empty-row"><td colspan="4">Đang tải dữ liệu...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── GPS MAP TAB ── -->
            <div id="gpsmap" class="tab-content">
                <div class="gps-map-layout">
                    <!-- Bản đồ -->
                    <div class="gps-map-panel">
                        <div class="gps-map-header">
                            <div>
                                <h2 class="gps-map-title">🗺️ Bản đồ GPS thời gian thực</h2>
                                <p class="gps-map-sub">Hiển thị vị trí tài xế và shipper đang hoạt động. Tự động cập nhật mỗi 30 giây.</p>
                            </div>
                            <div class="gps-map-actions">
                                <span id="gpsMapStatus" class="gps-status-badge">
                                    <span class="gps-pulse"></span> Đang kết nối...
                                </span>
                                <button class="btn btn-primary btn-sm" onclick="refreshGpsMap()" id="btnRefreshGps">
                                    🔄 Làm mới
                                </button>
                            </div>
                        </div>
                        <div id="gpsLeafletMap" class="gps-leaflet-container"></div>
                        <div class="gps-map-legend">
                            <div class="legend-item"><span class="legend-dot legend-driver"></span> Tài xế đang di chuyển</div>
                            <div class="legend-item"><span class="legend-dot legend-shipper"></span> Shipper đang giao hàng</div>
                            <div class="legend-item"><span class="legend-dot legend-branch"></span> Chi nhánh</div>
                        </div>
                    </div>

                    <!-- Panel thông tin bên cạnh -->
                    <div class="gps-info-panel">
                        <div class="gps-summary-cards">
                            <div class="gps-summary-card driver-card">
                                <div class="gps-sum-icon">🚚</div>
                                <div>
                                    <div class="gps-sum-count" id="gpsDriverCount">0</div>
                                    <div class="gps-sum-label">Tài xế đang chạy</div>
                                </div>
                            </div>
                            <div class="gps-summary-card shipper-card">
                                <div class="gps-sum-icon">🛵</div>
                                <div>
                                    <div class="gps-sum-count" id="gpsShipperCount">0</div>
                                    <div class="gps-sum-label">Shipper đang giao</div>
                                </div>
                            </div>
                        </div>

                        <!-- Danh sách tài xế -->
                        <div class="gps-list-section">
                            <h3 class="gps-list-title">🚚 Tài xế</h3>
                            <div id="gpsDriverList" class="gps-marker-list">
                                <p class="gps-empty">Chưa có tài xế đang di chuyển</p>
                            </div>
                        </div>

                        <!-- Danh sách shipper -->
                        <div class="gps-list-section">
                            <h3 class="gps-list-title">🛵 Shipper</h3>
                            <div id="gpsShipperList" class="gps-marker-list">
                                <p class="gps-empty">Chưa có shipper đang giao hàng</p>
                            </div>
                        </div>

                        <div class="gps-last-update">
                            Cập nhật lúc: <span id="gpsLastUpdate">--</span>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div><!-- /container -->

<!-- ===== MODALS ===== -->

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>👤 Thêm / Sửa người dùng</h2>
            <span class="close-modal" onclick="closeUserModal()">✕</span>
        </div>
        <form id="userForm">
            <div class="modal-body">
                <input type="hidden" id="userId" value="">
                <div class="form-group"><label>Họ tên</label><input type="text" id="userName" required placeholder="Nhập họ tên..."></div>
                <div class="form-group"><label>Số điện thoại</label><input type="tel" id="userPhone" required placeholder="0xxxxxxxxx"></div>
                <div class="form-group"><label>Mật khẩu <small style="color:var(--text-muted)">(để trống nếu không đổi)</small></label><input type="password" id="userPassword" placeholder="••••••••"></div>
                <div class="form-row">
                    <div class="form-group"><label>Vai trò</label>
                        <select id="userRole" required>
                            <option value="admin">Admin</option>
                            <option value="nhan_vien_tiep_nhan">Nhân viên tiếp nhận</option>
                            <option value="nhan_vien_dieu_phoi">Nhân viên điều phối</option>
                            <option value="tai_xe">Tài xế</option>
                            <option value="shipper">Người giao hàng</option>
                            <option value="khach_hang">Khách hàng</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Trạng thái</label>
                        <select id="userStatus">
                            <option value="1">Hoạt động</option>
                            <option value="0">Khóa</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeUserModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- Vehicle Modal -->
<div id="vehicleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>🚗 Thêm / Sửa phương tiện</h2>
            <span class="close-modal" onclick="closeVehicleModal()">✕</span>
        </div>
        <form id="vehicleForm">
            <div class="modal-body">
                <input type="hidden" id="vehicleId" value="">
                <div class="form-group"><label>Biển số xe</label><input type="text" id="vehiclePlate" required placeholder="VD: 51A-12345"></div>
                <div class="form-row">
                    <div class="form-group"><label>Trọng tải (kg)</label><input type="number" step="0.01" id="vehicleCapacity" required placeholder="0"></div>
                    <div class="form-group"><label>Trạng thái</label>
                        <select id="vehicleStatus">
                            <option value="1">Sẵn sàng</option>
                            <option value="0">Không hoạt động</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeVehicleModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- Route Modal -->
<div id="routeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📍 Thêm / Sửa tuyến đường</h2>
            <span class="close-modal" onclick="closeRouteModal()">✕</span>
        </div>
        <form id="routeForm">
            <div class="modal-body">
                <input type="hidden" id="routeId" value="">
                <div class="form-group"><label>Tên tuyến</label><input type="text" id="routeName" required placeholder="VD: HCM → Hà Nội"></div>
                <div class="form-row">
                    <div class="form-group"><label>Điểm đi</label><input type="text" id="routeFrom" required placeholder="Tỉnh/TP đi"></div>
                    <div class="form-group"><label>Điểm đến</label><input type="text" id="routeTo" required placeholder="Tỉnh/TP đến"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Quãng đường (km)</label><input type="number" step="0.01" id="routeDistance" required placeholder="0"></div>
                    <div class="form-group"><label>Thời gian dự kiến (phút)</label><input type="number" step="1" id="routeTime" value="0"></div>
                </div>
                <div class="form-group"><label>Trạng thái</label>
                    <select id="routeStatus">
                        <option value="1">Hoạt động</option>
                        <option value="0">Tạm dừng</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeRouteModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- Delivery Person Modal -->
<div id="deliveryPersonModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>🛵 Thêm / Sửa người giao hàng</h2>
            <span class="close-modal" onclick="closeDeliveryPersonModal()">✕</span>
        </div>
        <form id="deliveryPersonForm">
            <div class="modal-body">
                <input type="hidden" id="deliveryPersonId" value="">
                <div class="form-group"><label>Họ tên</label><input type="text" id="deliveryPersonName" required placeholder="Nhập họ tên..."></div>
                <div class="form-row">
                    <div class="form-group"><label>Số điện thoại</label><input type="tel" id="deliveryPersonPhone" required placeholder="0xxxxxxxxx"></div>
                    <div class="form-group"><label>Chi nhánh ID</label><input type="number" step="1" id="deliveryPersonBranchId" placeholder="ID chi nhánh"></div>
                </div>
                <div class="form-group"><label>Khu vực phụ trách</label><input type="text" id="deliveryPersonArea" placeholder="VD: Quận 1, TP.HCM"></div>
                <div class="form-group"><label>Trạng thái</label>
                    <select id="deliveryPersonStatus">
                        <option value="1">Hoạt động</option>
                        <option value="0">Tạm dừng</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeDeliveryPersonModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- Pricing Modal -->
<div id="pricingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>💰 Thêm / Sửa bảng giá</h2>
            <span class="close-modal" onclick="closePricingModal()">✕</span>
        </div>
        <form id="pricingForm">
            <div class="modal-body">
                <input type="hidden" id="pricingId" value="">
                <div class="form-row">
                    <div class="form-group"><label>Từ KL (kg)</label><input type="number" step="0.01" id="pricingFromKg" required placeholder="0"></div>
                    <div class="form-group"><label>Đến KL (kg)</label><input type="number" step="0.01" id="pricingToKg" required placeholder="0"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Phí cơ bản (VNĐ)</label><input type="number" step="1" id="pricingBaseFee" required placeholder="0"></div>
                    <div class="form-group"><label>Phí/km (VNĐ)</label><input type="number" step="1" id="pricingPerKm" placeholder="0"></div>
                </div>
                <div class="form-group"><label>Trạng thái</label>
                    <select id="pricingStatus">
                        <option value="1">Hoạt động</option>
                        <option value="0">Tạm dừng</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closePricingModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- Order Detail Modal -->
<div id="orderDetailModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2>📦 Chi tiết đơn hàng <span id="detailOrderCode"></span></h2>
            <span class="close-modal" onclick="closeOrderDetailModal()">✕</span>
        </div>
        <div class="modal-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1rem;">
                <div>
                    <h3 style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--accent); border-bottom: 1px solid var(--border); padding-bottom: 3px;">👤 Người gửi</h3>
                    <p style="margin-bottom: 4px;"><strong>Họ tên:</strong> <span id="detailSenderName"></span></p>
                    <p style="margin-bottom: 4px;"><strong>SĐT:</strong> <span id="detailSenderPhone"></span></p>
                    <p style="margin-bottom: 4px;"><strong>Địa chỉ:</strong> <span id="detailSenderAddress"></span></p>
                </div>
                <div>
                    <h3 style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--accent); border-bottom: 1px solid var(--border); padding-bottom: 3px;">👤 Người nhận</h3>
                    <p style="margin-bottom: 4px;"><strong>Họ tên:</strong> <span id="detailReceiverName"></span></p>
                    <p style="margin-bottom: 4px;"><strong>SĐT:</strong> <span id="detailReceiverPhone"></span></p>
                    <p style="margin-bottom: 4px;"><strong>Địa chỉ:</strong> <span id="detailReceiverAddress"></span></p>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1rem;">
                <div>
                    <h3 style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--accent); border-bottom: 1px solid var(--border); padding-bottom: 3px;">📦 Thông tin hàng hóa</h3>
                    <p style="margin-bottom: 4px;"><strong>Mặt hàng:</strong> <span id="detailProductName"></span></p>
                    <p style="margin-bottom: 4px;"><strong>Khối lượng:</strong> <span id="detailWeight"></span> kg</p>
                </div>
                <div>
                    <h3 style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--accent); border-bottom: 1px solid var(--border); padding-bottom: 3px;">💵 Thanh toán & Vận chuyển</h3>
                    <p style="margin-bottom: 4px;"><strong>Trạng thái đơn:</strong> <span id="detailStatusBadge"></span></p>
                    <p style="margin-bottom: 4px;"><strong>Phí vận chuyển:</strong> <span id="detailShippingFee"></span></p>
                    <p style="margin-bottom: 4px;"><strong>Hình thức:</strong> <span id="detailPaymentMethod"></span></p>
                    <p style="margin-bottom: 4px;"><strong>Số tiền thu hộ:</strong> <span id="detailCOD"></span></p>
                    <p style="margin-bottom: 4px;"><strong>Thanh toán:</strong> <span id="detailInvoiceStatus"></span></p>
                </div>
            </div>
            
            <div style="margin-bottom: 1rem; background: var(--accent-light); padding: 0.75rem 1rem; border-radius: 6px;">
                <h3 style="font-size: 0.9rem; margin-bottom: 0.3rem; color: var(--accent);">🛵 Shipper giao hàng</h3>
                <p style="margin: 0;"><strong>Shipper đảm nhận:</strong> <span id="detailShipperName" style="font-weight: 600; color: #1e40af;">Chưa phân công</span></p>
            </div>
            
            <div>
                <h3 style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--accent); border-bottom: 1px solid var(--border); padding-bottom: 3px;">🕐 Lịch sử trạng thái</h3>
                <div style="max-height: 150px; overflow-y: auto; padding-left: 5px;" id="detailTimeline">
                    <!-- Timeline items will be rendered here -->
                </div>
            </div>
        </div>
        <div class="modal-footer" style="padding: 0.75rem 1.5rem;">
            <button type="button" class="btn btn-ghost" onclick="closeOrderDetailModal()">Đóng</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
