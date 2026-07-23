<?php
require_once __DIR__ . '/../../backend/config/cauhinh.php';
require_once __DIR__ . '/../../backend/core/helpers.php';
requireRole('nhan_vien_tiep_nhan');
$pageTitle = 'Nhân viên Tiếp nhận';
$moduleCSS = APP_BASE_URL . '/frontend/assets/css/tiepnhan.css';
$moduleJS  = APP_BASE_URL . '/frontend/assets/js/tiepnhan.js';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="header">
        <div>
            <h1>📬 Tiếp nhận & Nhập liệu đơn hàng</h1>
            <p>Màn hình này phục vụ việc ghi nhận thông tin đơn hàng tại quầy. Hệ thống tự động tính khối lượng quy đổi từ kích thước, phân loại hàng cồng kềnh, cập nhật phí dự kiến và xử lý luồng thanh toán.</p>
            <div class="user-meta">
                <span><strong>Họ tên:</strong> <?php echo htmlspecialchars($_SESSION['ho_ten']); ?></span>
                <span><strong>SĐT:</strong> <?php echo htmlspecialchars($_SESSION['so_dien_thoai'] ?? ''); ?></span>
                <span><strong>Vai trò:</strong> Nhân viên tiếp nhận</span>
                <?php if (isset($_SESSION['co_so'])): ?>
                    <span><strong>Cơ sở:</strong> <?php echo htmlspecialchars($_SESSION['co_so']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <a href="<?php echo APP_BASE_URL; ?>/backend/api/auth/logout.php" class="logout-btn">🚪 Đăng xuất</a>
    </div>

    <!-- Tab Navigation -->
    <div id="mainTabs" style="margin-bottom: 24px;">
        <button class="tab-btn" data-tab="goods">Quản lý hàng hóa</button>
        <button class="tab-btn" data-tab="orders">Quản lý đơn hàng</button>
        <button class="tab-btn active" data-tab="input">Tiếp nhận nhập liệu đơn hàng</button>
    </div>

    <!-- Tab Sections -->
    <div id="tab_goods" class="tab-section" hidden>
        <div class="card">
            <h2>Quản lý hàng hóa</h2>
            <div class="card-desc">Quản lý danh mục loại hàng để nhân viên chọn nhanh trong quá trình tiếp nhận đơn.</div>
            <div id="goodsTypeMessage" class="message"></div>
            <form id="goodsTypeForm" class="form-grid" style="margin-bottom: 18px;">
                <div class="form-group">
                    <label for="goodsTypeName">Tên loại hàng</label>
                    <input type="text" id="goodsTypeName" required placeholder="Ví dụ: Hồ sơ, Thực phẩm khô, Điện tử...">
                </div>
                <div class="form-group">
                    <label for="goodsTypeDesc">Mô tả</label>
                    <input type="text" id="goodsTypeDesc" placeholder="Mô tả ngắn cho loại hàng">
                </div>
                <div class="form-group full">
                    <button type="submit" class="btn-submit">Thêm loại hàng</button>
                </div>
            </form>
            <table class="orders-table">
                <thead><tr><th>ID</th><th>Tên loại hàng</th><th>Mô tả</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody id="goodsTypesTableBody">
                    <tr><td colspan="5" class="muted" style="text-align:center;">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="tab_orders" class="tab-section" hidden>
        <div class="card">
            <h2>Quản lý đơn hàng</h2>
            <div class="card-desc">Theo dõi các đơn mới tiếp nhận, lọc nhanh và cập nhật trạng thái nghiệp vụ.</div>
            <div class="form-grid" style="margin-bottom: 16px;">
                <div class="form-group">
                    <label for="orderSearchInput">Tìm kiếm</label>
                    <input type="text" id="orderSearchInput" placeholder="Nhập mã đơn / người gửi / tên hàng...">
                </div>
                <div class="form-group">
                    <label for="orderStatusFilter">Lọc trạng thái</label>
                    <select id="orderStatusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="cho_tiep_nhan">Chờ tiếp nhận</option>
                        <option value="da_nhap_kho">Đã nhập kho</option>
                        <option value="dang_van_chuyen">Đang vận chuyển</option>
                        <option value="da_giao_hang">Đã giao hàng</option>
                        <option value="hoan_tat">Hoàn tất</option>
                    </select>
                </div>
            </div>
            <table class="orders-table">
                <thead><tr><th>Mã đơn</th><th>Người gửi</th><th>Hàng hóa</th><th>Phí</th><th>Trạng thái đơn</th><th>Hóa đơn</th><th>Cập nhật</th></tr></thead>
                <tbody id="ordersTableBody">
                    <tr><td colspan="7" class="muted" style="text-align:center;">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="tab_input" class="tab-section">
        <div class="layout">
            <div class="card">
                <h2>Nhập thông tin đơn hàng</h2>
                <div class="card-desc">Gồm 3 khối bắt buộc: <strong>Người gửi</strong>, <strong>Người nhận</strong>, <strong>Hàng hóa</strong>.</div>
                <div id="formMessage" class="message"></div>
                <form id="orderForm">
                    <div class="section-block">
                        <div class="section-title">1. Người gửi</div>
                        <div class="form-grid">
                            <div class="form-group"><label for="senderName">Họ tên người gửi</label><input type="text" id="senderName" name="sender_name" required placeholder="Nguyễn Văn A"></div>
                            <div class="form-group"><label for="senderPhone">Số điện thoại</label><input type="tel" id="senderPhone" name="sender_phone" required placeholder="09xxxxxxxx"></div>
                            <div class="form-group"><label for="senderEmail">Email</label><input type="email" id="senderEmail" name="sender_email" placeholder="email@example.com"></div>
                            <div class="form-group"><label for="senderCCCD">CCCD/CMND</label><input type="text" id="senderCCCD" name="sender_cccd" placeholder="Không bắt buộc"></div>
                            <div class="form-group full"><label for="senderAddress">Địa chỉ gửi</label><textarea id="senderAddress" name="sender_address" required placeholder="Nhập địa chỉ chi tiết của người gửi"></textarea></div>
                        </div>
                    </div>
                    <div class="section-block">
                        <div class="section-title">2. Người nhận</div>
                        <div class="form-grid">
                            <div class="form-group"><label for="receiverName">Họ tên người nhận</label><input type="text" id="receiverName" name="receiver_name" required placeholder="Trần Thị B"></div>
                            <div class="form-group"><label for="receiverPhone">Số điện thoại</label><input type="tel" id="receiverPhone" name="receiver_phone" required placeholder="09xxxxxxxx"></div>
                            <div class="form-group"><label for="receiverEmail">Email</label><input type="email" id="receiverEmail" name="receiver_email" placeholder="email@example.com"></div>
                            <div class="form-group"><label for="receiverCCCD">CCCD/CMND</label><input type="text" id="receiverCCCD" name="receiver_cccd" placeholder="Không bắt buộc"></div>
                            <div class="form-group full">
                                <label for="receiverBranchSelect">🏢 Chi nhánh đến (Thuật toán gom hàng)</label>
                                <select id="receiverBranchSelect" name="chi_nhanh_nhan_id">
                                    <option value="">-- Chọn chi nhánh đến --</option>
                                </select>
                            </div>
                            <div class="form-group full"><label for="receiverAddress">Địa chỉ nhận chi tiết</label><textarea id="receiverAddress" name="receiver_address" required placeholder="Nhập địa chỉ chi tiết của người nhận"></textarea></div>
                        </div>
                    </div>
                    <div class="section-block">
                        <div class="section-title">3. Hàng hóa</div>
                        <div class="form-grid">
                            <div class="form-group"><label for="productName">Tên hàng</label><input type="text" id="productName" name="ten_hang_hoa" required placeholder="Ví dụ: Hồ sơ, quần áo, điện tử..."></div>
                            <div class="form-group">
                                <label for="weight">Khối lượng thực tế (kg)</label>
                                <input type="number" id="weight" name="khoi_luong_kg" min="0.1" step="0.1" required placeholder="Ví dụ: 2.5">
                            </div>
                            <div class="form-group">
                                <label for="dimLength">Chiều dài (cm)</label>
                                <input type="number" id="dimLength" name="chieu_dai_cm" min="0" step="0.5" placeholder="Ví dụ: 30">
                            </div>
                            <div class="form-group">
                                <label for="dimWidth">Chiều rộng (cm)</label>
                                <input type="number" id="dimWidth" name="chieu_rong_cm" min="0" step="0.5" placeholder="Ví dụ: 20">
                            </div>
                            <div class="form-group">
                                <label for="dimHeight">Chiều cao (cm)</label>
                                <input type="number" id="dimHeight" name="chieu_cao_cm" min="0" step="0.5" placeholder="Ví dụ: 15">
                            </div>
                            <div class="form-group">
                                <label for="distanceKm">Số km vận chuyển</label>
                                <input type="number" id="distanceKm" name="so_km" min="0" step="0.1" required placeholder="Ví dụ: 5.0">
                                <div class="inline-note">Phí dự kiến sẽ tự động cập nhật theo bảng giá.</div>
                            </div>
                            <div class="form-group">
                                <label for="goodsTypeSelect">Loại hàng</label>
                                <select id="goodsTypeSelect" name="loai_hang_id" required>
                                    <option value="">-- Chọn loại hàng --</option>
                                </select>
                            </div>
                            <div class="form-group full" style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                                <div style="font-size: 0.9rem; font-weight: 600; color: #334155; display: flex; justify-content: space-between; align-items: center;">
                                    <span>📐 Quy đổi kích thước & Thể tích:</span>
                                    <span id="bulkyBadge" style="display: none; background: #fef3c7; color: #92400e; padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 700; border: 1px solid #f59e0b;">📦 Hàng cồng kềnh (Tự động phân loại)</span>
                                </div>
                                <div style="font-size: 0.85rem; color: #64748b; margin-top: 6px;">
                                    Thể tích: <strong id="calcVolume">0</strong> cm³ | Khối lượng quy đổi ((D×R×C)/6000): <strong id="calcVolWeight">0</strong> kg
                                </div>
                                <div style="font-size: 0.85rem; color: #1e40af; font-weight: 600; margin-top: 4px;">
                                    👉 Khối lượng tính cước: <strong id="chargedWeightDisplay">0 kg</strong>
                                </div>
                            </div>
                            <div class="form-group full"><label for="notes">Ghi chú</label><textarea id="notes" name="ghi_chu" placeholder="Ghi chú thêm về tình trạng hàng hóa..."></textarea></div>
                        </div>
                    </div>
                    <div class="section-block">
                        <div class="section-title">4. Nghiệp vụ thanh toán tại quầy</div>
                        <input type="hidden" id="paymentFlow" value="prepaid">
                        <div class="payment-switch">
                            <div class="payment-option active" data-flow="prepaid">
                                <strong>Trả trước toàn bộ (Prepaid)</strong>
                                <span>Nhân viên xử lý thu toàn bộ tiền ngay tại quầy.</span>
                            </div>
                            <div class="payment-option" data-flow="partial">
                                <strong>Trả trước một phần</strong>
                                <span>Người gửi trả trước một phần, người nhận thanh toán phần còn lại.</span>
                            </div>
                            <div class="payment-option" data-flow="postpaid">
                                <strong>Người nhận trả tiền (Postpaid/COD)</strong>
                                <span>Chỉ ghi nhận phương thức thanh toán mong muốn, chưa thu tiền.</span>
                            </div>
                        </div>
                        <div class="form-group" id="partialPaymentGroup" style="display: none; margin-top: 15px;">
                            <label for="partialAmount">Số tiền khách trả trước (VNĐ)</label>
                            <input type="number" id="partialAmount" placeholder="Nhập số tiền..." min="0">
                        </div>
                        <div class="form-group">
                            <label for="paymentMethod">Phương thức thanh toán mong muốn</label>
                            <select id="paymentMethod" required>
                                <option value="tien_mat">Tiền mặt</option>
                                <option value="qr_code">Mã QR</option>
                            </select>
                        </div>
                    </div>
                    <div class="action-row">
                        <button type="submit" class="btn-submit" id="submitBtn">Xác nhận nhập kho & tạo đơn</button>
                        <button type="button" class="btn-secondary" id="resetBtn">Làm mới biểu mẫu</button>
                    </div>
                </form>
            </div>
            <div>
                <div class="card" style="margin-bottom: 20px;">
                    <h2>Tóm tắt xử lý tại quầy</h2>
                    <div class="summary-box">
                        <h3>Phí dự kiến</h3>
                        <div class="summary-line"><span>Khối lượng</span><strong id="summaryWeight">0 kg</strong></div>
                        <div class="summary-line"><span>Luồng thanh toán</span><strong id="summaryFlow">Trả trước</strong></div>
                        <div class="summary-line"><span>Phương thức</span><strong id="summaryMethod">Tiền mặt</strong></div>
                        <div class="summary-line total"><span>Số tiền dự kiến</span><strong id="summaryFee">Chưa tính</strong></div>
                    </div>
                    <div class="payment-panel" id="paymentPanel">
                        <h4>Thu tiền tại quầy</h4>
                        <p id="paymentDescription">Khách đang chọn <strong>Trả trước</strong>. Nhân viên thu tiền mặt hoặc hướng dẫn quét QR trước khi hoàn tất nhập kho.</p>
                        <div class="qr-box" id="qrBox" style="display: none;">Mã QR thanh toán sẽ được hiển thị/gửi cho khách tại bước này</div>
                    </div>
                    <div class="small muted">
                        Sau khi tạo đơn:
                        <ul style="padding-left: 18px; margin-top: 8px; line-height: 1.7;">
                            <li><strong>Prepaid:</strong> hóa đơn ghi nhận <strong>đã thanh toán</strong>.</li>
                            <li><strong>Postpaid/COD:</strong> hóa đơn ghi nhận <strong>chưa thanh toán</strong>.</li>
                            <li>Đơn hàng được đưa vào trạng thái <strong>đã nhập kho</strong>.</li>
                        </ul>
                    </div>
                </div>
                <div class="card">
                    <h2>Đơn đã tiếp nhận trong phiên</h2>
                    <table class="orders-table">
                        <thead><tr><th>Mã đơn</th><th>Người gửi</th><th>Hàng hóa</th><th>Phí</th><th>Hóa đơn</th><th></th></tr></thead>
                        <tbody id="ordersList">
                            <tr><td colspan="6" class="muted" style="text-align:center;">Chưa có đơn nào được tạo trong phiên làm việc này</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
