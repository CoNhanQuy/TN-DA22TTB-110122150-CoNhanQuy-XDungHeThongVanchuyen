const API_URL = (window.API_BASE || '/DATN') + '/backend/api/index.php';
const STATUS_LABELS = {
    cho_tiep_nhan: 'Chờ tiếp nhận',
    da_nhap_kho: 'Đã nhập kho',
    dang_van_chuyen: 'Đang vận chuyển',
    da_giao_hang: 'Đã giao hàng',
    hoan_tat: 'Hoàn tất',
    da_huy: 'Đã hủy'
};

const orderForm = document.getElementById('orderForm');
const formMessage = document.getElementById('formMessage');
const weightInput = document.getElementById('weight');
const paymentMethodSelect = document.getElementById('paymentMethod');
const paymentFlowInput = document.getElementById('paymentFlow');
const paymentOptions = document.querySelectorAll('.payment-option');
const qrBox = document.getElementById('qrBox');
const ordersList = document.getElementById('ordersList');
const goodsTypeForm = document.getElementById('goodsTypeForm');
const goodsTypeMessage = document.getElementById('goodsTypeMessage');
const goodsTypesTableBody = document.getElementById('goodsTypesTableBody');
const ordersTableBody = document.getElementById('ordersTableBody');
const orderSearchInput = document.getElementById('orderSearchInput');
const orderStatusFilter = document.getElementById('orderStatusFilter');

let estimatedFee = null;
let sessionOrders = [];
let allOrders = [];

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&',
        '<': '<',
        '>': '>',
        '"': '"',
        "'": '&#039;'
    }[ch]));
}

function formatCurrency(value) {
    if (value === null || value === undefined || value === '' || Number.isNaN(Number(value))) {
        return 'Chưa tính';
    }
    return Number(value).toLocaleString('vi-VN') + ' đ';
}

function setMessage(element, type, text) {
    if (!element) return;
    element.className = `message ${type}`;
    element.textContent = text;
    element.style.display = 'block';
}

function clearElementMessage(element) {
    if (!element) return;
    element.className = 'message';
    element.textContent = '';
    element.style.display = 'none';
}

function showMessage(type, text) {
    setMessage(formMessage, type, text);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function clearMessage() {
    clearElementMessage(formMessage);
}

function statusBadgeClass(status) {
    if (status === 'da_nhap_kho' || status === 'dang_van_chuyen') return 'status-warehouse';
    if (status === 'hoan_tat' || status === 'da_giao_hang') return 'invoice-paid';
    if (status === 'da_huy') return 'invoice-unpaid';
    return 'invoice-unpaid';
}

async function loadGoodsTypes() {
    goodsTypesTableBody.innerHTML = `<tr><td colspan="5" class="muted" style="text-align:center;">Đang tải dữ liệu...</td></tr>`;
    try {
        const res = await apiFetch(`${API_URL}?action=goods_types`, { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
            goodsTypesTableBody.innerHTML = `<tr><td colspan="5" class="muted" style="text-align:center;">Chưa có loại hàng nào</td></tr>`;
            return;
        }

        goodsTypesTableBody.innerHTML = data.data.map(item => `
            <tr>
                <td>#${escapeHtml(item.id)}</td>
                <td><strong>${escapeHtml(item.ten_danh_muc)}</strong></td>
                <td>${escapeHtml(item.mo_ta || '')}</td>
                <td>
                    <span class="status-badge ${Number(item.trang_thai) === 1 ? 'invoice-paid' : 'invoice-unpaid'}">
                        ${Number(item.trang_thai) === 1 ? 'Đang dùng' : 'Tạm ngưng'}
                    </span>
                </td>
                <td>
                    <button type="button" class="btn-secondary" style="padding:8px 12px;" onclick="deleteGoodsType(${Number(item.id)})">Xóa</button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        goodsTypesTableBody.innerHTML = `<tr><td colspan="5" class="muted" style="text-align:center;">Lỗi tải danh mục loại hàng</td></tr>`;
    }
}

async function deleteGoodsType(id) {
    if (!confirm('Xóa loại hàng này?')) return;
    clearElementMessage(goodsTypeMessage);

    const payload = new FormData();
    payload.append('action', 'goods_types');
    payload.append('op', 'delete');
    payload.append('id', id);

    try {
        const res = await apiFetch(API_URL, { method: 'POST', body: payload });
        const data = await res.json();
        if (!data.success) {
            setMessage(goodsTypeMessage, 'error', data.message || 'Không thể xóa loại hàng.');
            return;
        }
        setMessage(goodsTypeMessage, 'success', data.message || 'Đã xóa loại hàng.');
        loadGoodsTypes();
    } catch (error) {
        setMessage(goodsTypeMessage, 'error', 'Lỗi kết nối máy chủ khi xóa loại hàng.');
    }
}

function renderOrdersTable() {
    const keyword = (orderSearchInput.value || '').trim().toLowerCase();
    const status = orderStatusFilter.value;
    const rows = allOrders.filter(row => {
        const haystack = `${row.ma_don || ''} ${row.sender_name || ''} ${row.ten_hang_hoa || ''}`.toLowerCase();
        const matchKeyword = !keyword || haystack.includes(keyword);
        const matchStatus = !status || row.trang_thai === status;
        return matchKeyword && matchStatus;
    });

    if (rows.length === 0) {
        ordersTableBody.innerHTML = `<tr><td colspan="7" class="muted" style="text-align:center;">Không có dữ liệu phù hợp</td></tr>`;
        return;
    }

    ordersTableBody.innerHTML = rows.map(row => {
        const invoicePaid = row.invoice_status === 'da_thanh_toan';
        const canCancel = ['cho_tiep_nhan', 'da_nhap_kho'].includes(row.trang_thai);
        return `
            <tr>
                <td><strong>${escapeHtml(row.ma_don)}</strong></td>
                <td>${escapeHtml(row.sender_name || '')}</td>
                <td>${escapeHtml(row.ten_hang_hoa || '')}</td>
                <td>${formatCurrency(row.phi_van_chuyen)}</td>
                <td>
                    <span class="status-badge ${statusBadgeClass(row.trang_thai)}">
                        ${escapeHtml(STATUS_LABELS[row.trang_thai] || row.trang_thai || '---')}
                    </span>
                </td>
                <td>
                    <span class="status-badge ${invoicePaid ? 'invoice-paid' : 'invoice-unpaid'}">
                        ${invoicePaid ? 'Đã thanh toán' : 'Chưa thanh toán'}
                    </span>
                </td>
                <td>
                    <select data-order-status="${Number(row.id)}" style="padding:8px;border-radius:10px;border:1px solid #d1d5db;margin-bottom:6px;">
                        ${Object.keys(STATUS_LABELS).map(key => `<option value="${key}" ${row.trang_thai === key ? 'selected' : ''}>${STATUS_LABELS[key]}</option>`).join('')}
                    </select>
                    <button type="button" class="btn-submit" style="padding:8px 12px;width:100%;margin-bottom:6px;" onclick="updateOrderStatus(${Number(row.id)})">Lưu</button>
                    <button type="button" class="btn-secondary" style="padding:8px 12px;width:100%;margin-bottom:6px;background:#e0e7ff;color:#1e40af;" onclick="printPhieuById(${Number(row.id)})">🖨️ Xuất phiếu</button>
                    ${canCancel ? `<button type="button" class="btn-secondary" style="padding:8px 12px;width:100%;" onclick="cancelOrder(${Number(row.id)})">Hủy đơn</button>` : ''}
                </td>
            </tr>
        `;
    }).join('');
}

async function loadOrdersTable() {
    ordersTableBody.innerHTML = `<tr><td colspan="7" class="muted" style="text-align:center;">Đang tải dữ liệu...</td></tr>`;
    try {
        const res = await apiFetch(`${API_URL}?action=receptionist_orders`, { credentials: 'same-origin' });
        const data = await res.json();
        allOrders = data.success && Array.isArray(data.data) ? data.data : [];
        renderOrdersTable();
    } catch (error) {
        ordersTableBody.innerHTML = `<tr><td colspan="7" class="muted" style="text-align:center;">Lỗi tải dữ liệu đơn hàng</td></tr>`;
    }
}

async function updateOrderStatus(id) {
    const select = document.querySelector(`[data-order-status="${id}"]`);
    if (!select) return;

    const payload = new FormData();
    payload.append('action', 'orders');
    payload.append('op', 'update_status');
    payload.append('id', id);
    payload.append('trang_thai', select.value);
    payload.append('ghi_chu', 'Cập nhật từ dashboard nhân viên tiếp nhận');

    try {
        const res = await apiFetch(API_URL, { method: 'POST', body: payload });
        const data = await res.json();
        if (!data.success) {
            alert(data.message || 'Không thể cập nhật trạng thái.');
            return;
        }
        await loadOrdersTable();
        alert(data.message || 'Cập nhật trạng thái thành công.');
    } catch (error) {
        alert('Lỗi kết nối máy chủ khi cập nhật trạng thái.');
    }
}

async function cancelOrder(id) {
    const reason = prompt('Nhập lý do hủy đơn (không bắt buộc):', '') ?? '';
    if (!confirm('Bạn chắc chắn muốn hủy đơn hàng này?')) return;

    const payload = new FormData();
    payload.append('action', 'orders');
    payload.append('op', 'cancel');
    payload.append('id', id);
    payload.append('reason', reason);

    try {
        const res = await apiFetch(API_URL, { method: 'POST', body: payload });
        const data = await res.json();
        if (!data.success) {
            alert(data.message || 'Không thể hủy đơn hàng.');
            return;
        }
        await loadOrdersTable();
        alert(data.message || 'Hủy đơn hàng thành công.');
    } catch (error) {
        alert('Lỗi kết nối máy chủ khi hủy đơn hàng.');
    }
}

function updateSummary() {
    const weight = parseFloat(weightInput.value || '0');
    const paymentFlow = paymentFlowInput.value;
    const paymentMethod = paymentMethodSelect.value;

    document.getElementById('summaryWeight').textContent = `${weight > 0 ? weight : 0} kg`;
    document.getElementById('summaryFlow').textContent = paymentFlow === 'prepaid' ? 'Trả trước toàn bộ' : (paymentFlow === 'partial' ? 'Trả trước một phần' : 'Người nhận trả tiền');
    document.getElementById('summaryMethod').textContent = paymentMethod === 'qr_code' ? 'Mã QR' : 'Tiền mặt';
    document.getElementById('summaryFee').textContent = formatCurrency(estimatedFee);

    const paymentDescription = document.getElementById('paymentDescription');
    if (paymentFlow === 'prepaid') {
        paymentDescription.innerHTML = 'Khách đang chọn <strong>Trả trước toàn bộ</strong>. Nhân viên thu tiền tại quầy bằng tiền mặt hoặc hỗ trợ khách quét QR trước khi xác nhận nhập kho.';
    } else if (paymentFlow === 'partial') {
        paymentDescription.innerHTML = 'Khách đang chọn <strong>Trả trước một phần</strong>. Nhân viên thu số tiền khách trả trước và hệ thống sẽ tự tính phần còn lại cho người nhận.';
    } else {
        paymentDescription.innerHTML = 'Khách đang chọn <strong>Người nhận trả tiền</strong>. Nhân viên chỉ xác nhận nhập kho, hệ thống sẽ lưu hóa đơn ở trạng thái <strong>chưa thanh toán</strong>.';
    }

    const showQr = paymentMethod === 'qr_code';
    qrBox.style.display = showQr ? 'block' : 'none';
    if (showQr) {
        const feeDisplay = estimatedFee !== null ? formatCurrency(estimatedFee) : 'Chưa xác định';
        const partialAmt = paymentFlow === 'partial' ? (parseFloat(document.getElementById('partialAmount')?.value || 0) || null) : null;
        const amountToShow = partialAmt !== null ? formatCurrency(partialAmt) : (paymentFlow === 'prepaid' ? feeDisplay : feeDisplay);
        qrBox.innerHTML = `
            <div style="display:flex; align-items:flex-start; gap:20px; flex-wrap:wrap;">
                <div style="text-align:center; flex-shrink:0;">
                    <img src="/DATN/frontend/assets/images/qr_payment.png"
                         alt="QR Thanh toán"
                         style="width:180px; height:auto; border-radius:12px; box-shadow:0 4px 16px rgba(0,0,0,.15);"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                    />
                    <div style="display:none; width:180px; height:180px; background:#f3f4f6; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#6b7280; font-size:13px;">
                        QR không tải được
                    </div>
                    <div style="margin-top:8px; font-size:12px; color:#6b7280;">Quét bằng mọi ứng dụng ngân hàng</div>
                </div>
                <div style="flex:1; min-width:180px;">
                    <div style="font-weight:700; font-size:15px; color:#111827; margin-bottom:10px;">📱 Hướng dẫn khách quét QR</div>
                    <div style="background:#f0fdf4; border:1px solid #a7f3d0; border-radius:10px; padding:12px 14px; margin-bottom:10px;">
                        <div style="font-size:13px; color:#065f46; font-weight:600;">Số tiền cần thanh toán:</div>
                        <div style="font-size:20px; font-weight:800; color:#047857; margin-top:4px;">${amountToShow}</div>
                    </div>
                    <div style="font-size:13px; color:#374151; line-height:1.7;">
                        <div>1️⃣ Khách mở app ngân hàng / ví điện tử</div>
                        <div>2️⃣ Chọn <strong>Quét mã QR</strong> hoặc <strong>Chuyển tiền QR</strong></div>
                        <div>3️⃣ Quét mã QR bên cạnh</div>
                        <div>4️⃣ Xác nhận số tiền <strong>${amountToShow}</strong> và nội dung</div>
                        <div>5️⃣ Nhân viên xác nhận đã nhận → nhấn <strong>"Xác nhận nhập kho"</strong></div>
                    </div>
                </div>
            </div>
        `;
    }
}

async function fetchQuote() {
    const weight = parseFloat(weightInput.value || '0');
    if (!weight || weight <= 0) {
        estimatedFee = null;
        updateSummary();
        return;
    }

    try {
        const response = await apiFetch(`${API_URL}?action=quote&weight=${encodeURIComponent(weight)}`, { credentials: 'same-origin' });
        const result = await response.json();
        estimatedFee = result.success && result.data ? result.data.estimated_fee : null;
    } catch (error) {
        estimatedFee = null;
    }

    updateSummary();
}

function renderOrders() {
    if (sessionOrders.length === 0) {
        ordersList.innerHTML = `<tr><td colspan="6" class="muted" style="text-align:center;">Chưa có đơn nào được tạo trong phiên làm việc này</td></tr>`;
        return;
    }

    ordersList.innerHTML = sessionOrders.map(order => `
        <tr>
            <td><strong>${escapeHtml(order.code)}</strong><br><span class="status-badge status-warehouse">Đã nhập kho</span></td>
            <td><strong>${escapeHtml(order.sender)}</strong><br><span class="muted small">${escapeHtml(order.phone)}</span></td>
            <td>${escapeHtml(order.product)}<br><span class="muted small">${escapeHtml(order.weight)} kg</span></td>
            <td><strong>${formatCurrency(order.fee)}</strong></td>
            <td>
                <span class="status-badge ${order.invoiceStatus === 'da_thanh_toan' ? 'invoice-paid' : 'invoice-unpaid'}">
                    ${order.invoiceStatus === 'da_thanh_toan' ? 'Đã thanh toán' : 'Chưa thanh toán'}
                </span>
                <div class="small muted" style="margin-top:6px;">${order.flow === 'prepaid' ? 'Trả trước toàn bộ' : (order.flow === 'partial' ? 'Trả trước một phần' : 'Người nhận trả tiền')}</div>
            </td>
            <td>
                <button type="button" class="btn-submit" style="padding:8px 14px;font-size:0.9rem;" onclick="printPhieuById(${order.id})">🖨️ Xuất phiếu</button>
            </td>
        </tr>
    `).join('');
}

function setPaymentFlow(flow) {
    paymentFlowInput.value = flow;
    paymentOptions.forEach(option => option.classList.toggle('active', option.dataset.flow === flow));

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.textContent = flow === 'prepaid' ? 'Thu toàn bộ tiền / xác nhận nhập kho' : (flow === 'partial' ? 'Thu tiền một phần / xác nhận nhập kho' : 'Xác nhận nhập kho');
    
    const partialGroup = document.getElementById('partialPaymentGroup');
    if (partialGroup) {
        partialGroup.style.display = flow === 'partial' ? 'block' : 'none';
    }
    
    updateSummary();
}

document.addEventListener('DOMContentLoaded', function () {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabSections = {
        goods: document.getElementById('tab_goods'),
        orders: document.getElementById('tab_orders'),
        input: document.getElementById('tab_input')
    };

    function showTab(tabKey) {
        tabBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === tabKey));
        Object.keys(tabSections).forEach(key => {
            tabSections[key].toggleAttribute('hidden', key !== tabKey);
            tabSections[key].style.display = key === tabKey ? 'block' : 'none';
        });

        if (tabKey === 'goods') loadGoodsTypes();
        if (tabKey === 'orders') loadOrdersTable();
    }

    tabBtns.forEach(btn => btn.addEventListener('click', () => showTab(btn.dataset.tab)));
    showTab(document.querySelector('.tab-btn.active')?.dataset.tab || 'input');

    paymentOptions.forEach(option => option.addEventListener('click', () => setPaymentFlow(option.dataset.flow)));
    paymentMethodSelect.addEventListener('change', updateSummary);
    weightInput.addEventListener('input', fetchQuote);
    orderSearchInput.addEventListener('input', renderOrdersTable);
    orderStatusFilter.addEventListener('change', renderOrdersTable);

    goodsTypeForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearElementMessage(goodsTypeMessage);

        const payload = new FormData();
        payload.append('action', 'goods_types');
        payload.append('op', 'create');
        payload.append('ten_danh_muc', document.getElementById('goodsTypeName').value.trim());
        payload.append('mo_ta', document.getElementById('goodsTypeDesc').value.trim());

        try {
            const res = await apiFetch(API_URL, { method: 'POST', body: payload });
            const data = await res.json();
            if (!data.success) {
                setMessage(goodsTypeMessage, 'error', data.message || 'Không thể thêm loại hàng.');
                return;
            }
            goodsTypeForm.reset();
            setMessage(goodsTypeMessage, 'success', data.message || 'Thêm loại hàng thành công.');
            loadGoodsTypes();
        } catch (error) {
            setMessage(goodsTypeMessage, 'error', 'Lỗi kết nối máy chủ khi thêm loại hàng.');
        }
    });

    document.getElementById('resetBtn').addEventListener('click', () => {
        orderForm.reset();
        estimatedFee = null;
        clearMessage();
        setPaymentFlow('prepaid');
        updateSummary();
    });

    orderForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearMessage();

        const weight = parseFloat(weightInput.value || '0');
        if (!weight || weight <= 0) {
            showMessage('error', 'Khối lượng không hợp lệ.');
            return;
        }

        if (estimatedFee === null) await fetchQuote();
        if (estimatedFee === null) {
            showMessage('error', 'Không tính được phí dự kiến từ bảng giá. Vui lòng kiểm tra cấu hình bảng giá.');
            return;
        }

        const payload = new FormData();
        payload.append('action', 'orders');
        payload.append('sender_name', document.getElementById('senderName').value.trim());
        payload.append('sender_phone', document.getElementById('senderPhone').value.trim());
        payload.append('sender_email', document.getElementById('senderEmail').value.trim());
        payload.append('sender_cccd', document.getElementById('senderCCCD').value.trim());
        payload.append('sender_address', document.getElementById('senderAddress').value.trim());
        payload.append('receiver_name', document.getElementById('receiverName').value.trim());
        payload.append('receiver_phone', document.getElementById('receiverPhone').value.trim());
        payload.append('receiver_email', document.getElementById('receiverEmail').value.trim());
        payload.append('receiver_cccd', document.getElementById('receiverCCCD').value.trim());
        payload.append('receiver_address', document.getElementById('receiverAddress').value.trim());
        payload.append('ten_hang_hoa', document.getElementById('productName').value.trim());
        payload.append('khoi_luong_kg', weight);
        payload.append('phi_van_chuyen', estimatedFee);
        payload.append('phuong_thuc_thanh_toan', paymentMethodSelect.value);
        payload.append('kieu_thanh_toan', paymentFlowInput.value);
        if (paymentFlowInput.value === 'partial') {
            const partialAmt = document.getElementById('partialAmount').value;
            payload.append('tien_tra_truoc', partialAmt ? parseFloat(partialAmt) : 0);
        }
        payload.append('ghi_chu', document.getElementById('notes').value.trim());

        try {
            const response = await apiFetch(API_URL, { method: 'POST', body: payload });
            const result = await response.json();

            if (!result.success) {
                showMessage('error', result.message || 'Không thể tạo đơn hàng.');
                return;
            }

            const orderId = result.data?.id || 0;
            const orderCode = result.data?.ma_don || '---';
            const invoiceStatus = result.data?.invoice_status || 'chua_thanh_toan';

            sessionOrders.unshift({
                id: orderId,
                code: orderCode,
                sender: document.getElementById('senderName').value.trim(),
                phone: document.getElementById('senderPhone').value.trim(),
                senderAddress: document.getElementById('senderAddress').value.trim(),
                senderCCCD: document.getElementById('senderCCCD').value.trim(),
                receiver: document.getElementById('receiverName').value.trim(),
                receiverPhone: document.getElementById('receiverPhone').value.trim(),
                receiverAddress: document.getElementById('receiverAddress').value.trim(),
                receiverCCCD: document.getElementById('receiverCCCD').value.trim(),
                product: document.getElementById('productName').value.trim(),
                notes: document.getElementById('notes').value.trim(),
                weight,
                fee: estimatedFee,
                flow: paymentFlowInput.value,
                paymentMethod: paymentMethodSelect.value,
                invoiceStatus,
                createdAt: new Date().toLocaleString('vi-VN')
            });

            renderOrders();

            const successText = paymentFlowInput.value === 'prepaid'
                ? `Tạo đơn ${orderCode} thành công. Hóa đơn đã được ghi nhận là đã thanh toán.`
                : (paymentFlowInput.value === 'partial'
                    ? `Tạo đơn ${orderCode} thành công. Đã ghi nhận trả trước một phần, người nhận sẽ thanh toán phần còn lại.`
                    : `Tạo đơn ${orderCode} thành công. Đơn đã nhập kho, hóa đơn đang ở trạng thái chưa thanh toán.`);

            showMessage('success', successText);
            orderForm.reset();
            estimatedFee = null;
            setPaymentFlow('prepaid');
            updateSummary();
        } catch (error) {
            showMessage('error', 'Có lỗi kết nối tới máy chủ. Vui lòng thử lại.');
        }
    });

    setPaymentFlow('prepaid');
    updateSummary();
    renderOrders();
});

// ===================== XUẤT PHIẾU GỬI (KÈM QR CODE) =====================
async function printPhieuById(id) {
    try {
        const res = await apiFetch(`${API_URL}?action=order_detail&id=${id}`);
        const data = await res.json();
        if (!data.success || !data.data) {
            alert('Không thể tải thông tin đơn hàng: ' + (data.message || 'Không có dữ liệu'));
            return;
        }
        const order = data.data;

        // Tạo container tạm chứa QR code
        let tempQr = document.getElementById('tempQr');
        if (!tempQr) {
            tempQr = document.createElement('div');
            tempQr.id = 'tempQr';
            tempQr.style.display = 'none';
            document.body.appendChild(tempQr);
        }
        tempQr.innerHTML = '';

        // Đường dẫn QR cho shipper quét
        const qrUrl = `${window.location.origin}/DATN/frontend/giaohang/?order=${encodeURIComponent(order.ma_don_hang)}`;
        
        new QRCode(tempQr, {
            text: qrUrl,
            width: 150,
            height: 150,
            correctLevel: QRCode.CorrectLevel.H
        });

        // Đợi tạo ảnh QR
        setTimeout(() => {
            const qrImg = tempQr.querySelector('img');
            const qrSrc = qrImg ? qrImg.src : '';

            // Mở cửa sổ in
            const printWindow = window.open('', '_blank', 'width=800,height=650');
            if (!printWindow) {
                alert('Vui lòng cho phép trình duyệt mở popup để in phiếu!');
                return;
            }

            const paymentStatusHtml = order.invoice_status === 'da_thanh_toan' 
                ? '<span style="color:green; font-weight:bold;">ĐÃ THANH TOÁN</span>'
                : '<span style="color:red; font-weight:bold;">CHƯA THANH TOÁN</span>';

            const paymentMethodText = order.payment_method === 'tien_mat' ? 'Tiền mặt' : 'Chuyển khoản (QR)';

            // Tạo hàng chi tiết hàng hóa
            let goodsRowsHtml = '';
            if (Array.isArray(order.hang_hoa) && order.hang_hoa.length > 0) {
                goodsRowsHtml = order.hang_hoa.map((item, idx) => `
                    <tr>
                        <td style="text-align:center;">${idx + 1}</td>
                        <td>${escapeHtml(item.ten_mat_hang)}</td>
                        <td style="text-align:center;">${escapeHtml(item.so_luong)}</td>
                        <td style="text-align:center;">${parseFloat(item.khoi_luong_uoc_tinh_kg || 0).toFixed(1)} kg</td>
                        <td>${escapeHtml(item.ghi_chu || '---')}</td>
                    </tr>
                `).join('');
            } else {
                goodsRowsHtml = `
                    <tr>
                        <td style="text-align:center;">1</td>
                        <td>${escapeHtml(order.ten_hang_hoa || 'Hàng hóa')}</td>
                        <td style="text-align:center;">1</td>
                        <td style="text-align:center;">${parseFloat(order.tong_khoi_luong_kg || 0).toFixed(1)} kg</td>
                        <td>---</td>
                    </tr>
                `;
            }

            printWindow.document.write(`
                <html>
                <head>
                    <title>Phiếu gửi hàng - ${escapeHtml(order.ma_don_hang)}</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            margin: 20px;
                            color: #333;
                            font-size: 14px;
                            line-height: 1.5;
                        }
                        .ticket-container {
                            border: 2px solid #333;
                            padding: 20px;
                            max-width: 700px;
                            margin: 0 auto;
                            position: relative;
                        }
                        .header-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 20px;
                        }
                        .header-title {
                            font-size: 18px;
                            font-weight: bold;
                            text-align: center;
                        }
                        .qr-cell {
                            width: 160px;
                            text-align: center;
                            vertical-align: middle;
                        }
                        .qr-cell img {
                            width: 140px;
                            height: 140px;
                        }
                        .info-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 20px;
                        }
                        .info-table td {
                            padding: 6px 0;
                            vertical-align: top;
                        }
                        .info-table .label {
                            font-weight: bold;
                            width: 110px;
                        }
                        .info-table .col-divider {
                            width: 4%;
                        }
                        .goods-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 20px;
                        }
                        .goods-table th, .goods-table td {
                            border: 1px solid #333;
                            padding: 8px;
                            text-align: left;
                        }
                        .goods-table th {
                            background-color: #f2f2f2;
                        }
                        .footer-info {
                            display: flex;
                            justify-content: space-between;
                            margin-top: 30px;
                        }
                        .signature-block {
                            text-align: center;
                            width: 200px;
                        }
                        .signature-space {
                            height: 60px;
                        }
                        @media print {
                            body { margin: 0; }
                            .ticket-container { border: none; padding: 0; }
                        }
                    </style>
                </head>
                <body>
                    <div class="ticket-container">
                        <table class="header-table">
                            <tr>
                                <td>
                                    <div style="font-size: 12px; font-weight: bold; text-transform: uppercase;">Hệ Thống Vận Tải Xanh</div>
                                    <div style="font-size: 10px; color: #555;">Dịch vụ chuyển phát chuyên nghiệp</div>
                                    <div class="header-title" style="margin-top: 15px; text-align: left;">PHIẾU GỬI HÀNG HÓA</div>
                                    <div style="font-size: 13px; margin-top: 5px;">Mã đơn: <strong style="font-size: 16px; letter-spacing: 1px;">${escapeHtml(order.ma_don_hang)}</strong></div>
                                    <div style="font-size: 11px; color: #666; margin-top: 2px;">Ngày tạo: ${escapeHtml(order.ngay_tao)}</div>
                                </td>
                                <td class="qr-cell">
                                    <img src="${qrSrc}" alt="QR Code" />
                                    <div style="font-size: 10px; color: #555; margin-top: 4px;">Quét QR để cập nhật</div>
                                </td>
                            </tr>
                        </table>

                        <hr style="border: 0; border-top: 1px dashed #333; margin: 15px 0;" />

                        <table class="info-table">
                            <tr>
                                <td style="width: 48%;">
                                    <div style="font-weight: bold; text-decoration: underline; margin-bottom: 8px; font-size: 13px;">1. NGƯỜI GỬI:</div>
                                    <table>
                                        <tr><td class="label">Họ tên:</td><td>${escapeHtml(order.nguoi_gui)}</td></tr>
                                        <tr><td class="label">Điện thoại:</td><td>${escapeHtml(order.sdt_gui)}</td></tr>
                                        <tr><td class="label">Địa chỉ:</td><td>${escapeHtml(order.dia_chi_gui)}</td></tr>
                                    </table>
                                </td>
                                <td class="col-divider"></td>
                                <td style="width: 48%;">
                                    <div style="font-weight: bold; text-decoration: underline; margin-bottom: 8px; font-size: 13px;">2. NGƯỜI NHẬN:</div>
                                    <table>
                                        <tr><td class="label">Họ tên:</td><td>${escapeHtml(order.nguoi_nhan)}</td></tr>
                                        <tr><td class="label">Điện thoại:</td><td>${escapeHtml(order.sdt_nhan)}</td></tr>
                                        <tr><td class="label">Địa chỉ:</td><td>${escapeHtml(order.dia_chi_nhan)}</td></tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <div style="font-weight: bold; text-decoration: underline; margin-bottom: 8px; font-size: 13px;">3. CHI TIẾT HÀNG HÓA:</div>
                        <table class="goods-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px; text-align:center;">STT</th>
                                    <th>Tên mặt hàng</th>
                                    <th style="width: 70px; text-align:center;">Số lượng</th>
                                    <th style="width: 100px; text-align:center;">Khối lượng</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${goodsRowsHtml}
                            </tbody>
                        </table>

                        <table class="info-table" style="background-color: #f9f9f9; padding: 10px; border-radius: 4px;">
                            <tr>
                                <td style="width: 50%;">
                                    <strong>Cước vận chuyển:</strong> ${formatCurrency(order.phi_van_chuyen)}
                                </td>
                                <td style="width: 50%;">
                                    <strong>Trạng thái hóa đơn:</strong> ${paymentStatusHtml}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Hình thức thanh toán:</strong> ${paymentMethodText}
                                </td>
                                <td>
                                    <strong>Số tiền thu hộ (COD):</strong> ${formatCurrency(order.tien_thu_ho || 0)}
                                </td>
                            </tr>
                        </table>

                        ${order.invoice_status !== 'da_thanh_toan' ? `
                        <div style="margin-top: 16px; padding: 14px; border: 2px dashed #3b82f6; border-radius: 8px; background: #eff6ff;">
                            <div style="font-weight: bold; font-size: 13px; margin-bottom: 10px; color: #1e40af;">💳 QR THANH TOÁN CƯỚC VẬN CHUYỂN (Người nhận quét)</div>
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <img src="${window.location.origin}/DATN/frontend/assets/images/qr_payment.png"
                                     alt="QR Thanh toán cước" style="width:120px; height:auto; border-radius:8px; border:2px solid #3b82f6;"
                                />
                                <div style="font-size: 13px; color: #1e40af; line-height: 1.7;">
                                    <div>• Quét để thanh toán cước vận chuyển</div>
                                    <div>• Số tiền còn lại: <strong style="font-size:16px;">${formatCurrency(order.tien_thu_ho || 0)}</strong></div>
                                    <div>• Nội dung CK: <strong>${escapeHtml(order.ma_don_hang)}</strong></div>
                                    <div style="margin-top:6px; font-size:11px; color:#6b7280;">Hỗ trợ: ZaloPay, MoMo, VCB, BIDV, MB, Agribank và 50+ ngân hàng</div>
                                </div>
                            </div>
                        </div>` : ''}

                        <div class="footer-info">
                            <div class="signature-block">
                                <div><strong>Chữ ký người gửi</strong></div>
                                <div style="font-style: italic; font-size: 11px;">(Ký và ghi rõ họ tên)</div>
                                <div class="signature-space"></div>
                            </div>
                            <div class="signature-block">
                                <div><strong>Nhân viên tiếp nhận</strong></div>
                                <div style="font-style: italic; font-size: 11px;">(Ký xác nhận)</div>
                                <div class="signature-space"></div>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.onload = function() {
                printWindow.print();
            };
        }, 150);

    } catch (error) {
        console.error('Lỗi in phiếu:', error);
        alert('Lỗi khi chuẩn bị in phiếu: ' + error.message);
    }
}
