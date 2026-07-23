<?php
require_once __DIR__ . '/../../backend/config/cauhinh.php';
require_once __DIR__ . '/../../backend/core/helpers.php';

// Dashboard Khách hàng

requireRole('khach_hang');

$hoTen = $_SESSION['ho_ten'] ?? 'Khách hàng';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Khách Hàng - Vận Tải Xanh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_BASE_URL; ?>/frontend/assets/css/styles.css?v=4">
    <!-- Leaflet.js — bản đồ mã nguồn mở -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <style>
        /* ─── Layout ─── */
        .kh-container { max-width: 1100px; margin: 0 auto; padding: 28px 24px; }
        .kh-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; margin-bottom: 20px;
            background: #fff; border: 1px solid #e5e7eb;
            border-radius: 14px; padding: 18px 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,.05);
        }
        .kh-header-left { display: flex; align-items: center; gap: 14px; }
        .kh-header-avatar {
            width: 52px; height: 52px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: #fff;
        }
        .kh-header h1 { margin: 0; font-size: 20px; font-weight: 700; color: #111827; }
        .kh-header-sub { font-size: 13px; color: #6b7280; margin-top: 2px; }
        .kh-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .kh-btn {
            border: 0; cursor: pointer; padding: 10px 18px; border-radius: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2); color: #fff;
            text-decoration: none; font-size: 14px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px;
            transition: opacity .2s, transform .15s;
        }
        .kh-btn:hover { opacity: .88; transform: translateY(-1px); }
        .kh-btn.secondary {
            background: #f3f4f6; color: #374151;
            border: 1px solid #e5e7eb;
        }
        .kh-btn.secondary:hover { background: #e5e7eb; opacity: 1; }
        /* ─── Tabs ─── */
        .kh-tabs {
            display: flex; gap: 4px; flex-wrap: wrap;
            margin: 0 0 20px;
            background: #f3f4f6; border-radius: 12px; padding: 4px;
        }
        .kh-tab {
            background: transparent; border: none; color: #6b7280;
            padding: 9px 18px; border-radius: 9px; cursor: pointer; user-select: none;
            font-size: 14px; font-weight: 500; transition: all .2s; flex: 1; text-align: center;
        }
        .kh-tab.active { background: #fff; color: #111827; font-weight: 700; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        /* ─── Card & grid ─── */
        .kh-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
        .kh-card-title { margin: 0 0 4px; font-size: 17px; font-weight: 700; color: #111827; }
        .kh-grid { display: grid; grid-template-columns: 1fr; gap: 14px; }
        @media (min-width: 700px) { .kh-grid.cols-2 { grid-template-columns: 1fr 1fr; } }
        .kh-field { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; padding: 8px 0; border-bottom: 1px dashed #e5e7eb; }
        .kh-field:last-child { border-bottom: 0; }
        .kh-field .label { color: #6b7280; font-size: 13px; white-space: nowrap; }
        .kh-field .value { color: #111827; font-weight: 600; text-align: right; }
        /* ─── Table ─── */
        .kh-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .kh-table th { background: #f9fafb; color: #374151; font-weight: 700; padding: 11px 12px; border-bottom: 2px solid #e5e7eb; text-align: left; }
        .kh-table td { padding: 11px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .kh-table tr:hover td { background: #fafafa; }
        /* ─── Badge ─── */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; border: 1px solid #e5e7eb; }
        .badge.gray   { background: #f3f4f6; color: #374151; }
        .badge.green  { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
        .badge.blue   { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
        .badge.orange { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }
        /* ─── Utility ─── */
        .kh-muted   { color: #6b7280; font-size: 13px; line-height: 1.5; }
        .kh-error   { color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; padding: 10px 14px; border-radius: 10px; font-size: 14px; }
        .kh-success { color: #065f46; background: #ecfdf5; border: 1px solid #a7f3d0;  padding: 10px 14px; border-radius: 10px; font-size: 14px; }
        .kh-divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        /* ─── Track box ─── */
        .track-box { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
        .track-box input { flex: 1; min-width: 220px; padding: 12px 14px; border-radius: 10px; border: 1.5px solid #e5e7eb; outline: none; font-size: 14px; transition: border-color .2s; }
        .track-box input:focus { border-color: #667eea; }
        /* ─── Profile layout (2 cột) ─── */
        .kh-profile-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
        @media (max-width: 720px) { .kh-profile-layout { grid-template-columns: 1fr; } }
        .kh-profile-card {
            background: linear-gradient(135deg, #f0f4ff 0%, #faf5ff 100%);
            border: 1px solid #dbeafe; border-radius: 16px; padding: 24px;
        }
        .kh-profile-avatar {
            width: 72px; height: 72px; border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; color: #fff; margin-bottom: 14px;
            box-shadow: 0 4px 14px rgba(102,126,234,.3);
        }
        .kh-profile-name { font-size: 19px; font-weight: 700; color: #111827; }
        .kh-profile-meta { font-size: 13px; color: #6b7280; margin-top: 3px; }
        .kh-profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; }
        @media (max-width: 480px) { .kh-profile-grid { grid-template-columns: 1fr; } }
        .kh-info-item { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 11px 14px; }
        .kh-info-item .lbl { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .kh-info-item .val { font-size: 14px; font-weight: 600; color: #111827; word-break: normal; word-wrap: break-word; overflow-wrap: break-word; }
        .kh-edit-btn {
            margin-top: 16px; background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff; border: none; border-radius: 10px; padding: 10px 20px;
            cursor: pointer; font-size: 14px; font-weight: 600; width: 100%;
            transition: opacity .2s;
        }
        .kh-edit-btn:hover { opacity: .88; }
        /* ─── Form ─── */
        .kh-form-panel { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px 22px; }
        .kh-form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .kh-input { width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color .2s; background: #fff; font-family: inherit; }
        .kh-input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.12); }
        .kh-input:disabled { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }
        .kh-pwd-wrap { position: relative; }
        .kh-pwd-wrap .kh-input { padding-right: 44px; }
        .kh-pwd-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 16px; padding: 0; color: #6b7280; }
        .od-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 1000; align-items: flex-end; justify-content: center; }
        .od-overlay.open { display: flex; }
        @media (min-width: 640px) { .od-overlay { align-items: center; } }
        .od-sheet { background: #fff; width: 100%; max-width: 640px; border-radius: 20px 20px 0 0; max-height: 92vh; overflow-y: auto; animation: slideUp .25s ease; }
        @media (min-width: 640px) { .od-sheet { border-radius: 16px; max-height: 88vh; } }
        @keyframes slideUp { from { transform: translateY(60px); opacity:0; } to { transform: translateY(0); opacity:1; } }
        .od-header { position: sticky; top: 0; background: #fff; z-index: 10; padding: 16px 20px 12px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 12px; }
        .od-close { width: 32px; height: 32px; border-radius: 50%; border: none; background: #f3f4f6; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .od-body { padding: 16px 20px 28px; }
        .od-steps { display: flex; align-items: center; margin: 16px 0 20px; }
        .od-step { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
        .od-step::before { content: ''; position: absolute; top: 14px; left: -50%; right: 50%; height: 2px; background: #e5e7eb; z-index: 0; }
        .od-step:first-child::before { display: none; }
        .od-step-dot { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #e5e7eb; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; z-index: 1; position: relative; }
        .od-step.done .od-step-dot { background: #667eea; border-color: #667eea; color: #fff; }
        .od-step.done::before { background: #667eea; }
        .od-step.active .od-step-dot { background: linear-gradient(135deg, #667eea, #764ba2); border-color: #667eea; color: #fff; box-shadow: 0 0 0 4px rgba(102,126,234,.2); }
        .od-step-label { font-size: 10px; color: #9ca3af; margin-top: 5px; text-align: center; line-height: 1.2; }
        .od-step.done .od-step-label, .od-step.active .od-step-label { color: #667eea; font-weight: 600; }
        .od-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        @media (max-width: 400px) { .od-info { grid-template-columns: 1fr; } }
        .od-info-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; }
        .od-info-box .lbl { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: .4px; }
        .od-info-box .val { font-weight: 700; color: #111827; margin-top: 2px; font-size: 14px; }
        .od-timeline { position: relative; padding-left: 28px; }
        .od-timeline::before { content: ''; position: absolute; left: 8px; top: 8px; bottom: 0; width: 2px; background: #e5e7eb; }
        .od-tl-item { position: relative; margin-bottom: 20px; }
        .od-tl-item:last-child { margin-bottom: 0; }
        .od-tl-dot { position: absolute; left: -24px; top: 4px; width: 14px; height: 14px; border-radius: 50%; border: 2px solid #e5e7eb; background: #e5e7eb; transition: background .2s, border-color .2s; }
        .od-tl-time { font-size: 12px; color: #9ca3af; margin-bottom: 2px; }
        .od-tl-status { font-size: 14px; font-weight: 600; color: #374151; }
        .od-tl-note { font-size: 13px; color: #6b7280; margin-top: 4px; line-height: 1.4; }
        .od-tl-actor { font-size: 11px; color: #9ca3af; margin-top: 3px; }
        /* ─── Map GPS ─── */
        .map-section {
            margin-top: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            background: #f9fafb;
        }
        .map-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }
        .map-header-title { font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 6px; }
        .map-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: rgba(255,255,255,.2); border-radius: 999px;
            padding: 3px 10px; font-size: 12px; font-weight: 600;
        }
        .map-badge .dot { width: 7px; height: 7px; border-radius: 50%; background: #4ade80; animation: blink 1.2s infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
        #orderMap { height: 270px; width: 100%; display: block; }
        .map-info-bar {
            padding: 10px 16px;
            background: #fff;
            border-top: 1px solid #f3f4f6;
            font-size: 13px; color: #374151;
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        }
        .map-no-gps {
            padding: 28px 16px;
            text-align: center;
            background: #fff;
            color: #9ca3af;
            font-size: 14px;
        }
        /* ─── Track map (trang tra cứu) ─── */
        #trackMapSection {
            margin-top: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            background: #f9fafb;
            display: none;
        }
        #trackMap { height: 250px; width: 100%; display: block; }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <a href="<?php echo APP_BASE_URL; ?>/index.php" class="logo">Vận Tải <span>Xanh</span></a>
            <div class="nav-links">
                <a href="#" onclick="document.querySelector('[data-tab=tab-track]').click(); return false;">Tra cứu</a>
                <a href="#" onclick="document.querySelector('[data-tab=tab-orders]').click(); return false;">Đơn của tôi</a>
                <a href="<?php echo APP_BASE_URL; ?>/backend/api/auth/logout.php" class="btn-login">Đăng xuất</a>
            </div>
        </div>
    </header>
    <div class="kh-container">
        <div class="kh-header">
            <div class="kh-header-left">
                <div class="kh-header-avatar">👤</div>
                <div>
                    <h1>Xin chào, <?php echo htmlspecialchars($hoTen); ?></h1>
                    <div class="kh-header-sub">Quản lý đơn hàng và thông tin cá nhân</div>
                </div>
            </div>
            <div class="kh-actions">
                <a class="kh-btn secondary" href="#" onclick="document.querySelector('[data-tab=tab-track]').click(); return false;">🔍 Tra cứu nhanh</a>
            </div>
        </div>
        <div class="kh-tabs" role="tablist">
            <button class="kh-tab active" data-tab="tab-track" type="button">Tra cứu đơn</button>
            <button class="kh-tab" data-tab="tab-orders" type="button">Đơn của tôi</button>
            <button class="kh-tab" data-tab="tab-profile" type="button">Hồ sơ</button>
        </div>
        <!-- TAB: Track -->
        <section id="tab-track" class="kh-card">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:6px;">
                <h2 class="kh-card-title">🔍 Tra cứu đơn hàng</h2>
            </div>
            <div class="kh-muted">Nhập mã đơn để theo dõi trạng thái (không phân biệt đơn của ai).</div>
            <div class="track-box">
                <input type="text" id="khOrderCode" placeholder="Nhập mã đơn hàng (VD: DH202605180001)">
                <button class="kh-btn" id="btnTrack" type="button">Tra cứu</button>
            </div>
            <div id="khTrackResult" style="margin-top:14px; display:none;">
                <div id="khTrackMsg" class="kh-success" style="display:none;"></div>
                <div id="khTrackErr" class="kh-error" style="display:none;"></div>
                <div id="khTrackStatus" style="margin-top:10px;"></div>
                <!-- Bản đồ GPS real-time — chỉ hiện khi đơn đang vận chuyển -->
                <div id="trackMapSection"></div>
            </div>
        </section>

        <!-- TAB: My Orders -->
        <section id="tab-orders" class="kh-card" style="display:none;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
                <h2 class="kh-card-title">📦 Đơn của tôi</h2>
                <button class="kh-btn secondary" id="btnReloadOrders" type="button">↻ Tải lại</button>
            </div>
            <div class="kh-muted" style="margin-bottom:14px;">Danh sách đơn hàng mà bạn là người gửi hoặc người nhận (dựa theo số điện thoại đăng nhập).</div>
            <div id="ordersBox"><div class="kh-muted">Đang tải...</div></div>
        </section>

        <!-- TAB: Profile -->
        <section id="tab-profile" class="kh-card" style="display:none;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px;">
                <h2 class="kh-card-title">👤 Hồ sơ của tôi</h2>
                <button class="kh-btn secondary" id="btnReloadProfile" type="button">↻ Tải lại</button>
            </div>

            <!-- Layout 2 cột: trái = thông tin, phải = form chỉnh sửa + đổi mật khẩu -->
            <div class="kh-profile-layout">
                <!-- Cột trái: thông tin hồ sơ -->
                <div>
                    <div id="profileBox"><div class="kh-muted">Đang tải...</div></div>
                </div>

                <!-- Cột phải: form chỉnh sửa + đổi mật khẩu -->
                <div style="display:flex; flex-direction:column; gap:20px;">
                    <!-- Form chỉnh sửa -->
                    <div id="editProfileSection" class="kh-form-panel" style="display:none;">
                        <h3 style="margin:0 0 14px; font-size:15px; font-weight:700; color:#111827;">✏️ Chỉnh sửa thông tin</h3>
                        <div id="editProfileMsg" style="display:none; margin-bottom:12px;"></div>
                        <form id="editProfileForm" style="display:grid; gap:12px;">
                            <div>
                                <label class="kh-form-label">Họ tên <span style="color:#e11d48;">*</span></label>
                                <input class="kh-input" type="text" id="editHoTen" placeholder="Nhập họ tên" required>
                            </div>
                            <div>
                                <label class="kh-form-label">Số điện thoại</label>
                                <input class="kh-input" type="text" id="editSdt" disabled>
                                <small style="color:#9ca3af; font-size:12px;">Không thể thay đổi</small>
                            </div>
                            <div>
                                <label class="kh-form-label">Số CCCD / CMND</label>
                                <input class="kh-input" type="text" id="editCccd" placeholder="Nhập số CCCD (tùy chọn)" maxlength="12">
                            </div>
                            <div>
                                <label class="kh-form-label">Địa chỉ</label>
                                <input class="kh-input" type="text" id="editDiaChi" placeholder="Nhập địa chỉ (tùy chọn)">
                            </div>
                            <div style="display:flex; gap:10px;">
                                <button class="kh-btn" type="submit" id="btnSaveProfile" style="flex:1;">💾 Lưu thay đổi</button>
                                <button class="kh-btn secondary" type="button" id="btnCancelEdit">Hủy</button>
                            </div>
                        </form>
                    </div>

                    <!-- Form đổi mật khẩu -->
                    <div class="kh-form-panel">
                        <h3 style="margin:0 0 4px; font-size:15px; font-weight:700; color:#111827;">🔒 Đổi mật khẩu</h3>
                        <p class="kh-muted" style="margin:0 0 14px;">Nhập mật khẩu hiện tại để xác nhận danh tính.</p>
                        <div id="changePwdMsg" style="display:none; margin-bottom:12px;"></div>
                        <form id="changePwdForm" style="display:grid; gap:12px;">
                            <div>
                                <label class="kh-form-label">Mật khẩu hiện tại <span style="color:#e11d48;">*</span></label>
                                <div class="kh-pwd-wrap">
                                    <input class="kh-input" type="password" id="pwdCurrent" placeholder="Nhập mật khẩu hiện tại" required>
                                    <button type="button" class="kh-pwd-toggle" onclick="togglePwd('pwdCurrent', this)">👁</button>
                                </div>
                            </div>
                            <div>
                                <label class="kh-form-label">Mật khẩu mới <span style="color:#e11d48;">*</span></label>
                                <div class="kh-pwd-wrap">
                                    <input class="kh-input" type="password" id="pwdNew" placeholder="Ít nhất 6 ký tự" required>
                                    <button type="button" class="kh-pwd-toggle" onclick="togglePwd('pwdNew', this)">👁</button>
                                </div>
                            </div>
                            <div id="pwdStrengthBar" style="display:none;">
                                <div style="height:6px; border-radius:4px; background:#e5e7eb; overflow:hidden;">
                                    <div id="pwdStrengthFill" style="height:100%; width:0; transition:width .3s, background .3s;"></div>
                                </div>
                                <small id="pwdStrengthLabel" style="color:#6b7280; font-size:12px;"></small>
                            </div>
                            <div>
                                <label class="kh-form-label">Xác nhận mật khẩu mới <span style="color:#e11d48;">*</span></label>
                                <div class="kh-pwd-wrap">
                                    <input class="kh-input" type="password" id="pwdConfirm" placeholder="Nhập lại mật khẩu mới" required>
                                    <button type="button" class="kh-pwd-toggle" onclick="togglePwd('pwdConfirm', this)">👁</button>
                                </div>
                            </div>
                            <div>
                                <button class="kh-btn" type="submit" id="btnChangePwd" style="width:100%;">🔑 Đổi mật khẩu</button>
                            </div>
                        </form>
                    </div>
                </div><!-- /cột phải -->
            </div><!-- /kh-profile-layout -->
        </section>
    </div>

    <!-- Modal Chi tiết đơn hàng -->
    <div class="od-overlay" id="odOverlay" onclick="closeOrderDetail(event)">
        <div class="od-sheet" id="odSheet">
            <div class="od-header">
                <button class="od-close" onclick="closeOrderDetailBtn()" aria-label="Đóng">✕</button>
                <div>
                    <div style="font-weight:700; font-size:16px;" id="odTitle">Chi tiết đơn hàng</div>
                    <div id="odSubtitle" style="font-size:12px; color:#9ca3af;"></div>
                </div>
            </div>
            <div class="od-body" id="odBody">
                <div class="kh-muted" style="text-align:center; padding:32px 0;">Đang tải...</div>
            </div>
        </div>
    </div>
    <script>
        const API_BASE = '<?php echo APP_BASE_URL; ?>/backend/api/index.php';

        function formatCurrency(val) {
            const n = Number(val) || 0;
            return n.toLocaleString('vi-VN') + ' ₫';
        }

        const tabs = document.querySelectorAll('.kh-tab');
        const tabSections = ['tab-track', 'tab-orders', 'tab-profile'];
        tabs.forEach(btn => {
            btn.addEventListener('click', () => {
                tabs.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const target = btn.getAttribute('data-tab');
                tabSections.forEach(id => {
                    document.getElementById(id).style.display = (id === target) ? 'block' : 'none';
                });
                if (target === 'tab-orders') loadMyOrders();
                if (target === 'tab-profile') loadMyProfile();
            });
        });

        const STATUS_LABEL = {
            'cho_tiep_nhan'   : 'Chờ tiếp nhận',
            'da_nhap_kho'     : 'Đã nhập kho',
            'dang_van_chuyen' : 'Đang vận chuyển',
            'da_den_kho_dich' : 'Đến kho đích',
            'dang_giao_hang'  : 'Đang giao hàng',
            'hoan_tat'        : 'Giao thành công',
            'da_giao_hang'    : 'Đã giao hàng',
            'da_huy'          : 'Đã hủy',
            'tra_lai'         : 'Trả lại người gửi',
            'cho_khoi_hanh'   : 'Chờ khởi hành',
            'dang_di_chuyen'  : 'Đang di chuyển',
            'da_den_kho_nhan' : 'Đến kho nhận',
            'cho_lay_hang'    : 'Chờ lấy hàng',
            'dang_giao'       : 'Đang đi giao',
        };
        const STATUS_ICON = {
            'cho_tiep_nhan'   : '📝',
            'da_nhap_kho'     : '📦',
            'dang_van_chuyen' : '🚚',
            'da_den_kho_dich' : '🏭',
            'dang_giao_hang'  : '🛵',
            'hoan_tat'        : '✅',
            'da_giao_hang'    : '✅',
            'da_huy'          : '❌',
            'tra_lai'         : '↩️',
            'cho_khoi_hanh'   : '⏳',
            'dang_di_chuyen'  : '🚚',
            'da_den_kho_nhan' : '🏭',
            'cho_lay_hang'    : '📦',
            'dang_giao'       : '🛵',
        };
        const STATUS_COLOR = {
            'cho_tiep_nhan'   : '#6b7280',
            'da_nhap_kho'     : '#2563eb',
            'dang_van_chuyen' : '#7c3aed',
            'da_den_kho_dich' : '#0891b2',
            'dang_giao_hang'  : '#d97706',
            'hoan_tat'        : '#16a34a',
            'da_giao_hang'    : '#16a34a',
            'da_huy'          : '#dc2626',
            'tra_lai'         : '#9a3412',
        };
        const STEPS = ['cho_tiep_nhan','da_nhap_kho','dang_van_chuyen','da_den_kho_dich','dang_giao_hang','hoan_tat'];

        function formatDateTime(str) {
            if (!str) return '—';
            const d = new Date(str.replace(' ', 'T'));
            if (isNaN(d)) return str;
            const pad = n => String(n).padStart(2, '0');
            return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function statusBadge(status) {
            const s = String(status || '');
            let cls = 'gray';
            if (s === 'hoan_tat') cls = 'green';
            else if (s === 'dang_van_chuyen') cls = 'blue';
            else if (s === 'da_giao_hang') cls = 'orange';
            return `<span class="badge ${cls}">${escapeHtml(STATUS_LABEL[s] || s)}</span>`;
        }

        async function trackOrder(code) {
            const resultWrap = document.getElementById('khTrackResult');
            const msgBox = document.getElementById('khTrackMsg');
            const errBox = document.getElementById('khTrackErr');
            const statusBox = document.getElementById('khTrackStatus');
            const mapSection = document.getElementById('trackMapSection');
            resultWrap.style.display = 'block';
            msgBox.style.display = 'none';
            errBox.style.display = 'none';
            statusBox.innerHTML = '';
            if (mapSection) { mapSection.style.display = 'none'; mapSection.innerHTML = ''; }
            // Dọn dẹp track map cũ
            if (_trackMapInterval) { clearInterval(_trackMapInterval); _trackMapInterval = null; }
            if (_trackMap) { _trackMap.remove(); _trackMap = null; _trackMarker = null; }
            try {
                const res = await fetch(`${API_BASE}?action=track&code=${encodeURIComponent(code)}`, { method: 'GET' });
                const data = await res.json();
                if (!data.success) {
                    errBox.textContent = data.message || 'Không tìm thấy đơn hàng';
                    errBox.style.display = 'block';
                    return;
                }
                // API track trả về data.data = { order: {...}, timeline: [...] }
                const payload = data.data || {};
                const o = payload.order || payload;
                const trangThai = o.trang_thai || o.trang_thai_don_hang || '';
                const statusLabel = STATUS_LABEL[trangThai] || trangThai;
                msgBox.innerHTML = `<strong>Mã đơn: ${escapeHtml(o.ma_don || code)}</strong><br>Trạng thái: ${escapeHtml(statusLabel)}`;
                msgBox.style.display = 'block';
                statusBox.innerHTML = `
                    <div style="background: #e5e7eb; border-radius: 10px; margin-top: 10px; overflow:hidden;">
                        <div style="width: ${Number(o.progress || 0)}%; background: linear-gradient(135deg, #667eea, #764ba2); padding: 8px; color: white; text-align: center;">
                            ${escapeHtml(statusLabel)} - ${Number(o.progress || 0)}%
                        </div>
                    </div>
                    <div style="margin-top:10px;" class="kh-grid cols-2">
                        <div class="kh-card" style="border-radius:12px;">
                            <div class="kh-field"><div class="label">Khối lượng</div><div class="value">${escapeHtml(String(o.tong_khoi_luong_kg || o.weight || '—'))} kg</div></div>
                            <div class="kh-field"><div class="label">Phí vận chuyển</div><div class="value">${formatCurrency(o.phi_van_chuyen || o.fee)}</div></div>
                            <div class="kh-field"><div class="label">Đã trả trước</div><div class="value">${formatCurrency(o.tien_tra_truoc || o.prepaid)}</div></div>
                            <div class="kh-field"><div class="label">Còn lại (cần thu)</div><div class="value" style="color:#e11d48;font-weight:bold;">${formatCurrency(Math.max(0,(o.phi_van_chuyen||o.fee||0)-(o.tien_tra_truoc||o.prepaid||0)))}</div></div>
                        </div>
                        <div class="kh-card" style="border-radius:12px;">
                            <div class="kh-field"><div class="label">Người nhận</div><div class="value">${escapeHtml((o.nguoi_nhan||{}).ho_ten || o.receiver || '—')}</div></div>
                            <div class="kh-field"><div class="label">SĐT</div><div class="value">${escapeHtml((o.nguoi_nhan||{}).so_dien_thoai || o.phone || '—')}</div></div>
                            <div class="kh-field"><div class="label">Địa chỉ</div><div class="value">${escapeHtml((o.nguoi_nhan||{}).dia_chi || o.address || '—')}</div></div>
                        </div>
                    </div>`;

                // Hiển thị bản đồ nếu đơn đang vận chuyển
                if (MAP_STATUSES.includes(trangThai)) {
                    loadTrackMap(code);
                }
            } catch (e) {
                errBox.textContent = 'Lỗi khi tra cứu. Vui lòng thử lại.';
                errBox.style.display = 'block';
            }
        }

        document.getElementById('btnTrack').addEventListener('click', () => {
            const code = document.getElementById('khOrderCode').value.trim();
            if (!code) { alert('Vui lòng nhập mã đơn hàng'); return; }
            trackOrder(code);
        });
        document.getElementById('khOrderCode').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') document.getElementById('btnTrack').click();
        });
        function maskSensitive(val) {
            if (!val) return '—';
            val = String(val);
            if (val.length <= 6) return val;
            return val.slice(0, 3) + '***' + val.slice(-3);
        }

        let _profileData = {};

        async function loadMyProfile() {
            const box = document.getElementById('profileBox');
            box.innerHTML = `<div class="kh-muted">Đang tải...</div>`;
            document.getElementById('editProfileSection').style.display = 'none';
            try {
                const res  = await fetch(`${API_BASE}?action=my_profile`, { method: 'GET' });
                const data = await res.json();
                if (!data.success) {
                    box.innerHTML = `<div class="kh-error">${escapeHtml(data.message || 'Không thể tải hồ sơ')}</div>`;
                    return;
                }
                const p = data.data || {};
                _profileData = p;
                const sdt  = escapeHtml(p.so_dien_thoai || '');
                const cccd = escapeHtml(p.so_cccd || '');
                const sdtMasked  = maskSensitive(p.so_dien_thoai);
                const cccdMasked = p.so_cccd ? maskSensitive(p.so_cccd) : '—';
                const trangThai  = p.trang_thai == 1
                    ? '<span style="color:#065f46; background:#ecfdf5; border:1px solid #a7f3d0; padding:3px 10px; border-radius:999px; font-size:12px;">✅ Đang hoạt động</span>'
                    : '<span style="color:#991b1b; background:#fef2f2; border:1px solid #fecaca; padding:3px 10px; border-radius:999px; font-size:12px;">⛔ Bị khóa</span>';
                box.innerHTML = `
                    <div class="kh-profile-card">
                        <div class="kh-profile-avatar">👤</div>
                        <div class="kh-profile-name">${escapeHtml(p.ho_ten || '')}</div>
                        <div class="kh-profile-meta">Khách hàng · Thành viên từ ${escapeHtml(p.created_at || '')}</div>
                        <div class="kh-profile-grid">
                            <div class="kh-info-item">
                                <div class="lbl">Số điện thoại</div>
                                <div class="val">
                                    <span id="sdtDisplay">${sdtMasked}</span>
                                    <button type="button" onclick="toggleReveal('sdtDisplay','${sdt}', this)"
                                        style="margin-left:8px; background:none; border:none; cursor:pointer; color:#667eea; font-size:12px; font-weight:600;">Xem</button>
                                </div>
                            </div>
                            <div class="kh-info-item">
                                <div class="lbl">Số CCCD / CMND</div>
                                <div class="val">
                                    <span id="cccdDisplay">${cccdMasked}</span>
                                    ${p.so_cccd ? `<button type="button" onclick="toggleReveal('cccdDisplay','${cccd}', this)"
                                        style="margin-left:8px; background:none; border:none; cursor:pointer; color:#667eea; font-size:12px; font-weight:600;">Xem</button>` : ''}
                                </div>
                            </div>
                            <div class="kh-info-item"><div class="lbl">Trạng thái</div><div class="val">${trangThai}</div></div>
                            <div class="kh-info-item" style="grid-column: span 2;"><div class="lbl">Địa chỉ</div><div class="val" style="font-size:13px;">${escapeHtml(p.dia_chi || '—')}</div></div>
                        </div>
                        <button class="kh-edit-btn" type="button" id="btnOpenEdit">✏️ Chỉnh sửa hồ sơ</button>
                    </div>`;
                document.getElementById('btnOpenEdit').addEventListener('click', openEditForm);
            } catch (e) {
                box.innerHTML = `<div class="kh-error">Lỗi khi tải hồ sơ.</div>`;
            }
        }

        function toggleReveal(spanId, realVal, btn) {
            const span = document.getElementById(spanId);
            if (btn.textContent === 'Xem') { span.textContent = realVal; btn.textContent = 'Ẩn'; }
            else { span.textContent = maskSensitive(realVal); btn.textContent = 'Xem'; }
        }

        function openEditForm() {
            const p = _profileData;
            document.getElementById('editHoTen').value  = p.ho_ten  || '';
            document.getElementById('editSdt').value    = p.so_dien_thoai || '';
            document.getElementById('editCccd').value   = p.so_cccd || '';
            document.getElementById('editDiaChi').value = p.dia_chi || '';
            document.getElementById('editProfileMsg').style.display = 'none';
            document.getElementById('editProfileSection').style.display = 'block';
        }

        document.getElementById('btnCancelEdit').addEventListener('click', () => {
            document.getElementById('editProfileSection').style.display = 'none';
        });

        document.getElementById('editProfileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSaveProfile');
            const msgBox = document.getElementById('editProfileMsg');
            btn.disabled = true; btn.textContent = 'Đang lưu...'; msgBox.style.display = 'none';
            try {
                const fd = new FormData();
                fd.append('ho_ten',  document.getElementById('editHoTen').value.trim());
                fd.append('so_cccd', document.getElementById('editCccd').value.trim());
                fd.append('dia_chi', document.getElementById('editDiaChi').value.trim());
                const res  = await fetch(`${API_BASE}?action=update_profile`, { method: 'POST', body: fd });
                const data = await res.json();
                msgBox.className = data.success ? 'kh-success' : 'kh-error';
                msgBox.textContent = data.message || (data.success ? 'Thành công' : 'Lỗi');
                msgBox.style.display = 'block';
                if (data.success) {
                    setTimeout(() => {
                        document.getElementById('editProfileSection').style.display = 'none';
                        loadMyProfile();
                        const greet = document.querySelector('.kh-header h1');
                        if (greet) greet.textContent = `Xin chào, ${document.getElementById('editHoTen').value.trim()}`;
                    }, 1200);
                }
            } catch {
                msgBox.className = 'kh-error'; msgBox.textContent = 'Lỗi kết nối. Vui lòng thử lại.'; msgBox.style.display = 'block';
            } finally {
                btn.disabled = false; btn.textContent = '💾 Lưu thay đổi';
            }
        });

        document.getElementById('btnReloadProfile').addEventListener('click', loadMyProfile);
        function togglePwd(inputId, btn) {
            const inp = document.getElementById(inputId);
            inp.type = inp.type === 'password' ? 'text' : 'password';
            btn.textContent = inp.type === 'password' ? '👁' : '🙈';
        }

        document.getElementById('pwdNew').addEventListener('input', function () {
            const bar = document.getElementById('pwdStrengthBar');
            const fill = document.getElementById('pwdStrengthFill');
            const label = document.getElementById('pwdStrengthLabel');
            const val = this.value;
            bar.style.display = val ? 'block' : 'none';
            let strength = 0;
            if (val.length >= 6)  strength++;
            if (val.length >= 10) strength++;
            if (/[A-Z]/.test(val))  strength++;
            if (/[0-9]/.test(val))  strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;
            const levels = [
                { pct: '20%', color: '#ef4444', text: 'Rất yếu' },
                { pct: '40%', color: '#f97316', text: 'Yếu' },
                { pct: '60%', color: '#eab308', text: 'Trung bình' },
                { pct: '80%', color: '#22c55e', text: 'Mạnh' },
                { pct: '100%',color: '#16a34a', text: 'Rất mạnh' },
            ];
            const lv = levels[Math.max(0, strength - 1)];
            fill.style.width = lv.pct; fill.style.background = lv.color;
            label.textContent = `Độ mạnh: ${lv.text}`; label.style.color = lv.color;
        });

        document.getElementById('changePwdForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnChangePwd');
            const msgBox = document.getElementById('changePwdMsg');
            msgBox.style.display = 'none';
            const newPwd = document.getElementById('pwdNew').value;
            const confirmPwd = document.getElementById('pwdConfirm').value;
            if (newPwd !== confirmPwd) {
                msgBox.className = 'kh-error'; msgBox.textContent = 'Xác nhận mật khẩu không khớp'; msgBox.style.display = 'block';
                return;
            }
            btn.disabled = true; btn.textContent = 'Đang xử lý...';
            try {
                const fd = new FormData();
                fd.append('mat_khau_cu',  document.getElementById('pwdCurrent').value);
                fd.append('mat_khau_moi', newPwd);
                fd.append('xac_nhan',     confirmPwd);
                const res  = await fetch(`${API_BASE}?action=change_password`, { method: 'POST', body: fd });
                const data = await res.json();
                msgBox.className = data.success ? 'kh-success' : 'kh-error';
                msgBox.textContent = data.message || (data.success ? 'Đổi mật khẩu thành công' : 'Lỗi');
                msgBox.style.display = 'block';
                if (data.success) {
                    document.getElementById('changePwdForm').reset();
                    document.getElementById('pwdStrengthBar').style.display = 'none';
                }
            } catch {
                msgBox.className = 'kh-error'; msgBox.textContent = 'Lỗi kết nối. Vui lòng thử lại.'; msgBox.style.display = 'block';
            } finally {
                btn.disabled = false; btn.textContent = '🔑 Đổi mật khẩu';
            }
        });

        async function loadMyOrders() {
            const box = document.getElementById('ordersBox');
            box.innerHTML = `<div class="kh-muted">Đang tải...</div>`;
            try {
                const res = await fetch(`${API_BASE}?action=my_orders&limit=50&offset=0`, { method: 'GET' });
                const data = await res.json();
                if (!data.success) {
                    box.innerHTML = `<div class="kh-error">${escapeHtml(data.message || 'Không thể tải danh sách đơn hàng')}</div>`;
                    return;
                }
                const payload = data.data || {};
                const orders = payload.orders || [];
                if (!Array.isArray(orders) || orders.length === 0) {
                    box.innerHTML = `<div class="kh-muted">Chưa có đơn hàng nào.</div>`;
                    return;
                }
                const rows = orders.map(o => `
                    <tr>
                        <td><strong>${escapeHtml(o.ma_don || '')}</strong></td>
                        <td>${escapeHtml(o.ten_hang_hoa || '')}</td>
                        <td>${escapeHtml(o.khoi_luong_kg || '')}</td>
                        <td>${escapeHtml(o.phi_van_chuyen || '')}</td>
                        <td>${statusBadge(o.trang_thai)}</td>
                        <td>${escapeHtml(o.ngay_tao || '')}</td>
                        <td><button class="kh-btn secondary" type="button" onclick="showOrderDetail('${String(o.ma_don || '').replace(/'/g, "\\'")}')">📋 Chi tiết</button></td>
                    </tr>`).join('');
                box.innerHTML = `
                    <div style="overflow:auto;">
                        <table class="kh-table">
                            <thead><tr><th>Mã đơn</th><th>Hàng hóa</th><th>Kg</th><th>Phí</th><th>Trạng thái</th><th>Ngày tạo</th><th></th></tr></thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>`;
            } catch (e) {
                box.innerHTML = `<div class="kh-error">Lỗi khi tải danh sách đơn hàng.</div>`;
            }
        }
        document.getElementById('btnReloadOrders').addEventListener('click', loadMyOrders);

        function closeOrderDetailBtn() {
            document.getElementById('odOverlay').classList.remove('open');
            document.body.style.overflow = '';
            // Dọn dẹp map và interval khi đóng modal
            if (_orderMapInterval) { clearInterval(_orderMapInterval); _orderMapInterval = null; }
            if (_orderMap) { _orderMap.remove(); _orderMap = null; _orderMarker = null; }
        }
        function closeOrderDetail(e) {
            if (e.target === document.getElementById('odOverlay')) closeOrderDetailBtn();
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOrderDetailBtn(); });

        async function showOrderDetail(madon) {
            const overlay = document.getElementById('odOverlay');
            const body    = document.getElementById('odBody');
            const title   = document.getElementById('odTitle');
            const sub     = document.getElementById('odSubtitle');
            title.textContent = madon; sub.textContent = '';
            body.innerHTML = `<div class="kh-muted" style="text-align:center; padding:32px 0;">⏳ Đang tải...</div>`;
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
            try {
                const res  = await fetch(`${API_BASE}?action=track&code=${encodeURIComponent(madon)}`);
                const data = await res.json();
                if (!data.success) {
                    body.innerHTML = `<div class="kh-error">${escapeHtml(data.message || 'Không tìm thấy đơn hàng')}</div>`;
                    return;
                }
                const o  = data.data?.order  || data.data || {};
                const tl = data.data?.timeline || [];
                const trangThai  = o.trang_thai || o.trang_thai_don_hang || '';
                const isCancel   = trangThai === 'da_huy';
                const curStepIdx = STEPS.indexOf(trangThai);
                sub.textContent = `Ngày tạo: ${escapeHtml(o.ngay_tao || '')}`;
                const stepsHtml = STEPS.map((s, i) => {
                    let cls = '';
                    if (!isCancel) { if (i < curStepIdx) cls = 'done'; else if (i === curStepIdx) cls = 'active'; }
                    return `<div class="od-step ${cls}">
                        <div class="od-step-dot">${cls ? (cls==='done' ? '✓' : (STATUS_ICON[s]||'•')) : (i+1)}</div>
                        <div class="od-step-label">${STATUS_LABEL[s] || s}</div>
                    </div>`;
                }).join('');
                const remaining = Math.max(0, (o.phi_van_chuyen||0) - (o.tien_tra_truoc||0));
                // Ưu tiên dùng invoice_status từ hoa_don; fallback về tính toán remaining
                const invoicePaid = o.invoice_status === 'da_thanh_toan' || trangThai === 'hoan_tat';
                const ng = o.nguoi_nhan || {}; const gg = o.nguoi_gui || {};
                const infoHtml = `
                    <div class="od-info">
                        <div class="od-info-box"><div class="lbl">Người gửi</div><div class="val">${escapeHtml(gg.ho_ten || '—')}</div><div style="font-size:12px;color:#6b7280;">${escapeHtml(gg.so_dien_thoai || '')}</div></div>
                        <div class="od-info-box"><div class="lbl">Người nhận</div><div class="val">${escapeHtml(ng.ho_ten || '—')}</div><div style="font-size:12px;color:#6b7280;">${escapeHtml(ng.so_dien_thoai || '')}</div><div style="font-size:12px;color:#6b7280;">${escapeHtml(ng.dia_chi || '')}</div></div>
                        <div class="od-info-box"><div class="lbl">Khối lượng</div><div class="val">${escapeHtml(String(o.tong_khoi_luong_kg || o.khoi_luong_kg || '—'))} kg</div></div>
                        <div class="od-info-box"><div class="lbl">Phí vận chuyển</div><div class="val">${formatCurrency(o.phi_van_chuyen)}</div>${invoicePaid ? '<div style="font-size:12px;color:#22c55e;">✓ Đã thanh toán đủ</div>' : (remaining > 0 ? `<div style="font-size:12px;color:#e11d48;">Còn thu: ${formatCurrency(remaining)}</div>` : '<div style="font-size:12px;color:#22c55e;">✓ Đã thanh toán đủ</div>')}</div>
                        ${o.chi_nhanh_gui ? `<div class="od-info-box"><div class="lbl">Chi nhánh gửi</div><div class="val" style="font-size:13px;">${escapeHtml(o.chi_nhanh_gui)}</div></div>` : ''}
                        ${o.chi_nhanh_nhan ? `<div class="od-info-box"><div class="lbl">Chi nhánh nhận</div><div class="val" style="font-size:13px;">${escapeHtml(o.chi_nhanh_nhan)}</div></div>` : ''}
                    </div>`;
                const tlReversed = [...tl].reverse();
                const tlHtml = tlReversed.length === 0
                    ? `<div class="kh-muted">Chưa có lịch sử cập nhật.</div>`
                    : tlReversed.map((item, i) => {
                        const isCurrent = (i === 0);
                        const isCancel2 = item.status === 'da_huy' || item.status === 'tra_lai';
                        const label = STATUS_LABEL[item.status] || item.status;
                        const icon  = STATUS_ICON[item.status]  || '•';
                        const color = STATUS_COLOR[item.status] || '#667eea';
                        const dotStyle = isCurrent
                            ? `background:${color};border-color:${color};box-shadow:0 0 0 3px ${color}33;`
                            : isCancel2 ? 'background:#dc2626;border-color:#dc2626;'
                            : 'background:#22c55e;border-color:#22c55e;';
                        const statusStyle = isCurrent
                            ? `color:${color};font-weight:700;`
                            : isCancel2 ? 'color:#dc2626;' : 'color:#374151;';
                        return `<div class="od-tl-item ${isCurrent ? 'current' : 'done'}">
                            <div class="od-tl-dot" style="${dotStyle}"></div>
                            <div class="od-tl-time">${escapeHtml(formatDateTime(item.time))}</div>
                            <div class="od-tl-status" style="${statusStyle}">${icon} ${escapeHtml(label)}</div>
                            ${item.note  ? `<div class="od-tl-note">${escapeHtml(item.note)}</div>` : ''}
                            ${item.actor ? `<div class="od-tl-actor">👤 ${escapeHtml(item.actor)}</div>` : ''}
                        </div>`;
                    }).join('');
                body.innerHTML = `
                    <div style="margin-bottom:8px;">
                        <div style="display:flex; justify-content:space-between; font-size:12px; color:#6b7280; margin-bottom:4px;">
                            <span>Tiến độ đơn hàng</span><span>${isCancel ? '❌ Đã hủy' : `${o.progress||0}%`}</span>
                        </div>
                        <div style="background:#e5e7eb; border-radius:999px; height:6px; overflow:hidden;">
                            <div style="width:${isCancel?'100':o.progress||0}%; height:100%; background:${isCancel ? '#ef4444' : 'linear-gradient(90deg,#667eea,#764ba2)'}; transition:width .4s;"></div>
                        </div>
                    </div>
                    <div class="od-steps">${stepsHtml}</div>
                    ${infoHtml}
                    ${MAP_STATUSES.includes(trangThai) ? `<div class="map-section" id="orderMapSection"><div class="map-no-gps">⏳ Đang tải bản đồ...</div></div>` : ''}
                    <div style="margin-top:16px;">
                        <div style="font-weight:700; font-size:14px; margin-bottom:14px; color:#374151;">🕐 Lịch sử vận chuyển</div>
                        <div class="od-timeline">${tlHtml}</div>
                    </div>`;

                // Tải bản đồ nếu đơn đang vận chuyển
                if (MAP_STATUSES.includes(trangThai)) {
                    loadOrderMap(madon);
                    // Auto-refresh mỗi 20 giây
                    if (_orderMapInterval) clearInterval(_orderMapInterval);
                    _orderMapInterval = setInterval(() => updateOrderMap(madon), 20000);
                }

            } catch (err) {
                body.innerHTML = `<div class="kh-error">Lỗi khi tải chi tiết. Vui lòng thử lại.</div>`;
            }
        }
        // ══════════════════════════════════════════════════════
        //  GPS MAP — Hiển thị vị trí tài xế/shipper theo đơn hàng
        // ══════════════════════════════════════════════════════

        let _orderMap = null;       // Leaflet map instance trong modal
        let _orderMarker = null;    // Marker tài xế/shipper trong modal
        let _orderMapInterval = null;

        let _trackMap = null;       // Leaflet map instance trên trang tra cứu
        let _trackMarker = null;
        let _trackMapInterval = null;

        const MAP_STATUSES = ['dang_van_chuyen', 'dang_giao_hang', 'da_den_kho_dich'];

        /** Đảm bảo thư viện Leaflet đã sẵn sàng */
        async function ensureLeaflet() {
            if (typeof L !== 'undefined') return true;
            return new Promise((resolve) => {
                if (!document.querySelector('link[href*="leaflet"]')) {
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
                    document.head.appendChild(link);
                }
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
                script.onload = () => resolve(typeof L !== 'undefined');
                script.onerror = () => {
                    const fallback = document.createElement('script');
                    fallback.src = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js';
                    fallback.onload = () => resolve(typeof L !== 'undefined');
                    fallback.onerror = () => resolve(false);
                    document.head.appendChild(fallback);
                };
                document.head.appendChild(script);
            });
        }

        /** Khởi tạo hoặc cập nhật bản đồ trong modal chi tiết đơn */
        async function loadOrderMap(maDon) {
            const mapSection = document.getElementById('orderMapSection');
            if (!mapSection) return;

            try {
                const res  = await fetch(`${API_BASE}?action=track_location&ma_don=${encodeURIComponent(maDon)}`);
                const data = await res.json();

                if (!data.success || !data.data) {
                    mapSection.innerHTML = `
                        <div class="map-header">
                            <div class="map-header-title">📍 Vị trí vận chuyển</div>
                        </div>
                        <div class="map-no-gps">🔍 Chưa có dữ liệu GPS cho đơn này.<br><small>Tài xế/Shipper chưa bật chia sẻ vị trí hoặc đơn chưa đến giai đoạn vận chuyển.</small></div>`;
                    return;
                }

                const loc = data.data;
                const lat = parseFloat(loc.vi_do);
                const lng = parseFloat(loc.kinh_do);

                if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) {
                    mapSection.innerHTML = `
                        <div class="map-header">
                            <div class="map-header-title">📍 Vị trí vận chuyển</div>
                        </div>
                        <div class="map-no-gps">🔍 Chưa có tọa độ GPS hợp lệ cho đơn này.<br><small>Vui lòng chờ tài xế/shipper cập nhật vị trí.</small></div>`;
                    return;
                }

                const isShipper = loc.loai === 'shipper';
                const label = isShipper
                    ? `🛵 Shipper: ${loc.ten_nguoi || 'Đang giao'}`
                    : `🚚 Tài xế: ${loc.ten_nguoi || ''}${loc.bien_so ? ' | ' + loc.bien_so : ''}`;

                // Header
                mapSection.innerHTML = `
                    <div class="map-header">
                        <div class="map-header-title">${isShipper ? '🛵' : '🚚'} Vị trí ${isShipper ? 'shipper' : 'tài xế'} theo thời gian thực</div>
                        <div class="map-badge"><span class="dot"></span>LIVE</div>
                    </div>
                    <div id="orderMap"></div>
                    <div class="map-info-bar">
                        <span style="font-weight:600;">${label}</span>
                        <span id="mapLastUpdate" style="color:#9ca3af; font-size:12px; margin-left:auto;">Cập nhật: ${loc.thoi_gian_ghi_nhan || '—'}</span>
                    </div>`;

                const isLeafletReady = await ensureLeaflet();
                if (!isLeafletReady || typeof L === 'undefined') {
                    const orderMapEl = document.getElementById('orderMap');
                    if (orderMapEl) orderMapEl.innerHTML = `<div class="map-no-gps">⚠️ Không thể tải thư viện bản đồ. Vui lòng kiểm tra kết nối mạng.</div>`;
                    return;
                }

                // Khởi tạo map Leaflet
                if (_orderMap) { 
                    try { _orderMap.remove(); } catch(err) {} 
                    _orderMap = null; 
                    _orderMarker = null; 
                }

                // Đợi DOM render xong
                await new Promise(r => setTimeout(r, 50));

                _orderMap = L.map('orderMap', { zoomControl: true, attributionControl: false }).setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap',
                    maxZoom: 19
                }).addTo(_orderMap);

                const iconHtml = isShipper
                    ? `<div style="background:linear-gradient(135deg,#667eea,#764ba2);width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);">🛵</div>`
                    : `<div style="background:linear-gradient(135deg,#667eea,#764ba2);width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);">🚚</div>`;

                const customIcon = L.divIcon({ html: iconHtml, className: '', iconSize: [38,38], iconAnchor: [19,19] });
                _orderMarker = L.marker([lat, lng], { icon: customIcon })
                    .addTo(_orderMap)
                    .bindPopup(`<strong>${label}</strong><br><small>Cập nhật: ${loc.thoi_gian_ghi_nhan || '—'}</small>`, { offset: [0, -14] })
                    .openPopup();

                // Thêm attribution nhỏ góc phải
                L.control.attribution({ prefix: '© OpenStreetMap' }).addTo(_orderMap);

                setTimeout(() => {
                    if (_orderMap) _orderMap.invalidateSize();
                }, 150);

            } catch (e) {
                console.error('Lỗi khi tải bản đồ (loadOrderMap):', e);
                const mapSection2 = document.getElementById('orderMapSection');
                if (mapSection2) mapSection2.innerHTML = `<div class="map-no-gps">⚠️ Không thể tải bản đồ. Vui lòng thử lại.</div>`;
            }
        }

        /** Cập nhật lại marker trên modal map (gọi theo interval) */
        async function updateOrderMap(maDon) {
            if (!_orderMap || !_orderMarker) return;
            try {
                const res  = await fetch(`${API_BASE}?action=track_location&ma_don=${encodeURIComponent(maDon)}`);
                const data = await res.json();
                if (!data.success || !data.data) return;
                const loc = data.data;
                const lat = parseFloat(loc.vi_do);
                const lng = parseFloat(loc.kinh_do);
                if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) return;
                _orderMarker.setLatLng([lat, lng]);
                const lastUpEl = document.getElementById('mapLastUpdate');
                if (lastUpEl) lastUpEl.textContent = 'Cập nhật: ' + (loc.thoi_gian_ghi_nhan || '—');
            } catch { /* bỏ qua nếu lỗi */ }
        }

        /** Tải bản đồ trên trang tra cứu (trackOrder) */
        async function loadTrackMap(maDon) {
            const section = document.getElementById('trackMapSection');
            if (!section) return;
            section.style.display = 'block';
            section.innerHTML = `
                <div class="map-header">
                    <div class="map-header-title">📍 Vị trí vận chuyển theo thời gian thực</div>
                    <div class="map-badge"><span class="dot"></span>LIVE</div>
                </div>
                <div id="trackMap"></div>
                <div class="map-info-bar" id="trackMapInfo">Đang tải vị trí...</div>`;

            if (_trackMapInterval) clearInterval(_trackMapInterval);
            if (_trackMap) { 
                try { _trackMap.remove(); } catch(err) {} 
                _trackMap = null; 
                _trackMarker = null; 
            }

            try {
                const res  = await fetch(`${API_BASE}?action=track_location&ma_don=${encodeURIComponent(maDon)}`);
                const data = await res.json();

                if (!data.success || !data.data) {
                    section.innerHTML = `
                        <div class="map-header"><div class="map-header-title">📍 Vị trí vận chuyển</div></div>
                        <div class="map-no-gps">🔍 Chưa có GPS — đơn chưa được vận chuyển hoặc tài xế/shipper chưa bật chia sẻ vị trí.</div>`;
                    return;
                }

                const loc = data.data;
                const lat = parseFloat(loc.vi_do);
                const lng = parseFloat(loc.kinh_do);

                if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) {
                    section.innerHTML = `
                        <div class="map-header"><div class="map-header-title">📍 Vị trí vận chuyển</div></div>
                        <div class="map-no-gps">🔍 Chưa có tọa độ GPS hợp lệ cho đơn này.</div>`;
                    return;
                }

                const isShipper = loc.loai === 'shipper';
                const label = isShipper
                    ? `🛵 Shipper: ${loc.ten_nguoi || 'Đang giao'}`
                    : `🚚 Tài xế: ${loc.ten_nguoi || ''}${loc.bien_so ? ' | ' + loc.bien_so : ''}`;

                const isLeafletReady = await ensureLeaflet();
                if (!isLeafletReady || typeof L === 'undefined') {
                    section.innerHTML = `<div class="map-no-gps">⚠️ Không thể tải thư viện bản đồ. Vui lòng kiểm tra kết nối mạng.</div>`;
                    return;
                }

                await new Promise(r => setTimeout(r, 50));

                _trackMap = L.map('trackMap', { zoomControl: true, attributionControl: false }).setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(_trackMap);

                const iconHtml = isShipper
                    ? `<div style="background:linear-gradient(135deg,#667eea,#764ba2);width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);">🛵</div>`
                    : `<div style="background:linear-gradient(135deg,#667eea,#764ba2);width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);">🚚</div>`;
                const customIcon = L.divIcon({ html: iconHtml, className: '', iconSize: [36,36], iconAnchor: [18,18] });
                _trackMarker = L.marker([lat, lng], { icon: customIcon })
                    .addTo(_trackMap)
                    .bindPopup(`<strong>${label}</strong><br><small>Cập nhật: ${loc.thoi_gian_ghi_nhan || '—'}</small>`, { offset: [0,-14] })
                    .openPopup();

                const infoEl = document.getElementById('trackMapInfo');
                if (infoEl) infoEl.innerHTML = `<span style="font-weight:600;">${label}</span><span style="color:#9ca3af;font-size:12px;margin-left:auto;">Cập nhật: ${loc.thoi_gian_ghi_nhan || '—'}</span>`;

                setTimeout(() => {
                    if (_trackMap) _trackMap.invalidateSize();
                }, 150);

                // Auto-refresh mỗi 20 giây
                _trackMapInterval = setInterval(async () => {
                    try {
                        const r2   = await fetch(`${API_BASE}?action=track_location&ma_don=${encodeURIComponent(maDon)}`);
                        const d2   = await r2.json();
                        if (!d2.success || !d2.data) return;
                        const ll = d2.data;
                        const lat2 = parseFloat(ll.vi_do);
                        const lng2 = parseFloat(ll.kinh_do);
                        if (isNaN(lat2) || isNaN(lng2) || (lat2 === 0 && lng2 === 0)) return;
                        _trackMarker && _trackMarker.setLatLng([lat2, lng2]);
                        const inf = document.getElementById('trackMapInfo');
                        if (inf) inf.innerHTML = `<span style="font-weight:600;">${label}</span><span style="color:#9ca3af;font-size:12px;margin-left:auto;">Cập nhật: ${ll.thoi_gian_ghi_nhan || '—'}</span>`;
                    } catch { /* ignore */ }
                }, 20000);

            } catch (err) {
                console.error('Lỗi khi tải bản đồ (loadTrackMap):', err);
                section.innerHTML = `<div class="map-no-gps">⚠️ Không thể tải bản đồ GPS.</div>`;
            }
        }

    </script>
</body>
</html>
