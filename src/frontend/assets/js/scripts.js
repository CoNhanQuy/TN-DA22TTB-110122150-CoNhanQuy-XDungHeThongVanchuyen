// Vận Tải Xanh - Main JavaScript
// (Di chuyển từ frontend/scripts.js)

function getApiBase() {
    return (typeof window.API_BASE === 'string') ? window.API_BASE : '';
}

/**
 * Helper fetch luôn kèm credentials (session cookie).
 * Dùng thay thế fetch() trực tiếp trong toàn bộ dự án.
 */
function apiFetch(url, options = {}) {
    return fetch(url, { credentials: 'include', ...options });
}

// Modal functions
function openModal() {
    document.getElementById('loginModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('loginModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('loginModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = String(str ?? '');
    return div.innerHTML;
}

function formatDateTime(value) {
    if (!value) return '';
    const s = String(value).replace(' ', 'T');
    const d = new Date(s);
    if (Number.isNaN(d.getTime())) return escapeHtml(value);
    return d.toLocaleString('vi-VN');
}

function mapStatusLabel(status) {
    switch (status) {
        case 'cho_tiep_nhan':   return 'Chờ tiếp nhận';
        case 'da_nhap_kho':     return 'Đã nhập kho';
        case 'dang_van_chuyen': return 'Đang vận chuyển';
        case 'da_den_kho_dich': return 'Đã đến kho đích';
        case 'dang_giao_hang':  return 'Đang giao hàng';
        case 'da_giao_hang':    return 'Đã giao hàng';
        case 'hoan_tat':        return 'Hoàn tất';
        case 'da_xep_len_xe':   return 'Đã xếp lên xe';
        case 'da_giao':         return 'Đã giao';
        case 'tra_lai':         return 'Trả lại';
        case 'cho_lay_hang':    return 'Chờ lấy hàng';
        case 'dang_giao':       return 'Đang giao hàng';
        case 'thanh_cong':      return 'Đã giao hàng';
        case 'that_bai':        return 'Trả lại';
        case 'da_huy':          return 'Đã hủy';
        case 'huy':             return 'Đã hủy';
        case 'dang_xu_ly':      return 'Đang xử lý';
        case 'dang_phat':       return 'Đang phát';
        case 'cho_xu_ly':       return 'Chờ xử lý';
        case 'dang_lay_hang':   return 'Đang lấy hàng';
        default: return status ? String(status) : '';
    }
}

// Track order function (AJAX - không reload)
async function trackOrder() {
    const orderCodeEl  = document.getElementById('orderCode');
    const orderPhoneEl = document.getElementById('orderPhone');
    const orderCCCDEl  = document.getElementById('orderCCCD');

    const orderCode  = (orderCodeEl?.value  ?? '').trim();
    const orderPhone = (orderPhoneEl?.value ?? '').trim();
    const orderCCCD  = (orderCCCDEl?.value  ?? '').trim();

    const resultDiv   = document.getElementById('trackResult');
    const errorDiv    = document.getElementById('trackError');
    const summaryDiv  = document.getElementById('trackSummary');
    const timelineDiv = document.getElementById('trackTimeline');

    if (!orderCode) {
        if (errorDiv) {
            errorDiv.textContent = 'Vui lòng nhập mã đơn hàng';
            errorDiv.style.display = 'block';
        }
        if (resultDiv) resultDiv.style.display = 'none';
        return;
    }

    if (errorDiv) errorDiv.style.display = 'none';
    if (resultDiv) resultDiv.style.display = 'none';

    const btn = document.querySelector('.tracking-btn') || document.querySelector('.tracking-input button');
    try {
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Đang tra cứu...';
        }

        const form = new FormData();
        form.append('ma_don', orderCode);
        if (orderPhone) form.append('so_dien_thoai', orderPhone);
        if (orderCCCD)  form.append('so_cccd', orderCCCD);

        const res = await apiFetch(getApiBase() + '/backend/api/index.php?action=track', {
            method: 'POST',
            body: form,
        });

        let data;
        const contentType = res.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            data = await res.json();
        } else {
            const text = await res.text();
            throw new Error('API không trả JSON: ' + text.slice(0, 200));
        }

        if (!data?.success) {
            if (errorDiv) {
                errorDiv.textContent = data?.message || 'Tra cứu thất bại';
                errorDiv.style.display = 'block';
            }
            return;
        }

        const payload  = data.data || {};
        const order    = payload.order || {};
        const timeline = Array.isArray(payload.timeline) ? payload.timeline : [];

        if (summaryDiv) {
            summaryDiv.innerHTML = `
                <div class="track-summary-row">
                    <div><strong>Mã đơn:</strong> ${escapeHtml(order.ma_don || orderCode)}</div>
                    <div><strong>Trạng thái:</strong> ${escapeHtml(mapStatusLabel(order.trang_thai))}</div>
                </div>
                <div class="track-summary-row">
                    <div><strong>Người gửi:</strong> ${escapeHtml(order?.nguoi_gui?.ho_ten || '')} (${escapeHtml(order?.nguoi_gui?.so_dien_thoai || '')}) - CCCD: ${escapeHtml(order?.nguoi_gui?.so_cccd || 'N/A')}</div>
                    <div><strong>Người nhận:</strong> ${escapeHtml(order?.nguoi_nhan?.ho_ten || '')} (${escapeHtml(order?.nguoi_nhan?.so_dien_thoai || '')}) - CCCD: ${escapeHtml(order?.nguoi_nhan?.so_cccd || 'N/A')}</div>
                </div>
            `;
        }

        if (timelineDiv) {
            timelineDiv.innerHTML = `
                <div class="timeline">
                    ${timeline.map((item, idx) => {
                        const label = mapStatusLabel(item.status);
                        const time  = formatDateTime(item.time);
                        const note  = item.note ? escapeHtml(item.note) : '';
                        return `
                            <div class="timeline-item ${idx === timeline.length - 1 ? 'is-last' : ''}">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">${escapeHtml(label)}</div>
                                    <div class="timeline-meta">${escapeHtml(time)}</div>
                                    ${note ? `<div class="timeline-note">${note}</div>` : ''}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        if (resultDiv) resultDiv.style.display = 'block';
    } catch (err) {
        console.error(err);
        if (errorDiv) {
            const msg = (err && err.message) ? String(err.message) : 'Lỗi khi tìm kiếm. Vui lòng thử lại.';
            errorDiv.textContent = 'Lỗi: ' + msg;
            errorDiv.style.display = 'block';
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Tra cứu ngay';
        }
    }
}

// Allow Enter key for search
document.addEventListener('DOMContentLoaded', function() {
    [
        document.getElementById('orderCode'),
        document.getElementById('orderPhone'),
        document.getElementById('orderCCCD'),
    ].forEach((el) => {
        if (!el) return;
        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                trackOrder();
            }
        });
    });
});
