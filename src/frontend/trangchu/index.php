<?php
require_once __DIR__ . '/../../backend/config/cauhinh.php';
require_once __DIR__ . '/../../backend/core/helpers.php';

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = login($username, $password);
    if ($role) {
        switch ($role) {
            case 'admin':                 header('Location: ../quantri/index.php');  exit();
            case 'khach_hang':            header('Location: ../khachhang/index.php'); exit();
            case 'nhan_vien_tiep_nhan':   header('Location: ../tiepnhan/index.php');  exit();
            case 'nhan_vien_dieu_phoi':   header('Location: ../dieuphoi/index.php');  exit();
            case 'tai_xe':                header('Location: ../taixe/index.php');     exit();
            case 'shipper':               header('Location: ../giaohang/index.php');  exit();
            default: $loginError = 'Vai trò không hợp lệ!';
        }
    } else {
        $loginError = 'Số điện thoại hoặc mật khẩu không đúng!';
    }
}

// ── Lấy số liệu thực từ CSDL ──────────────────────────────────
$stat_don_hang   = 0;  // tổng đơn hàng
$stat_tai_xe     = 0;  // tổng tài xế + shipper
$stat_khach_hang = 0;  // tổng khách hàng
$stat_chi_nhanh  = 0;  // số chi nhánh

if (isset($conn) && !$conn->connect_error) {
    // Tổng đơn hàng
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM don_hang");
    if ($r) $stat_don_hang = (int)$r->fetch_assoc()['cnt'];

    // Tài xế + shipper (dựa trên vai trò trong nguoi_dung thông qua bảng tai_xe + nguoi_giao_hang)
    $r = $conn->query("SELECT (SELECT COUNT(*) FROM tai_xe) + (SELECT COUNT(*) FROM nguoi_giao_hang) AS cnt");
    if ($r) $stat_tai_xe = (int)$r->fetch_assoc()['cnt'];

    // Khách hàng đã đăng ký (có nguoi_dung_id) hoặc tổng khách hàng
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM khach_hang");
    if ($r) $stat_khach_hang = (int)$r->fetch_assoc()['cnt'];

    // Số chi nhánh
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM chi_nhanh");
    if ($r) $stat_chi_nhanh = (int)$r->fetch_assoc()['cnt'];

    // Tỉ lệ giao thành công (hoan_tat / tổng đơn không bị hủy * 100)
    $stat_ty_le_giao = 0;
    $r = $conn->query("SELECT
        SUM(CASE WHEN trang_thai_don_hang IN ('hoan_tat','da_giao_hang') THEN 1 ELSE 0 END) AS thanh_cong,
        SUM(CASE WHEN trang_thai_don_hang != 'da_huy' THEN 1 ELSE 0 END) AS khong_huy
        FROM don_hang");
    if ($r) {
        $row = $r->fetch_assoc();
        $khong_huy = (int)($row['khong_huy'] ?? 0);
        $thanh_cong = (int)($row['thanh_cong'] ?? 0);
        $stat_ty_le_giao = $khong_huy > 0 ? round($thanh_cong / $khong_huy * 100) : 0;
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
    <link rel="stylesheet" href="/DATN/frontend/assets/css/styles.css?v=20260622b">
</head>
<body>

<!-- ═══ HEADER ═══ -->
<header class="header">
    <div class="nav-container">
        <a href="#" class="logo">
            <div class="logo-icon">🚚</div>
            Vận Tải <span>Xanh</span>
        </a>
        <nav class="nav-links">
            <a href="#">Trang chủ</a>
            <a href="#features">Tính năng</a>
            <a href="#tracking">Tra cứu</a>
            <a href="#contact">Liên hệ</a>
            <a href="#" class="btn-login" onclick="openModal(); return false;">Đăng nhập</a>
        </nav>
    </div>
</header>

<!-- ═══ HERO ═══ -->
<section class="hero">
    <div class="hero-inner">
        <!-- Cột trái: văn bản -->
        <div class="hero-left">
            <div class="hero-badge">
                <span>✦</span> Hệ thống quản lý vận tải
            </div>
            <div class="hero-content">
                <h1>Vận chuyển <span>thông minh</span>,<br>quản lý hiệu quả</h1>
                <p>Giải pháp chuyển đổi số toàn diện cho doanh nghiệp vận tải vừa và nhỏ — theo dõi đơn hàng thời gian thực, điều phối tự động và báo cáo chi tiết.</p>
                <div class="hero-buttons">
                    <a href="#" class="btn-primary" onclick="openModal(); return false;">
                        ▶ Bắt đầu ngay
                    </a>
                    <a href="#tracking" class="btn-outline">
                        🔍 Tra cứu đơn hàng
                    </a>
                </div>
            </div>
        </div>
        <!-- Cột phải: thống kê dạng card 2×2 -->
        <div class="hero-right">
            <div class="hero-stats-grid">
                <div class="hero-stat-card">
                    <div class="hero-stat-num"><?= $stat_don_hang ?></div>
                    <div class="hero-stat-lbl">Tổng đơn hàng</div>
                </div>
                <div class="hero-stat-card">
                    <div class="hero-stat-num"><?= $stat_tai_xe ?></div>
                    <div class="hero-stat-lbl">Tài xế & Shipper</div>
                </div>
                <div class="hero-stat-card">
                    <div class="hero-stat-num"><?= $stat_ty_le_giao ?>%</div>
                    <div class="hero-stat-lbl">Giao đúng hẹn</div>
                </div>
                <div class="hero-stat-card">
                    <div class="hero-stat-num"><?= $stat_chi_nhanh ?></div>
                    <div class="hero-stat-lbl">Chi nhánh</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ QUICK ACTIONS ═══ -->
<div class="quick-actions">
    <div class="quick-actions-inner">
        <a href="#features" class="quick-action-item active">
            <div class="quick-action-icon qa-blue">📦</div>
            Quản lý đơn hàng
        </a>
        <a href="#features" class="quick-action-item">
            <div class="quick-action-icon qa-teal">🗺️</div>
            Điều phối tuyến đường
        </a>
        <a href="#features" class="quick-action-item">
            <div class="quick-action-icon qa-orange">📊</div>
            Thống kê & báo cáo
        </a>
        <a href="#tracking" class="quick-action-item">
            <div class="quick-action-icon qa-purple">🔍</div>
            Tra cứu hành trình
        </a>
    </div>
</div>

<!-- ═══ FEATURES ═══ -->
<section class="features" id="features">
    <div class="section-header">
        <div class="section-label">Tính năng hệ thống</div>
        <h2 class="section-title">Mọi thứ bạn cần trong một nền tảng</h2>
        <p class="section-sub">Từ tiếp nhận đến giao hàng — được số hóa toàn bộ, dễ dùng cho tất cả vai trò.</p>
    </div>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon-wrap fi-blue">📦</div>
            <h3>Quản lý đơn hàng</h3>
            <p>Tạo đơn nhanh chóng, theo dõi trạng thái theo thời gian thực từ khi nhập kho đến khi giao thành công.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon-wrap fi-teal">🗺️</div>
            <h3>Điều phối thông minh</h3>
            <p>Gán đơn hàng vào đợt vận chuyển phù hợp dựa trên tuyến đường, tải trọng xe và lịch trình tài xế.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon-wrap fi-orange">📊</div>
            <h3>Báo cáo thống kê</h3>
            <p>Theo dõi hiệu suất tài xế, số lượng đơn thành công và doanh thu theo ngày / tuần / tháng.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon-wrap fi-purple">🔍</div>
            <h3>Tra cứu dễ dàng</h3>
            <p>Khách hàng tự tra cứu hành trình đơn hàng qua mã QR hoặc mã đơn mà không cần gọi điện.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon-wrap fi-green">💳</div>
            <h3>Thanh toán đa dạng</h3>
            <p>Hỗ trợ thanh toán tiền mặt hoặc quét mã QR ngay khi nhận hàng — nhanh, tiện, không tiền thối.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon-wrap fi-red">🔐</div>
            <h3>Phân quyền bảo mật</h3>
            <p>Phân quyền rõ ràng cho Admin, nhân viên, tài xế và khách hàng — mỗi vai trò đúng chức năng.</p>
        </div>
    </div>
</section>

<!-- ═══ STATS ═══ -->
<section class="stats">
    <div class="stats-inner">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-number"><?= $stat_don_hang ?></div>
            <div class="stat-label">Tổng đơn hàng</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-number"><?= $stat_khach_hang ?></div>
            <div class="stat-label">Khách hàng</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚗</div>
            <div class="stat-number"><?= $stat_tai_xe ?></div>
            <div class="stat-label">Tài xế & Shipper</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏢</div>
            <div class="stat-number"><?= $stat_chi_nhanh ?></div>
            <div class="stat-label">Chi nhánh</div>
        </div>
    </div>
</section>

<!-- ═══ HOW IT WORKS ═══ -->
<section class="how-it-works">
    <div class="section-header">
        <div class="section-label">Quy trình</div>
        <h2 class="section-title">Vận chuyển 4 bước đơn giản</h2>
        <p class="section-sub">Từ khi tiếp nhận đến tay người nhận — minh bạch từng bước.</p>
    </div>
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
            <p>Tài xế nhận hàng và cập nhật trạng thái theo thời gian thực trên lộ trình.</p>
        </div>
        <div class="step">
            <div class="step-number">4</div>
            <h3>Giao nhận</h3>
            <p>Xác nhận giao hàng thành công và thanh toán (tiền mặt / QR Code).</p>
        </div>
    </div>
</section>

<!-- ═══ TRACKING ═══ -->
<section class="tracking" id="tracking">
    <div class="section-header">
        <div class="section-label">Tra cứu đơn hàng</div>
        <h2 class="section-title">Theo dõi hành trình của bạn</h2>
        <p class="section-sub">Nhập mã đơn để xem trạng thái và lịch sử vận chuyển chi tiết.</p>
    </div>
    <div class="tracking-card">
        <div class="tracking-card-header">
            <h3>🔍 Tra cứu đơn hàng</h3>
            <p>Nhập thông tin bên dưới để kiểm tra trạng thái đơn hàng của bạn</p>
        </div>
        <div class="tracking-card-body">
            <div class="tracking-fields">
                <div class="tracking-field-full tracking-input-group">
                    <label for="orderCode">Mã đơn hàng <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="orderCode" placeholder="Ví dụ: DH001">
                </div>
                <div class="tracking-input-group">
                    <label for="orderPhone">Số điện thoại (tùy chọn)</label>
                    <input type="text" id="orderPhone" placeholder="SĐT người gửi hoặc người nhận">
                </div>
                <div class="tracking-input-group">
                    <label for="orderCCCD">CCCD người gửi (tùy chọn)</label>
                    <input type="text" id="orderCCCD" placeholder="Số CCCD / CMND">
                </div>
            </div>
            <button type="button" class="tracking-btn" onclick="trackOrder()">
                🔍 Tra cứu ngay
            </button>

            <div id="trackError" class="track-alert track-alert-error" style="display:none;"></div>

            <div id="trackResult" style="display:none;">
                <div class="track-result">
                    <div id="trackSummary" class="track-summary"></div>
                    <div id="trackTimeline" class="track-timeline"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ FOOTER ═══ -->
<footer class="footer" id="contact">
    <div class="footer-inner">
        <div class="footer-top">
            <div class="footer-brand">
                <a href="#" class="logo">
                    <div class="logo-icon">🚚</div>
                    Vận Tải <span>Xanh</span>
                </a>
                <p>Hệ thống quản lý vận chuyển hàng hóa chuyên nghiệp — giải pháp số hóa cho doanh nghiệp vừa và nhỏ tại Việt Nam.</p>
            </div>
            <div class="footer-col">
                <h4>Tính năng</h4>
                <a href="#features">Quản lý đơn hàng</a>
                <a href="#features">Điều phối tuyến đường</a>
                <a href="#features">Thống kê báo cáo</a>
                <a href="#tracking">Tra cứu đơn hàng</a>
            </div>
            <div class="footer-col">
                <h4>Tài khoản</h4>
                <a href="#" onclick="openModal(); return false;">Đăng nhập</a>
                <a href="dangki.php">Đăng ký</a>
                <a href="quen_mat_khau.php">Quên mật khẩu</a>
            </div>
            <div class="footer-col" id="contact">
                <h4>Liên hệ</h4>
                <p>📞 1900 XXXX</p>
                <p>📧 info@vantaixanh.com</p>
                <p>📍 Trà Vinh, Việt Nam</p>
                <p>🕐 T2–T7: 8:00 – 17:30</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Vận Tải Xanh. All rights reserved.</p>
            <p>Hệ thống quản lý vận chuyển hàng hóa</p>
        </div>
    </div>
</footer>

<!-- ═══ LOGIN MODAL ═══ -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal()" aria-label="Đóng">✕</button>
        <div class="modal-logo">
            <div class="modal-logo-icon">🚚</div>
            <h2>Đăng nhập hệ thống</h2>
            <p class="modal-subtitle">Nhập thông tin tài khoản để tiếp tục</p>
        </div>
        <?php if ($loginError): ?>
        <div class="error-message">
            ⚠️ <?php echo htmlspecialchars($loginError); ?>
        </div>
        <?php endif; ?>
        <form method="POST" action="index.php">
            <div class="form-group">
                <label class="form-label" for="username">Số điện thoại</label>
                <input type="text" id="username" name="username" placeholder="Nhập số điện thoại" pattern="[0-9]{9,15}" required autocomplete="username">
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required autocomplete="current-password">
            </div>
            <button type="submit">Đăng nhập</button>
        </form>
        <div class="modal-footer-links">
            <span><a href="quen_mat_khau.php">Quên mật khẩu?</a></span>
            <span>Chưa có tài khoản? <a href="dangki.php">Đăng ký ngay</a></span>
        </div>
    </div>
</div>

<script src="/DATN/frontend/assets/js/scripts.js?v=3"></script>
<?php if ($loginError): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() { openModal(); });
</script>
<?php endif; ?>

</body>
</html>
