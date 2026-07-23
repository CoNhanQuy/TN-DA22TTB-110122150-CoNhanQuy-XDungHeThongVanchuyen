function renderShipmentBadge(status) {
    let cls = 'badge-chua_khoi_hanh';
    let text = 'Chưa khởi hành';
    if (status === 'dang_di_chuyen') { cls = 'badge-dang_chay'; text = 'Đang chạy'; }
    if (status === 'da_den_kho_nhan') { cls = 'badge-hoan_thanh'; text = 'Hoàn thành'; }
    if (status === 'huy') { cls = 'badge-huy'; text = 'Đã hủy'; }
    return `<span class="status-badge ${cls}">${escapeHtml(text)}</span>`;
}

function openStatusModal(id, code, currentStatus) {
    document.getElementById('updateDotId').value = id;
    document.getElementById('displayDotCode').value = code;
    document.getElementById('updateStatus').value = currentStatus === 'cho_khoi_hanh' ? 'dang_di_chuyen' : 'da_den_kho_nhan';
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
        const baseUrl = getApiBase();
        const res = await apiFetch(`${baseUrl}/backend/api/index.php?action=my_shipments`);
        const data = await res.json();
        const tbody = document.getElementById('shipmentsList');
        if (!tbody) return;
        
        if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">' + (data.message || 'Bạn chưa được phân công chuyến xe nào.') + '</td></tr>';
            return;
        }

        myShipments = data.data || [];
        tbody.innerHTML = myShipments.map(shipment => `
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
        populateIncidentShipmentDropdown();
    } catch (error) {
        console.error('Lỗi tải chuyến xe:', error);
        const tbody = document.getElementById('shipmentsList');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Lỗi tải dữ liệu. Vui lòng thử lại.</td></tr>';
        }
    }
}

let myShipments = [];

function populateIncidentShipmentDropdown() {
    const sel = document.getElementById('incidentShipmentId');
    if (!sel) return;
    while (sel.options.length > 1) sel.remove(1);
    myShipments.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.ma_dot;
        opt.textContent = s.ma_dot + ' (' + (s.ten_tuyen || '') + ')';
        sel.appendChild(opt);
    });
}

document.getElementById('statusForm')?.addEventListener('submit', async function(e) {
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
        const baseUrl = getApiBase();
        const res = await apiFetch(`${baseUrl}/backend/api/index.php?action=update_shipment_status`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('✓ ' + data.message);
            closeStatusModal();
            loadShipments();
        } else {
            let errMsg = '✗ Lỗi: ' + data.message;
            if (data.data) {
                errMsg += '\n\n[Debug] ' + JSON.stringify(data.data, null, 2);
            }
            alert(errMsg);
        }
    } catch (error) {
        alert('✗ Lỗi kết nối máy chủ');
        console.error(error);
    } finally {
        btn.disabled = false;
        btn.textContent = '✓ Cập nhật trạng thái';
    }
});

function initTaixePage() {
    loadShipments();

    const form = document.getElementById('incidentForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitIncident');
            const msgBox = document.getElementById('incidentMsg');
            btn.disabled = true;
            btn.textContent = 'Đang gửi...';
            msgBox.style.display = 'none';

            const fd = new FormData();
            fd.append('ma_dot_van_chuyen', document.getElementById('incidentShipmentId').value);
            fd.append('loai_su_co', document.getElementById('incidentType').value);
            fd.append('mo_ta',      document.getElementById('incidentDescription').value);
            fd.append('vi_tri',     document.getElementById('incidentLocation').value);
            fd.append('muc_do',     document.getElementById('incidentSeverity').value);

            try {
                const baseUrl = getApiBase();
                const res = await apiFetch(`${baseUrl}/backend/api/index.php?action=driver_report_incident`, {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                msgBox.style.display = 'block';
                if (data.success) {
                    msgBox.style.cssText = 'display:block; padding:12px 16px; border-radius:8px; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; margin-bottom:1rem;';
                    msgBox.textContent = '✅ ' + (data.message || 'Báo cáo sự cố đã được gửi thành công.');
                    form.reset();
                } else {
                    msgBox.style.cssText = 'display:block; padding:12px 16px; border-radius:8px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; margin-bottom:1rem;';
                    msgBox.textContent = '❌ ' + (data.message || 'Không thể gửi báo cáo.');
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
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTaixePage);
} else {
    initTaixePage();
}

// ═══════════════════════════════════════════════════════════════
// GPS TRACKING — Tài xế
// ═══════════════════════════════════════════════════════════════

let _driverGpsWatchId  = null;
let _driverGpsOn       = false;
let _driverLastPushMs  = 0;
const GPS_PUSH_INTERVAL = 30000; // 30 giây

function toggleDriverGps() {
    if (_driverGpsOn) {
        _stopDriverGps();
    } else {
        _startDriverGps();
    }
}

function _startDriverGps() {
    if (!navigator.geolocation) {
        _setGpsBarStatus('❌ Trình duyệt không hỗ trợ GPS', 'error');
        return;
    }

    _setGpsBarStatus('⏳ Đang lấy vị trí...', 'loading');
    const btn = document.getElementById('btnToggleGps');
    if (btn) { btn.textContent = '⏹ Tắt GPS'; btn.classList.add('btn-danger'); }

    _driverGpsOn = true;

    _driverGpsWatchId = navigator.geolocation.watchPosition(
        _onDriverPosition,
        _onDriverGpsError,
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
    );
}

function _stopDriverGps() {
    if (_driverGpsWatchId !== null) {
        navigator.geolocation.clearWatch(_driverGpsWatchId);
        _driverGpsWatchId = null;
    }
    _driverGpsOn = false;
    const btn = document.getElementById('btnToggleGps');
    if (btn) { btn.textContent = '📍 Bật GPS'; btn.classList.remove('btn-danger'); }
    _setGpsBarStatus('GPS đã tắt', 'idle');
    const coordEl = document.getElementById('gpsCoords');
    if (coordEl) coordEl.textContent = '';
}

async function _onDriverPosition(pos) {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;
    const acc = Math.round(pos.coords.accuracy);

    // Update UI coords
    const coordEl = document.getElementById('gpsCoords');
    if (coordEl) coordEl.textContent = `${lat.toFixed(5)}, ${lng.toFixed(5)} (±${acc}m)`;

    const now = Date.now();
    if (now - _driverLastPushMs < GPS_PUSH_INTERVAL) return; // throttle
    _driverLastPushMs = now;

    try {
        const fd = new FormData();
        fd.append('vi_do',   lat);
        fd.append('kinh_do', lng);
        const baseUrl = getApiBase();
        const res  = await apiFetch(`${baseUrl}/backend/api/index.php?action=gps_update`, {
            method: 'POST', body: fd
        });
        const data = await res.json();
        if (data.success) {
            _setGpsBarStatus(`✅ Đã gửi vị trí — ${new Date().toLocaleTimeString('vi-VN')}`, 'active');
        } else {
            _setGpsBarStatus(`⚠️ ${data.message || 'Lỗi gửi vị trí'}`, 'warn');
        }
    } catch (err) {
        _setGpsBarStatus('❌ Lỗi kết nối khi gửi vị trí', 'error');
        console.error('GPS push error:', err);
    }
}

function _onDriverGpsError(err) {
    const msgs = {
        1: 'Bạn đã từ chối quyền truy cập vị trí',
        2: 'Không thể xác định vị trí',
        3: 'Hết thời gian lấy vị trí',
    };
    _setGpsBarStatus('❌ ' + (msgs[err.code] || 'Lỗi GPS'), 'error');
}

function _setGpsBarStatus(msg, state) {
    const el  = document.getElementById('gpsBarStatus');
    const bar = document.getElementById('gpsTrackingBar');
    if (el)  el.textContent = msg;
    if (bar) {
        bar.className = 'gps-tracking-bar';
        if (state === 'active')  bar.classList.add('gps-bar-active');
        if (state === 'error')   bar.classList.add('gps-bar-error');
        if (state === 'warn')    bar.classList.add('gps-bar-warn');
    }
}
