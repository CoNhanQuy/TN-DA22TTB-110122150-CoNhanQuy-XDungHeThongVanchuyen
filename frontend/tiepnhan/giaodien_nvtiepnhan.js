const API_URL = '../../backend/index.php';
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
        const res = await fetch(`${API_URL}?action=goods_types`, { credentials: 'same-origin' });
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
        const res = await fetch(API_URL, { method: 'POST', body: payload, credentials: 'same-origin' });
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
        const res = await fetch(`${API_URL}?action=receptionist_orders`, { credentials: 'same-origin' });
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
        const res = await fetch(API_URL, { method: 'POST', body: payload, credentials: 'same-origin' });
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
        const res = await fetch(API_URL, { method: 'POST', body: payload, credentials: 'same-origin' });
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

    qrBox.style.display = paymentMethod === 'qr_code' ? 'block' : 'none';
}

async function fetchQuote() {
    const weight = parseFloat(weightInput.value || '0');
    if (!weight || weight <= 0) {
        estimatedFee = null;
        updateSummary();
        return;
    }

    try {
        const response = await fetch(`${API_URL}?action=quote&weight=${encodeURIComponent(weight)}`, { credentials: 'same-origin' });
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
                <button type="button" class="btn-submit" style="padding:8px 14px;font-size:0.9rem;" onclick="printPhieu(${JSON.stringify(order).replace(/"/g, '&quot;')})">🖨️ Xuất phiếu</button>
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
            const res = await fetch(API_URL, { method: 'POST', body: payload, credentials: 'same-origin' });
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
            const response = await fetch(API_URL, { method: 'POST', body: payload, credentials: 'same-origin' });
            const result = await response.json();

            if (!result.success) {
                showMessage('error', result.message || 'Không thể tạo đơn hàng.');
                return;
            }

            const orderCode = result.data?.ma_don || '---';
            const invoiceStatus = result.data?.invoice_status || 'chua_thanh_toan';

            sessionOrders.unshift({
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
