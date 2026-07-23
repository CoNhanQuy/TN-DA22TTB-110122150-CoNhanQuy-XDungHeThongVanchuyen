<?php
// Fallback: nếu không được định nghĩa trước (thường từ cauhinh.php)
if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', '/DATN');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Vận Tải Xanh' : 'Vận Tải Xanh'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_BASE_URL; ?>/frontend/assets/css/styles.css?v=4">
    <?php if (isset($moduleCSS)): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($moduleCSS); ?>?v=2">
    <?php endif; ?>
    <style>
        /* Đảm bảo font áp dụng ngay khi load, trước khi module CSS parse xong */
        body { font-family: 'Be Vietnam Pro', 'Segoe UI', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
    </style>
    <script>
        // Base URL cho toàn bộ API calls — được inject từ PHP (đọc từ .env APP_BASE_URL)
        window.API_BASE = <?php echo json_encode(APP_BASE_URL); ?>;
        window.getApiBase = function() {
            return (typeof window.API_BASE === 'string') ? window.API_BASE : '';
        };
    </script>
</head>
<body>
