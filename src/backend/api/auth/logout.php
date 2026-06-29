<?php
/**
 * Route: GET/POST /api/auth/logout
 * Xóa session và redirect về trang chủ
 */
require_once __DIR__ . '/../../config/cauhinh.php';

session_destroy();
header('Location: /DATN/frontend/trangchu/index.php');
exit();
