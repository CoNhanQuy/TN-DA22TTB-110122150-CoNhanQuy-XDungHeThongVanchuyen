# 📡 API Documentation - Vận Tải Xanh

Hệ thống API đầy đủ cho ứng dụng quản lý vận tải.

---

## 🔗 Base URL
```
http://localhost/DATN/api.php
```

---

## 📦 API Endpoints

### 🔍 1. TRACK ORDER (Công khai - không cần đăng nhập)

**Endpoint:**
```
GET /api.php?action=track&code=DH001
```

**Description:** Tra cứu thông tin đơn hàng bằng mã đơn

**Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| action | string | ✓ | `track` |
| code | string | ✓ | Mã đơn hàng (VD: DH001) |

**Response Success:**
```json
{
    "success": true,
    "data": {
        "code": "DH001",
        "product": "Laptop Dell Inspiron",
        "weight": "5.5",
        "fee": "85000",
        "receiver": "Trần Thị B",
        "phone": "0987654321",
        "address": "123 Nguyễn Huệ, Vĩnh Long",
        "status": "Đang vận chuyển",
        "message": "Đơn hàng từ Cửa hàng điện tử A gửi đến Trần Thị B",
        "progress": 65
    },
    "message": "",
    "timestamp": "2026-05-11 15:30:45"
}
```

**Response Error:**
```json
{
    "success": false,
    "data": null,
    "message": "Không tìm thấy đơn hàng",
    "timestamp": "2026-05-11 15:30:45"
}
```

---

### 👥 2. USERS - Quản lý người dùng (Admin only)

#### 2.1 Lấy danh sách users

**Endpoint:**
```
GET /api.php?action=users
```

**Required:** Admin role

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": "1",
            "ho_ten": "Nguyễn Văn Admin",
            "email": "admin@vantai.com",
            "so_dien_thoai": "0123456789",
            "vai_tro": "admin",
            "trang_thai": "1",
            "created_at": "2026-05-11 10:00:00"
        },
        {
            "id": "2",
            "ho_ten": "Trần Thị Tiếp Nhận",
            "email": "staff@vantai.com",
            "so_dien_thoai": "0987654321",
            "vai_tro": "nhan_vien_tiep_nhan",
            "trang_thai": "1",
            "created_at": "2026-05-11 10:05:00"
        }
    ],
    "message": "",
    "timestamp": "2026-05-11 15:30:45"
}
```

#### 2.2 Thêm user mới

**Endpoint:**
```
POST /api.php?action=users
```

**Parameters:**
```json
{
    "ho_ten": "Người Mới",
    "email": "newuser@vantai.com",
    "so_dien_thoai": "0912345678",
    "mat_khau": "password123",
    "vai_tro": "nhan_vien_tiep_nhan"
}
```

**Response Success:**
```json
{
    "success": true,
    "data": {
        "id": "10"
    },
    "message": "Thêm user thành công",
    "timestamp": "2026-05-11 15:30:45"
}
```

---

### 🚗 3. VEHICLES - Quản lý xe

#### 3.1 Lấy danh sách xe

**Endpoint:**
```
GET /api.php?action=vehicles
```

**Required:** Admin role

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": "1",
            "bien_so": "51A-123.45",
            "trong_tai_kg": "5000.00",
            "trang_thai": "1",
            "created_at": "2026-05-11 10:00:00"
        }
    ],
    "message": "",
    "timestamp": "2026-05-11 15:30:45"
}
```

#### 3.2 Thêm xe mới

**Endpoint:**
```
POST /api.php?action=vehicles
```

**Parameters:**
```json
{
    "bien_so": "51A-999.99",
    "trong_tai_kg": 3000
}
```

**Response Success:**
```json
{
    "success": true,
    "data": {
        "id": "5"
    },
    "message": "Thêm xe thành công",
    "timestamp": "2026-05-11 15:30:45"
}
```

---

### 📍 4. ROUTES - Quản lý tuyến đường

#### 4.1 Lấy danh sách tuyến

**Endpoint:**
```
GET /api.php?action=routes
```

**Required:** Admin role

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": "1",
            "ten_tuyen": "Trà Vinh - Vĩnh Long",
            "diem_di": "Trà Vinh",
            "diem_den": "Vĩnh Long",
            "quang_duong_km": "30.50",
            "thoi_gian_du_kien_phut": "60",
            "trang_thai": "1"
        }
    ],
    "message": "",
    "timestamp": "2026-05-11 15:30:45"
}
```

#### 4.2 Thêm tuyến mới

**Endpoint:**
```
POST /api.php?action=routes
```

**Parameters:**
```json
{
    "ten_tuyen": "Trà Vinh - Bạc Liêu",
    "diem_di": "Trà Vinh",
    "diem_den": "Bạc Liêu",
    "quang_duong_km": 60,
    "thoi_gian_du_kien_phut": 120
}
```

**Response Success:**
```json
{
    "success": true,
    "data": {
        "id": "4"
    },
    "message": "Thêm tuyến thành công",
    "timestamp": "2026-05-11 15:30:45"
}
```

---

### 📦 5. ORDERS - Quản lý đơn hàng

#### 5.1 Lấy danh sách đơn hàng

**Endpoint:**
```
GET /api.php?action=orders&limit=100&offset=0
```

**Required:** Admin role

**Parameters:**
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| limit | int | 100 | Số bản ghi trả về |
| offset | int | 0 | Vị trí bắt đầu |

**Response:**
```json
{
    "success": true,
    "data": {
        "orders": [
            {
                "id": "1",
                "ma_don": "DH001",
                "ten_hang_hoa": "Laptop Dell Inspiron",
                "khoi_luong_kg": "5.50",
                "phi_van_chuyen": "85000.00",
                "trang_thai": "dang_van_chuyen",
                "ngay_tao": "2026-05-11 08:30:00"
            }
        ],
        "total": "42",
        "limit": 100,
        "offset": 0
    },
    "message": "",
    "timestamp": "2026-05-11 15:30:45"
}
```

#### 5.2 Tạo đơn hàng mới

**Endpoint:**
```
POST /api.php?action=orders
```

**Required:** Nhân viên tiếp nhận role

**Parameters:**
```json
{
    "ten_hang_hoa": "Điện thoại Samsung",
    "khoi_luong_kg": 0.5,
    "phi_van_chuyen": 50000,
    "nguoi_gui_id": 1,
    "nguoi_nhan_id": 1,
    "phuong_thuc_thanh_toan": "tien_mat"
}
```

**Response Success:**
```json
{
    "success": true,
    "data": {
        "id": "123",
        "ma_don": "DH20260511153045"
    },
    "message": "Tạo đơn hàng thành công",
    "timestamp": "2026-05-11 15:30:45"
}
```

---

### 🚚 6. SHIPMENTS - Quản lý đợt vận chuyển

#### 6.1 Lấy danh sách đợt vận chuyển

**Endpoint:**
```
GET /api.php?action=shipments
```

**Required:** Nhân viên điều phối role

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": "1",
            "ma_dot": "DVC20260511153045",
            "ten_tuyen": "Trà Vinh - Vĩnh Long",
            "so_cccd": "123456789",
            "tai_xe": "Phạm Văn Tài Xế 1",
            "bien_so": "51A-123.45",
            "trang_thai": "dang_chay",
            "ngay_gio_bat_dau": "2026-05-11 08:00:00"
        }
    ],
    "message": "",
    "timestamp": "2026-05-11 15:30:45"
}
```

#### 6.2 Tạo đợt vận chuyển

**Endpoint:**
```
POST /api.php?action=shipments
```

**Required:** Nhân viên điều phối role

**Parameters:**
```json
{
    "tuyen_id": 1,
    "tai_xe_id": 1,
    "xe_id": 1,
    "don_hang_id": 1,
    "ngay_gio_bat_dau": "2026-05-11 08:00:00"
}
```

**Response Success:**
```json
{
    "success": true,
    "data": {
        "id": "5",
        "ma_dot": "DVC20260511153045"
    },
    "message": "Tạo đợt vận chuyển thành công",
    "timestamp": "2026-05-11 15:30:45"
}
```

---

### 🔄 7. ORDER STATUS - Cập nhật trạng thái đơn hàng

**Endpoint:**
```
POST /api.php?action=order_status
```

**Required:** Tài xế role

**Parameters:**
```json
{
    "don_hang_id": 1,
    "trang_thai": "da_giao_hang",
    "ghi_chu": "Giao hàng thành công, khách hàng ký xác nhận"
}
```

**Response Success:**
```json
{
    "success": true,
    "data": {
        "id": "1"
    },
    "message": "Cập nhật trạng thái thành công",
    "timestamp": "2026-05-11 15:30:45"
}
```

---

### 📊 8. STATISTICS - Thống kê

**Endpoint:**
```
GET /api.php?action=statistics
```

**Required:** Admin role

**Response:**
```json
{
    "success": true,
    "data": {
        "total_orders": 150,
        "success_orders": 120,
        "total_revenue": 12500000,
        "total_drivers": 8,
        "total_vehicles": 5,
        "total_shipments": 25
    },
    "message": "",
    "timestamp": "2026-05-11 15:30:45"
}
```

---

## 🔐 Authentication

Tất cả endpoints (trừ `track`) cần yêu cầu đăng nhập thông qua session.

**Các vai trò:**
- `admin` - Quản trị viên
- `nhan_vien_tiep_nhan` - Nhân viên tiếp nhận
- `nhan_vien_dieu_phoi` - Nhân viên điều phối
- `tai_xe` - Tài xế
- `khach_hang` - Khách hàng

---

## ✅ Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 500 | Server Error |

---

## 📝 Order Status Values

```
- cho_tiep_nhan      → Chờ tiếp nhận
- da_nhap_kho        → Đã nhập kho
- dang_van_chuyen    → Đang vận chuyển
- da_giao_hang       → Đã giao hàng
- hoan_tat           → Hoàn tát
```

---

## 🧪 CURL Examples

### Track Order
```bash
curl "http://localhost/DATN/api.php?action=track&code=DH001"
```

### Get Users
```bash
curl -b cookies.txt "http://localhost/DATN/api.php?action=users"
```

### Create Order
```bash
curl -X POST -b cookies.txt \
  -d "ten_hang_hoa=Laptop&khoi_luong_kg=5&phi_van_chuyen=85000&nguoi_gui_id=1&nguoi_nhan_id=1&phuong_thuc_thanh_toan=tien_mat" \
  "http://localhost/DATN/api.php?action=orders"
```

### Update Order Status
```bash
curl -X POST -b cookies.txt \
  -d "don_hang_id=1&trang_thai=da_giao_hang&ghi_chu=Giao thành công" \
  "http://localhost/DATN/api.php?action=order_status"
```

---

## 🔗 JavaScript Fetch Examples

### Track Order
```javascript
fetch('api.php?action=track&code=DH001')
    .then(response => response.json())
    .then(data => console.log(data));
```

### Create Order
```javascript
fetch('api.php?action=orders', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
        ten_hang_hoa: 'Laptop',
        khoi_luong_kg: 5,
        phi_van_chuyen: 85000,
        nguoi_gui_id: 1,
        nguoi_nhan_id: 1,
        phuong_thuc_thanh_toan: 'tien_mat'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Update Status
```javascript
fetch('api.php?action=order_status', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
        don_hang_id: 1,
        trang_thai: 'da_giao_hang',
        ghi_chu: 'Giao thành công'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

---

## 📞 Notes

- Tất cả timestamps ở định dạng: `YYYY-MM-DD HH:mm:ss`
- API trả về JSON
- Session cookies tự động quản lý bởi browser
- Các endpoint yêu cầu authorization sẽ trả về error nếu không đủ quyền

---

**API Version**: 1.0  
**Last Updated**: May 11, 2026
