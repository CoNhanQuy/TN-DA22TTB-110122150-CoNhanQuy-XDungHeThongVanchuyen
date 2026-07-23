let allAssignedOrders = [];

function toggleReceiverField() {
    const status = document.getElementById('updateStatus').value;
    const receiverGroup = document.getElementById('receiverFieldGroup');
    const actualReceiver = document.getElementById('actualReceiver');
    const photoGroup = document.getElementById('photoFieldGroup');
    const photoInput = document.getElementById('deliveryPhoto');
    
    if (status === 'thanh_cong') {
        receiverGroup.style.display = 'block';
        actualReceiver.required = true;
        photoGroup.style.display = 'block';
        photoInput.required = true;
    } else {
        receiverGroup.style.display = 'none';
        actualReceiver.required = false;
        photoGroup.style.display = 'none';
        photoInput.required = false;
    }
}

function openStatusModal(id, code) {
    document.getElementById('updateOrderId').value = id;
    document.getElementById('displayOrderCode').value = code;
    document.getElementById('updateStatus').value = '';
    document.getElementById('actualReceiver').value = '';
    document.getElementById('updateNote').value = '';
    
    // Reset file input and preview
    const photoInput = document.getElementById('deliveryPhoto');
    if (photoInput) photoInput.value = '';
    const previewContainer = document.getElementById('photoPreviewContainer');
    if (previewContainer) previewContainer.style.display = 'none';
    const previewImg = document.getElementById('photoPreview');
    if (previewImg) previewImg.src = '';

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
    
    if (status === 'dang_giao' || status === 'dang_giao_hang') {
        cls = 'badge-on-route';
        text = 'Đang giao hàng';
    } else if (status === 'thanh_cong' || status === 'hoan_tat') {
        cls = 'badge-delivered';
        text = 'Đã giao hàng';
    } else if (status === 'that_bai' || status === 'da_nhap_kho' || status === 'da_huy') {
        cls = 'badge-returned';
        text = 'Trả lại';
    }
    
    return `<span class="status-badge ${cls}">${escapeHtml(text)}</span>`;
}

function checkQueryParam() {
    const params = new URLSearchParams(window.location.search);
    const orderCode = params.get('order');
    if (orderCode) {
        // Clean URL parameter so it doesn't reopen
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);

        const matched = allAssignedOrders.find(o => String(o.ma_don).trim() === String(orderCode).trim());
        if (matched) {
            openStatusModal(matched.don_hang_id, matched.ma_don);
        } else {
            const orderCodeInput = document.getElementById('orderCode');
            if (orderCodeInput) {
                orderCodeInput.value = orderCode;
                trackOrder();
            }
        }
    }
}

async function loadAssignedOrders() {
    try {
        const res = await apiFetch(getApiBase() + '/backend/api/index.php?action=driver_orders');
        const data = await res.json();
        const tbody = document.getElementById('assignedOrdersList');
        
        if (!data.success || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">Chưa có đơn hàng nào được phân công.</td></tr>';
            allAssignedOrders = [];
            return;
        }

        allAssignedOrders = data.data;

        tbody.innerHTML = data.data.map(order => `
            <tr>
                <td><strong>${escapeHtml(order.ma_don)}</strong></td>
                <td>${escapeHtml(order.ng_nhan)}</td>
                <td>${escapeHtml(order.sdt_nhan)}</td>
                <td>${escapeHtml(order.dia_chi_nhan)}</td>
                <td>${escapeHtml(order.ma_dot || '---')}</td>
                <td>${renderStatusBadge(order.trang_thai_giao_hang)}</td>
                <td>
                    <button class="btn btn-primary btn-small" onclick="openStatusModal(${order.don_hang_id}, '${escapeHtml(order.ma_don)}')">
                        Cập nhật
                    </button>
                </td>
            </tr>
        `).join('');

        checkQueryParam();
    } catch (error) {
        console.error('Lỗi tải đơn hàng:', error);
        document.getElementById('assignedOrdersList').innerHTML = '<tr><td colspan="7" style="text-align: center; color: red;">Lỗi tải dữ liệu.</td></tr>';
    }
}

async function loadDeliveryLog() {
    try {
        const res = await apiFetch(getApiBase() + '/backend/api/index.php?action=driver_delivery_log');
        const data = await res.json();
        const tbody = document.getElementById('deliveryLogList');
        
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
        document.getElementById('deliveryLogList').innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Lỗi tải dữ liệu.</td></tr>';
    }
}

// Live preview of captured photo
document.addEventListener('DOMContentLoaded', () => {
    const photoInput = document.getElementById('deliveryPhoto');
    photoInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const previewContainer = document.getElementById('photoPreviewContainer');
        const previewImg = document.getElementById('photoPreview');
        if (file) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                previewImg.src = evt.target.result;
                previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewImg.src = '';
            previewContainer.style.display = 'none';
        }
    });
});

document.getElementById('statusForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnUpdateStatus');
    btn.disabled = true;
    btn.textContent = 'Đang xử lý...';

    const id = document.getElementById('updateOrderId').value;
    const status = document.getElementById('updateStatus').value;
    const actualReceiver = document.getElementById('actualReceiver').value;
    const note = document.getElementById('updateNote').value;

    // If delivery is successful, upload photo first
    if (status === 'thanh_cong') {
        const photoInput = document.getElementById('deliveryPhoto');
        if (photoInput && photoInput.files.length > 0) {
            const photoData = new FormData();
            photoData.append('don_hang_id', id);
            photoData.append('photo', photoInput.files[0]);
            
            try {
                const uploadRes = await apiFetch(getApiBase() + '/backend/api/index.php?action=driver_upload_photo', {
                    method: 'POST',
                    body: photoData
                });
                const uploadResult = await uploadRes.json();
                if (!uploadResult.success) {
                    alert('✗ Lỗi tải lên ảnh minh chứng: ' + uploadResult.message);
                    btn.disabled = false;
                    btn.textContent = '✓ Cập nhật trạng thái';
                    return;
                }
            } catch (err) {
                alert('✗ Lỗi kết nối khi tải lên ảnh');
                console.error(err);
                btn.disabled = false;
                btn.textContent = '✓ Cập nhật trạng thái';
                return;
            }
        }
    }

    const formData = new FormData();
    formData.append('don_hang_id', id);
    formData.append('trang_thai', status);
    formData.append('nguoi_nhan_thuc_te', actualReceiver);
    formData.append('ghi_chu', note);

    try {
        const res = await apiFetch(getApiBase() + '/backend/api/index.php?action=driver_update_status', {
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
document.addEventListener('DOMContentLoaded', async () => {
    await loadAssignedOrders();
    await loadDeliveryLog();
    populateIncidentOrderDropdown();
});

// Populate incident form's order dropdown from assigned orders
function populateIncidentOrderDropdown() {
    const sel = document.getElementById('incidentOrderCode');
    if (!sel) return;
    // Clear old options except first
    while (sel.options.length > 1) sel.remove(1);
    allAssignedOrders.forEach(o => {
        const opt = document.createElement('option');
        opt.value = o.ma_don;
        opt.textContent = o.ma_don + ' – ' + (o.ng_nhan || '');
        sel.appendChild(opt);
    });
}

// Handle incident report submission
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('incidentForm');
    if (!form) return;
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitIncident');
        const msgBox = document.getElementById('incidentMsg');
        btn.disabled = true;
        btn.textContent = 'Đang gửi...';
        msgBox.style.display = 'none';

        const fd = new FormData();
        fd.append('ma_don',      document.getElementById('incidentOrderCode').value);
        fd.append('loai_su_co', document.getElementById('incidentType').value);
        fd.append('mo_ta',      document.getElementById('incidentDescription').value);
        fd.append('vi_tri',     document.getElementById('incidentLocation').value);
        fd.append('muc_do',     document.getElementById('incidentSeverity').value);

        try {
            const res  = await apiFetch(getApiBase() + '/backend/api/index.php?action=driver_report_incident', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            msgBox.style.display = 'block';
            if (data.success) {
                msgBox.style.cssText = 'display:block; padding:12px 16px; border-radius:8px; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; margin-bottom:1rem;';
                msgBox.textContent = '✅ ' + (data.message || 'Báo cáo sự cố đã được gửi thành công. Quản lý sẽ xử lý trong thời gian sớm nhất.');
                form.reset();
                populateIncidentOrderDropdown();
            } else {
                msgBox.style.cssText = 'display:block; padding:12px 16px; border-radius:8px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; margin-bottom:1rem;';
                msgBox.textContent = '❌ ' + (data.message || 'Không thể gửi báo cáo. Vui lòng thử lại.');
            }
        } catch (err) {
            msgBox.style.cssText = 'display:block; padding:12px 16px; border-radius:8px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; margin-bottom:1rem;';
            msgBox.textContent = '❌ Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại.';
            console.error(err);
        } finally {
            btn.disabled = false;
            btn.textContent = '🚨 Gửi báo cáo sự cố';
        }
    });
});

// ═══════════════════════════════════════════════════════════════
// GPS TRACKING — Shipper
// ═══════════════════════════════════════════════════════════════

let _shipperGpsWatchId = null;
let _shipperGpsOn      = false;
let _shipperLastPushMs = 0;
const SHIPPER_GPS_INTERVAL = 30000; // 30 giây

function toggleShipperGps() {
    if (_shipperGpsOn) {
        _stopShipperGps();
    } else {
        _startShipperGps();
    }
}

function _startShipperGps() {
    if (!navigator.geolocation) {
        _setShipperGpsBar('❌ Trình duyệt không hỗ trợ GPS', 'error');
        return;
    }
    _setShipperGpsBar('⏳ Đang lấy vị trí...', 'loading');
    const btn = document.getElementById('btnToggleGps');
    if (btn) { btn.textContent = '⏹ Tắt GPS'; btn.classList.add('btn-danger'); }
    _shipperGpsOn = true;
    _shipperGpsWatchId = navigator.geolocation.watchPosition(
        _onShipperPosition,
        _onShipperGpsError,
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
    );
}

function _stopShipperGps() {
    if (_shipperGpsWatchId !== null) {
        navigator.geolocation.clearWatch(_shipperGpsWatchId);
        _shipperGpsWatchId = null;
    }
    _shipperGpsOn = false;
    const btn = document.getElementById('btnToggleGps');
    if (btn) { btn.textContent = '📍 Bật GPS'; btn.classList.remove('btn-danger'); }
    _setShipperGpsBar('GPS đã tắt', 'idle');
    const coordEl = document.getElementById('gpsCoords');
    if (coordEl) coordEl.textContent = '';
}

async function _onShipperPosition(pos) {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;
    const acc = Math.round(pos.coords.accuracy);

    const coordEl = document.getElementById('gpsCoords');
    if (coordEl) coordEl.textContent = `${lat.toFixed(5)}, ${lng.toFixed(5)} (±${acc}m)`;

    const now = Date.now();
    if (now - _shipperLastPushMs < SHIPPER_GPS_INTERVAL) return;
    _shipperLastPushMs = now;

    try {
        const fd = new FormData();
        fd.append('vi_do',   lat);
        fd.append('kinh_do', lng);
        const res  = await apiFetch(`${getApiBase()}/backend/api/index.php?action=gps_update`, {
            method: 'POST', body: fd
        });
        const data = await res.json();
        if (data.success) {
            _setShipperGpsBar(`✅ Đã gửi vị trí — ${new Date().toLocaleTimeString('vi-VN')}`, 'active');
        } else {
            _setShipperGpsBar(`⚠️ ${data.message || 'Lỗi gửi vị trí'}`, 'warn');
        }
    } catch (err) {
        _setShipperGpsBar('❌ Lỗi kết nối khi gửi vị trí', 'error');
        console.error('GPS push error:', err);
    }
}

function _onShipperGpsError(err) {
    const msgs = {
        1: 'Bạn đã từ chối quyền truy cập vị trí',
        2: 'Không thể xác định vị trí',
        3: 'Hết thời gian lấy vị trí',
    };
    _setShipperGpsBar('❌ ' + (msgs[err.code] || 'Lỗi GPS'), 'error');
}

function _setShipperGpsBar(msg, state) {
    const el  = document.getElementById('gpsBarStatus');
    const bar = document.getElementById('gpsTrackingBar');
    if (el)  el.textContent = msg;
    if (bar) {
        bar.className = 'gps-tracking-bar';
        if (state === 'active') bar.classList.add('gps-bar-active');
        if (state === 'error')  bar.classList.add('gps-bar-error');
        if (state === 'warn')   bar.classList.add('gps-bar-warn');
    }
}
