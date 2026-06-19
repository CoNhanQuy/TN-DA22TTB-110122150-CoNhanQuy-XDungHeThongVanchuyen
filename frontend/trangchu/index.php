<?php
include_once '../../backend/cauhinh.php';
include_once '../../backend/xacthuc_dangnhap.php';

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = login($username, $password);
    if ($role) {
        // Redirect theo vai trò
        switch ($role) {
            case 'admin':
                header('Location: ../quantri/giaodien_quantri.php');
                exit();
            case 'khach_hang':
                header('Location: ../khachhang/giaodien_khachhang.php');
                exit();
            case 'nhan_vien_tiep_nhan':
                header('Location: ../tiepnhan/giaodien_nvtiepnhan.php');
                exit();
            case 'nhan_vien_dieu_phoi':
                header('Location: ../dieuphoi/giaodien_nvdieuphoi.php');
                exit();
            case 'tai_xe':
                header('Location: ../taixe/giaodien_taixe.php');
                exit();
            case 'shipper':
                header('Location: ../giaohang/giaodien_nvgiaohang.php');
                exit();
            default:
                $loginError = 'Vai trò không hợp lệ!';
        }
    } else {
        $loginError = 'Số điện thoại hoặc mật khẩu không đúng!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vận Tải Xanh - Hệ thống quản lý vận chuyển hàng hóa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="nav-container">
        <a href="#" class="logo">Vận Tải <span>Xanh</span></a>
        <div class="nav-links">
            <a href="#">Trang chủ</a>
            <a href="#">Dịch vụ</a>
            <a href="#tracking">Tra cứu</a>
            <a href="#">Liên hệ</a>
            <a href="#" class="btn-login" onclick="openModal()">Đăng nhập</a>
        </div>
    </div>
</header>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Quản lý vận chuyển<br>Thông minh & Hiệu quả</h1>
        <p>Giải pháp chuyển đổi số cho doanh nghiệp vận tải vừa và nhỏ. <br>Theo dõi đơn hàng thời gian thực, quản lý đội xe và tài xế dễ dàng.</p>
        <div class="hero-buttons">
            <a href="#" class="btn-primary" onclick="openModal()">Bắt đầu ngay</a>
            <a href="#tracking" class="btn-outline">Tra cứu đơn hàng</a>
        </div>
    </div>

</section>

<!-- Features -->
<section class="features">
    <h2 class="section-title">Tính năng nổi bật</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📦</div>
            <h3>Quản lý đơn hàng</h3>
            <p>Tạo đơn nhanh chóng, theo dõi trạng thái đơn hàng theo thời gian thực từ khi nhập kho đến khi giao thành công.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🚚</div>
            <h3>Điều phối thông minh</h3>
            <p>Gán đơn hàng vào đợt vận chuyển phù hợp dựa trên tuyến đường, tải trọng xe và lịch trình tài xế.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Báo cáo thống kê</h3>
            <p>Theo dõi hiệu suất tài xế, số lượng đơn hàng thành công và doanh thu theo ngày/tuần/tháng.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔍</div>
            <h3>Tra cứu dễ dàng</h3>
            <p>Khách hàng tự tra cứu hành trình đơn hàng qua mã QR hoặc mã đơn mà không cần gọi điện.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">💳</div>
            <h3>Thanh toán đa dạng</h3>
            <p>Hỗ trợ thanh toán tiền mặt hoặc quét mã QR ngay khi nhận hàng.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔐</div>
            <h3>Phân quyền bảo mật</h3>
            <p>Phân quyền rõ ràng cho Admin, nhân viên, tài xế và khách hàng với các chức năng phù hợp.</p>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="stats">
    <div class="stats-grid">
        <div>
            <div class="stat-number">500+</div>
            <div class="stat-label">Đơn hàng/tháng</div>
        </div>
        <div>
            <div class="stat-number">50+</div>
            <div class="stat-label">Đối tác</div>
        </div>
        <div>
            <div class="stat-number">30+</div>
            <div class="stat-label">Tài xế</div>
        </div>
        <div>
            <div class="stat-number">99%</div>
            <div class="stat-label">Giao hàng đúng hẹn</div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="how-it-works">
    <h2 class="section-title">Quy trình vận chuyển</h2>
    <div class="steps">
        <div class="step">
            <div class="step-number">1</div>
            <h3>Tiếp nhận</h3>
            <p>Khách hàng gửi hàng, nhân viên kiểm tra và nhập thông tin lên hệ thống.</p>
        </div>
        <div class="step">
            <div class="step-number">2</div>
            <h3>Điều phối</h3>
            <p>Gán đơn hàng vào đợt vận chuyển với tài xế và xe phù hợp theo tuyến đường.</p>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <h3>Vận chuyển</h3>
            <p>Tài xế nhận hàng, cập nhật trạng thái theo thời gian thực trên lộ trình.</p>
        </div>
        <div class="step">
            <div class="step-number">4</div>
            <h3>Giao nhận</h3>
            <p>Xác nhận giao hàng thành công và thanh toán (tiền mặt / QR Code).</p>
        </div>
    </div>
</section>

<!-- Track Order -->
<section class="tracking" id="tracking">
    <div class="tracking-box">
        <h3>🔍 Tra cứu đơn hàng</h3>
        <p>Nhập thông tin để theo dõi hành trình vận chuyển</p>

        <div class="tracking-input tracking-input-2">
             <input type="text" id="orderCode" placeholder="Nhập mã đơn hàng (VD: DH001)">
             <input type="text" id="orderPhone" placeholder="SĐT người gửi hoặc người nhận (tùy chọn)">
             <input type="text" id="orderCCCD" placeholder="CCCD người gửi (tùy chọn)">
             <button type="button" onclick="trackOrder()">Tra cứu ngay</button>
         </div>

        <div id="trackError" class="track-alert track-alert-error" style="display: none;"></div>

        <div id="trackResult" style="margin-top: 1rem; display: none;">
            <div class="track-result">
                <div id="trackSummary" class="track-summary"></div>
                <div id="trackTimeline" class="track-timeline"></div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <h4>Vận Tải Xanh</h4>
            <p>Hệ thống quản lý vận chuyển hàng hóa chuyên nghiệp dành cho doanh nghiệp vừa và nhỏ.</p>
        </div>
        <div class="footer-section">
            <h4>Liên hệ</h4>
            <p>📞 1900 XXXX</p>
            <p>📧 info@vantai.com</p>
            <p>📍 Trà Vinh, Việt Nam</p>
        </div>
        <div class="footer-section">
            <h4>Giờ làm việc</h4>
            <p>Thứ 2 - Thứ 7: 8h00 - 17h30</p>
            <p>Chủ nhật: 8h00 - 12h00</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2026 Vận Tải Xanh - Hệ thống quản lý vận chuyển hàng hóa. All rights reserved.</p>
    </div>
</footer>

<!-- Login Modal -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <h2>Đăng nhập hệ thống</h2>
        <?php if ($loginError): ?>
            <div style="background: #ff6b6b; color: white; padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                ⚠️ <?php echo htmlspecialchars($loginError); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="index.php">
            <input type="text" name="username" placeholder="Số điện thoại" pattern="[0-9]{9,15}" required>
            <input type="password" name="password" placeholder="Mật khẩu" required>
            <button type="submit">Đăng nhập</button>
        </form>
        <div style="margin-top: 1.2rem; text-align: center; font-size: 0.875rem;">
            <a href="quen_mat_khau.php" style="color: #9ca3af; text-decoration: none;">Quên mật khẩu?</a>
        </div>
        <div style="margin-top: 1rem; text-align: center; font-size: 0.9rem; color: #6b7280;">
            Chưa có tài khoản? <a href="dangki.php" style="color: #667eea; text-decoration: none; font-weight: 600;">Đăng ký ngay</a>
        </div>
    </div>
</div>

<script src="../scripts.js?v=2"></script>
<?php if ($loginError): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        openModal();
    });
</script>
<?php endif; ?>

</body>
</html>