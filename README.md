# 🚚 Vận Tải Xanh - Hệ thống quản lý vận chuyển hàng hóa

Hệ thống quản lý vận chuyển hàng hóa toàn diện cho doanh nghiệp vừa và nhỏ. Cung cấp giải pháp chuyên nghiệp để quản lý đơn hàng, điều phối tài xế, theo dõi lộ trình và báo cáo thống kê.

## 📋 Tính năng chính

### 🔐 Admin Dashboard
- **Quản lý Users**: Tạo, sửa, xóa người dùng từ các vai trò khác nhau
- **Quản lý Xe**: Quản lý phương tiện vận chuyển, trạng thái và trọng tải
- **Quản lý Tuyến**: Quản lý các tuyến đường vận chuyển
- **Quản lý Đơn hàng**: Theo dõi toàn bộ đơn hàng trong hệ thống
- **Bảng giá**: Cấu hình bảng giá vận chuyển linh hoạt
- **Báo cáo thống kê**: Thống kê doanh thu, hiệu suất tài xế, số đơn thành công

### 📬 Nhân viên Tiếp nhận
- Tạo đơn hàng mới nhanh chóng
- Nhập thông tin người gửi, người nhận, hàng hóa
- Quản lý danh sách đơn hàng trong ngày
- Theo dõi trạng thái nhập kho

### 🚚 Nhân viên Điều phối
- Xem danh sách đơn chờ điều phối
- Tạo đợt vận chuyển mới
- Gán đơn hàng cho tài xế và xe
- Theo dõi đợt vận chuyển theo thời gian thực
- Quản lý tuyến đường và tài xế

### 🚗 Tài xế
- Xem danh sách đơn hàng trong ngày
- Theo dõi thông tin đợt vận chuyển
- Cập nhật trạng thái đơn hàng (đang vận chuyển, đã giao, v.v.)
- Cập nhật vị trí theo thời gian thực
- Xem tiến độ giao hàng

### 📦 Khách hàng
- Tra cứu đơn hàng bằng mã QR hoặc mã đơn hàng
- Xem lộ trình vận chuyển chi tiết
- Theo dõi trạng thái đơn hàng thời gian thực
- Quản lý lịch sử đơn hàng

## 🛠️ Cài đặt

### Yêu cầu
- PHP 7.4 trở lên
- MySQL/MariaDB
- XAMPP hoặc máy chủ web tương tự

### Các bước cài đặt

1. **Sao chép file vào thư mục web**
   ```
   F:\xampp\htdocs\DATN
   ```

2. **Tạo database**
   - Mở phpMyAdmin (http://localhost/phpmyadmin)
   - Tạo database mới tên `vanchuyendn`
   - Import file `vanchuyendn.sql`

3. **Cấu hình kết nối database**
   - Mở file `config.php`
   - Cập nhật thông tin kết nối nếu cần:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'vanchuyendn');
     ```

4. **Tạo tài khoản demo**
   - Chạy các lệnh SQL để tạo người dùng mẫu (xem phần dưới)

5. **Truy cập hệ thống**
   - Mở trình duyệt
   - Đi đến: `http://localhost/DATN/`

## 👤 Tài khoản Demo

Sau khi import database, sử dụng các tài khoản demo để kiểm tra:

```sql
-- Admin
INSERT INTO users (ho_ten, email, so_dien_thoai, mat_khau, vai_tro, trang_thai) 
VALUES ('Admin User', 'admin@vantai.com', '0123456789', MD5('admin123'), 'admin', 1);

-- Nhân viên tiếp nhận
INSERT INTO users (ho_ten, email, so_dien_thoai, mat_khau, vai_tro, trang_thai) 
VALUES ('Staff Reception', 'staff@vantai.com', '0987654321', MD5('123456'), 'nhan_vien_tiep_nhan', 1);

-- Nhân viên điều phối
INSERT INTO users (ho_ten, email, so_dien_thoai, mat_khau, vai_tro, trang_thai) 
VALUES ('Dispatch Staff', 'dispatch@vantai.com', '0912345678', MD5('123456'), 'nhan_vien_dieu_phoi', 1);

-- Tài xế
INSERT INTO users (ho_ten, email, so_dien_thoai, mat_khau, vai_tro, trang_thai) 
VALUES ('Driver User', 'driver@vantai.com', '0909876543', MD5('123456'), 'tai_xe', 1);

-- Khách hàng
INSERT INTO users (ho_ten, email, so_dien_thoai, mat_khau, vai_tro, trang_thai) 
VALUES ('Customer User', 'customer@vantai.com', '0988888888', MD5('123456'), 'khach_hang', 1);
```

Thông tin đăng nhập:
- **Email**: admin@vantai.com | **Mật khẩu**: admin123
- **Email**: staff@vantai.com | **Mật khẩu**: 123456
- **Email**: dispatch@vantai.com | **Mật khẩu**: 123456
- **Email**: driver@vantai.com | **Mật khẩu**: 123456
- **Email**: customer@vantai.com | **Mật khẩu**: 123456

## 📁 Cấu trúc thư mục

```
DATN/
├── index.php                    # Trang chủ và đăng nhập
├── config.php                   # Cấu hình database
├── auth.php                     # Xử lý xác thực
├── api.php                      # API endpoints
├── logout.php                   # Đăng xuất
├── dashboard_admin.php          # Dashboard quản trị viên
├── dashboard_nvtiepnhan.php     # Dashboard nhân viên tiếp nhận
├── dashboard_nvdieuphoi.php     # Dashboard nhân viên điều phối
├── dashboard_taixe.php          # Dashboard tài xế
├── dashboard_khachhang.php      # Dashboard khách hàng
├── vanchuyendn.sql              # Dump database
└── README.md                    # Hướng dẫn sử dụng
```

## 🔄 Quy trình vận chuyển

1. **Tiếp nhận** → Nhân viên tiếp nhận tạo đơn hàng mới
2. **Điều phối** → Nhân viên điều phối gán đơn vào đợt vận chuyển
3. **Vận chuyển** → Tài xế nhận đơn và cập nhật trạng thái
4. **Giao nhận** → Xác nhận giao hàng thành công
5. **Hoàn tất** → Thanh toán và lưu hóa đơn

## 🔐 Bảo mật

- **Mã hóa mật khẩu**: Sử dụng MD5 (trong thực tế nên dùng bcrypt)
- **Phân quyền**: Mỗi vai trò có quyền truy cập riêng
- **Session**: Kiểm tra session để đảm bảo chỉ người dùng hợp lệ mới có thể truy cập

## 📱 Tính năng QR Code

- Mỗi đơn hàng có mã QR độc nhất
- Khách hàng có thể quét mã QR để tra cứu đơn hàng
- Tài xế có thể quét QR để xác nhận giao hàng

## 💾 Công nghệ sử dụng

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Server**: Apache (XAMPP)

## 🚀 Tính năng trong tương lai

- [ ] Tích hợp bản đồ Google Maps cho theo dõi vị trí thời gian thực
- [ ] SMS/Email thông báo cho khách hàng
- [ ] Mobile app cho tài xế
- [ ] Thanh toán trực tuyến (VNPay, Momo)
- [ ] Báo cáo nâng cao với biểu đồ
- [ ] AI tối ưu hóa tuyến đường
- [ ] Tích hợp API vận chuyển bên thứ ba

## 📞 Liên hệ & Hỗ trợ

- 📧 Email: info@vantai.com
- 📞 Hotline: 1900 XXXX
- 📍 Địa chỉ: Trà Vinh, Việt Nam

## 📄 Giấy phép

MIT License

---

**Phiên bản**: 1.0.0  
**Cập nhật lần cuối**: May 11, 2026
