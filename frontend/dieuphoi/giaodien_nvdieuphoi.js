// ===================== STATE =====================
let allPendingOrders = [];   // Tất cả đơn chờ điều phối
let matchedOrderIds = [];    // ID các đơn khớp điểm đến
let currentDiemDen = '';     // Điểm đến của tuyến đang chọn

// ===================== MODAL =====================
function openAssignModal() {
    document.getElementById('assignModal').style.display = 'flex';
    // Reset form
    document.getElementById('assignForm').reset();
    document.getElementById('orderAreaTitle').textContent = '(Chọn tuyến để lọc đơn)';
    document.getElementById('bulkActions').style.display = 'none';
    document.getElementById('orderSummary').textContent = '';
    document.getElementById('checkAll').checked = false;
    renderOrderCheckList(allPendingOrders, []);
}

function closeAssignModal() {
    document.getElementById('assignModal').style.display = 'none';
}

window.onclick = function(event) {
    const assignModal = document.getElementById('assignModal');
    const detailModal = document.getElementById('detailModal');
    if (event.target === assignModal) assignModal.style.display = 'none';
    if (event.target === detailModal) detailModal.style.display = 'none';
}

// ===================== ROUTE CHANGE → LỌC ĐƠN =====================
function onRouteChange() {
    const routeSelect = document.getElementById('routeSelect');
    const tuyenId = routeSelect.value;

    if (!tuyenId) {
        document.getElementById('orderAreaTitle').textContent = '(Chọn tuyến để lọc đơn)';
        document.getElementById('bulkActions').style.display = 'none';
        renderOrderCheckList(allPendingOrders, []);
        matchedOrderIds = [];
        currentDiemDen = '';
        updateOrderSummary();
        return;
    }

    fetch(`../../backend/index.php?action=orders_by_destination&tuyen_id=${tuyenId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                currentDiemDen = data.data.diem_den;
                matchedOrderIds = data.data.matched.map(o => String(o.id));
                allPendingOrders = data.data.all;

                document.getElementById('orderAreaTitle').textContent =
                    `→ điểm đến: ${currentDiemDen} | ${matchedOrderIds.length} đơn khớp trên ${allPendingOrders.length} đơn`;
                document.getElementById('bulkActions').style.display = 'flex';

                // Auto-check các đơn khớp
                renderOrderCheckList(allPendingOrders, matchedOrderIds);
                checkOrderIds(matchedOrderIds);
                updateOrderSummary();
            }
        })
        .catch(err => console.error('Lỗi lọc đơn:', err));
}

// Render bảng checkbox đơn hàng
function renderOrderCheckList(orders, autoCheckedIds) {
    const tbody = document.getElementById('orderCheckList');
    const checkedSet = new Set(autoCheckedIds.map(String));

    if (!orders || orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#999; padding:2rem;">Không có đơn hàng chờ điều phối</td></tr>';
        return;
    }

    tbody.innerHTML = orders.map(order => {
        const isMatched = checkedSet.has(String(order.id));
        const rowStyle = isMatched ? 'background: #f0f4ff;' : '';
        const matchBadge = isMatched
            ? '<span style="background:#51cf66; color:white; padding:2px 8px; border-radius:10px; font-size:0.75rem;">✓ Khớp</span>'
            : '<span style="background:#eee; color:#999; padding:2px 8px; border-radius:10px; font-size:0.75rem;">—</span>';

        return `
            <tr style="${rowStyle}" id="row_${order.id}">
                <td style="text-align:center;">
                    <input type="checkbox" class="order-checkbox" value="${order.id}"
                        onchange="updateOrderSummary()" ${isMatched ? 'checked' : ''}>
                </td>
                <td><strong>${order.ma_don}</strong></td>
                <td>${order.receiver_name || 'N/A'}</td>
                <td style="max-width:200px; word-break:break-word;">${order.receiver_address || 'N/A'}</td>
                <td>${parseFloat(order.khoi_luong_kg || 0).toFixed(1)}</td>
                <td style="text-align:center;">${matchBadge}</td>
            </tr>
        `;
    }).join('');

    updateOrderSummary();
}

// Check các checkbox theo danh sách id
function checkOrderIds(ids) {
    const idSet = new Set(ids.map(String));
    document.querySelectorAll('.order-checkbox').forEach(cb => {
        cb.checked = idSet.has(cb.value);
    });
}

function selectAllMatchedOrders() {
    checkOrderIds(matchedOrderIds);
    updateOrderSummary();
}

function clearAllOrders() {
    document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('checkAll').checked = false;
    updateOrderSummary();
}

function toggleCheckAll(masterCb) {
    document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = masterCb.checked);
    updateOrderSummary();
}

function updateOrderSummary() {
    const checked = getSelectedOrderIds();
    const totalKg = checked.reduce((sum, id) => {
        const order = allPendingOrders.find(o => String(o.id) === String(id));
        return sum + (order ? parseFloat(order.khoi_luong_kg || 0) : 0);
    }, 0);

    const summaryEl = document.getElementById('orderSummary');
    if (checked.length > 0) {
        summaryEl.textContent = `Đã chọn ${checked.length} đơn • Tổng khối lượng: ${totalKg.toFixed(1)} kg`;
    } else {
        summaryEl.textContent = '';
    }

    // Sync check-all state
    const allCbs = document.querySelectorAll('.order-checkbox');
    const checkAllCb = document.getElementById('checkAll');
    if (allCbs.length > 0) {
        checkAllCb.checked = checked.length === allCbs.length;
        checkAllCb.indeterminate = checked.length > 0 && checked.length < allCbs.length;
    }
}

function getSelectedOrderIds() {
    return Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
}

// ===================== LOAD DỮ LIỆU =====================
function loadPendingOrders() {
    return fetch('../../backend/index.php?action=pending_orders')
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text(); // Lấy text trước để debug nếu JSON lỗi
        })
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch(e) {
                console.error('JSON parse lỗi, raw response:', text);
                throw new Error('Response không phải JSON: ' + text.substring(0, 200));
            }

            console.log('[pending_orders] response:', data);

            const tbody = document.getElementById('pendingOrdersList');
            if (data.success && data.data && data.data.length > 0) {
                allPendingOrders = data.data;
                tbody.innerHTML = data.data.map(order => `
                    <tr>
                        <td><strong>${order.ma_don}</strong></td>
                        <td>${order.sender_name || 'N/A'}</td>
                        <td>${order.receiver_name || 'N/A'}</td>
                        <td>${parseFloat(order.khoi_luong_kg || 0).toFixed(1)} kg</td>
                        <td>${order.receiver_address || 'N/A'}</td>
                        <td><span class="status-badge status-waiting">${order.trang_thai}</span></td>
                        <td><button class="btn btn-primary btn-small" onclick="quickSelectOrder('${order.id}')">+ Chọn</button></td>
                    </tr>
                `).join('');
            } else {
                allPendingOrders = [];
                console.warn('[pending_orders] Không có dữ liệu:', data);
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">Không có đơn hàng chờ điều phối</td></tr>';
            }
        })
        .catch(err => {
            console.error('[pending_orders] Lỗi:', err);
            document.getElementById('pendingOrdersList').innerHTML =
                `<tr><td colspan="7" style="text-align: center; color: #e03;">Lỗi tải dữ liệu: ${err.message}</td></tr>`;
        });
}

// Khi nhấn nút "Chọn" từ bảng chính → mở modal và pre-check đơn đó
function quickSelectOrder(orderId) {
    openAssignModal();
    setTimeout(() => {
        const cb = document.querySelector(`.order-checkbox[value="${orderId}"]`);
        if (cb) { cb.checked = true; updateOrderSummary(); }
    }, 100);
}

function loadShipments() {
    return fetch('../../backend/index.php?action=shipments')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('shipmentsList');
            if (data.success && data.data && data.data.length > 0) {
                tbody.innerHTML = data.data.map(shipment => `
                    <tr>
                        <td><strong>${shipment.ma_dot}</strong></td>
                        <td>${shipment.ten_tuyen || 'N/A'}</td>
                        <td>${shipment.tai_xe || 'N/A'}</td>
                        <td>${shipment.bien_so || 'N/A'}</td>
                        <td>${shipment.so_don || 0}</td>
                        <td><span class="status-badge ${getStatusClass(shipment.trang_thai)}">${formatTrangThai(shipment.trang_thai)}</span></td>
                        <td>${new Date(shipment.ngay_gio_bat_dau).toLocaleString('vi-VN')}</td>
                        <td>
                            <button class="btn btn-small" style="background: #4c6ef5; color: white;" onclick="showShipmentDetail(${shipment.id})">Chi tiết</button>
                            ${isExpiredAndPending(shipment) ? `<button class="btn btn-small" style="background: #ff6b6b; color: white; margin-left: 4px;" onclick="deferShipment(${shipment.id}, '${shipment.ma_dot}')">Dời đơn</button>` : ''}
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #999;">Không có đợt vận chuyển</td></tr>';
            }
        })
        .catch(err => {
            console.error('Lỗi tải đợt vận chuyển:', err);
            document.getElementById('shipmentsList').innerHTML =
                '<tr><td colspan="8" style="text-align: center; color: #999;">Không có đợt vận chuyển</td></tr>';
        });
}

function getStatusClass(status) {
    const map = {
        'chua_khoi_hanh': 'status-waiting',
        'dang_chay': 'status-assigned',
        'hoan_thanh': 'status-done',
        'huy': 'status-cancelled'
    };
    return map[status] || 'status-waiting';
}

function formatTrangThai(status) {
    const map = {
        'chua_khoi_hanh': 'Chưa khởi hành',
        'dang_chay': 'Đang chạy',
        'hoan_thanh': 'Hoàn thành',
        'huy': 'Đã hủy'
    };
    return map[status] || status;
}

// Kiểm tra đợt đã quá giờ và chưa chạy (để hiển thị nút Dời đơn)
function isExpiredAndPending(shipment) {
    if (shipment.trang_thai !== 'chua_khoi_hanh') return false;
    const departureTime = new Date(shipment.ngay_gio_bat_dau);
    return departureTime < new Date();
}

// ===================== DỜI ĐƠN SANG ĐỢT KẾ =====================
function deferShipment(dotId, maDot) {
    if (!confirm(`Đợt ${maDot} đã quá giờ khởi hành.\n\nBạn muốn dời các đơn chưa giao về hàng chờ để điều phối sang đợt kế?`)) return;

    const formData = new FormData();
    formData.append('action', 'defer_expired_shipments');
    formData.append('dot_id', dotId);

    fetch('../../backend/index.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(`✓ ${data.message}\n\nCác đơn đã trở về hàng chờ. Bạn có thể tạo đợt mới để điều phối lại.`);
                loadShipments();
                loadPendingOrders();
                loadStats();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể dời đơn'));
            }
        })
        .catch(err => alert('Lỗi khi dời đơn: ' + err.message));
}

// ===================== CHI TIẾT ĐỢT =====================
function showShipmentDetail(shipmentId) {
    fetch(`../../backend/index.php?action=shipment_details&id=${shipmentId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                const shipment = data.data.shipment;
                const orders = data.data.orders || [];

                document.getElementById('detailMaDot').textContent = shipment.ma_dot || '-';
                document.getElementById('detailTuyen').textContent = shipment.ten_tuyen || '-';
                document.getElementById('detailTaiXe').textContent = shipment.tai_xe || '-';
                document.getElementById('detailXe').textContent = shipment.bien_so || '-';
                document.getElementById('detailSoDon').textContent = shipment.so_don || 0;
                document.getElementById('detailTongKhoiLuong').textContent = (parseFloat(shipment.tong_khoi_luong) || 0).toFixed(2);
                document.getElementById('detailTrangThai').textContent = formatTrangThai(shipment.trang_thai) || '-';
                document.getElementById('detailGioKhoiHanh').textContent = shipment.ngay_gio_bat_dau
                    ? new Date(shipment.ngay_gio_bat_dau).toLocaleString('vi-VN') : '-';

                const ordersTbody = document.getElementById('detailOrdersList');
                if (orders.length > 0) {
                    ordersTbody.innerHTML = orders.map(order => `
                        <tr>
                            <td><strong>${order.ma_don}</strong></td>
                            <td>${order.ten_hang_hoa || 'N/A'}</td>
                            <td>${order.khoi_luong_kg || 0} kg</td>
                            <td>${order.sender_name || 'N/A'}</td>
                            <td>${order.receiver_name || 'N/A'}</td>
                            <td>${order.receiver_address || 'N/A'}</td>
                        </tr>
                    `).join('');
                } else {
                    ordersTbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">Không có đơn hàng</td></tr>';
                }

                document.getElementById('detailModal').style.display = 'flex';
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể tải chi tiết'));
            }
        })
        .catch(err => alert('Lỗi khi tải chi tiết: ' + err.message));
}

function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}

// ===================== LOAD DRIVERS / VEHICLES / ROUTES =====================
function loadDrivers() {
    return fetch('../../backend/index.php?action=available_drivers')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                document.getElementById('availableDrivers').textContent = data.data.count || 0;
                const sel = document.getElementById('driverSelect');
                sel.innerHTML = '<option value="">-- Chọn tài xế --</option>';
                if (data.data.drivers && data.data.drivers.length > 0) {
                    sel.innerHTML += data.data.drivers.map(d =>
                        `<option value="${d.id}">${d.ho_ten}</option>`
                    ).join('');
                }
            }
        })
        .catch(err => console.error('Lỗi tải tài xế:', err));
}

function loadVehicles() {
    return fetch('../../backend/index.php?action=available_vehicles')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                document.getElementById('availableVehicles').textContent = data.data.count || 0;
                const sel = document.getElementById('vehicleSelect');
                sel.innerHTML = '<option value="">-- Chọn xe --</option>';
                if (data.data.vehicles && data.data.vehicles.length > 0) {
                    sel.innerHTML += data.data.vehicles.map(v =>
                        `<option value="${v.id}">${v.bien_so}${v.trong_tai_kg ? ' (' + v.trong_tai_kg + ' kg)' : ''}</option>`
                    ).join('');
                }
            }
        })
        .catch(err => console.error('Lỗi tải xe:', err));
}

function loadRoutes() {
    return fetch('../../backend/index.php?action=routes')
        .then(r => r.json())
        .then(data => {
            const sel = document.getElementById('routeSelect');
            sel.innerHTML = '<option value="">-- Chọn tuyến --</option>';
            if (data.success && data.data && data.data.length > 0) {
                sel.innerHTML += data.data
                    .filter(t => t.trang_thai == 1 || t.trang_thai === undefined)
                    .map(t => `<option value="${t.id}">${t.ten_tuyen} (→ ${t.diem_den})</option>`)
                    .join('');
            }
        })
        .catch(err => {
            console.error('Lỗi tải tuyến:', err);
            // Fallback hardcode nếu API lỗi
            document.getElementById('routeSelect').innerHTML = `
                <option value="">-- Chọn tuyến --</option>
                <option value="1">Trà Vinh - Vĩnh Long</option>
                <option value="2">Trà Vinh - Cần Thơ</option>
                <option value="3">Trà Vinh - Sóc Trăng</option>
            `;
        });
}

function loadStats() {
    return fetch('../../backend/index.php?action=dispatcher_stats')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                document.getElementById('pendingOrders').textContent = data.data.pending_orders || 0;
                document.getElementById('todayShipments').textContent = data.data.today_shipments || 0;
                document.getElementById('availableDrivers').textContent = data.data.available_drivers || 0;
                document.getElementById('availableVehicles').textContent = data.data.available_vehicles || 0;
            }
        })
        .catch(err => console.error('Lỗi tải thống kê:', err));
}

// ===================== KHỞI TẠO =====================
// Load tuần tự để tránh vấn đề với PHP built-in single-threaded server
async function initPage() {
    await loadStats();
    await loadPendingOrders();
    await loadShipments();
    await loadDrivers();
    await loadVehicles();
    await loadRoutes();
}

initPage();

// ===================== SUBMIT FORM =====================
document.getElementById('assignForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const routeSelect  = document.getElementById('routeSelect').value;
    const driverSelect = document.getElementById('driverSelect').value;
    const vehicleSelect= document.getElementById('vehicleSelect').value;
    const departureTime= document.getElementById('departureTime').value;
    const notes        = document.getElementById('notes').value;
    const selectedOrders = getSelectedOrderIds();

    if (!routeSelect || !driverSelect || !vehicleSelect || !departureTime) {
        alert('Vui lòng điền đầy đủ tuyến đường, tài xế, xe và giờ khởi hành');
        return;
    }
    if (selectedOrders.length === 0) {
        if (!confirm('Bạn chưa chọn đơn hàng nào. Vẫn tạo đợt trống?')) return;
    }

    // Convert datetime-local → MySQL datetime
    const dateObj = new Date(departureTime);
    const mysqlDateTime = dateObj.getFullYear() + '-' +
        String(dateObj.getMonth() + 1).padStart(2, '0') + '-' +
        String(dateObj.getDate()).padStart(2, '0') + ' ' +
        String(dateObj.getHours()).padStart(2, '0') + ':' +
        String(dateObj.getMinutes()).padStart(2, '0') + ':' +
        String(dateObj.getSeconds()).padStart(2, '0');

    const formData = new FormData();
    formData.append('action', 'shipments');
    formData.append('tuyen_id', routeSelect);
    formData.append('tai_xe_id', driverSelect);
    formData.append('xe_id', vehicleSelect);
    formData.append('ngay_gio_bat_dau', mysqlDateTime);
    formData.append('ghi_chu', notes);
    selectedOrders.forEach(id => formData.append('don_hang_ids[]', id));

    fetch('../../backend/index.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(`✓ Đợt vận chuyển ${data.data.ma_dot} được tạo thành công!\n${data.data.so_don} đơn hàng đã được gán.`);
                closeAssignModal();
                loadShipments();
                loadPendingOrders();
                loadStats();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể tạo đợt vận chuyển'));
            }
        })
        .catch(err => {
            console.error('Lỗi:', err);
            alert('Lỗi khi tạo đợt vận chuyển: ' + err.message);
        });
});
