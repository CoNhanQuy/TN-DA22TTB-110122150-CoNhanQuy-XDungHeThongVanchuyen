# 🚚 Vận Tải Xanh - Hệ Thống Quản Lý Vận Chuyển Hàng Hóa (TMS)

Hệ thống quản lý vận chuyển hàng hóa toàn diện dành cho doanh nghiệp vận tải quy mô vừa và nhỏ. Cung cấp giải pháp số hóa quy trình logistics khép kín chặng đầu (First-mile), chặng giữa (Middle-mile), và chặng cuối (Last-mile delivery) với kiến trúc phân tách rõ ràng giữa Backend RESTful API và Frontend Web-responsive.

---

## 📋 Tính năng cốt lõi theo vai trò tác nhân

### 🔐 1. Quản trị viên (Admin)
- **Quản lý tài khoản**: Thêm mới, cập nhật, khóa hoặc kích hoạt tài khoản nhân sự.
- **Quản lý phương tiện**: Số hóa thông tin đội xe tải (biển số, loại xe, trọng tải tối đa).
- **Quản lý tuyến đường**: Cấu hình mạng lưới liên kho bưu cục, khoảng cách cự ly (km) và thời gian ước tính chạy chặng lớn.
- **Cấu hình bảng giá**: Thiết lập biểu cước tự động dựa trên dải khối lượng và cự ly di chuyển.
- **Báo cáo thống kê**: Dashboard trực quan hiển thị KPI tổng quan, biểu đồ đường xu hướng doanh thu & sản lượng đơn hàng theo thời gian, biểu đồ cột thống kê theo tỉnh thành.

### 📬 2. Nhân viên Tiếp nhận (Quầy gửi)
- **Tiếp nhận hàng hóa**: Nhập thông tin người gửi, người nhận, loại hàng hóa (hệ số phụ thu).
- **Áp phí tự động**: Tự động tính toán tổng phí vận chuyển theo khối lượng và cự ly.
- **QR Code đơn hàng**: Tự động sinh mã QR độc nhất cho mỗi đơn hàng để in nhãn dán định danh lên bưu gửi.
- **Quản lý danh sách tại quầy**: Chỉnh sửa thông tin sai sót hoặc hủy đơn khi chưa gán đợt di chuyển.

### 🚚 3. Nhân viên Điều phối
- **Gom đơn chặng giữa**: Lọc và chọn hàng loạt đơn hàng có chung tuyến đường đích để đóng đợt vận chuyển.
- **Kiểm soát tải trọng**: Thuật toán so khớp tổng trọng lượng hàng gom với giới hạn trọng tải xe tải để đưa ra cảnh báo quá tải trước khi duyệt đợt.
- **Phân công chuyến**: Gán xe tải và chỉ định tài xế phụ trách chạy chặng lớn.
- **Phân phát chặng cuối**: Gán đơn hàng và phân bổ shipper chịu trách nhiệm giao nhận chặng cuối theo địa bàn xã/phường.

### 🚗 4. Tài xế (Đường dài chặng giữa)
- **Theo dõi lịch trình**: Đăng nhập xem danh sách đợt vận chuyển liên kho được gán.
- **Cập nhật hành trình**: Đồng bộ trạng thái đợt chạy thời gian thực (`cho_khoi_hanh` -> `dang_di_chuyen` -> `da_den_kho_nhan`).
- **Báo cáo sự cố**: Gửi thông tin báo cáo sự cố (hỏng xe, thời tiết xấu) kèm vị trí và mức độ trực tiếp về trung tâm quản trị.

### 📦 5. Người giao hàng (Shipper chặng cuối)
- **Danh sách giao phát**: Xem danh sách các bưu kiện chặng cuối cần phát tại địa bàn phụ trách.
- **Cập nhật trạng thái**: Đánh dấu trạng thái giao nhận (`thanh_cong` / `that_bai` kèm lý do).
- **Chụp ảnh hiện trường**: Upload ảnh thực tế tại hiện trường làm bằng chứng giao hàng (POD).
- **Đối soát COD**: Cập nhật trạng thái dòng tiền thu hộ cho hóa đơn kèm theo.

### 👤 6. Khách hàng
- **Đăng ký / Đăng nhập**: Quản lý tài khoản cá nhân, xem hồ sơ, đổi mật khẩu.
- **Tra cứu hành trình**: Tra cứu công khai tiến độ bưu gửi thông qua mã đơn hàng hoặc quét mã QR Code để xem timeline lịch sử trạng thái thời gian thực.
- **Thanh toán giả lập**: Hỗ trợ quét mã QR giả lập thanh toán nhanh cước phí hoặc tiền thu hộ COD.
- **Quên mật khẩu**: Khôi phục mật khẩu bảo mật qua hệ thống xác thực mã số OTP gửi về điện thoại di động.

---

## 💾 Công nghệ sử dụng

- **Frontend**: HTML5, CSS3 (Vanilla CSS, Flexbox/Grid), JavaScript (ES6, Fetch API bất đồng bộ, Zero Page Reload).
- **Backend**: PHP 8.2 (Cấu trúc Hướng đối tượng OOP, API Router tập trung).
- **Database**: MySQL/MariaDB 10.4 (InnoDB Storage Engine, Database Transactions bảo vệ tính nhất quán dữ liệu ACID).
- **Dịch vụ tích hợp**: SMS Gateway Textbee API (gửi tin nhắn OTP).

---

## 📁 Cấu trúc thư mục dự án

```text
DATN/
├── backend/
│   ├── api/
│   │   ├── admin/             # Các API nghiệp vụ quản trị
│   │   ├── auth/              # Các API đăng nhập, logout, OTP
│   │   ├── donhang/           # Các API tạo đơn, tra cứu, cập nhật trạng thái đơn
│   │   ├── taixe/             # Các API điều phối, tài xế, shipper
│   │   ├── thongke/           # Các API kết xuất dữ liệu thống kê, báo cáo
│   │   └── index.php          # Entry Point & Router chính của hệ thống API
│   ├── config/
│   │   ├── cauhinh.php        # Cấu hình kết nối DB mặc định và khởi tạo Session
│   │   ├── database.php       # Khởi tạo kết nối mysqli dùng chung
│   │   └── sms_config.php     # Tích hợp hàm gửi SMS OTP qua Textbee API
│   ├── controllers/           # Lớp điều khiển xử lý logic nghiệp vụ
│   ├── core/
│   │   └── helpers.php        # Các hàm tiện ích dùng chung
│   └── models/                # Lớp tương tác và truy vấn trực tiếp Database
├── frontend/
│   ├── assets/                # CSS, JS, hình ảnh giao diện tĩnh
│   ├── dieuphoi/              # Giao diện & xử lý logic của Điều phối viên
│   ├── giaohang/              # Giao diện & xử lý logic của Shipper
│   ├── includes/              # Sidebar, Header, Footer dùng chung
│   ├── khachhang/             # Giao diện & xử lý logic của Khách hàng
│   ├── quantri/               # Giao diện & xử lý logic của Admin
│   ├── taixe/                 # Giao diện & xử lý logic của Tài xế chặng lớn
│   ├── tiepnhan/              # Giao diện & xử lý logic của Nhân viên Tiếp nhận
│   └── trangchu/              # Trang Landing Page và Đăng nhập hệ thống
├── uploads/                   # Thư mục lưu trữ hình ảnh minh chứng giao hàng (POD)
├── .env                       # Lưu cấu hình môi trường nhạy cảm (API Keys, DB Credentials)
├── vanchuyen_dn.sql           # File dump cơ sở dữ liệu MySQL
└── README.md                  # Tài liệu hướng dẫn sử dụng
```

---

## 🛠️ Hướng dẫn cài đặt

### 1. Yêu cầu hệ thống
- Máy chủ giả lập **XAMPP** (hỗ trợ **PHP 8.0 trở lên**, khuyến nghị **PHP 8.2**).
- Cơ sở dữ liệu **MySQL / MariaDB**.

### 2. Các bước cài đặt
1. **Sao chép mã nguồn**:
   Đưa thư mục dự án `DATN` vào thư mục gốc của máy chủ web:
   ```text
   C:\xampp\htdocs\DATN
   ```
2. **Cấu hình biến môi trường**:
   Tạo file `.env` ở thư mục gốc dự án (nếu chưa có) và khai báo các cấu hình kết nối DB cùng thông tin tài khoản Textbee:
   ```env
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=
   DB_NAME=vanchuyen_dn
   
   TEXTBEE_API_KEY=your_textbee_api_key_here
   TEXTBEE_DEVICE_ID=your_textbee_device_id_here
   ```
3. **Import Cơ sở dữ liệu**:
   - Truy cập trang quản trị cơ sở dữ liệu `http://localhost/phpmyadmin/`.
   - Tạo mới một cơ sở dữ liệu tên: `vanchuyen_dn` (nhớ chọn kiểu mã hóa `utf8mb4_general_ci`).
   - Chọn database `vanchuyen_dn`, chuyển sang tab **Import**, tải lên file `vanchuyen_dn.sql` ở thư mục gốc của dự án và chọn **Go** để thực thi import dữ liệu.

4. **Truy cập hệ thống**:
   Mở trình duyệt và truy cập vào đường dẫn:
   `http://localhost/DATN/frontend/trangchu/index.php`

---

## 👤 Danh sách Tài khoản Demo

Hệ thống đăng nhập bằng **Số điện thoại** và mật khẩu chung mặc định cho tất cả tài khoản là **`123456`**:

| Vai trò | Số điện thoại đăng nhập | Mật khẩu mặc định | Họ tên demo |
|:---|:---:|:---:|:---|
| **Quản trị viên (Admin)** | `0900000001` | `123456` | Nguyễn Admin |
| **Nhân viên Tiếp nhận** | `0900000002` | `123456` | Lê Thị Tiếp |
| **Nhân viên Điều phối** | `0900000003` | `123456` | Trần Văn Điều |
| **Tài xế chặng lớn** | `0909123456` | `123456` | Nguyễn Văn Tài |
| **Shipper chặng cuối** | `0901112221` | `123456` | Phạm Văn Giao |
| **Khách hàng** | `0911222333` | `123456` | Nguyễn Văn Gửi |

---

## 🚀 Định hướng phát triển trong tương lai
- **Tích hợp bản đồ số**: Nhúng thư viện Mapbox/Google Maps API để cập nhật trực quan định vị GPS và vẽ lộ trình di chuyển thời gian thực.
- **Tối ưu hóa hành trình (AI Routing)**: Áp dụng thuật toán thông minh để tự động phân phối đơn hàng và gợi ý đường đi ngắn nhất, tiết kiệm nhiên liệu.
- **Nâng cấp bảo mật**: Triển khai hệ thống xác thực Token hai lớp (JWT/OAuth2) và mã hóa bất đối xứng cho dữ liệu nhạy cảm (CCCD, SĐT).
- **Phát triển Hybrid App**: Đóng gói phân hệ Tài xế và Shipper sang ứng dụng di động native bằng React Native hoặc Flutter.
- **Mở rộng cổng thanh toán**: Tích hợp ZaloPay Production API chính thức (thay cho Sandbox).
