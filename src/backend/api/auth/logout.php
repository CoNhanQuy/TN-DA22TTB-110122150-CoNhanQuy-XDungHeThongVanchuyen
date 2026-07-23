<?php
/**
 * Route: GET/POST /api/auth/logout
 * Xóa session và redirect về trang chủ
 */
require_once __DIR__ . '/../../config/cauhinh.php';

session_destroy();
header('Location: ' . APP_BASE_URL . '/index.php');
exit();
