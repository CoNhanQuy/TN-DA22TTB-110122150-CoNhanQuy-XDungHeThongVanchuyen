<?php
/**
 * Textbee SMS Webhook Endpoint — backend/api/textbee_webhook.php
 *
 * Đường dẫn cấu hình trên Textbee Dashboard:
 * https://<domain-cua-ban>/backend/api/textbee_webhook.php
 */

// Đặt header phản hồi chuẩn JSON
header('Content-Type: application/json; charset=utf-8');

// Nhận cấu hình hệ thống và kết nối DB
require_once __DIR__ . '/../config/cauhinh.php';

// Tạo thư mục logs nếu chưa tồn tại
$logDir = __DIR__ . '/../logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/webhook.log';

// Đọc dữ liệu POST từ Textbee (dạng JSON)
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

// Ghi log toàn bộ request nhận được để dễ theo dõi và debug
$timestamp = date('Y-m-d H:i:s');
$logData = "[$timestamp] IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
$logData .= "Payload: " . ($rawInput ? $rawInput : "Trống") . "\n";
$logData .= "--------------------------------------------------------\n";
file_put_contents($logFile, $logData, FILE_APPEND);

// Kiểm tra dữ liệu đầu vào có hợp lệ không
if (!$payload || !isset($payload['webhookEvent'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ hoặc thiếu webhookEvent']);
    exit;
}

$event = $payload['webhookEvent'];
$message = trim($payload['message'] ?? '');
$senderRaw = trim($payload['sender'] ?? '');
$deviceId = $payload['deviceId'] ?? '';
$smsId = $payload['smsId'] ?? '';

// Định nghĩa mã bảo mật webhook (tùy chọn - nếu cấu hình trong .env)
$webhookSecret = $_ENV['TEXTBEE_WEBHOOK_SECRET'] ?? '';
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

if ($webhookSecret !== '') {
    // 1. Kiểm tra chữ ký HMAC-SHA256 (Khuyên dùng từ Textbee)
    $expectedSignature = hash_hmac('sha256', $rawInput, $webhookSecret);
    $isValid = hash_equals($expectedSignature, $signature);
    
    // 2. Dự phòng (cho phép kiểm tra tham số ?secret=... để dễ dàng test bằng Postman/cURL)
    if (!$isValid && isset($_GET['secret']) && $_GET['secret'] === $webhookSecret) {
        $isValid = true;
    }

    if (!$isValid) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập (Chữ ký x-signature không hợp lệ)']);
        exit;
    }
}

// Xử lý các loại sự kiện khác nhau từ Textbee
switch ($event) {
    case 'MESSAGE_RECEIVED':
        // ── XỬ LÝ KHI NHẬN ĐƯỢC SMS GỬI ĐẾN ĐIỆN THOẠI GATEWAY ─────────────────
        
        // Chuẩn hóa số điện thoại người gửi (+84909... thành 0909...)
        $sender = $senderRaw;
        if (strpos($sender, '+84') === 0) {
            $sender = '0' . substr($sender, 3);
        }
        $sender = preg_replace('/\D/', '', $sender);

        // Tìm mã OTP 6 chữ số trong nội dung tin nhắn nhận được
        if (preg_match('/\b(\d{6})\b/', $message, $matches)) {
            $otp = $matches[1];
            
            // Tìm mã OTP khớp trong CSDL đang chờ xác nhận (trang_thai = 0)
            $stmt = $conn->prepare(
                "SELECT id, so_dien_thoai FROM xac_minh_otp 
                 WHERE so_dien_thoai = ? 
                   AND ma_otp = ? 
                   AND trang_thai = 0 
                   AND thoi_gian_het_han > NOW()
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($stmt) {
                $stmt->bind_param("ss", $sender, $otp);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($row) {
                    $otpId = (int)$row['id'];
                    
                    // Cập nhật trạng thái thành ĐÃ XÁC THỰC (trang_thai = 1)
                    $stmtUpdate = $conn->prepare("UPDATE xac_minh_otp SET trang_thai = 1 WHERE id = ?");
                    if ($stmtUpdate) {
                        $stmtUpdate->bind_param("i", $otpId);
                        $stmtUpdate->execute();
                        $stmtUpdate->close();
                        
                        // Thiết lập session tạm để cho phép đổi mật khẩu (nếu cần trên giao diện)
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        $_SESSION['otp_verified_sdt'] = $sender;
                        $_SESSION['otp_verified_time'] = time();
                        
                        $msgSuccess = "[$timestamp] [OK] Đã xác thực thành công OTP $otp cho SĐT $sender qua tin nhắn SMS.\n";
                        file_put_contents($logFile, $msgSuccess, FILE_APPEND);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Xác thực OTP thành công qua SMS phản hồi!',
                            'data' => ['sdt' => $sender, 'otp' => $otp]
                        ]);
                        exit;
                    }
                } else {
                    $msgFail = "[$timestamp] [FAILED] Không tìm thấy mã OTP $otp đang chờ cho SĐT $sender hoặc mã đã hết hạn.\n";
                    file_put_contents($logFile, $msgFail, FILE_APPEND);
                }
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Nhận tin nhắn thành công nhưng không có hành động xác thực nào được thực thi.'
        ]);
        break;

    case 'MESSAGE_SENT':
        // Log trạng thái tin nhắn đã gửi thành công đi từ gateway
        $msgSent = "[$timestamp] [SENT] Tin nhắn ID $smsId đã được gửi thành công từ thiết bị $deviceId.\n";
        file_put_contents($logFile, $msgSent, FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Ghi nhận trạng thái MESSAGE_SENT thành công']);
        break;

    case 'MESSAGE_DELIVERED':
        // Log trạng thái tin nhắn đã được chuyển đến máy người nhận
        $msgDelivered = "[$timestamp] [DELIVERED] Tin nhắn ID $smsId đã được chuyển tới người nhận.\n";
        file_put_contents($logFile, $msgDelivered, FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Ghi nhận trạng thái MESSAGE_DELIVERED thành công']);
        break;

    case 'MESSAGE_FAILED':
        // Log lỗi gửi tin nhắn thất bại
        $msgFailed = "[$timestamp] [FAILED] Tin nhắn ID $smsId gửi thất bại từ thiết bị $deviceId.\n";
        file_put_contents($logFile, $msgFailed, FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Ghi nhận trạng thái MESSAGE_FAILED thành công']);
        break;

    default:
        echo json_encode(['success' => true, 'message' => 'Ghi nhận sự kiện: ' . $event]);
        break;
}
