function toggleReceiverField() {
    const status = document.getElementById('updateStatus').value;
    const receiverGroup = document.getElementById('receiverFieldGroup');
    const actualReceiver = document.getElementById('actualReceiver');
    
    if (status === 'da_giao_hang') {
        receiverGroup.style.display = 'block';
        actualReceiver.required = true;
    } else {
        receiverGroup.style.display = 'none';
        actualReceiver.required = false;
    }
}

function openStatusModal(id, code) {
    document.getElementById('updateOrderId').value = id;
    document.getElementById('displayOrderCode').value = code;
    document.getElementById('updateStatus').value = '';
    document.getElementById('actualReceiver').value = '';
    document.getElementById('updateNote').value = '';
    toggleReceiverField();
    document.getElementById('statusModal').style.display = 'flex';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('statusModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

function renderStatusBadge(status) {
    let cls = 'badge-pending';
    let text = mapStatusLabel(status);
    if (status === 'dang_van_chuyen') cls = 'badge-on-route';
    if (status === 'da_giao_hang' || status === 'hoan_tat') cls = 'badge-delivered';
    if (status === 'tra_lai' || status === 'da_huy') cls = 'badge-returned';
    return `<span class="status-badge ${cls}">${escapeHtml(text)}</span>`;
}

async function loadAssignedOrders() {
    try {
        const res = await fetch('/DATN/backend/index.php?action=driver_orders');
        const data = await res.json();
        const tbody = document.getElementById('ordersList');
        
        if (!data.success || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">Chưa có đơn hàng nào được phân công.</td></tr>';
            return;
        }

        tbody.innerHTML = data.data.map(order => `
            <tr>
                <td><strong>${escapeHtml(order.ma_don)}</strong></td>
                <td>${escapeHtml(order.ng_nhan)}</td>
                <td>${escapeHtml(order.sdt_nhan)}</td>
                <td>${escapeHtml(order.dia_chi_nhan)}</td>
                <td>${escapeHtml(order.ma_dot)}</td>
                <td>${renderStatusBadge(order.trang_thai)}</td>
                <td>
                    <button class="btn btn-primary btn-small" onclick="openStatusModal(${order.id}, '${escapeHtml(order.ma_don)}')">
                        Cập nhật
                    </button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Lỗi tải đơn hàng:', error);
        document.getElementById('ordersList').innerHTML = '<tr><td colspan="7" style="text-align: center; color: red;">Lỗi tải dữ liệu.</td></tr>';
    }
}

async function loadDeliveryLog() {
    try {
        const res = await fetch('/DATN/backend/index.php?action=driver_delivery_log');
        const data = await res.json();
        const tbody = document.getElementById('logList');
        
        if (!data.success || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #999;">Chưa có nhật ký giao hàng.</td></tr>';
            return;
        }

        tbody.innerHTML = data.data.map(log => `
            <tr>
                <td>${formatDateTime(log.thoi_gian)}</td>
                <td><strong>${escapeHtml(log.ma_don)}</strong></td>
                <td>${renderStatusBadge(log.trang_thai_moi)}</td>
                <td>${escapeHtml(log.ng_nhan)}</td>
                <td>${escapeHtml(log.ghi_chu)}</td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Lỗi tải nhật ký:', error);
        document.getElementById('logList').innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Lỗi tải dữ liệu.</td></tr>';
    }
}

document.getElementById('statusForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnUpdateStatus');
    btn.disabled = true;
    btn.textContent = 'Đang xử lý...';

    const id = document.getElementById('updateOrderId').value;
    const status = document.getElementById('updateStatus').value;
    const actualReceiver = document.getElementById('actualReceiver').value;
    const note = document.getElementById('updateNote').value;

    const formData = new FormData();
    formData.append('don_hang_id', id);
    formData.append('trang_thai', status);
    formData.append('nguoi_nhan_thuc_te', actualReceiver);
    formData.append('ghi_chu', note);

    try {
        const res = await fetch('/DATN/backend/index.php?action=driver_update_status', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('✓ ' + data.message);
            closeStatusModal();
            loadAssignedOrders();
            loadDeliveryLog();
        } else {
            alert('✗ Lỗi: ' + data.message);
        }
    } catch (error) {
        alert('✗ Lỗi kết nối máy chủ');
        console.error(error);
    } finally {
        btn.disabled = false;
        btn.textContent = '✓ Cập nhật trạng thái';
    }
});

// Init load
document.addEventListener('DOMContentLoaded', () => {
    loadAssignedOrders();
    loadDeliveryLog();
});
