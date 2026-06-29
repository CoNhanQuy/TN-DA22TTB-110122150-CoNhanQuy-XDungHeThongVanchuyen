<?php
require_once __DIR__ . '/../../backend/config/cauhinh.php';
require_once __DIR__ . '/../../backend/core/helpers.php';
requireRole('shipper');
$pageTitle = 'Người giao hàng';
$moduleCSS = '/DATN/frontend/assets/css/giaohang.css';
$moduleJS  = '/DATN/frontend/assets/js/giaohang.js';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="header">
        <div>
            <h1>🛵 Dashboard Người giao hàng</h1>
            <div class="header-meta">
                <strong>Họ tên:</strong> <?php echo htmlspecialchars($_SESSION['ho_ten']); ?> &nbsp;|&nbsp;
                <strong>SĐT:</strong> <?php echo htmlspecialchars($_SESSION['so_dien_thoai'] ?? ''); ?> &nbsp;|&nbsp;
                <strong>Vai trò:</strong> Người giao hàng
                <?php if (isset($_SESSION['co_so'])): ?> &nbsp;|&nbsp;
                    <strong>Cơ sở:</strong> <?php echo htmlspecialchars($_SESSION['co_so']); ?>
                <?php endif; ?>
            </div>
        </div>
        <a href="/DATN/backend/api/auth/logout.php" class="logout-btn">🚪 Đăng xuất</a>
    </div>

    <!-- Tra cứu đơn hàng nhanh -->
    <div class="card">
        <h2>🔍 Tra cứu đơn hàng</h2>
        <div class="card-body">
            <p style="margin-bottom: 1rem; color: var(--text-muted);">Nhập mã đơn hàng hoặc SĐT/CCCD người gửi để kiểm tra chi tiết thông tin giao hàng.</p>
            <div class="tracking-input">
                <input type="text" id="orderCode" placeholder="Nhập mã đơn hàng (VD: DH123...)">
                <input type="text" id="orderPhone" placeholder="Hoặc SĐT người gửi">
                <button class="btn btn-primary" onclick="trackOrder()" style="padding: 0.65rem 1.5rem; line-height: 1.5; height: auto;">Tra cứu ngay</button>
            </div>
            <div id="trackError" style="color: red; margin-top: 1rem; display: none;"></div>
            <div id="trackResult" style="margin-top: 1.5rem; display: none; background: #f9f9f9; padding: 1.5rem; border-radius: 8px;">
                <div id="trackSummary" style="margin-bottom: 1.5rem;"></div>
                <div id="trackTimeline"></div>
            </div>
        </div>
    </div>

    <!-- Danh sách đơn hàng đang vận chuyển -->
    <div class="card">
        <h2>📦 Danh sách đơn hàng được phân công</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th><th>Người nhận</th><th>SĐT Nhận</th>
                        <th>Địa chỉ giao</th><th>Mã đợt</th><th>Trạng thái</th><th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="assignedOrdersList">
                    <tr><td colspan="7" style="text-align: center; color: #999;">Đang tải dữ liệu...</td></tr>
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
                        <th>Thời gian</th><th>Mã đơn</th><th>Trạng thái mới</th>
                        <th>Người nhận</th><th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody id="deliveryLogList">
                    <tr><td colspan="5" style="text-align: center; color: #999;">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Báo cáo sự cố -->
    <div class="card">
        <h2>⚠️ Báo cáo sự cố</h2>
        <div class="card-body">
            <p style="margin-bottom: 1.25rem; color: var(--text-muted);">Nếu gặp vấn đề trong quá trình giao hàng (tai nạn, xe hỏng, không liên lạc được khách...), vui lòng báo cáo tại đây.</p>
            <div id="incidentMsg" style="display:none; margin-bottom: 1rem;"></div>
            <form id="incidentForm">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Mã đơn hàng liên quan</label>
                        <select id="incidentOrderCode">
                            <option value="">-- Không liên quan đơn cụ thể --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Loại sự cố <span style="color:red;">*</span></label>
                        <select id="incidentType" required>
                            <option value="">-- Chọn loại sự cố --</option>
                            <option value="xe_hong">🔧 Xe hỏng / Sự cố phương tiện</option>
                            <option value="tai_nan">🚨 Tai nạn giao thông</option>
                            <option value="khong_lien_lac">📵 Không liên lạc được khách</option>
                            <option value="dia_chi_sai">📍 Địa chỉ giao sai / không tìm thấy</option>
                            <option value="hang_hu_hong">📦 Hàng hóa bị hư hỏng</option>
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
                        <input type="text" id="incidentLocation" placeholder="VD: Đường Nguyễn Huệ, TP. Vĩnh Long">
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
                    <option value="dang_giao">Đang vận chuyển</option>
                    <option value="thanh_cong">Đã giao hàng (Thành công)</option>
                    <option value="that_bai">Trả lại (Không giao được)</option>
                </select>
            </div>
            <div style="margin-bottom: 1rem; display: none;" id="receiverFieldGroup">
                <label style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600;">Tên người nhận thực tế <span style="color:red;">*</span></label>
                <input type="text" id="actualReceiver" placeholder="Nhập tên người trực tiếp nhận hàng" style="width: 100%; padding: 0.8rem; border: 2px solid #e0e0e0; border-radius: 5px;">
            </div>
            <div style="margin-bottom: 1rem; display: none;" id="photoFieldGroup">
                <label style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600;">Ảnh minh chứng giao hàng <span style="color:red;">*</span></label>
                <input type="file" id="deliveryPhoto" accept="image/*" capture="environment" style="width: 100%; padding: 0.8rem; border: 2px solid #e0e0e0; border-radius: 5px;">
                <div id="photoPreviewContainer" style="margin-top: 0.5rem; display: none; text-align: center;">
                    <img id="photoPreview" style="max-width: 100%; max-height: 200px; border-radius: 5px; border: 1px solid #ccc; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600;">Ghi chú (Tùy chọn)</label>
                <textarea id="updateNote" placeholder="Ghi chú thêm..." style="width: 100%; padding: 0.8rem; border: 2px solid #e0e0e0; border-radius: 5px; min-height: 80px; resize: vertical;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;" id="btnUpdateStatus">✓ Cập nhật trạng thái</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
