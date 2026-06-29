<?php
require_once __DIR__ . '/../../backend/config/cauhinh.php';
require_once __DIR__ . '/../../backend/core/helpers.php';
requireRole('tai_xe');
$pageTitle = 'Tài xế';
$moduleCSS = '/DATN/frontend/assets/css/taixe.css';
$moduleJS  = '/DATN/frontend/assets/js/taixe.js';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="header">
        <div>
            <h1>🚚 Dashboard Tài xế – Quản lý Chuyến xe</h1>
            <div class="header-meta">
                <strong>Họ tên:</strong> <?php echo htmlspecialchars($_SESSION['ho_ten']); ?> &nbsp;|&nbsp;
                <strong>SĐT:</strong> <?php echo htmlspecialchars($_SESSION['so_dien_thoai'] ?? ''); ?> &nbsp;|&nbsp;
                <strong>Vai trò:</strong> Tài xế
            </div>
        </div>
        <a href="/DATN/backend/api/auth/logout.php" class="logout-btn">🚪 Đăng xuất</a>
    </div>

    <!-- Đợt vận chuyển -->
    <div class="card">
        <h2>🚚 Các chuyến xe (Đợt vận chuyển) của tôi</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Mã đợt</th><th>Tuyến đường</th><th>Biển số xe</th>
                        <th>Khởi hành</th><th>Trạng thái</th><th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="shipmentsList">
                    <tr><td colspan="6" style="text-align: center; color: #999;">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Báo cáo sự cố -->
    <div class="card" style="margin-top: 20px;">
        <h2>⚠️ Báo cáo sự cố</h2>
        <div class="card-body">
            <p style="margin-bottom: 1.25rem; color: var(--text-muted);">Nếu gặp vấn đề trong quá trình vận chuyển chuyến xe (xe tải hỏng, thời tiết xấu, tai nạn...), vui lòng báo cáo tại đây.</p>
            <div id="incidentMsg" style="display:none; margin-bottom: 1rem;"></div>
            <form id="incidentForm">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Chuyến xe liên quan</label>
                        <select id="incidentShipmentId">
                            <option value="">-- Không liên quan chuyến cụ thể --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Loại sự cố <span style="color:red;">*</span></label>
                        <select id="incidentType" required>
                            <option value="">-- Chọn loại sự cố --</option>
                            <option value="xe_hong">🔧 Xe hỏng / Sự cố phương tiện</option>
                            <option value="thoi_tiet">🌧️ Thời tiết xấu / ngập lụt</option>
                            <option value="tai_nan">🚨 Tai nạn giao thông</option>
                            <option value="khac">❓ Sự cố khác</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mô tả chi tiết <span style="color:red;">*</span></label>
                    <textarea id="incidentDescription" required placeholder="Mô tả sự cố xảy ra, địa điểm, thời gian và các thông tin liên quan..."></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Vị trí hiện tại</label>
                        <input type="text" id="incidentLocation" placeholder="VD: QL1A đoạn qua cầu Mỹ Thuận">
                    </div>
                    <div class="form-group">
                        <label>Mức độ nghiêm trọng <span style="color:red;">*</span></label>
                        <select id="incidentSeverity" required>
                            <option value="">-- Chọn mức độ --</option>
                            <option value="thap">🟢 Thấp – Tự xử lý được</option>
                            <option value="trung_binh">🟡 Trung bình – Cần hỗ trợ</option>
                            <option value="cao">🔴 Cao – Cần can thiệp ngay</option>
                        </select>
                    </div>
                </div>
                <button type="submit" id="btnSubmitIncident" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.75rem; background: linear-gradient(135deg, #f97316, #ef4444); color: #fff;">
                    🚨 Gửi báo cáo sự cố
                </button>
            </form>
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
                    <option value="dang_di_chuyen">Đang chạy (Bắt đầu hành trình)</option>
                    <option value="da_den_kho_nhan">Hoàn thành (Đã đến nơi)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;" id="btnUpdateStatus">✓ Cập nhật trạng thái</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
