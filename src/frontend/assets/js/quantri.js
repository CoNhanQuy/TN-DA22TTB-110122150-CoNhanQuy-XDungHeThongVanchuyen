// Tab labels for topbar
const TAB_META = {
    dashboard:        { title: 'Dashboard',          sub: 'Tổng quan hệ thống vận tải' },
    users:            { title: 'Người dùng',         sub: 'Quản lý tài khoản hệ thống' },
    delivery_persons: { title: 'Người giao hàng',    sub: 'Danh sách shipper & tài xế' },
    customers:        { title: 'Khách hàng',         sub: 'Danh sách khách hàng' },
    orders:           { title: 'Đơn hàng',           sub: 'Quản lý đơn vận chuyển' },
    vehicles:         { title: 'Phương tiện',        sub: 'Quản lý xe & phương tiện' },
    routes:           { title: 'Tuyến đường',        sub: 'Quản lý tuyến vận chuyển' },
    pricing:          { title: 'Bảng giá',           sub: 'Cấu hình phí vận chuyển' },
    reports:          { title: 'Báo cáo',            sub: 'Thống kê & phân tích dữ liệu' },
    gpsmap:           { title: 'Bản đồ GPS',         sub: 'Vị trí thời gian thực của tài xế & shipper' },
};

// -- Map vai trò sang tiếng Việt -----------------------------------------------
const VAI_TRO_LABEL = {
    'admin':                 'Admin',
    'nhan_vien_tiep_nhan':   'Tiếp nhận',
    'nhan_vien_dieu_phoi':   'Điều phối',
    'tai_xe':                'Tài xế',
    'shipper':               'Người giao hàng',
    'khach_hang':            'Khách hàng',
};
function labelVaiTro(role) {
    return VAI_TRO_LABEL[role] ?? role ?? 'Khách hàng';
}

function formatLoaiXe(type) {
    if (!type) return '';
    const map = {
        'xe_tai_nho': 'Xe tải nhỏ',
        'xe_tai_trung': 'Xe tải trung',
        'xe_tai_lon': 'Xe tải lớn'
    };
    return map[type] ?? type.replace(/_/g, ' ');
}

function formatBranchName(name) {
    if (!name) return '';
    return name
        .replace(/Chi nhanh/gi, 'Chi nhánh')
        .replace(/Trung tam/gi, 'Trung tâm')
        .replace(/Thanh pho/gi, 'Thành phố')
        .replace(/Phuong/gi, 'Phường')
        .replace(/Tra Vinh/gi, 'Trà Vinh')
        .replace(/Vinh Long/gi, 'Vĩnh Long');
}

// -- Helpers -------------------------------------------------------------------

const vnd = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' });

function emptyRow(cols, msg = 'Chưa có dữ liệu') {
    return `<tr class="empty-row"><td colspan="${cols}">${msg}</td></tr>`;
}

function badgeStatus(active, labelOn = 'Hoạt động', labelOff = 'Khóa') {
    return active == 1
        ? `<span class="badge badge-success">${labelOn}</span>`
        : `<span class="badge badge-danger">${labelOff}</span>`;
}

const ORDER_STATUS_MAP = {
    'cho_tiep_nhan':    ['badge-warning', 'Chờ tiếp nhận'],
    'da_nhap_kho':      ['badge-info',    'Đã nhập kho'],
    'dang_xu_ly':       ['badge-info',    'Đang xử lý'],
    'da_den_kho_dich':  ['badge-info',    'Đã đến kho đích'],
    'dang_phat':        ['badge-info',    'Đang phát'],
    'da_giao_hang':     ['badge-success', 'Đã giao hàng'],
    'hoan_tat':         ['badge-success', 'Hoàn tất'],
    'tra_lai':          ['badge-danger',  'Trả lại'],
    'huy':              ['badge-danger',  'Hủy'],
    'da_huy':           ['badge-danger',  'Đã hủy'],
    // alias cu
    'cho_xu_ly':        ['badge-warning', 'Chờ xử lý'],
    'dang_lay_hang':    ['badge-info',    'Đang lấy hàng'],
    'dang_van_chuyen':  ['badge-info',    'Đang vận chuyển'],
    'dang_giao_hang':   ['badge-info',    'Đang giao hàng'],
};
function badgeOrder(status) {
    const [cls, label] = ORDER_STATUS_MAP[status] ?? ['badge-gray', status ?? ''];
    return `<span class="badge ${cls}">${label}</span>`;
}

// -----------------------------------------------------------------------------

function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));

    const tabEl = document.getElementById(tabName);
    if (tabEl) tabEl.classList.add('active');

    const link = document.querySelector(`.nav-link[data-tab="${tabName}"]`);
    if (link) link.classList.add('active');

    const meta = TAB_META[tabName] || { title: tabName, sub: '' };
    const titleEl = document.getElementById('topbarTitle');
    const subEl   = document.getElementById('topbarSub');
    if (titleEl) titleEl.textContent = meta.title;
    if (subEl)   subEl.textContent   = meta.sub;

    if (tabName === 'gpsmap') {
        setTimeout(() => {
            _initGpsMap();
            if (_gpsMap) {
                _gpsMap.invalidateSize();
            }
            loadGpsLocations();

            if (_gpsRefreshInt) clearInterval(_gpsRefreshInt);
            _gpsRefreshInt = setInterval(loadGpsLocations, 30000);
        }, 150);
    } else {
        if (typeof _gpsRefreshInt !== 'undefined' && _gpsRefreshInt) {
            clearInterval(_gpsRefreshInt);
            _gpsRefreshInt = null;
        }
    }
}

// Tab switching
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        switchTab(this.getAttribute('data-tab'));
    });
});

let __cacheUsers = [];
let __cacheVehicles = [];
let __cacheRoutes = [];
let __cacheDeliveryPersons = [];
let __cachePricing = [];
let __cacheCustomers = [];
let __cacheOrders = [];

// Filtering & Sorting states
let userRoleFilter = '';
let orderStatusFilter = '';
let userSortState = { field: '', direction: '' };
let deliverySortState = { field: '', direction: '' };
let customerSortState = { field: '', direction: '' };

function updateSortIcons(section, sortState) {
    let selectors = [];
    if (section === 'users') {
        selectors = [
            { field: 'ho_ten', id: 'userSortName' },
            { field: 'vai_tro', id: 'userSortRole' }
        ];
    } else if (section === 'delivery_persons') {
        selectors = [
            { field: 'ho_ten', id: 'deliverySortName' }
        ];
    } else if (section === 'customers') {
        selectors = [
            { field: 'ho_ten', id: 'customerSortName' }
        ];
    }
    
    selectors.forEach(sel => {
        const el = document.getElementById(sel.id);
        if (el) {
            const iconEl = el.querySelector('.sort-icon');
            if (iconEl) {
                if (sortState.field === sel.field && sortState.direction) {
                    iconEl.textContent = sortState.direction === 'asc' ? '▲' : '▼';
                    el.classList.add('active-sort');
                } else {
                    iconEl.textContent = '↕';
                    el.classList.remove('active-sort');
                }
            }
        }
    });
}

function applyUserFilterAndSort() {
    let list = [...__cacheUsers];
    
    // Lọc theo vai trò
    if (userRoleFilter) {
        list = list.filter(u => u.vai_tro === userRoleFilter);
    }
    
    // Sắp xếp
    if (userSortState.field && userSortState.direction) {
        const field = userSortState.field;
        const d = userSortState.direction === 'asc' ? 1 : -1;
        list.sort((a, b) => {
            let valA = a[field] ?? '';
            let valB = b[field] ?? '';
            
            if (field === 'vai_tro') {
                valA = labelVaiTro(valA);
                valB = labelVaiTro(valB);
            }
            
            let cmp = valA.localeCompare(valB, 'vi', { sensitivity: 'base' });
            if (cmp === 0 && field !== 'ho_ten') {
                const nameA = a.ho_ten ?? '';
                const nameB = b.ho_ten ?? '';
                return nameA.localeCompare(nameB, 'vi', { sensitivity: 'base' });
            }
            return cmp * d;
        });
    }
    
    renderUsersTable(list);
}

function filterUserByRole() {
    const select = document.getElementById('userRoleFilter');
    if (select) {
        userRoleFilter = select.value;
    }
    applyUserFilterAndSort();
}

function toggleUserSort(field) {
    if (userSortState.field === field) {
        if (userSortState.direction === 'asc') {
            userSortState.direction = 'desc';
        } else if (userSortState.direction === 'desc') {
            userSortState.direction = '';
        } else {
            userSortState.direction = 'asc';
        }
    } else {
        userSortState.field = field;
        userSortState.direction = 'asc';
    }
    
    updateSortIcons('users', userSortState);
    applyUserFilterAndSort();
}

function toggleDeliverySort(field) {
    if (deliverySortState.field === field) {
        if (deliverySortState.direction === 'asc') {
            deliverySortState.direction = 'desc';
        } else if (deliverySortState.direction === 'desc') {
            deliverySortState.direction = '';
        } else {
            deliverySortState.direction = 'asc';
        }
    } else {
        deliverySortState.field = field;
        deliverySortState.direction = 'asc';
    }
    
    updateSortIcons('delivery_persons', deliverySortState);
    
    let sorted = [...__cacheDeliveryPersons];
    if (deliverySortState.direction) {
        const d = deliverySortState.direction === 'asc' ? 1 : -1;
        sorted.sort((a, b) => {
            const valA = a[field] ?? '';
            const valB = b[field] ?? '';
            return valA.localeCompare(valB, 'vi', { sensitivity: 'base' }) * d;
        });
    }
    renderDeliveryPersonsTable(sorted);
}

function toggleCustomerSort(field) {
    if (customerSortState.field === field) {
        if (customerSortState.direction === 'asc') {
            customerSortState.direction = 'desc';
        } else if (customerSortState.direction === 'desc') {
            customerSortState.direction = '';
        } else {
            customerSortState.direction = 'asc';
        }
    } else {
        customerSortState.field = field;
        customerSortState.direction = 'asc';
    }
    
    updateSortIcons('customers', customerSortState);
    
    let sorted = [...__cacheCustomers];
    if (customerSortState.direction) {
        const d = customerSortState.direction === 'asc' ? 1 : -1;
        sorted.sort((a, b) => {
            const valA = a[field] ?? '';
            const valB = b[field] ?? '';
            return valA.localeCompare(valB, 'vi', { sensitivity: 'base' }) * d;
        });
    }
    renderCustomersTable(sorted);
}

function renderUsersTable(users) {
    const tbody = document.getElementById('usersList');
    if (!tbody) return;
    if (Array.isArray(users) && users.length > 0) {
        tbody.innerHTML = users.map((u, idx) => `
            <tr>
                <td>${idx + 1}</td>
                <td><strong>${u.ho_ten ?? ''}</strong></td>
                <td>${u.so_dien_thoai ?? ''}</td>
                <td><span class="badge badge-purple">${labelVaiTro(u.vai_tro)}</span></td>
                <td>${badgeStatus(u.trang_thai)}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="openUserModal(__cacheUsers.find(x=>x.id==${u.id}))">Sửa</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id})">Xóa</button>
                </td>
            </tr>`).join('');
    } else {
        tbody.innerHTML = emptyRow(6, 'Chưa có dữ liệu người dùng');
    }
}

function renderDeliveryPersonsTable(deliveryPersons) {
    const tbody = document.getElementById('deliveryPersonsList');
    if (!tbody) return;
    if (Array.isArray(deliveryPersons) && deliveryPersons.length > 0) {
        tbody.innerHTML = deliveryPersons.map((p, idx) => `
            <tr>
                <td>${idx + 1}</td>
                <td><strong>${p.ho_ten ?? ''}</strong></td>
                <td>${p.so_dien_thoai ?? ''}</td>
                <td>${formatBranchName(p.khu_vuc_phu_trach)}</td>
                <td>${formatBranchName(p.ten_chi_nhanh ?? (p.chi_nhanh_id ? 'Chi nhánh ' + p.chi_nhanh_id : ''))}</td>
                <td>${badgeStatus(p.trang_thai, 'Hoạt động', 'Tạm dừng')}</td>
                <td style="white-space: nowrap;">
                    <button class="btn btn-primary btn-sm" onclick="openDeliveryPersonModal(__cacheDeliveryPersons.find(x=>x.id==${p.id}))">Sửa</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteDeliveryPerson(${p.id})">Xóa</button>
                </td>
            </tr>`).join('');
    } else {
        tbody.innerHTML = emptyRow(7, 'Chưa có dữ liệu người giao hàng');
    }
}

function renderCustomersTable(customers) {
    const tbody = document.getElementById('customersList');
    if (!tbody) return;
    if (Array.isArray(customers) && customers.length > 0) {
        tbody.innerHTML = customers.map((c, idx) => `
            <tr>
                <td>${idx + 1}</td>
                <td><strong>${c.ho_ten ?? ''}</strong></td>
                <td>${c.so_dien_thoai ?? ''}</td>
                <td>${c.so_cccd ?? ''}</td>
                <td>${formatBranchName(c.dia_chi)}</td>
                <td>—</td>
            </tr>`).join('');
    } else {
        tbody.innerHTML = emptyRow(6, 'Chưa có dữ liệu khách hàng');
    }
}

function renderOrdersTable(orders) {
    const tbody = document.getElementById('ordersList');
    if (!tbody) return;
    if (Array.isArray(orders) && orders.length > 0) {
        tbody.innerHTML = orders.map(o => {
            const status = o.trang_thai_don_hang ?? o.trang_thai ?? '';
            const hangHoa = o.ten_hang_hoa ?? '';
            return `
            <tr>
                <td><code>${o.ma_don ?? ''}</code></td>
                <td>${hangHoa}</td>
                <td>${o.khoi_luong_kg ?? ''}</td>
                <td>${vnd.format(Number(o.phi_van_chuyen ?? 0))}</td>
                <td>${badgeOrder(status)}</td>
                <td>${(o.ngay_tao ?? '').slice(0, 16)}</td>
                <td><button class="btn btn-ghost btn-sm" onclick="openOrderDetailModal(${o.id})">Chi tiết</button></td>
            </tr>`;
        }).join('');
    } else {
        tbody.innerHTML = emptyRow(7, 'Chưa có đơn hàng nào');
    }
}

function applyOrderFilter() {
    let list = [...__cacheOrders];
    if (orderStatusFilter) {
        list = list.filter(o => {
            const status = o.trang_thai_don_hang ?? o.trang_thai ?? '';
            return status === orderStatusFilter;
        });
    }
    renderOrdersTable(list);
}

function filterOrderByStatus() {
    const select = document.getElementById('orderStatusFilter');
    if (select) {
        orderStatusFilter = select.value;
    }
    applyOrderFilter();
}


function apiGet(action) {
    return fetch(`${getApiBase()}/backend/api/index.php?action=${encodeURIComponent(action)}`, {
        method: 'GET',
        credentials: 'include'
    }).then(r => r.json());
}

function apiPost(action, payload) {
    const formData = new FormData();
    Object.entries(payload ?? {}).forEach(([key, value]) => {
        if (value === undefined || value === null) return;
        formData.append(key, value);
    });

    return fetch(`${getApiBase()}/backend/api/index.php?action=${encodeURIComponent(action)}`, {
        method: 'POST',
        credentials: 'include',
        body: formData
    }).then(r => r.json());
}

function apiDelete(action, payload) {
    return fetch(`${getApiBase()}/backend/api/index.php?action=${encodeURIComponent(action)}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload ?? {})
    }).then(r => r.json());
}

function toast(msg) {
    alert(msg);
}

function reloadAllAdminLists() {
    location.reload();
}

function bindAdminForms() {
    const userForm = document.getElementById('userForm');
    const vehicleForm = document.getElementById('vehicleForm');
    const routeForm = document.getElementById('routeForm');
    const deliveryPersonForm = document.getElementById('deliveryPersonForm');
    const pricingForm = document.getElementById('pricingForm');

    if (userForm) {
        userForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('userId').value.trim();
            const payload = {
                id: id ? Number(id) : undefined,
                ho_ten: document.getElementById('userName').value.trim(),
                so_dien_thoai: document.getElementById('userPhone').value.trim(),
                vai_tro: document.getElementById('userRole').value,
                trang_thai: Number(document.getElementById('userStatus').value),
                mat_khau: document.getElementById('userPassword').value
            };
            payload.op = id ? 'update' : 'create';
            const res = await apiPost('users', payload);
            if (!res.success) return toast(res.message || 'Lưu người dùng thất bại');
            closeUserModal();
            reloadAllAdminLists();
        }, { once: true });
    }

    if (vehicleForm) {
        vehicleForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('vehicleId').value.trim();
            const payload = {
                id: id ? Number(id) : undefined,
                bien_so: document.getElementById('vehiclePlate').value.trim(),
                trong_tai_kg: Number(document.getElementById('vehicleCapacity').value),
                trang_thai: Number(document.getElementById('vehicleStatus').value)
            };
            payload.op = id ? 'update' : 'create';
            const res = await apiPost('vehicles', payload);
            if (!res.success) return toast(res.message || 'Lưu xe thất bại');
            closeVehicleModal();
            reloadAllAdminLists();
        }, { once: true });
    }

    if (routeForm) {
        routeForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('routeId').value.trim();
            const payload = {
                id: id ? Number(id) : undefined,
                ten_tuyen: document.getElementById('routeName').value.trim(),
                diem_di: document.getElementById('routeFrom').value.trim(),
                diem_den: document.getElementById('routeTo').value.trim(),
                quang_duong_km: Number(document.getElementById('routeDistance').value),
                thoi_gian_du_kien_phut: Number(document.getElementById('routeTime').value || 0),
                trang_thai: Number(document.getElementById('routeStatus').value)
            };
            payload.op = id ? 'update' : 'create';
            const res = await apiPost('routes', payload);
            if (!res.success) return toast(res.message || 'Lưu tuyến thất bại');
            closeRouteModal();
            reloadAllAdminLists();
        }, { once: true });
    }

    if (deliveryPersonForm) {
        deliveryPersonForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('deliveryPersonId').value.trim();
            const branchVal = document.getElementById('deliveryPersonBranchId').value;
            const payload = {
                id: id ? Number(id) : undefined,
                ho_ten: document.getElementById('deliveryPersonName').value.trim(),
                so_dien_thoai: document.getElementById('deliveryPersonPhone').value.trim(),
                chi_nhanh_id: branchVal === '' ? null : Number(branchVal),
                khu_vuc_phu_trach: document.getElementById('deliveryPersonArea').value.trim(),
                trang_thai: Number(document.getElementById('deliveryPersonStatus').value)
            };
            payload.op = id ? 'update' : 'create';
            const res = await apiPost('delivery_persons', payload);
            if (!res.success) return toast(res.message || 'Lưu người giao hàng thất bại');
            closeDeliveryPersonModal();
            reloadAllAdminLists();
        }, { once: true });
    }

    if (pricingForm) {
        pricingForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('pricingId').value.trim();
            const payload = {
                id: id ? Number(id) : undefined,
                tu_kg: Number(document.getElementById('pricingFromKg').value),
                den_kg: Number(document.getElementById('pricingToKg').value),
                phi_co_ban: Number(document.getElementById('pricingBaseFee').value),
                phi_km: document.getElementById('pricingPerKm').value ? Number(document.getElementById('pricingPerKm').value) : null,
                trang_thai: Number(document.getElementById('pricingStatus').value)
            };
            payload.op = id ? 'update' : 'create';
            const res = await apiPost('pricing', payload);
            if (!res.success) return toast(res.message || 'Lưu bảng giá thất bại');
            closePricingModal();
            reloadAllAdminLists();
        }, { once: true });
    }
}

async function deleteUser(id) {
    if (!confirm('Xóa người dùng này?')) return;
    const res = await apiPost('users', { op: 'delete', id: Number(id) });
    if (!res.success) return toast(res.message || 'Xóa thất bại');
    reloadAllAdminLists();
}

async function deleteVehicle(id) {
    if (!confirm('Xóa xe này?')) return;
    const res = await apiPost('vehicles', { op: 'delete', id: Number(id) });
    if (!res.success) return toast(res.message || 'Xóa xe thất bại');
    reloadAllAdminLists();
}

async function deleteRoute(id) {
    if (!confirm('Xóa tuyến này?')) return;
    const res = await apiPost('routes', { op: 'delete', id: Number(id) });
    if (!res.success) return toast(res.message || 'Xóa tuyến thất bại');
    reloadAllAdminLists();
}

async function deleteDeliveryPerson(id) {
    if (!confirm('Xóa người giao hàng này?')) return;
    const res = await apiPost('delivery_persons', { op: 'delete', id: Number(id) });
    if (!res.success) return toast(res.message || 'Xóa thất bại');
    reloadAllAdminLists();
}

async function deletePricing(id) {
    if (!confirm('Xóa bảng giá này?')) return;
    const res = await apiPost('pricing', { op: 'delete', id: Number(id) });
    if (!res.success) return toast(res.message || 'Xóa bảng giá thất bại');
    reloadAllAdminLists();
}

function openUserModal(user = null) {
    document.getElementById('userModal').style.display = 'flex';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = user?.id ?? '';
    document.getElementById('userName').value = user?.ho_ten ?? '';
    document.getElementById('userPhone').value = user?.so_dien_thoai ?? '';
    document.getElementById('userRole').value = user?.vai_tro ?? 'khach_hang';
    document.getElementById('userStatus').value = (user?.trang_thai ?? 1).toString();
    document.getElementById('userPassword').value = '';
}
function closeUserModal() { document.getElementById('userModal').style.display = 'none'; }

function openVehicleModal(vehicle = null) {
    document.getElementById('vehicleModal').style.display = 'flex';
    document.getElementById('vehicleForm').reset();
    document.getElementById('vehicleId').value = vehicle?.id ?? '';
    document.getElementById('vehiclePlate').value = vehicle?.bien_so ?? '';
    document.getElementById('vehicleCapacity').value = vehicle?.trong_tai_kg ?? '';
    document.getElementById('vehicleStatus').value = (vehicle?.trang_thai ?? 1).toString();
}
function closeVehicleModal() { document.getElementById('vehicleModal').style.display = 'none'; }

function openRouteModal(route = null) {
    document.getElementById('routeModal').style.display = 'flex';
    document.getElementById('routeForm').reset();
    document.getElementById('routeId').value = route?.id ?? '';
    document.getElementById('routeName').value = route?.ten_tuyen ?? '';
    document.getElementById('routeFrom').value = route?.diem_di ?? '';
    document.getElementById('routeTo').value = route?.diem_den ?? '';
    document.getElementById('routeDistance').value = route?.quang_duong_km ?? '';
    document.getElementById('routeTime').value = route?.thoi_gian_du_kien_phut ?? 0;
    document.getElementById('routeStatus').value = (route?.trang_thai ?? 1).toString();
}
function closeRouteModal() { document.getElementById('routeModal').style.display = 'none'; }

function openDeliveryPersonModal(p = null) {
    document.getElementById('deliveryPersonModal').style.display = 'flex';
    document.getElementById('deliveryPersonForm').reset();
    document.getElementById('deliveryPersonId').value = p?.id ?? '';
    document.getElementById('deliveryPersonName').value = p?.ho_ten ?? '';
    document.getElementById('deliveryPersonPhone').value = p?.so_dien_thoai ?? '';
    document.getElementById('deliveryPersonBranchId').value = (p?.chi_nhanh_id ?? '') === null ? '' : (p?.chi_nhanh_id ?? '');
    document.getElementById('deliveryPersonArea').value = p?.khu_vuc_phu_trach ?? '';
    document.getElementById('deliveryPersonStatus').value = (p?.trang_thai ?? 1).toString();
}
function closeDeliveryPersonModal() { document.getElementById('deliveryPersonModal').style.display = 'none'; }

function openPricingModal(item = null) {
    document.getElementById('pricingModal').style.display = 'flex';
    document.getElementById('pricingForm').reset();
    document.getElementById('pricingId').value = item?.id ?? '';
    document.getElementById('pricingFromKg').value = item?.tu_kg ?? '';
    document.getElementById('pricingToKg').value = item?.den_kg ?? '';
    document.getElementById('pricingBaseFee').value = item?.phi_co_ban ?? '';
    document.getElementById('pricingPerKm').value = item?.phi_km ?? '';
    document.getElementById('pricingStatus').value = (item?.trang_thai ?? 1).toString();
}
function closePricingModal() { document.getElementById('pricingModal').style.display = 'none'; }

async function openOrderDetailModal(id) {
    const modal = document.getElementById('orderDetailModal');
    if (!modal) return;
    modal.style.display = 'flex';

    // Clear previous details
    document.getElementById('detailOrderCode').textContent = '...';
    document.getElementById('detailSenderName').textContent = '...';
    document.getElementById('detailSenderPhone').textContent = '...';
    document.getElementById('detailSenderAddress').textContent = '...';
    document.getElementById('detailReceiverName').textContent = '...';
    document.getElementById('detailReceiverPhone').textContent = '...';
    document.getElementById('detailReceiverAddress').textContent = '...';
    document.getElementById('detailProductName').textContent = '...';
    document.getElementById('detailWeight').textContent = '...';
    document.getElementById('detailStatusBadge').innerHTML = '...';
    document.getElementById('detailShippingFee').textContent = '...';
    document.getElementById('detailPaymentMethod').textContent = '...';
    document.getElementById('detailCOD').textContent = '...';
    document.getElementById('detailInvoiceStatus').innerHTML = '...';
    document.getElementById('detailShipperName').textContent = 'Đang tải...';
    document.getElementById('detailTimeline').innerHTML = '<p class="muted">Đang tải lịch sử...</p>';

    try {
        const res = await fetch(`${getApiBase()}/backend/api/index.php?action=order_detail&id=${id}`, {
            method: 'GET',
            credentials: 'include'
        }).then(r => r.json());
        if (!res.success || !res.data) {
            toast(res.message || 'Không thể tải chi tiết đơn hàng');
            closeOrderDetailModal();
            return;
        }
        const o = res.data;
        document.getElementById('detailOrderCode').textContent = o.ma_don_hang ?? '';
        document.getElementById('detailSenderName').textContent = o.nguoi_gui ?? '';
        document.getElementById('detailSenderPhone').textContent = o.sdt_gui ?? '';
        document.getElementById('detailSenderAddress').textContent = formatBranchName(o.dia_chi_gui ?? '');
        
        document.getElementById('detailReceiverName').textContent = o.nguoi_nhan ?? '';
        document.getElementById('detailReceiverPhone').textContent = o.sdt_nhan ?? '';
        document.getElementById('detailReceiverAddress').textContent = formatBranchName(o.dia_chi_nhan ?? '');
        
        const productName = Array.isArray(o.hang_hoa) && o.hang_hoa.length > 0 
            ? o.hang_hoa.map(h => h.ten_mat_hang).join(', ') 
            : (o.ten_hang_hoa || 'Hàng hóa');
        document.getElementById('detailProductName').textContent = productName;
        document.getElementById('detailWeight').textContent = o.tong_khoi_luong_kg ?? '0';
        
        document.getElementById('detailStatusBadge').innerHTML = badgeOrder(o.trang_thai_don_hang);
        document.getElementById('detailShippingFee').textContent = vnd.format(Number(o.phi_van_chuyen ?? 0));
        document.getElementById('detailPaymentMethod').textContent = o.payment_method === 'tien_mat' ? 'Tiền mặt' : 'QR Code';
        document.getElementById('detailCOD').textContent = vnd.format(Number(o.tien_thu_ho ?? 0));
        
        const invoicePaid = o.invoice_status === 'da_thanh_toan';
        document.getElementById('detailInvoiceStatus').innerHTML = `<span class="badge ${invoicePaid ? 'badge-success' : 'badge-danger'}">${invoicePaid ? 'Đã thanh toán' : 'Chưa thanh toán'}</span>`;
        
        // Update Shipper name
        const shipperText = o.ten_shipper ? o.ten_shipper : 'Chưa phân công';
        document.getElementById('detailShipperName').textContent = shipperText;

        // Render Timeline
        const timeline = o.timeline || [];
        if (timeline.length === 0) {
            document.getElementById('detailTimeline').innerHTML = '<p class="muted">Chưa có lịch sử trạng thái</p>';
        } else {
            document.getElementById('detailTimeline').innerHTML = timeline.map(t => `
                <div style="margin-bottom: 0.75rem; border-left: 2px solid var(--accent); padding-left: 8px; font-size: 0.85rem;">
                    <div style="font-weight: 600; display: flex; justify-content: space-between;">
                        <span>${badgeOrder(t.status)}</span>
                        <span style="color: var(--text-muted); font-weight: normal; font-size: 0.75rem;">${t.time}</span>
                    </div>
                    ${t.actor ? `<div style="color: var(--text-muted); font-size: 0.78rem;">Thực hiện bởi: <strong>${t.actor}</strong></div>` : ''}
                    ${t.note ? `<div style="font-style: italic; margin-top: 2px; color: #475569;">Ghi chú: ${t.note}</div>` : ''}
                </div>
            `).join('');
        }
    } catch (err) {
        toast('Lỗi kết nối máy chủ khi tải chi tiết đơn hàng');
        closeOrderDetailModal();
    }
}

function closeOrderDetailModal() {
    const modal = document.getElementById('orderDetailModal');
    if (modal) modal.style.display = 'none';
}

window.onclick = function(event) {
    ['userModal', 'vehicleModal', 'routeModal', 'deliveryPersonModal', 'pricingModal', 'orderDetailModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && event.target === modal) modal.style.display = 'none';
    });
}

let chartTrendInstance = null;
let chartProvinceInstance = null;

function buildStatisticsUrl(withInterval = false) {
    const from = document.getElementById('reportFromDate')?.value ?? '';
    const to = document.getElementById('reportToDate')?.value ?? '';
    const interval = document.getElementById('reportInterval')?.value ?? 'day';
    const params = new URLSearchParams();
    if (from) params.set('from', from);
    if (to) params.set('to', to);
    if (withInterval) params.set('interval', interval);
    const q = params.toString();
    return `${getApiBase()}/backend/api/index.php?action=statistics${q ? `&${q}` : ''}`;
}

function renderTimeTable(rows) {
    const tbody = document.getElementById('ordersByTimeBody');
    if (!tbody) return;
    if (!Array.isArray(rows) || rows.length === 0) {
        tbody.innerHTML = emptyRow(4, 'Không có dữ liệu thời gian');
        return;
    }
    tbody.innerHTML = rows.map(r => `
        <tr>
            <td><strong>${r.time_label ?? ''}</strong></td>
            <td>${r.total_orders ?? 0}</td>
            <td>${r.success_orders ?? 0}</td>
            <td style="color:var(--success); font-weight:600;">${vnd.format(Number(r.revenue ?? 0))}</td>
        </tr>`).join('');
}

function renderProvinceTable(rows) {
    const tbody = document.getElementById('ordersByProvinceBody');
    if (!tbody) return;
    if (!Array.isArray(rows) || rows.length === 0) {
        tbody.innerHTML = emptyRow(4, 'Không có dữ liệu tỉnh thành');
        return;
    }
    tbody.innerHTML = rows.map(r => `
        <tr>
            <td><strong>${r.tinh_thanh_gui ?? ''}</strong></td>
            <td>${r.total_orders ?? 0}</td>
            <td>${r.success_orders ?? 0}</td>
            <td style="color:var(--success); font-weight:600;">${vnd.format(Number(r.revenue ?? 0))}</td>
        </tr>`).join('');
}

function renderCharts(timeData, provinceData) {
    // 1. Chart Xu hướng (Line chart)
    const ctxTrend = document.getElementById('chartTrend')?.getContext('2d');
    if (ctxTrend) {
        if (chartTrendInstance) {
            chartTrendInstance.destroy();
        }

        const labels = timeData.map(d => d.time_label);
        const orders = timeData.map(d => Number(d.total_orders ?? 0));
        const revenues = timeData.map(d => Number(d.revenue ?? 0));

        chartTrendInstance = new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Số đơn đã gửi',
                        data: orders,
                        borderColor: '#4a6cf7',
                        backgroundColor: 'rgba(74, 108, 247, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#4a6cf7',
                        yAxisID: 'yOrders',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Doanh thu (VNĐ)',
                        data: revenues,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#22c55e',
                        yAxisID: 'yRevenue',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Be Vietnam Pro', size: 12 } }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        titleFont: { family: 'Be Vietnam Pro' },
                        bodyFont: { family: 'Be Vietnam Pro' }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Be Vietnam Pro' } }
                    },
                    yOrders: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Số lượng đơn',
                            font: { family: 'Be Vietnam Pro', weight: 'bold' }
                        },
                        ticks: {
                            beginAtZero: true,
                            stepSize: 1,
                            font: { family: 'Be Vietnam Pro' }
                        }
                    },
                    yRevenue: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Doanh thu (VNĐ)',
                            font: { family: 'Be Vietnam Pro', weight: 'bold' }
                        },
                        grid: { drawOnChartArea: false },
                        ticks: {
                            beginAtZero: true,
                            font: { family: 'Be Vietnam Pro' },
                            callback: function(value) {
                                return (value / 1000).toLocaleString() + 'k';
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Chart Tỉnh thành (Bar chart)
    const ctxProvince = document.getElementById('chartProvince')?.getContext('2d');
    if (ctxProvince) {
        if (chartProvinceInstance) {
            chartProvinceInstance.destroy();
        }

        const labels = provinceData.map(d => d.tinh_thanh_gui);
        const orders = provinceData.map(d => Number(d.total_orders ?? 0));
        const revenues = provinceData.map(d => Number(d.revenue ?? 0));

        chartProvinceInstance = new Chart(ctxProvince, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Số đơn đã gửi',
                        data: orders,
                        backgroundColor: '#4a6cf7',
                        borderRadius: 5,
                        yAxisID: 'yOrders'
                    },
                    {
                        label: 'Doanh thu (VNĐ)',
                        data: revenues,
                        backgroundColor: '#22c55e',
                        borderRadius: 5,
                        yAxisID: 'yRevenue'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Be Vietnam Pro', size: 12 } }
                    },
                    tooltip: {
                        titleFont: { family: 'Be Vietnam Pro' },
                        bodyFont: { family: 'Be Vietnam Pro' }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Be Vietnam Pro' } }
                    },
                    yOrders: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Số lượng đơn',
                            font: { family: 'Be Vietnam Pro', weight: 'bold' }
                        },
                        ticks: {
                            beginAtZero: true,
                            stepSize: 1,
                            font: { family: 'Be Vietnam Pro' }
                        }
                    },
                    yRevenue: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Doanh thu (VNĐ)',
                            font: { family: 'Be Vietnam Pro', weight: 'bold' }
                        },
                        grid: { drawOnChartArea: false },
                        ticks: {
                            beginAtZero: true,
                            font: { family: 'Be Vietnam Pro' },
                            callback: function(value) {
                                return (value / 1000).toLocaleString() + 'k';
                            }
                        }
                    }
                }
            }
        });
    }
}

function loadStatistics() {
    // 1. Tải tổng quan hệ thống (không lọc thời gian chi tiết / không phân khoảng)
    fetch(buildStatisticsUrl(false), { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const s = data.data;
            const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
            setEl('dashTotalOrders',    s.total_orders    ?? 0);
            setEl('dashTotalShipments', s.total_shipments ?? 0);
            setEl('dashTotalDrivers',   s.total_drivers   ?? 0);
            setEl('dashTotalVehicles',  s.total_vehicles  ?? 0);
            setEl('totalDrivers',  s.total_drivers  ?? 0);
        })
        .catch(err => console.error('Lỗi tải tổng quan:', err));

    // 2. Tải thống kê chi tiết & vẽ biểu đồ (nếu có các element của trang Reports)
    const timeBody = document.getElementById('ordersByTimeBody');
    const provBody = document.getElementById('ordersByProvinceBody');
    if (timeBody && provBody) {
        fetch(buildStatisticsUrl(true), { credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    renderTimeTable([]);
                    renderProvinceTable([]);
                    return;
                }
                const s = data.data;
                const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
                
                // Cập nhật card tóm tắt của bộ lọc
                setEl('totalOrders',   s.summary?.total_orders   ?? 0);
                setEl('successOrders', s.summary?.success_orders ?? 0);
                setEl('totalRevenue',  vnd.format(s.summary?.total_revenue ?? 0));

                // Render bảng dữ liệu
                renderTimeTable(s.by_time ?? []);
                renderProvinceTable(s.by_province ?? []);

                // Vẽ/cập nhật biểu đồ
                renderCharts(s.by_time ?? [], s.by_province ?? []);
            })
            .catch(err => {
                console.error('Lỗi tải thống kê chi tiết:', err);
                renderTimeTable([]);
                renderProvinceTable([]);
            });
    }
}

function resetReportFilter() {
    const fromEl = document.getElementById('reportFromDate');
    const toEl = document.getElementById('reportToDate');
    const intervalEl = document.getElementById('reportInterval');
    if (fromEl) fromEl.value = '';
    if (toEl) toEl.value = '';
    if (intervalEl) intervalEl.value = 'day';
    loadStatistics();
}

// -- Load all data -------------------------------------------------------------

// Load tuần tự để tránh lỗi trên PHP built-in single-threaded server
document.addEventListener('DOMContentLoaded', async function() {

    // 1. Statistics
    loadStatistics();

    // 2. Users
    try {
        const data = await apiGet('users');
        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
            __cacheUsers = data.data;
            applyUserFilterAndSort();
        } else {
            __cacheUsers = [];
            applyUserFilterAndSort();
        }
    } catch(err) {
        const el = document.getElementById('usersList');
        if (el) el.innerHTML = emptyRow(6, 'Lỗi tải dữ liệu người dùng: ' + err.message);
        console.error('Users fetch error:', err);
    }

    // 3. Orders
    try {
        const data = await apiGet('orders');
        const orders = data.data?.orders ?? data.data ?? [];
        __cacheOrders = orders;
        const recent = document.getElementById('dashRecentOrders');

        if (data.success && Array.isArray(orders) && orders.length > 0) {
            applyOrderFilter();

            if (recent) recent.innerHTML = orders.slice(0, 8).map(o => {
                const status = o.trang_thai_don_hang ?? o.trang_thai ?? '';
                const hangHoa = o.ten_hang_hoa ?? '';
                return `
                <tr>
                    <td><code>${o.ma_don ?? ''}</code></td>
                    <td>${hangHoa}</td>
                    <td>${o.khoi_luong_kg ?? ''} kg</td>
                    <td>${vnd.format(Number(o.phi_van_chuyen ?? 0))}</td>
                    <td>${badgeOrder(status)}</td>
                    <td>${(o.ngay_tao ?? '').slice(0, 10)}</td>
                </tr>`;
            }).join('');
        } else {
            const tbody  = document.getElementById('ordersList');
            if (tbody)  tbody.innerHTML  = emptyRow(7, 'Chưa có đơn hàng nào');
            if (recent) recent.innerHTML = emptyRow(6, 'Chưa có đơn hàng nào');
        }
    } catch(err) {
        const el = document.getElementById('ordersList');
        if (el) el.innerHTML = emptyRow(7, 'Lỗi tải dữ liệu đơn hàng');
        const el2 = document.getElementById('dashRecentOrders');
        if (el2) el2.innerHTML = emptyRow(6, 'Lỗi tải dữ liệu');
    }

    // 4. Vehicles
    try {
        const data = await apiGet('vehicles');
        const tbody = document.getElementById('vehiclesList');
        if (tbody) {
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                __cacheVehicles = data.data;
                tbody.innerHTML = data.data.map((v, idx) => `
                    <tr>
                        <td>${idx + 1}</td>
                        <td><strong>${v.bien_so ?? ''}</strong></td>
                        <td>${formatLoaiXe(v.loai_xe)}</td>
                        <td>${v.trong_tai_kg ?? ''}</td>
                        <td>${badgeStatus(v.trang_thai, 'Sẵn sàng', 'Không hoạt động')}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="openVehicleModal(__cacheVehicles.find(x=>x.id==${v.id}))">Sửa</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteVehicle(${v.id})">Xóa</button>
                        </td>
                    </tr>`).join('');
            } else {
                __cacheVehicles = [];
                tbody.innerHTML = emptyRow(6, 'Chưa có dữ liệu phương tiện');
            }
        }
    } catch(err) {
        const el = document.getElementById('vehiclesList');
        if (el) el.innerHTML = emptyRow(6, 'Lỗi tải dữ liệu phương tiện');
    }

    // 5. Routes
    try {
        const data = await apiGet('routes');
        const tbody = document.getElementById('routesList');
        if (tbody) {
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                __cacheRoutes = data.data;
                tbody.innerHTML = data.data.map((r, idx) => `
                    <tr>
                        <td>${idx + 1}</td>
                        <td>${formatBranchName(r.diem_di)} → ${formatBranchName(r.diem_den)}</td>
                        <td>${formatBranchName(r.diem_di)}</td>
                        <td>${formatBranchName(r.diem_den)}</td>
                        <td>${r.quang_duong_km ?? ''}</td>
                        <td>${badgeStatus(1, 'Hoạt động', 'Tạm dừng')}</td>
                        <td style="white-space: nowrap;">
                            <button class="btn btn-primary btn-sm" onclick="openRouteModal(__cacheRoutes.find(x=>x.id==${r.id}))">Sửa</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteRoute(${r.id})">Xóa</button>
                        </td>
                    </tr>`).join('');
            } else {
                __cacheRoutes = [];
                tbody.innerHTML = emptyRow(7, 'Chua co du lieu tuyen duong');
            }
        }
    } catch(err) {
        const el = document.getElementById('routesList');
        if (el) el.innerHTML = emptyRow(7, 'Loi tai du lieu tuyen duong');
    }

    // 6. Delivery persons
    try {
        const data = await apiGet('delivery_persons');
        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
            __cacheDeliveryPersons = data.data;
            renderDeliveryPersonsTable(__cacheDeliveryPersons);
        } else {
            __cacheDeliveryPersons = [];
            renderDeliveryPersonsTable([]);
        }
    } catch(err) {
        const el = document.getElementById('deliveryPersonsList');
        if (el) el.innerHTML = emptyRow(7, 'Lỗi tải dữ liệu người giao hàng');
    }

    // 7. Pricing
    try {
        const data = await apiGet('pricing');
        const tbody = document.getElementById('pricingList');
        if (tbody) {
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                __cachePricing = data.data;
                tbody.innerHTML = data.data.map(p => `
                    <tr>
                        <td>${p.tu_kg ?? ''}</td>
                        <td>${p.den_kg ?? ''}</td>
                        <td>${p.phi_co_ban ? vnd.format(Number(p.phi_co_ban)) : ''}</td>
                        <td>${p.phi_per_km ? vnd.format(Number(p.phi_per_km)) : ''}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="openPricingModal(__cachePricing.find(x=>x.id==${p.id}))">Sửa</button>
                            <button class="btn btn-danger btn-sm" onclick="deletePricing(${p.id})">Xóa</button>
                        </td>
                    </tr>`).join('');
            } else {
                __cachePricing = [];
                tbody.innerHTML = emptyRow(5, 'Chưa có dữ liệu bảng giá');
            }
        }
    } catch(err) {
        const el = document.getElementById('pricingList');
        if (el) el.innerHTML = emptyRow(5, 'Lỗi tải dữ liệu bảng giá');
    }

    // 8. Customers
    try {
        const data = await apiGet('customers');
        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
            __cacheCustomers = data.data;
            renderCustomersTable(__cacheCustomers);
        } else {
            __cacheCustomers = [];
            renderCustomersTable([]);
        }
    } catch(err) {
        const el = document.getElementById('customersList');
        if (el) el.innerHTML = emptyRow(6, 'Lỗi tải dữ liệu khách hàng');
    }

    // Bind forms
    bindAdminForms();
});

// ═══════════════════════════════════════════════════════════════════
// GPS MAP MODULE
// ═══════════════════════════════════════════════════════════════════

let _gpsMap        = null;   // Leaflet map instance
let _gpsMarkers    = [];     // All current markers
let _gpsRefreshInt = null;   // setInterval handle

// Custom icons
function _gpsIcon(emoji, color) {
    return L.divIcon({
        className: '',
        html: `<div class="gps-marker-icon" style="background:${color};">${emoji}</div>`,
        iconSize:   [38, 38],
        iconAnchor: [19, 38],
        popupAnchor:[0, -40],
    });
}

const GPS_ICONS = {
    driver:  () => _gpsIcon('🚚', '#1d4ed8'),
    shipper: () => _gpsIcon('🛵', '#d97706'),
    branch:  () => _gpsIcon('🏢', '#dc2626'),
};

function _initGpsMap() {
    if (_gpsMap) return true;

    if (typeof L === 'undefined') {
        console.warn('Leaflet (L) chưa được nạp.');
        return false;
    }

    const mapContainer = document.getElementById('gpsLeafletMap');
    if (!mapContainer) return false;

    try {
        _gpsMap = L.map('gpsLeafletMap', {
            center: [10.05, 106.10],
            zoom: 10,
            zoomControl: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
        }).addTo(_gpsMap);

        return true;
    } catch (err) {
        console.error('Lỗi khi khởi tạo bản đồ Leaflet:', err);
        return false;
    }
}

function _clearGpsMarkers() {
    if (_gpsMap && Array.isArray(_gpsMarkers)) {
        _gpsMarkers.forEach(m => _gpsMap.removeLayer(m));
    }
    _gpsMarkers = [];
}

function _addMarker(lat, lng, icon, popupHtml) {
    if (!_gpsMap) return null;
    const marker = L.marker([lat, lng], { icon }).addTo(_gpsMap);
    marker.bindPopup(popupHtml, { maxWidth: 260 });
    _gpsMarkers.push(marker);
    return marker;
}

async function loadGpsLocations() {
    if (!_gpsMap) {
        if (!_initGpsMap()) {
            const statusEl = document.getElementById('gpsMapStatus');
            if (statusEl) statusEl.innerHTML = '<span class="gps-pulse error"></span> Lỗi nạp bản đồ';
            return;
        }
    }

    const statusEl = document.getElementById('gpsMapStatus');
    const lastUpEl = document.getElementById('gpsLastUpdate');

    try {
        const res  = await apiFetch(`${getApiBase()}/backend/api/index.php?action=gps_locations`);
        const json = await res.json();

        if (!json.success) {
            if (statusEl) statusEl.innerHTML = '<span class="gps-pulse error"></span> Lỗi tải dữ liệu';
            return;
        }

        _clearGpsMarkers();

        const { branches = [], drivers = [], shippers = [] } = json.data;

        // ─── Chi nhánh ───
        branches.forEach(b => {
            const lat = parseFloat(b.vi_do);
            const lng = parseFloat(b.kinh_do);
            if (isNaN(lat) || isNaN(lng)) return;
            _addMarker(lat, lng, GPS_ICONS.branch(),
                `<div class="gps-popup">
                    <strong>🏢 ${escapeHtml(b.ten_chi_nhanh)}</strong>
                    <p>${escapeHtml(b.dia_chi || '')}</p>
                </div>`
            );
        });

        // ─── Tài xế ───
        const driverListEl = document.getElementById('gpsDriverList');
        const driverCountEl = document.getElementById('gpsDriverCount');
        if (driverCountEl) driverCountEl.textContent = drivers.length;

        if (driverListEl) {
            if (drivers.length === 0) {
                driverListEl.innerHTML = '<p class="gps-empty">Chưa có tài xế đang di chuyển</p>';
            } else {
                driverListEl.innerHTML = drivers.map(d => `
                    <div class="gps-list-item" onclick="focusGpsMarker(${parseFloat(d.vi_do)}, ${parseFloat(d.kinh_do)})">
                        <div class="gps-list-avatar driver-avatar">🚚</div>
                        <div class="gps-list-info">
                            <strong>${escapeHtml(d.ten_nguoi || '---')}</strong>
                            <span>${escapeHtml(d.ma_dot || '')} · ${escapeHtml(d.bien_so || '')}</span>
                            <small>${formatTimeAgo(d.thoi_gian_ghi_nhan)}</small>
                        </div>
                    </div>`).join('');
            }
        }

        drivers.forEach(d => {
            const lat = parseFloat(d.vi_do);
            const lng = parseFloat(d.kinh_do);
            if (isNaN(lat) || isNaN(lng)) return;
            _addMarker(lat, lng, GPS_ICONS.driver(),
                `<div class="gps-popup">
                    <strong>🚚 ${escapeHtml(d.ten_nguoi || '---')}</strong>
                    <p>Chuyến: <b>${escapeHtml(d.ma_dot || '---')}</b></p>
                    <p>Biển số: ${escapeHtml(d.bien_so || '---')}</p>
                    <p class="gps-popup-time">⏱ ${formatTimeAgo(d.thoi_gian_ghi_nhan)}</p>
                </div>`
            );
        });

        // ─── Shipper ───
        const shipperListEl = document.getElementById('gpsShipperList');
        const shipperCountEl = document.getElementById('gpsShipperCount');
        if (shipperCountEl) shipperCountEl.textContent = shippers.length;

        if (shipperListEl) {
            if (shippers.length === 0) {
                shipperListEl.innerHTML = '<p class="gps-empty">Chưa có shipper đang giao hàng</p>';
            } else {
                shipperListEl.innerHTML = shippers.map(s => `
                    <div class="gps-list-item" onclick="focusGpsMarker(${parseFloat(s.vi_do)}, ${parseFloat(s.kinh_do)})">
                        <div class="gps-list-avatar shipper-avatar">🛵</div>
                        <div class="gps-list-info">
                            <strong>${escapeHtml(s.ten_nguoi || '---')}</strong>
                            <small>${formatTimeAgo(s.thoi_gian_ghi_nhan)}</small>
                        </div>
                    </div>`).join('');
            }
        }

        shippers.forEach(s => {
            const lat = parseFloat(s.vi_do);
            const lng = parseFloat(s.kinh_do);
            if (isNaN(lat) || isNaN(lng)) return;
            _addMarker(lat, lng, GPS_ICONS.shipper(),
                `<div class="gps-popup">
                    <strong>🛵 ${escapeHtml(s.ten_nguoi || '---')}</strong>
                    <p>Đang giao hàng tận nơi</p>
                    <p class="gps-popup-time">⏱ ${formatTimeAgo(s.thoi_gian_ghi_nhan)}</p>
                </div>`
            );
        });

        // Fit map to all markers if any
        if (_gpsMarkers.length > 0) {
            const group = L.featureGroup(_gpsMarkers);
            _gpsMap.fitBounds(group.getBounds().pad(0.15));
        }

        if (statusEl) statusEl.innerHTML = '<span class="gps-pulse active"></span> Trực tuyến';
        if (lastUpEl) lastUpEl.textContent = new Date().toLocaleTimeString('vi-VN');

    } catch (err) {
        console.error('GPS load error:', err);
        if (statusEl) statusEl.innerHTML = '<span class="gps-pulse error"></span> Lỗi kết nối';
    }
}

function focusGpsMarker(lat, lng) {
    if (!_gpsMap || isNaN(lat) || isNaN(lng)) return;
    _gpsMap.setView([lat, lng], 15, { animate: true });
}

async function refreshGpsMap() {
    const btn = document.getElementById('btnRefreshGps');
    let originalHtml = '🔄 Làm mới';

    if (btn) {
        btn.disabled = true;
        originalHtml = btn.innerHTML;
        btn.innerHTML = '⏳ Đang tải...';
    }

    try {
        if (!_gpsMap) {
            _initGpsMap();
        }
        if (_gpsMap) {
            _gpsMap.invalidateSize();
        }

        await loadGpsLocations();

        if (_gpsRefreshInt) {
            clearInterval(_gpsRefreshInt);
            _gpsRefreshInt = setInterval(loadGpsLocations, 30000);
        }
    } catch (err) {
        console.error('Lỗi khi làm mới bản đồ GPS:', err);
    } finally {
        if (btn) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    }
}

// Global scope export for inline event handlers
window.refreshGpsMap = refreshGpsMap;
window.focusGpsMarker = focusGpsMarker;
window.loadGpsLocations = loadGpsLocations;

function formatTimeAgo(dateStr) {
    if (!dateStr) return '';
    try {
        const d    = new Date(dateStr);
        const diff = Math.floor((Date.now() - d.getTime()) / 1000);
        if (diff < 60)  return `${diff}s trước`;
        if (diff < 3600) return `${Math.floor(diff/60)}ph trước`;
        return d.toLocaleTimeString('vi-VN');
    } catch { return dateStr; }
}



