<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu - Vận Tải Xanh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Be Vietnam Pro', 'Segoe UI', sans-serif;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 440px;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo-area .icon { font-size: 2.8rem; }

        .logo-area h2 {
            font-size: 22px;
            color: #1f2937;
            margin: 10px 0 6px;
        }

        .logo-area p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Steps indicator */
        .steps-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 28px;
            gap: 0;
        }

        .step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #9ca3af;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .3s;
        }

        .step-dot.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .step-dot.done {
            background: #10b981;
            color: white;
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: #e5e7eb;
            max-width: 60px;
            transition: background .3s;
        }

        .step-line.done { background: #10b981; }

        /* Form */
        .step-panel { display: none; }
        .step-panel.active { display: block; }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            margin-bottom: 7px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color .2s;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,.15);
        }

        /* OTP input */
        #otpInput {
            text-align: center;
            letter-spacing: 10px;
            font-size: 26px;
            font-weight: 700;
            color: #1f2937;
        }

        .btn-primary {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity .2s, transform .2s;
            margin-top: 4px;
        }

        .btn-primary:hover { opacity: .92; transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert-error   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-info    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

        .hint {
            font-size: 13px;
            color: #6b7280;
            margin-top: 6px;
        }

        .resend-row {
            text-align: center;
            margin-top: 14px;
            font-size: 13px;
            color: #6b7280;
        }

        #resendBtn {
            background: none;
            border: none;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            padding: 0;
        }

        #resendBtn:disabled { color: #9ca3af; cursor: not-allowed; }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6b7280;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .success-icon {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
<div class="container">

    <div class="logo-area">
        <div class="icon">🔐</div>
        <h2>Khôi phục mật khẩu</h2>
        <p>Nhập số điện thoại để nhận mã OTP xác thực</p>
    </div>

    <!-- Steps -->
    <div class="steps-bar">
        <div class="step-dot active" id="dot1">1</div>
        <div class="step-line" id="line1"></div>
        <div class="step-dot" id="dot2">2</div>
        <div class="step-line" id="line2"></div>
        <div class="step-dot" id="dot3">3</div>
    </div>

    <!-- Alert chung -->
    <div id="alertBox" style="display:none;"></div>

    <!-- BƯỚC 1: Nhập SĐT -->
    <div class="step-panel active" id="panel1">
        <div class="form-group">
            <label for="phoneInput">Số điện thoại đăng ký</label>
            <input type="tel" id="phoneInput" placeholder="VD: 0909123456" maxlength="15" inputmode="numeric">
        </div>
        <button class="btn-primary" id="btnRequestOtp">Gửi mã OTP</button>
        <div class="back-link">
            <a href="../../index.php">← Quay lại đăng nhập</a>
        </div>
    </div>

    <!-- BƯỚC 2: Nhập OTP -->
    <div class="step-panel" id="panel2">
        <div id="otpInfo" class="alert alert-info" style="margin-bottom:18px;">
            Mã OTP đã gửi đến số <strong id="sdtMasked"></strong>. Có hiệu lực trong <strong id="countdown">5:00</strong>.
        </div>
        <div class="form-group">
            <label for="otpInput">Mã OTP (6 chữ số)</label>
            <input type="text" id="otpInput" placeholder="000000" maxlength="6" inputmode="numeric" autocomplete="one-time-code">
        </div>
        <button class="btn-primary" id="btnVerifyOtp">Xác nhận OTP</button>
        <div class="resend-row">
            Chưa nhận được? <button id="resendBtn" disabled>Gửi lại (<span id="resendTimer">60</span>s)</button>
        </div>
        <div class="back-link" style="margin-top:12px;">
            <a href="#" onclick="goToStep(1); return false;">← Sửa số điện thoại</a>
        </div>
    </div>

    <!-- BƯỚC 3: Nhập mật khẩu mới -->
    <div class="step-panel" id="panel3">
        <div class="form-group">
            <label for="newPass">Mật khẩu mới</label>
            <input type="password" id="newPass" placeholder="Ít nhất 6 ký tự">
        </div>
        <div class="form-group">
            <label for="confPass">Xác nhận mật khẩu</label>
            <input type="password" id="confPass" placeholder="Nhập lại mật khẩu mới">
        </div>
        <button class="btn-primary" id="btnResetPass">Đổi mật khẩu</button>
    </div>

    <!-- BƯỚC 4: Thành công -->
    <div class="step-panel" id="panel4">
        <div class="success-icon">✅</div>
        <div class="alert alert-success" style="text-align:center;">
            <strong>Đổi mật khẩu thành công!</strong><br>
            Bạn có thể đăng nhập với mật khẩu mới.
        </div>
        <a href="../../index.php" class="btn-primary" style="display:block; text-align:center; text-decoration:none; line-height:1.5;">
            Đăng nhập ngay
        </a>
    </div>

</div>

<script>
const API = '../../backend/api/index.php';
let currentPhone = '';
let countdownTimer = null;
let resendTimer   = null;

function showAlert(type, msg) {
    const box = document.getElementById('alertBox');
    box.className = `alert alert-${type}`;
    box.innerHTML = msg;
    box.style.display = 'block';
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearAlert() {
    const box = document.getElementById('alertBox');
    box.style.display = 'none';
    box.innerHTML = '';
}

function goToStep(step) {
    clearAlert();
    [1,2,3,4].forEach(i => {
        document.getElementById(`panel${i}`).classList.toggle('active', i === step);
    });
    // Update dots
    [1,2,3].forEach(i => {
        const dot  = document.getElementById(`dot${i}`);
        const line = i < 3 ? document.getElementById(`line${i}`) : null;
        dot.classList.remove('active','done');
        if (i < step)       { dot.classList.add('done'); if (line) line.classList.add('done'); }
        else if (i === step) { dot.classList.add('active'); if (line) line.classList.remove('done'); }
        else                { if (line) line.classList.remove('done'); }
    });
}

// ── Bước 1: Yêu cầu OTP ───────────────────────────────────────────
document.getElementById('btnRequestOtp').addEventListener('click', async () => {
    clearAlert();
    const phone = document.getElementById('phoneInput').value.trim();
    if (!/^[0-9]{9,11}$/.test(phone)) {
        showAlert('error', 'Số điện thoại không hợp lệ (9-11 chữ số)');
        return;
    }

    const btn = document.getElementById('btnRequestOtp');
    btn.disabled = true;
    btn.textContent = 'Đang gửi...';

    try {
        const fd = new FormData();
        fd.append('action', 'request_otp');
        fd.append('so_dien_thoai', phone);

        const res  = await fetch(API, { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.success) {
            showAlert('error', data.message);
            return;
        }

        currentPhone = phone;
        document.getElementById('sdtMasked').textContent = data.data?.sdt_masked || phone;
        goToStep(2);
        startCountdown();
        startResendTimer();
    } catch(e) {
        showAlert('error', 'Lỗi kết nối. Vui lòng thử lại.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Gửi mã OTP';
    }
});

// Enter key ở bước 1
document.getElementById('phoneInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('btnRequestOtp').click();
});

// ── Đếm ngược hiệu lực OTP (5 phút) ──────────────────────────────
function startCountdown() {
    if (countdownTimer) clearInterval(countdownTimer);
    let seconds = 300;
    const el = document.getElementById('countdown');
    const update = () => {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        el.textContent = `${m}:${s.toString().padStart(2,'0')}`;
        if (seconds <= 0) {
            clearInterval(countdownTimer);
            el.textContent = 'Hết hạn';
            el.style.color = '#ef4444';
            document.getElementById('otpInfo').classList.replace('alert-info','alert-error');
        }
        seconds--;
    };
    update();
    countdownTimer = setInterval(update, 1000);
}

// ── Đếm ngược nút gửi lại (60s) ──────────────────────────────────
function startResendTimer() {
    if (resendTimer) clearInterval(resendTimer);
    let sec = 60;
    const btn  = document.getElementById('resendBtn');
    const span = document.getElementById('resendTimer');
    btn.disabled = true;
    const update = () => {
        span.textContent = sec;
        if (sec <= 0) {
            clearInterval(resendTimer);
            btn.disabled = false;
            btn.textContent = 'Gửi lại OTP';
        }
        sec--;
    };
    update();
    resendTimer = setInterval(update, 1000);
}

// ── Gửi lại OTP ───────────────────────────────────────────────────
document.getElementById('resendBtn').addEventListener('click', async () => {
    clearAlert();
    const btn = document.getElementById('resendBtn');
    btn.disabled = true;

    try {
        const fd = new FormData();
        fd.append('action', 'request_otp');
        fd.append('so_dien_thoai', currentPhone);

        const res  = await fetch(API, { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.success) {
            showAlert('error', data.message);
            btn.disabled = false;
            return;
        }

        // Reset OTP input và đồng hồ
        document.getElementById('otpInput').value = '';
        document.getElementById('otpInfo').className = 'alert alert-info';
        document.getElementById('countdown').style.color = '';
        startCountdown();
        startResendTimer();
        showAlert('success', 'Đã gửi lại mã OTP mới.');
    } catch(e) {
        showAlert('error', 'Lỗi kết nối khi gửi lại OTP.');
        btn.disabled = false;
    }
});

// ── Bước 2: Xác thực OTP ─────────────────────────────────────────
document.getElementById('btnVerifyOtp').addEventListener('click', async () => {
    clearAlert();
    const otp = document.getElementById('otpInput').value.trim();
    if (!/^[0-9]{6}$/.test(otp)) {
        showAlert('error', 'Mã OTP phải gồm đúng 6 chữ số');
        return;
    }

    const btn = document.getElementById('btnVerifyOtp');
    btn.disabled = true;
    btn.textContent = 'Đang xác thực...';

    try {
        const fd = new FormData();
        fd.append('action', 'verify_otp');
        fd.append('so_dien_thoai', currentPhone);
        fd.append('ma_otp', otp);

        const res  = await fetch(API, { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.success) {
            showAlert('error', data.message);
            return;
        }

        if (countdownTimer) clearInterval(countdownTimer);
        if (resendTimer)    clearInterval(resendTimer);
        goToStep(3);
    } catch(e) {
        showAlert('error', 'Lỗi kết nối. Vui lòng thử lại.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Xác nhận OTP';
    }
});

// Auto-submit khi nhập đủ 6 số
document.getElementById('otpInput').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
    if (this.value.length === 6) document.getElementById('btnVerifyOtp').click();
});

// ── Bước 3: Đổi mật khẩu ─────────────────────────────────────────
document.getElementById('btnResetPass').addEventListener('click', async () => {
    clearAlert();
    const pass = document.getElementById('newPass').value;
    const conf = document.getElementById('confPass').value;

    if (pass.length < 6) {
        showAlert('error', 'Mật khẩu phải có ít nhất 6 ký tự');
        return;
    }
    if (pass !== conf) {
        showAlert('error', 'Xác nhận mật khẩu không khớp');
        return;
    }

    const btn = document.getElementById('btnResetPass');
    btn.disabled = true;
    btn.textContent = 'Đang cập nhật...';

    try {
        const fd = new FormData();
        fd.append('action', 'reset_password');
        fd.append('so_dien_thoai', currentPhone);
        fd.append('mat_khau_moi', pass);
        fd.append('xac_nhan_mat_khau', conf);

        const res  = await fetch(API, { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.success) {
            showAlert('error', data.message);
            return;
        }

        goToStep(4);
        // Ẩn steps bar khi thành công
        document.querySelector('.steps-bar').style.display = 'none';
    } catch(e) {
        showAlert('error', 'Lỗi kết nối. Vui lòng thử lại.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Đổi mật khẩu';
    }
});
</script>
</body>
</html>
