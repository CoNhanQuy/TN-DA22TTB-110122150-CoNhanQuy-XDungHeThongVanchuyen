<?php
require_once __DIR__ . '/cauhinh.php';
header('Content-Type: text/plain; charset=utf-8');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');
$sql = "SELECT id, ten_danh_muc, gia_tri, trang_thai FROM danh_muc WHERE loai_danh_muc='bang_phi' AND trang_thai=1";
$result = $conn->query($sql);
if (!$result) {
    echo "Lỗi truy vấn: " . $conn->error;
    exit;
}
if ($result->num_rows === 0) {
    echo "Không có dữ liệu bảng giá (bang_phi)!";
    exit;
}
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}\nTên: {$row['ten_danh_muc']}\nGiá trị: {$row['gia_tri']}\nTrạng thái: {$row['trang_thai']}\n---\n";
}
$result->free();
$conn->close();
?>