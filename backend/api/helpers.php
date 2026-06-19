<?php
/**
 * Hàm response JSON chuẩn — dùng chung cho tất cả API modules
 */
function response($success, $data = null, $message = '') {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode([
        'success'   => $success,
        'data'      => $data,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
