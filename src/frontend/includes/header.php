<?php
// Đọc APP_BASE_URL từ .env để inject vào JS
if (!defined('APP_BASE_URL')) {
    $__envFile = dirname(__DIR__, 2) . '/.env';
    $__appBase = '';
    if (file_exists($__envFile)) {
        foreach (file($__envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $__line) {
            if (str_starts_with(trim($__line), '#') || !str_contains($__line, '=')) continue;
            [$__k, $__v] = explode('=', $__line, 2);
            if (trim($__k) === 'APP_BASE_URL') { $__appBase = trim($__v); break; }
        }
    }
    // Fallback: nếu không set thì dùng /DATN (localhost)
    define('APP_BASE_URL', $__appBase !== '' ? rtrim($__appBase, '/') : '/DATN');
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
    <link rel="stylesheet" href="/DATN/frontend/assets/css/styles.css?v=4">
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
    </script>
</head>
<body>
