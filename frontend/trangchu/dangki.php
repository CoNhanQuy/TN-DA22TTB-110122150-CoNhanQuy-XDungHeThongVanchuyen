<?php
include_once '../../backend/cauhinh.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten   = trim($_POST['ho_ten'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $sdt      = trim($_POST['so_dien_thoai'] ?? '');
    $cccd     = trim($_POST['cccd'] ?? '');
    $dia_chi  = trim($_POST['dia_chi'] ?? '');
    $matkhau  = $_POST['mat_khau'] ?? '';
    $confirm  = $_POST['mat_khau_confirm'] ?? '';

    // Validate cơ bản — chỉ dùng các cột thực có trong DB: ho_ten, so_dien_thoai, mat_khau
    if (!$ho_ten || !$sdt || !$matkhau) {
        $error = 'Vui lòng điền đầy đủ các trường bắt buộc!';
    } elseif (!preg_match('/^[0-9]{9,15}$/', $sdt)) {
        $error = 'Số điện thoại không hợp lệ!';
    } elseif (strlen($matkhau) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } elseif ($matkhau !== $confirm) {
        $error = 'Xác nhận mật khẩu không khớp!';
    } else {
        // Kiểm tra trùng SĐT
        $stmt = $conn->prepare("SELECT id FROM nguoi_dung WHERE so_dien_thoai = ? LIMIT 1");
        $stmt->bind_param("s", $sdt);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Số điện thoại này đã được đăng ký!';
            $stmt->close();
        } else {
            $stmt->close();

            if (!$error) {
                $hash = password_hash($matkhau, PASSWORD_DEFAULT);

                // Insert vào nguoi_dung (trang_thai = 1: đang hoạt động)
                $ins = $conn->prepare("INSERT INTO nguoi_dung (ho_ten, so_dien_thoai, mat_khau, trang_thai) VALUES (?, ?, ?, 1)");
                if (!$ins) {
                    $error = 'Lỗi hệ thống: ' . $conn->error;
                } else {
                    $ins->bind_param("sss", $ho_ten, $sdt, $hash);
                    if ($ins->execute()) {
                        $newId = $conn->insert_id;

                        // Gán role khach_hang (chưa có trong vai_tro mặc định, nhưng thêm nếu tồn tại)
                        // Schema mới chỉ có 5 vai trò hệ thống, khách hàng đăng ký tự phục vụ
                        // Không gán vai trò → để trống (hoặc thêm vai trò khach_hang nếu cần)

                        $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
                    } else {
                        $error = 'Có lỗi xảy ra khi tạo tài khoản: ' . $ins->error;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - Vận Tải Xanh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
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

        .register-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 480px;
        }

        .register-container h2 {
            text-align: center;
            color: #1f2937;
            margin-bottom: 24px;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.2px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .form-group input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'Be Vietnam Pro', 'Segoe UI', sans-serif;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
            color: #1f2937;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.98rem;
            font-weight: 700;
            font-family: 'Be Vietnam Pro', 'Segoe UI', sans-serif;
            cursor: pointer;
            margin-top: 8px;
            letter-spacing: 0.01em;
            transition: opacity 0.2s, transform 0.2s;
        }

        .btn-register:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-register:active { transform: translateY(0); }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .alert-error   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        .form-info {
            background: #eff6ff;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 0.84rem;
            color: #1d4ed8;
            margin-bottom: 18px;
            line-height: 1.5;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .login-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="register-container">
    <h2>🚚 Đăng Ký Tài Khoản</h2>

    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✓ <?php echo htmlspecialchars($success); ?></div>
        <div style="text-align:center; margin-top:16px;">
            <a href="index.php" style="color:#667eea; font-weight:600; text-decoration:none;">← Quay lại trang đăng nhập</a>
        </div>
    <?php else: ?>

        <div class="form-info">ℹ️ Đăng ký tài khoản khách hàng để theo dõi đơn hàng và sử dụng dịch vụ</div>

        <form method="POST" action="dangki.php">
            <div class="form-group">
                <label>Họ và Tên <span style="color:red">*</span></label>
                <input type="text" name="ho_ten" placeholder="Nhập họ và tên"
                       value="<?php echo htmlspecialchars($_POST['ho_ten'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Số Điện Thoại <span style="color:red">*</span></label>
                <input type="tel" name="so_dien_thoai" placeholder="0123456789" pattern="[0-9]{9,15}"
                       value="<?php echo htmlspecialchars($_POST['so_dien_thoai'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Mật Khẩu <span style="color:red">*</span> <span style="color:#aaa;font-weight:normal">(ít nhất 6 ký tự)</span></label>
                <input type="password" name="mat_khau" placeholder="Nhập mật khẩu" required>
            </div>

            <div class="form-group">
                <label>Xác Nhận Mật Khẩu <span style="color:red">*</span></label>
                <input type="password" name="mat_khau_confirm" placeholder="Nhập lại mật khẩu" required>
            </div>

            <button type="submit" class="btn-register">📝 Đăng Ký</button>
        </form>

        <div class="login-link">Đã có tài khoản? <a href="index.php">Đăng nhập ngay</a></div>

    <?php endif; ?>
</div>
</body>
</html>
