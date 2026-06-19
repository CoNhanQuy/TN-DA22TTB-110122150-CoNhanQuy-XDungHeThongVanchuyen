function renderShipmentBadge(status) {
    let cls = 'badge-chua_khoi_hanh';
    let text = 'Chưa khởi hành';
    if (status === 'dang_chay') { cls = 'badge-dang_chay'; text = 'Đang chạy'; }
    if (status === 'hoan_thanh') { cls = 'badge-hoan_thanh'; text = 'Hoàn thành'; }
    if (status === 'huy') { cls = 'badge-huy'; text = 'Đã hủy'; }
    return `<span class="status-badge ${cls}">${escapeHtml(text)}</span>`;
}

function openStatusModal(id, code, currentStatus) {
    document.getElementById('updateDotId').value = id;
    document.getElementById('displayDotCode').value = code;
    document.getElementById('updateStatus').value = currentStatus === 'chua_khoi_hanh' ? 'dang_chay' : 'hoan_thanh';
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

async function loadShipments() {
    try {
        const res = await fetch('../../backend/index.php?action=my_shipments');
        const data = await res.json();
        const tbody = document.getElementById('shipmentsList');
        
        if (!data.success || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">Bạn chưa được phân công chuyến xe nào.</td></tr>';
            return;
        }

        tbody.innerHTML = data.data.map(shipment => `
            <tr>
                <td><strong>${escapeHtml(shipment.ma_dot)}</strong></td>
                <td>${escapeHtml(shipment.ten_tuyen)}</td>
                <td>${escapeHtml(shipment.bien_so)}</td>
                <td>${formatDateTime(shipment.ngay_gio_bat_dau)}</td>
                <td>${renderShipmentBadge(shipment.trang_thai)}</td>
                <td>
                    ${shipment.trang_thai !== 'hoan_thanh' && shipment.trang_thai !== 'huy' ? 
                    `<button class="btn btn-primary btn-small" onclick="openStatusModal(${shipment.id}, '${escapeHtml(shipment.ma_dot)}', '${shipment.trang_thai}')">
                        Cập nhật
                    </button>` : ''}
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Lỗi tải chuyến xe:', error);
        document.getElementById('shipmentsList').innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Lỗi tải dữ liệu.</td></tr>';
    }
}

document.getElementById('statusForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnUpdateStatus');
    btn.disabled = true;
    btn.textContent = 'Đang xử lý...';

    const id = document.getElementById('updateDotId').value;
    const status = document.getElementById('updateStatus').value;

    const formData = new FormData();
    formData.append('dot_id', id);
    formData.append('trang_thai', status);

    try {
        const res = await fetch('../../backend/index.php?action=update_shipment_status', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('✓ ' + data.message);
            closeStatusModal();
            loadShipments();
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

document.addEventListener('DOMContentLoaded', () => {
    loadShipments();
});
