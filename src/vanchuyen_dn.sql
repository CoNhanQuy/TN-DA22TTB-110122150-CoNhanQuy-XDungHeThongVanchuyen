-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: sql108.infinityfree.com
-- Thời gian đã tạo: Th7 21, 2026 lúc 06:01 AM
-- Phiên bản máy phục vụ: 11.4.12-MariaDB
-- Phiên bản PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `if0_42253679_vanchuyen_dn`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bang_gia_cuoc`
--

CREATE TABLE `bang_gia_cuoc` (
  `id` int(11) NOT NULL,
  `khoi_luong_tu_kg` decimal(10,2) NOT NULL,
  `khoi_luong_den_kg` decimal(10,2) NOT NULL,
  `gia_co_ban` decimal(15,2) NOT NULL,
  `gia_theo_moi_km` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bang_gia_cuoc`
--

INSERT INTO `bang_gia_cuoc` (`id`, `khoi_luong_tu_kg`, `khoi_luong_den_kg`, `gia_co_ban`, `gia_theo_moi_km`) VALUES
(1, '0.00', '4.99', '20000.00', '300.00'),
(2, '5.00', '19.99', '35000.00', '400.00'),
(3, '20.00', '999.00', '50000.00', '600.00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bao_cao_su_co`
--

CREATE TABLE `bao_cao_su_co` (
  `id` int(11) NOT NULL,
  `don_hang_id` int(11) DEFAULT NULL COMMENT 'Nếu sự cố xảy ra với 1 đơn lẻ chặng cuối',
  `dot_van_chuyen_id` int(11) DEFAULT NULL COMMENT 'Nếu sự cố xảy ra với cả chuyến xe tải chặng lớn',
  `nguoi_bao_cao_id` int(11) NOT NULL COMMENT 'ID người dùng (tài xế/shipper) phát hiện sự cố',
  `loai_su_co` enum('hong_xe','thoi_tiet','khach_hen_lai','hang_hu_hong','khac') NOT NULL,
  `mo_ta_chi_tiet` text NOT NULL,
  `anh_hien_truong` varchar(255) DEFAULT NULL,
  `thoi_gian_xay_ra` timestamp NOT NULL DEFAULT current_timestamp(),
  `trang_thai_xu_ly` enum('cho_duyet','dang_xu_ly','da_khac_phuc') DEFAULT 'cho_duyet'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bao_cao_su_co`
--

INSERT INTO `bao_cao_su_co` (`id`, `don_hang_id`, `dot_van_chuyen_id`, `nguoi_bao_cao_id`, `loai_su_co`, `mo_ta_chi_tiet`, `anh_hien_truong`, `thoi_gian_xay_ra`, `trang_thai_xu_ly`) VALUES
(1, NULL, 3, 16, 'hong_xe', 'Xe tải 84A-999.99 bị thủng lốp trên đoạn cầu Cổ Chiên, đang chờ cứu hộ thay lốp.', NULL, '2026-06-26 06:03:42', 'dang_xu_ly'),
(2, 6, NULL, 15, 'thoi_tiet', 'Mưa lớn cục bộ tại khu vực nhận chặng cuối, shipper xin phép lùi giờ giao đơn DH006.', NULL, '2026-06-26 06:03:42', 'cho_duyet');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_nhanh`
--

CREATE TABLE `chi_nhanh` (
  `id` int(11) NOT NULL,
  `ma_chi_nhanh` varchar(20) NOT NULL,
  `ten_chi_nhanh` varchar(100) NOT NULL,
  `dia_chi` text NOT NULL,
  `so_dien_thoai` varchar(15) DEFAULT NULL,
  `toa_do_kinh_do` decimal(11,8) DEFAULT NULL,
  `toa_do_vi_do` decimal(10,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_nhanh`
--

INSERT INTO `chi_nhanh` (`id`, `ma_chi_nhanh`, `ten_chi_nhanh`, `dia_chi`, `so_dien_thoai`, `toa_do_kinh_do`, `toa_do_vi_do`) VALUES
(1, 'CN_TRAVINH', 'Chi nhánh Trà Vinh', '123 Lê Lợi, Khóm 1, Phường Trà Vinh, tỉnh Vĩnh Long', '02943123456', '106.34654300', '9.93456300'),
(2, 'CN_VINHLONG', 'Chi nhánh Vĩnh Long', '456 Nguyễn Huệ, Khóm 4, Phường Long Châu, tỉnh Vĩnh Long', '02703456789', '105.96443200', '10.25345200');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_dot_van_chuyen`
--

CREATE TABLE `chi_tiet_dot_van_chuyen` (
  `id` int(11) NOT NULL,
  `dot_van_chuyen_id` int(11) NOT NULL,
  `don_hang_id` int(11) NOT NULL,
  `trang_thai_trong_dot` enum('da_xep_len_xe','dang_van_chuyen','da_giao_kho_dich','tra_lai') DEFAULT 'da_xep_len_xe'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_dot_van_chuyen`
--

INSERT INTO `chi_tiet_dot_van_chuyen` (`id`, `dot_van_chuyen_id`, `don_hang_id`, `trang_thai_trong_dot`) VALUES
(1, 1, 1, 'da_giao_kho_dich'),
(2, 2, 3, 'da_giao_kho_dich'),
(3, 2, 4, 'da_giao_kho_dich'),
(4, 2, 5, 'da_giao_kho_dich'),
(5, 2, 6, 'dang_van_chuyen'),
(6, 2, 11, 'da_giao_kho_dich'),
(7, 3, 12, 'dang_van_chuyen'),
(8, 4, 2, 'da_giao_kho_dich'),
(9, 4, 13, 'da_giao_kho_dich'),
(10, 4, 20, 'da_giao_kho_dich'),
(11, 5, 7, 'da_giao_kho_dich'),
(12, 5, 21, 'da_giao_kho_dich'),
(13, 5, 23, 'da_giao_kho_dich'),
(14, 6, 22, 'dang_van_chuyen'),
(15, 6, 24, 'dang_van_chuyen');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_hang_hoa`
--

CREATE TABLE `chi_tiet_hang_hoa` (
  `id` int(11) NOT NULL,
  `don_hang_id` int(11) NOT NULL,
  `loai_hang_hoa_id` int(11) NOT NULL,
  `ten_mat_hang` varchar(255) NOT NULL,
  `so_luong` int(11) NOT NULL DEFAULT 1,
  `khoi_luong_uoc_tinh_kg` decimal(10,2) DEFAULT 0.00,
  `ghi_chu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_hang_hoa`
--

INSERT INTO `chi_tiet_hang_hoa` (`id`, `don_hang_id`, `loai_hang_hoa_id`, `ten_mat_hang`, `so_luong`, `khoi_luong_uoc_tinh_kg`, `ghi_chu`) VALUES
(1, 1, 4, 'Thùng đựng Linh kiện máy tính', 1, '2.50', 'Hàng giá trị cao'),
(2, 1, 1, 'Dây cáp sạc bọc chống sốc', 2, '0.50', NULL),
(3, 2, 2, 'Thùng cam sành Trà Vinh', 1, '12.00', 'Hàng thực phẩm giao nhanh'),
(4, 3, 4, 'Điện thoại iPhone 15 Pro Max', 1, '2.50', 'Hàng giá trị cao, bọc xốp kỹ'),
(5, 4, 3, 'Tủ lạnh mini Electrolux', 1, '14.00', 'Hàng cồng kềnh, tránh va đập'),
(6, 5, 3, 'Thùng linh kiện máy công nghiệp', 2, '45.00', 'Hàng nặng, cần xe nâng'),
(7, 6, 1, 'Tập sách giáo khoa cũ', 5, '1.20', NULL),
(8, 7, 2, 'Thùng khoai lang Bình Tân Vĩnh Long', 1, '6.80', 'Hàng nông sản tươi sống'),
(9, 8, 3, 'Bộ máy tính để bàn (PC)', 1, '32.00', 'Màn hình bọc chống sốc'),
(10, 9, 1, 'Phong bì hồ sơ du học', 1, '0.50', 'Giao hỏa tốc'),
(11, 10, 2, 'Thùng bưởi năm roi Vĩnh Long', 1, '18.50', 'Hàng thực phẩm dập nhẹ'),
(12, 11, 3, 'Thùng hàng quần áo may mặc xuất khẩu', 3, '60.00', 'Hàng nặng đóng kiện'),
(13, 12, 1, 'Bộ nồi chảo dùng cho bếp từ', 1, '3.10', NULL),
(14, 13, 2, 'Thùng cam sành Tam Bình', 1, '8.20', 'Giao trong ngày'),
(15, 14, 3, 'Thùng sơn tường Dulux 18L', 1, '22.00', 'Hàng nặng, dễ tràn đổ'),
(21, 20, 5, 'Hoa', 1, '1.00', 'hoa tươi giao gấp'),
(22, 21, 1, 'Vali quần áo', 1, '8.00', ''),
(23, 22, 1, 'Tập sách tiểu thuyết', 1, '2.00', ''),
(24, 23, 2, 'Combo snack', 1, '2.00', ''),
(25, 24, 1, 'Vali quần áo', 1, '5.00', ''),
(26, 25, 4, 'Samsung A12', 1, '0.20', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `don_hang`
--

CREATE TABLE `don_hang` (
  `id` int(11) NOT NULL,
  `ma_don_hang` varchar(50) NOT NULL,
  `khach_hang_gui_id` int(11) NOT NULL,
  `khach_hang_nhan_id` int(11) NOT NULL,
  `chi_nhanh_gui_id` int(11) DEFAULT NULL,
  `chi_nhanh_nhan_id` int(11) DEFAULT NULL,
  `tong_khoi_luong_kg` decimal(10,2) NOT NULL,
  `phi_van_chuyen` decimal(15,2) NOT NULL,
  `tien_tra_truoc` decimal(15,2) DEFAULT 0.00,
  `trang_thai_don_hang` enum('cho_tiep_nhan','da_nhap_kho','dang_van_chuyen','da_den_kho_dich','dang_giao_hang','hoan_tat','da_huy') DEFAULT 'cho_tiep_nhan',
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `don_hang`
--

INSERT INTO `don_hang` (`id`, `ma_don_hang`, `khach_hang_gui_id`, `khach_hang_nhan_id`, `chi_nhanh_gui_id`, `chi_nhanh_nhan_id`, `tong_khoi_luong_kg`, `phi_van_chuyen`, `tien_tra_truoc`, `trang_thai_don_hang`, `ngay_tao`) VALUES
(1, 'DH001', 1, 2, 1, 2, '3.00', '330000.00', '150000.00', 'hoan_tat', '2026-06-19 01:34:19'),
(2, 'DH002', 1, 2, 1, 2, '12.00', '315000.00', '0.00', 'dang_giao_hang', '2026-06-19 01:34:19'),
(3, 'DH003', 4, 8, 1, 2, '2.50', '379500.00', '330000.00', 'hoan_tat', '2026-06-26 05:56:23'),
(4, 'DH004', 5, 9, 1, 2, '14.00', '330000.00', '0.00', 'dang_giao_hang', '2026-06-26 05:56:23'),
(5, 'DH005', 6, 10, 1, 2, '45.00', '264000.00', '240000.00', 'dang_giao_hang', '2026-06-26 05:56:23'),
(6, 'DH006', 7, 11, 1, 2, '1.20', '330000.00', '0.00', 'dang_van_chuyen', '2026-06-26 05:56:23'),
(7, 'DH007', 8, 4, 2, 1, '6.80', '315000.00', '300000.00', 'dang_giao_hang', '2026-06-26 05:56:23'),
(8, 'DH008', 9, 5, 2, 1, '32.00', '264000.00', '0.00', 'cho_tiep_nhan', '2026-06-26 05:56:23'),
(9, 'DH009', 10, 6, 2, 1, '0.50', '330000.00', '330000.00', 'hoan_tat', '2026-06-26 05:56:23'),
(10, 'DH010', 11, 7, 2, 1, '18.50', '315000.00', '0.00', 'dang_giao_hang', '2026-06-26 05:56:23'),
(11, 'DH011', 13, 12, 1, 2, '60.00', '264000.00', '240000.00', 'dang_giao_hang', '2026-06-26 05:56:23'),
(12, 'DH012', 14, 15, 2, 1, '3.10', '330000.00', '0.00', 'dang_van_chuyen', '2026-06-26 05:56:23'),
(13, 'DH013', 15, 13, 1, 2, '8.20', '315000.00', '300000.00', 'da_den_kho_dich', '2026-06-26 05:56:23'),
(14, 'DH014', 12, 14, 2, 1, '22.00', '264000.00', '0.00', 'cho_tiep_nhan', '2026-06-26 05:56:23'),
(20, 'DH20260702110148972', 26, 27, NULL, NULL, '1.00', '57000.00', '57000.00', 'dang_giao_hang', '2026-07-02 15:01:48'),
(21, 'DH20260704034033720', 28, 29, NULL, NULL, '8.00', '59000.00', '50000.00', 'dang_giao_hang', '2026-07-04 07:40:33'),
(22, 'DH20260705185749180', 31, 32, NULL, NULL, '2.00', '39500.00', '39500.00', 'dang_van_chuyen', '2026-07-05 22:57:49'),
(23, 'DH20260705192351166', 33, 34, NULL, NULL, '2.00', '43050.00', '0.00', 'hoan_tat', '2026-07-05 23:23:51'),
(24, 'DH20260705214918797', 37, 38, NULL, NULL, '5.00', '61000.00', '50000.00', 'dang_van_chuyen', '2026-07-06 01:49:18'),
(25, 'DH20260721053249574', 39, 40, NULL, NULL, '0.20', '24725.00', '0.00', 'da_nhap_kho', '2026-07-21 09:32:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dot_van_chuyen`
--

CREATE TABLE `dot_van_chuyen` (
  `id` int(11) NOT NULL,
  `ma_dot_van_chuyen` varchar(50) NOT NULL,
  `tuyen_duong_id` int(11) NOT NULL,
  `tai_xe_id` int(11) NOT NULL,
  `xe_van_tai_id` int(11) NOT NULL,
  `trang_thai_dot_van_chuyen` enum('cho_khoi_hanh','dang_di_chuyen','da_den_kho_nhan','huy') DEFAULT 'cho_khoi_hanh',
  `ngay_gio_khoi_hanh` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dot_van_chuyen`
--

INSERT INTO `dot_van_chuyen` (`id`, `ma_dot_van_chuyen`, `tuyen_duong_id`, `tai_xe_id`, `xe_van_tai_id`, `trang_thai_dot_van_chuyen`, `ngay_gio_khoi_hanh`) VALUES
(1, 'DOT_001', 1, 1, 1, 'da_den_kho_nhan', '2026-06-19 07:00:00'),
(2, 'DOT_002', 1, 2, 1, 'da_den_kho_nhan', '2026-06-26 08:00:00'),
(3, 'DOT_003', 2, 3, 2, 'dang_di_chuyen', '2026-06-26 11:30:00'),
(4, 'DOT_20260702110413', 1, 1, 2, 'da_den_kho_nhan', '2026-07-02 22:04:00'),
(5, 'DOT_20260705193130', 2, 1, 2, 'da_den_kho_nhan', '2026-07-06 06:31:00'),
(6, 'DOT_20260721053413', 1, 1, 2, 'dang_di_chuyen', '2026-07-21 16:34:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giao_hang_tan_noi`
--

CREATE TABLE `giao_hang_tan_noi` (
  `id` int(11) NOT NULL,
  `don_hang_id` int(11) NOT NULL,
  `nguoi_giao_hang_id` int(11) NOT NULL,
  `trang_thai_giao_hang` enum('cho_lay_hang','dang_giao','thanh_cong','that_bai') DEFAULT 'cho_lay_hang',
  `nguoi_nhan_thuc_te` varchar(100) DEFAULT NULL,
  `anh_minh_chung` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn ảnh chụp hiện trường khi giao hàng thành công/thất bại',
  `ngay_gio_giao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `giao_hang_tan_noi`
--

INSERT INTO `giao_hang_tan_noi` (`id`, `don_hang_id`, `nguoi_giao_hang_id`, `trang_thai_giao_hang`, `nguoi_nhan_thuc_te`, `anh_minh_chung`, `ngay_gio_giao`) VALUES
(1, 1, 1, 'thanh_cong', 'Lê Thị Nhận', '/DATN/uploads/delivery/giao_hang_1_1782482413.jpg', '2026-06-26 16:00:14'),
(2, 3, 12, 'thanh_cong', 'Vũ Hải Đăng', 'uploads/chung_minh_dh003.jpg', '2026-06-26 10:15:00'),
(3, 4, 13, 'dang_giao', NULL, NULL, NULL),
(4, 9, 2, 'thanh_cong', 'Phạm Minh Long', 'uploads/chung_minh_dh009.jpg', '2026-06-26 09:40:00'),
(5, 10, 3, 'dang_giao', NULL, NULL, NULL),
(6, 2, 1, 'dang_giao', NULL, NULL, NULL),
(7, 5, 1, 'dang_giao', NULL, NULL, NULL),
(8, 11, 1, 'dang_giao', NULL, NULL, NULL),
(9, 20, 1, 'dang_giao', NULL, NULL, NULL),
(10, 7, 1, 'dang_giao', NULL, NULL, NULL),
(11, 21, 1, 'dang_giao', NULL, NULL, NULL),
(12, 23, 1, 'thanh_cong', 'Chính chủ', '/uploads/delivery/giao_hang_23_1783294739.jpg', '2026-07-05 19:38:59');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hanh_trinh_xe`
--

CREATE TABLE `hanh_trinh_xe` (
  `id` int(11) NOT NULL,
  `loai_hanh_trinh` enum('trung_chuyen_kho','shipper_giao_khach') NOT NULL,
  `ma_dinh_danh_luong` int(11) NOT NULL,
  `vi_do_hien_tai` decimal(10,8) NOT NULL,
  `kinh_do_hien_tai` decimal(11,8) NOT NULL,
  `thoi_gian_ghi_nhan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hanh_trinh_xe`
--

INSERT INTO `hanh_trinh_xe` (`id`, `loai_hanh_trinh`, `ma_dinh_danh_luong`, `vi_do_hien_tai`, `kinh_do_hien_tai`, `thoi_gian_ghi_nhan`) VALUES
(1, 'shipper_giao_khach', 1, '10.25123400', '105.96123400', '2026-06-19 01:34:19'),
(2, 'trung_chuyen_kho', 3, '10.12456300', '106.12453200', '2026-06-26 06:03:30'),
(3, 'shipper_giao_khach', 4, '10.25431200', '105.96874300', '2026-06-26 06:03:30'),
(4, 'shipper_giao_khach', 10, '9.93856200', '106.34213400', '2026-06-26 06:03:30'),
(5, 'shipper_giao_khach', 1, '9.92110265', '106.34724553', '2026-07-21 09:14:45'),
(6, 'trung_chuyen_kho', 6, '9.92115346', '106.34726843', '2026-07-21 09:47:33');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoa_don`
--

CREATE TABLE `hoa_don` (
  `id` int(11) NOT NULL,
  `don_hang_id` int(11) NOT NULL,
  `so_tien_thu_ho` decimal(15,2) DEFAULT 0.00,
  `hinh_thuc_thanh_toan` enum('tien_mat','qr_code') NOT NULL DEFAULT 'tien_mat',
  `trang_thai_thanh_toan` enum('chua_thanh_toan','da_thanh_toan','that_bai') DEFAULT 'chua_thanh_toan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hoa_don`
--

INSERT INTO `hoa_don` (`id`, `don_hang_id`, `so_tien_thu_ho`, `hinh_thuc_thanh_toan`, `trang_thai_thanh_toan`) VALUES
(1, 1, '0.00', 'qr_code', 'da_thanh_toan'),
(2, 2, '250000.00', 'tien_mat', 'chua_thanh_toan'),
(3, 3, '0.00', 'qr_code', 'da_thanh_toan'),
(4, 4, '1500000.00', 'tien_mat', 'chua_thanh_toan'),
(5, 5, '0.00', 'qr_code', 'da_thanh_toan'),
(6, 6, '0.00', 'tien_mat', 'chua_thanh_toan'),
(7, 7, '0.00', 'qr_code', 'da_thanh_toan'),
(8, 8, '450000.00', 'tien_mat', 'chua_thanh_toan'),
(9, 9, '0.00', 'qr_code', 'da_thanh_toan'),
(10, 10, '600000.00', 'tien_mat', 'chua_thanh_toan'),
(11, 11, '0.00', 'qr_code', 'da_thanh_toan'),
(12, 12, '0.00', 'tien_mat', 'chua_thanh_toan'),
(13, 13, '0.00', 'qr_code', 'da_thanh_toan'),
(14, 14, '1200000.00', 'tien_mat', 'chua_thanh_toan'),
(15, 20, '0.00', 'tien_mat', 'da_thanh_toan'),
(16, 21, '9000.00', 'tien_mat', 'chua_thanh_toan'),
(17, 22, '0.00', 'tien_mat', 'da_thanh_toan'),
(18, 23, '43050.00', 'tien_mat', 'chua_thanh_toan'),
(19, 24, '11000.00', 'tien_mat', 'chua_thanh_toan'),
(20, 25, '24725.00', 'tien_mat', 'chua_thanh_toan');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khach_hang`
--

CREATE TABLE `khach_hang` (
  `id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) DEFAULT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `so_cccd` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `dia_chi` text NOT NULL,
  `toa_do_kinh_do` decimal(11,8) DEFAULT NULL,
  `toa_do_vi_do` decimal(10,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khach_hang`
--

INSERT INTO `khach_hang` (`id`, `nguoi_dung_id`, `ho_ten`, `so_dien_thoai`, `so_cccd`, `email`, `dia_chi`, `toa_do_kinh_do`, `toa_do_vi_do`) VALUES
(1, 6, 'Nguyễn Văn Gửi', '0911222333', '084001002003', 'nguyengui@gmail.com', 'Khóm 4, phường Long Đức, tỉnh Vĩnh Long', '106.34000000', '9.93000000'),
(2, NULL, 'Lê Thị Nhận', '0944555666', '084001002004', 'lenhan@gmail.com', 'Phường Tân Ngãi, tỉnh Vĩnh Long', '105.96000000', '10.25000000'),
(3, 9, 'Cô Nhân Quý', '0773998235', '084201000666', NULL, 'Khóm 2 Phường Nguyệt Hóa', NULL, NULL),
(4, NULL, 'Nguyễn Kiều Oanh', '0939111222', '084201000111', 'oanhnguyen@gmail.com', 'Khóm 1, phường Trà Vinh, tỉnh Vĩnh Long', NULL, NULL),
(5, NULL, 'Trần Thanh Sơn', '0939222333', '084201000222', 'sontran@gmail.com', 'Xã Trà Cú, tỉnh Vĩnh Long\r\n', NULL, NULL),
(6, NULL, 'Phạm Minh Long', '0939333444', '084201000333', 'longpham@gmail.com', 'Khóm 5, phường Long Đức, tỉnh Vĩnh Long', NULL, NULL),
(7, NULL, 'Lê Hoàng Yến', '0939444555', '084201000444', 'yenle@gmail.com', 'Phường Hòa Thuận, tỉnh Vĩnh Long', NULL, NULL),
(8, NULL, 'Vũ Hải Đăng', '0939555666', '084201000555', 'dangvu@gmail.com', 'Xã Thạnh Phú, tỉnh Vĩnh Long', NULL, NULL),
(9, NULL, 'Đặng Thu Thảo', '0939666777', '084201000666', 'thaodang@gmail.com', 'Xã Tam Bình, tỉnh Vĩnh Long', NULL, NULL),
(10, NULL, 'Bùi Quốc Anh', '0939777888', '084201000777', 'anhbui@gmail.com', 'Phường Bình Minh, tỉnh Vĩnh Long', NULL, NULL),
(11, NULL, 'Ngô Minh Trí', '0939888999', '084201000888', 'tringo@gmail.com', 'Xã Hòa Hiệp, tỉnh Vĩnh Long', NULL, NULL),
(12, NULL, 'Huỳnh Xuân Hương', '0949111222', '084201000999', 'huonghuynh@gmail.com', 'Xã Hiếu Phụng, tỉnh Vĩnh Long', NULL, NULL),
(13, NULL, 'Kiều Tam Lý', '0949222333', '084201001111', 'lykieu@gmail.com', 'Phường Duyên Hải, tỉnh Vĩnh Long', NULL, NULL),
(14, NULL, 'Dương Ngọc Hàn', '0949333444', '084201002222', 'handuong@gmail.com', 'Xã An Bình, tỉnh Vĩnh Long', NULL, NULL),
(15, NULL, 'Tuyết Nhân Quý', '0949555666', '084201003333', 'quytuyet@gmail.com', 'Xã Song Lộc, tỉnh Vĩnh Long', NULL, NULL),
(26, NULL, 'Lang Trung Quân', '0909888808', '084188000808', NULL, 'travinh', NULL, NULL),
(27, NULL, 'Kỳ Nương Tử', '0909444404', '064244000444', NULL, 'vinhlong', NULL, NULL),
(28, NULL, 'Phạm Anh Tư', '0777444888', '064724000159', NULL, 'Bình Minh, Vĩnh Long', NULL, NULL),
(29, NULL, 'Võ Minh Duy', '0773888424', '084159222454', NULL, 'Trà Vinh', NULL, NULL),
(30, 45, 'longho', '0383277120', NULL, NULL, '', NULL, NULL),
(31, NULL, 'Cô Nhân Quý', '0773998235', '084201000684', NULL, 'Phường Nguyệt Hóa, tỉnh Vĩnh Long', NULL, NULL),
(32, NULL, 'Tuyết Thiên Nguyệt', '0774225616', '', NULL, 'Phường Long Châu, tỉnh Vĩnh Long', NULL, NULL),
(33, NULL, 'Võ Ngọc Minh', '0123456789', '084300000159', NULL, 'Xã Bình Minh, tỉnh Vĩnh Long', NULL, NULL),
(34, NULL, 'Cô Nhân Qúy', '0773998235', '084201000684', NULL, 'Khóm 2 Phường Nguyệt Hóa, tỉnh Vĩnh Long ( Khóm 2 Phường 7 - Trà Vinh cũ )', NULL, NULL),
(35, 46, 'Nhật Long', '0987557727', NULL, NULL, '', NULL, NULL),
(36, 47, 'demo', '0245651234', NULL, NULL, '', NULL, NULL),
(37, NULL, 'Cô Nhân Quý', '0773998235', '', NULL, 'Trà Vinh', NULL, NULL),
(38, NULL, 'Ngọc Liễu', '0367007511', '', NULL, 'Vĩnh Long', NULL, NULL),
(39, NULL, 'Cô Nhân Quý', '0773998235', '084201000684', NULL, 'Khóm 2 Phường Nguyệt Hóa, tỉnh Vĩnh Long', NULL, NULL),
(40, NULL, 'Bích Phụng', '0352016666', '0842304000112', NULL, 'Khóm 1 Phường Trà Vinh, tỉnh Vĩnh Long', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lich_su_trang_thai`
--

CREATE TABLE `lich_su_trang_thai` (
  `id` int(11) NOT NULL,
  `don_hang_id` int(11) NOT NULL,
  `trang_thai_moi` varchar(50) NOT NULL,
  `thoi_gian_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp(),
  `nguoi_thuc_hien` varchar(100) DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lich_su_trang_thai`
--

INSERT INTO `lich_su_trang_thai` (`id`, `don_hang_id`, `trang_thai_moi`, `thoi_gian_cap_nhat`, `nguoi_thuc_hien`, `ghi_chu`) VALUES
(1, 1, 'Chờ tiếp nhận', '2026-06-19 01:34:19', 'Lê Thị Tiếp', 'Đã  tiếp nhận và đóng gói tại quầy'),
(2, 1, 'Đã nhập kho', '2026-06-19 01:34:19', 'Lê Thị Tiếp', 'Hàng đã nhập kho, chờ khởi hành'),
(3, 1, 'Đang vận chuyển', '2026-06-19 01:34:19', 'Trần Văn Điều', 'Đã xếp hàng lên xe tải, đang vận chuyển'),
(4, 1, 'Đã đến kho', '2026-06-19 01:34:19', 'Nguyễn Văn Tài', 'Đã đến chi nhánh Vĩnh Long và nhập kho'),
(5, 1, 'Hoàn tất', '2026-06-23 07:30:17', 'Phạm Văn Giao', 'Cập nhật từ dashboard nhân viên giao hàng'),
(6, 4, 'Chờ tiếp nhận', '2026-06-26 06:03:51', 'Lê Thị Tiếp', 'Nhận hàng thành công tại quầy Trà Vinh'),
(7, 4, 'Đã nhập kho', '2026-06-26 06:03:51', 'Trần Văn Nhận', 'Đóng kiện và lưu kho tổng Trà Vinh'),
(8, 4, 'Đang vận chuyển', '2026-06-26 06:03:51', 'Lê Minh Nguyệt', 'Xếp lên xe tải đợt DOT_002 chạy đi Vĩnh Long'),
(9, 4, 'Đã đến kho dịch', '2026-06-26 06:03:51', 'Cố Thiên Minh', 'Hạ hàng tại kho đích Vĩnh Long'),
(10, 4, 'Đang giao hàng', '2026-06-26 06:03:51', 'Phạm Văn Giao', 'Bàn giao cho shipper chặng cuối đi phát'),
(11, 8, 'Chờ tiếp nhận', '2026-06-26 06:03:51', 'Võ Huyền Trân', 'Khách hàng tạo đơn online qua ứng dụng, chờ nhân viên đến lấy'),
(12, 1, 'hoan_tat', '2026-06-26 14:00:14', 'Shipper: Phạm Văn Giao', ''),
(13, 20, 'da_nhap_kho', '2026-07-02 15:01:48', 'Lê Thị Tiếp', 'Tiếp nhận và nhập kho tại quầy'),
(14, 20, 'dang_giao_hang', '2026-07-02 15:22:46', 'Shipper: Lê Thị Tiếp', ''),
(15, 20, 'dang_van_chuyen', '2026-07-03 05:04:00', 'Tài xế: Nguyễn Văn Tài', 'Bắt đầu vận chuyển theo đợt DOT_20260702110413'),
(16, 20, 'da_den_kho_dich', '2026-07-02 15:17:46', 'Tài xế: Nguyễn Văn Tài', 'Đã giao đến kho đích từ đợt DOT_20260702110413'),
(17, 1, 'dang_van_chuyen', '2026-06-19 14:00:00', 'Tài xế: Nguyễn Văn Tài', 'Bắt đầu vận chuyển theo đợt DOT_001'),
(18, 1, 'da_den_kho_dich', '2026-06-19 14:30:00', 'Tài xế: Nguyễn Văn Tài', 'Đã giao đến kho đích từ đợt DOT_001'),
(19, 21, 'da_nhap_kho', '2026-07-04 07:40:33', 'Lê Thị Tiếp', 'Tiếp nhận và nhập kho tại quầy'),
(20, 22, 'da_nhap_kho', '2026-07-05 22:57:49', 'Lê Thị Tiếp', 'Tiếp nhận và nhập kho tại quầy'),
(21, 23, 'da_nhap_kho', '2026-07-05 23:23:51', 'Lê Thị Tiếp', 'Tiếp nhận và nhập kho tại quầy'),
(22, 7, 'dang_van_chuyen', '2026-07-05 23:32:04', 'Tài xế: Nguyễn Văn Tài', 'Bắt đầu vận chuyển theo đợt DOT_20260705193130'),
(23, 21, 'dang_van_chuyen', '2026-07-05 23:32:04', 'Tài xế: Nguyễn Văn Tài', 'Bắt đầu vận chuyển theo đợt DOT_20260705193130'),
(24, 23, 'dang_van_chuyen', '2026-07-05 23:32:04', 'Tài xế: Nguyễn Văn Tài', 'Bắt đầu vận chuyển theo đợt DOT_20260705193130'),
(25, 7, 'da_den_kho_dich', '2026-07-05 23:32:40', 'Tài xế: Nguyễn Văn Tài', 'Đã giao đến kho đích từ đợt DOT_20260705193130'),
(26, 21, 'da_den_kho_dich', '2026-07-05 23:32:40', 'Tài xế: Nguyễn Văn Tài', 'Đã giao đến kho đích từ đợt DOT_20260705193130'),
(27, 23, 'da_den_kho_dich', '2026-07-05 23:32:40', 'Tài xế: Nguyễn Văn Tài', 'Đã giao đến kho đích từ đợt DOT_20260705193130'),
(28, 23, 'dang_giao_hang', '2026-07-05 23:36:49', 'Shipper: Phạm Văn Giao', ''),
(29, 7, 'dang_giao_hang', '2026-07-05 23:37:07', 'Shipper: Phạm Văn Giao', ''),
(30, 20, 'dang_giao_hang', '2026-07-05 23:37:18', 'Shipper: Phạm Văn Giao', ''),
(31, 11, 'dang_giao_hang', '2026-07-05 23:37:25', 'Shipper: Phạm Văn Giao', ''),
(32, 5, 'dang_giao_hang', '2026-07-05 23:37:31', 'Shipper: Phạm Văn Giao', ''),
(33, 2, 'dang_giao_hang', '2026-07-05 23:37:37', 'Shipper: Phạm Văn Giao', ''),
(34, 21, 'dang_giao_hang', '2026-07-05 23:38:24', 'Shipper: Phạm Văn Giao', ''),
(35, 23, 'hoan_tat', '2026-07-05 23:38:59', 'Shipper: Phạm Văn Giao', ''),
(36, 24, 'da_nhap_kho', '2026-07-06 01:49:18', 'Nguyễn Quản Trị', 'Tiếp nhận và nhập kho tại quầy'),
(37, 2, 'dang_van_chuyen', '2026-07-03 05:04:00', 'Tài xế: Nguyễn Văn Tài', 'Bắt đầu vận chuyển theo đợt DOT_20260702110413'),
(38, 2, 'da_den_kho_dich', '2026-07-05 23:32:37', 'Tài xế: Nguyễn Văn Tài', 'Đã giao đến kho đích từ đợt DOT_20260702110413'),
(39, 2, 'dang_giao_hang', '2026-07-21 09:15:00', 'Shipper: Phạm Văn Giao', ''),
(40, 5, 'dang_van_chuyen', '2026-06-26 15:00:00', 'Tài xế: Lý Thập Nhất', 'Bắt đầu vận chuyển theo đợt DOT_002'),
(41, 5, 'da_den_kho_dich', '2026-07-05 23:32:31', 'Tài xế: Lý Thập Nhất', 'Đã giao đến kho đích từ đợt DOT_002'),
(42, 4, 'dang_van_chuyen', '2026-06-26 15:00:00', 'Tài xế: Lý Thập Nhất', 'Bắt đầu vận chuyển theo đợt DOT_002'),
(43, 4, 'da_den_kho_dich', '2026-06-26 15:30:00', 'Tài xế: Lý Thập Nhất', 'Đã giao đến kho đích từ đợt DOT_002'),
(44, 11, 'dang_van_chuyen', '2026-06-26 15:00:00', 'Tài xế: Lý Thập Nhất', 'Bắt đầu vận chuyển theo đợt DOT_002'),
(45, 11, 'da_den_kho_dich', '2026-07-05 23:32:25', 'Tài xế: Lý Thập Nhất', 'Đã giao đến kho đích từ đợt DOT_002'),
(46, 25, 'da_nhap_kho', '2026-07-21 09:32:48', 'Lê Thị Tiếp', 'Tiếp nhận và nhập kho tại quầy'),
(47, 22, 'dang_van_chuyen', '2026-07-21 09:38:07', 'Tài xế: Nguyễn Văn Tài', 'Bắt đầu vận chuyển theo đợt DOT_20260721053413'),
(48, 24, 'dang_van_chuyen', '2026-07-21 09:38:07', 'Tài xế: Nguyễn Văn Tài', 'Bắt đầu vận chuyển theo đợt DOT_20260721053413');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loai_hang_hoa`
--

CREATE TABLE `loai_hang_hoa` (
  `id` int(11) NOT NULL,
  `ten_loai_hang` varchar(100) NOT NULL,
  `he_so_phu_thu` decimal(3,2) NOT NULL DEFAULT 1.00,
  `mo_ta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `loai_hang_hoa`
--

INSERT INTO `loai_hang_hoa` (`id`, `ten_loai_hang`, `he_so_phu_thu`, `mo_ta`) VALUES
(1, 'Hàng phổ thông', '1.00', 'Quần áo, sách vở, đồ gia dụng khô, không yêu cầu bảo quản đặc biệt'),
(2, 'Hàng thực phẩm / Đồ tươi sống', '1.05', 'Trái cây, đồ ăn, hàng cần vận chuyển nhanh và ưu tiên'),
(3, 'Hàng cồng kềnh / Khối lượng lớn', '1.10', 'Máy móc, tủ lạnh, linh kiện lớn chiếm nhiều diện tích xe'),
(4, 'Hàng dễ vỡ / Giá trị cao', '1.15', 'Thiết bị điện tử, đồ thủy tinh, cần bọc lót chống sốc cẩn thận'),
(5, 'Hàng dễ vỡ', '1.50', ''),
(6, 'Hàng hóa thông thường', '1.00', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `id` int(11) NOT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `trang_thai` tinyint(4) DEFAULT 0 COMMENT '0: Cho xac minh OTP, 1: Dang hoat dong, 2: Bi khoa',
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoi_dung`
--

INSERT INTO `nguoi_dung` (`id`, `so_dien_thoai`, `mat_khau`, `ho_ten`, `trang_thai`, `ngay_tao`) VALUES
(1, '0900000001', 'e10adc3949ba59abbe56e057f20f883e', 'Nguyễn Quản Trị', 1, '2026-06-19 01:34:18'),
(2, '0900000002', 'e10adc3949ba59abbe56e057f20f883e', 'Lê Thị Tiếp', 1, '2026-06-19 01:34:18'),
(3, '0900000003', 'e10adc3949ba59abbe56e057f20f883e', 'Trần Văn Điều', 1, '2026-06-19 01:34:18'),
(4, '0909123456', 'e10adc3949ba59abbe56e057f20f883e', 'Nguyễn Văn Tài', 1, '2026-06-19 01:34:18'),
(5, '0911112222', 'e10adc3949ba59abbe56e057f20f883e', 'Phạm Văn Giao', 1, '2026-06-19 01:34:18'),
(6, '0911222333', '$2y$10$cJ6PrITAzO7wivD/ZAGkS.XTIxraLreJHMLOOYwRsjXmSFlsqiYLS', 'Nguyễn Văn Gửi', 1, '2026-06-19 03:06:59'),
(8, '0909000001', '$2y$10$qCXWu2Nqx237dcwu0FOUKelKaGpuKQG/rk99rXFjuv./XK.1KXUe6', 'Trần Quân', 1, '2026-06-22 13:53:14'),
(9, '0773998235', '$2y$10$7obf4jcvFm0aJ6uzhYoI8uO1NPBpue4eFuJoZkLxIlE/.rhRLPDcG', 'Cô Nhân Quý', 1, '2026-06-23 07:33:12'),
(11, '0909000002', 'e10adc3949ba59abbe56e057f20f883e', 'Trần Văn Nhận', 1, '2026-06-26 05:33:22'),
(12, '0909000003', 'e10adc3949ba59abbe56e057f20f883e', 'Võ Huyền Trân', 1, '2026-06-26 05:33:22'),
(13, '0909000004', 'e10adc3949ba59abbe56e057f20f883e', 'Lê Minh Nguyệt', 1, '2026-06-26 05:34:59'),
(14, '0909000005', 'e10adc3949ba59abbe56e057f20f883e', 'Cố Thiên Minh', 1, '2026-06-26 05:34:59'),
(15, '0909234567', 'e10adc3949ba59abbe56e057f20f883e', 'Lý Thập Nhất', 1, '2026-06-26 05:35:23'),
(16, '0909345678', 'e10adc3949ba59abbe56e057f20f883e', 'Trần Nhị', 1, '2026-06-26 05:35:23'),
(17, '0909456789', 'e10adc3949ba59abbe56e057f20f883e', 'Lý Văn Tam', 1, '2026-06-26 05:35:23'),
(18, '0909000006', 'e10adc3949ba59abbe56e057f20f883e', 'Lý Tứ', 1, '2026-06-26 05:35:23'),
(19, '0909000007', 'e10adc3949ba59abbe56e057f20f883e', 'Trần Văn Ngũ', 1, '2026-06-26 05:35:23'),
(20, '0909000008', 'e10adc3949ba59abbe56e057f20f883e', 'Nguyễn Văn Lục', 1, '2026-06-26 05:35:23'),
(21, '0909000009', 'e10adc3949ba59abbe56e057f20f883e', 'Thất Dạ', 1, '2026-06-26 05:35:23'),
(22, '0909000010', 'e10adc3949ba59abbe56e057f20f883e', 'Lý Bát', 1, '2026-06-26 05:35:23'),
(23, '0909000011', 'e10adc3949ba59abbe56e057f20f883e', 'Thiên Cửu', 1, '2026-06-26 05:35:23'),
(24, '0909000012', 'e10adc3949ba59abbe56e057f20f883e', 'Kiên Văn Mười', 1, '2026-06-26 05:35:23'),
(25, '0909000013', 'e10adc3949ba59abbe56e057f20f883e', 'Cố Thập Nhất', 1, '2026-06-26 05:35:23'),
(26, '0901000001', 'e10adc3949ba59abbe56e057f20f883e', 'Dạ Nguyệt', 1, '2026-06-26 05:35:31'),
(27, '0901000002', 'e10adc3949ba59abbe56e057f20f883e', 'Dạ Đàm', 1, '2026-06-26 05:35:31'),
(28, '0901000003', 'e10adc3949ba59abbe56e057f20f883e', 'Tịch Dạ', 1, '2026-06-26 05:35:31'),
(29, '0901000004', 'e10adc3949ba59abbe56e057f20f883e', 'Võ Linh Nguyệt', 1, '2026-06-26 05:35:31'),
(30, '0901000005', 'e10adc3949ba59abbe56e057f20f883e', 'Phạm Không Minh', 1, '2026-06-26 05:35:31'),
(31, '0901000006', 'e10adc3949ba59abbe56e057f20f883e', 'Lý Thủy Vượng', 1, '2026-06-26 05:35:31'),
(32, '0901000007', 'e10adc3949ba59abbe56e057f20f883e', 'Lê Phi', 1, '2026-06-26 05:35:31'),
(33, '0901000008', 'e10adc3949ba59abbe56e057f20f883e', 'Phi Vũ', 1, '2026-06-26 05:35:31'),
(34, '0901000009', 'e10adc3949ba59abbe56e057f20f883e', 'Trần Thất Thất', 1, '2026-06-26 05:35:31'),
(35, '0901000010', 'e10adc3949ba59abbe56e057f20f883e', 'Lý Thất Dạ', 1, '2026-06-26 05:35:31'),
(36, '0901000011', 'e10adc3949ba59abbe56e057f20f883e', 'Dạ Trường Minh', 1, '2026-06-26 05:35:31'),
(37, '0901000012', 'e10adc3949ba59abbe56e057f20f883e', 'Âu Dương Không', 1, '2026-06-26 05:35:31'),
(38, '0901000013', 'e10adc3949ba59abbe56e057f20f883e', 'Kiều Hoàng Nguyệt', 1, '2026-06-26 05:35:31'),
(39, '0901000014', 'e10adc3949ba59abbe56e057f20f883e', 'Dã Âu Tử', 1, '2026-06-26 05:35:31'),
(40, '0901000015', 'e10adc3949ba59abbe56e057f20f883e', 'Diệp Tử Liên', 1, '2026-06-26 05:35:31'),
(41, '0901000016', 'e10adc3949ba59abbe56e057f20f883e', 'Phan Thanh Liên', 1, '2026-06-26 05:35:31'),
(42, '0901000017', 'e10adc3949ba59abbe56e057f20f883e', 'Huỳnh Kim Vũ', 1, '2026-06-26 05:35:31'),
(43, '0901000018', 'e10adc3949ba59abbe56e057f20f883e', 'Trương Thiển Nguyệt', 1, '2026-06-26 05:35:31'),
(44, '0901000019', 'e10adc3949ba59abbe56e057f20f883e', 'Lưu Bích Vũ', 1, '2026-06-26 05:35:31'),
(45, '0383277120', '$2y$10$tzRmZ7OAT9uxmhf22p.b..CLRqlh8rCKdM8e8hcYkSzZSbTmqkayq', 'longho', 1, '2026-07-04 08:30:56'),
(46, '0987557727', '$2y$10$0TTjXSvuXlVNlOgAOguq8ufeIQvG2MVLsf5gHPGWWwV4CdehnBNFu', 'Nhật Long', 1, '2026-07-06 01:04:09'),
(47, '0245651234', '$2y$10$ojjSkgjd1yrv5b.YyNEmleec.Yy13nwcN9/3FxxHawSbugGuST3ZW', 'demo', 1, '2026-07-06 01:43:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoi_giao_hang`
--

CREATE TABLE `nguoi_giao_hang` (
  `id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) NOT NULL,
  `chi_nhanh_id` int(11) NOT NULL,
  `khu_vuc_phu_trach` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoi_giao_hang`
--

INSERT INTO `nguoi_giao_hang` (`id`, `nguoi_dung_id`, `chi_nhanh_id`, `khu_vuc_phu_trach`) VALUES
(1, 5, 2, 'Trung tâm thành phố Vĩnh Long'),
(2, 26, 1, 'Trung tâm TP Trà Vinh'),
(3, 27, 1, 'Trung tâm TP Trà Vinh'),
(4, 28, 1, 'Trung tâm TP Trà Vinh'),
(5, 29, 1, 'Trung tâm TP Trà Vinh'),
(6, 30, 1, 'Trung tâm TP Trà Vinh'),
(7, 31, 1, 'Trung tâm TP Trà Vinh'),
(8, 32, 1, 'Trung tâm TP Trà Vinh'),
(9, 33, 1, 'Trung tâm TP Trà Vinh'),
(10, 34, 1, 'Trung tâm TP Trà Vinh'),
(11, 35, 1, 'Trung tâm TP Trà Vinh'),
(12, 36, 2, 'Trung tâm thành phố Vĩnh Long'),
(13, 37, 2, 'Trung tâm thành phố Vĩnh Long'),
(14, 38, 2, 'Trung tâm thành phố Vĩnh Long'),
(15, 39, 2, 'Trung tâm thành phố Vĩnh Long'),
(16, 40, 2, 'Trung tâm thành phố Vĩnh Long'),
(17, 41, 2, 'Trung tâm thành phố Vĩnh Long'),
(18, 42, 2, 'Trung tâm thành phố Vĩnh Long'),
(19, 43, 2, 'Trung tâm thành phố Vĩnh Long'),
(20, 44, 2, 'Trung tâm thành phố Vĩnh Long');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhan_vien`
--

CREATE TABLE `nhan_vien` (
  `id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) NOT NULL,
  `chi_nhanh_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nhan_vien`
--

INSERT INTO `nhan_vien` (`id`, `nguoi_dung_id`, `chi_nhanh_id`) VALUES
(1, 2, 1),
(2, 3, 1),
(3, 11, 1),
(4, 12, 1),
(5, 13, 1),
(6, 14, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhat_ky_gui_tin`
--

CREATE TABLE `nhat_ky_gui_tin` (
  `id` int(11) NOT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `noi_dung_tin` text NOT NULL,
  `loai_tin` enum('otp_dang_ky','otp_quen_mk','thong_bao_giao_hang') NOT NULL,
  `trang_thai_api` enum('thanh_cong','that_bai') NOT NULL,
  `ma_phan_hoi_api` varchar(100) DEFAULT NULL COMMENT 'Lưu ID tin nhắn hoặc thông báo lỗi từ Textbee',
  `thoi_gian_gui` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tai_xe`
--

CREATE TABLE `tai_xe` (
  `id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) NOT NULL,
  `xe_van_tai_id` int(11) NOT NULL,
  `loai_bang_lai` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tai_xe`
--

INSERT INTO `tai_xe` (`id`, `nguoi_dung_id`, `xe_van_tai_id`, `loai_bang_lai`) VALUES
(1, 4, 1, 'Bằng C'),
(2, 15, 1, 'Bằng C'),
(3, 16, 2, 'Bằng C'),
(4, 17, 2, 'Bằng C'),
(5, 18, 3, 'Bằng C'),
(6, 19, 3, 'Bằng C'),
(7, 20, 4, 'Bằng C'),
(8, 21, 4, 'Bằng C'),
(9, 22, 5, 'Bằng C'),
(10, 23, 5, 'Bằng C'),
(11, 24, 6, 'Bằng C'),
(12, 25, 6, 'Bằng C');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tuyen_duong`
--

CREATE TABLE `tuyen_duong` (
  `id` int(11) NOT NULL,
  `chi_nhanh_di_id` int(11) NOT NULL,
  `chi_nhanh_den_id` int(11) NOT NULL,
  `khoang_cach_km` decimal(10,2) NOT NULL,
  `thgian_vanchuyen_uoctinh` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tuyen_duong`
--

INSERT INTO `tuyen_duong` (`id`, `chi_nhanh_di_id`, `chi_nhanh_den_id`, `khoang_cach_km`, `thgian_vanchuyen_uoctinh`) VALUES
(1, 1, 2, '60.00', 90),
(2, 2, 1, '60.00', 90);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vai_tro`
--

CREATE TABLE `vai_tro` (
  `id` int(11) NOT NULL,
  `ten_vai_tro` varchar(50) NOT NULL,
  `mo_ta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `vai_tro`
--

INSERT INTO `vai_tro` (`id`, `ten_vai_tro`, `mo_ta`) VALUES
(1, 'admin', 'Quản trị viên'),
(2, 'nhan_vien_tiep_nhan', 'NV Tiếp nhận - Tạo đơn tại quầy'),
(3, 'nhan_vien_dieu_phoi', 'NV Điều phối - Lập tuyến và tạo đợt'),
(4, 'tai_xe', 'Tài xế - NV Trung chuyển liên kho'),
(5, 'shipper', 'Người giao hàng');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vai_tro_nguoi_dung`
--

CREATE TABLE `vai_tro_nguoi_dung` (
  `id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) NOT NULL,
  `vai_tro_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `vai_tro_nguoi_dung`
--

INSERT INTO `vai_tro_nguoi_dung` (`id`, `nguoi_dung_id`, `vai_tro_id`) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 3),
(4, 4, 4),
(7, 11, 2),
(8, 12, 2),
(9, 13, 3),
(10, 14, 3),
(11, 15, 4),
(12, 16, 4),
(13, 17, 4),
(14, 18, 4),
(15, 19, 4),
(16, 20, 4),
(17, 21, 4),
(18, 22, 4),
(19, 23, 4),
(20, 24, 4),
(21, 25, 4),
(22, 26, 5),
(23, 27, 5),
(24, 28, 5),
(25, 29, 5),
(26, 30, 5),
(27, 31, 5),
(28, 32, 5),
(29, 33, 5),
(30, 34, 5),
(31, 35, 5),
(32, 36, 5),
(33, 37, 5),
(34, 38, 5),
(35, 39, 5),
(36, 40, 5),
(37, 41, 5),
(38, 42, 5),
(39, 43, 5),
(40, 44, 5),
(41, 8, 1),
(42, 5, 5);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `xac_minh_otp`
--

CREATE TABLE `xac_minh_otp` (
  `id` int(11) NOT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `ma_otp` varchar(10) NOT NULL,
  `loai_hanh_dong` enum('dang_ky','khoi_phuc_mat_khau') NOT NULL,
  `thoi_gian_het_han` datetime NOT NULL,
  `trang_thai` tinyint(4) DEFAULT 0 COMMENT '0: Chua su dung, 1: Da xac minh, 2: Het han',
  `thoi_gian_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `xac_minh_otp`
--

INSERT INTO `xac_minh_otp` (`id`, `so_dien_thoai`, `ma_otp`, `loai_hanh_dong`, `thoi_gian_het_han`, `trang_thai`, `thoi_gian_tao`) VALUES
(1, '0773998235', '733709', 'khoi_phuc_mat_khau', '2026-07-03 05:45:42', 1, '2026-07-03 09:40:42'),
(2, '0383277120', '757035', 'khoi_phuc_mat_khau', '2026-07-04 04:36:05', 2, '2026-07-04 08:31:04'),
(3, '0383277120', '925243', 'khoi_phuc_mat_khau', '2026-07-04 04:39:15', 1, '2026-07-04 08:34:15');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `xe_van_tai`
--

CREATE TABLE `xe_van_tai` (
  `id` int(11) NOT NULL,
  `bien_so_xe` varchar(20) NOT NULL,
  `trong_tai_toi_da_kg` decimal(10,2) NOT NULL,
  `loai_xe` varchar(50) NOT NULL,
  `trang_thai_hoat_dong` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `xe_van_tai`
--

INSERT INTO `xe_van_tai` (`id`, `bien_so_xe`, `trong_tai_toi_da_kg`, `loai_xe`, `trang_thai_hoat_dong`) VALUES
(1, '84C-123.45', '1500.00', 'Xe tải nhỏ', 1),
(2, '84A-999.99', '500.00', 'Xe tải nhỏ', 1),
(3, '64C-002.22', '3500.00', 'Xe tải trung', 1),
(4, '64C-003.33', '3500.00', 'Xe tải trung', 1),
(5, '84C-004.44', '2500.00', 'Xe tải nhỏ', 1),
(6, '84C-005.55', '5000.00', 'Xe tải trung', 1),
(7, '84C-006.66', '2000.00', 'Xe tải nhỏ', 1);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bang_gia_cuoc`
--
ALTER TABLE `bang_gia_cuoc`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `bao_cao_su_co`
--
ALTER TABLE `bao_cao_su_co`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_suco_donhang` (`don_hang_id`),
  ADD KEY `fk_suco_dotvanchuyen` (`dot_van_chuyen_id`),
  ADD KEY `fk_suco_nguoidung` (`nguoi_bao_cao_id`);

--
-- Chỉ mục cho bảng `chi_nhanh`
--
ALTER TABLE `chi_nhanh`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_chi_nhanh` (`ma_chi_nhanh`);

--
-- Chỉ mục cho bảng `chi_tiet_dot_van_chuyen`
--
ALTER TABLE `chi_tiet_dot_van_chuyen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_chitietdot_dotvanchuyen` (`dot_van_chuyen_id`),
  ADD KEY `fk_chitietdot_donhang` (`don_hang_id`);

--
-- Chỉ mục cho bảng `chi_tiet_hang_hoa`
--
ALTER TABLE `chi_tiet_hang_hoa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_chitiet_donhang` (`don_hang_id`),
  ADD KEY `fk_chitiet_loaihang` (`loai_hang_hoa_id`);

--
-- Chỉ mục cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_don_hang` (`ma_don_hang`),
  ADD KEY `fk_donhang_khachgui` (`khach_hang_gui_id`),
  ADD KEY `fk_donhang_khachnhan` (`khach_hang_nhan_id`),
  ADD KEY `fk_donhang_chinhanhgui` (`chi_nhanh_gui_id`),
  ADD KEY `fk_donhang_chinhanhnhan` (`chi_nhanh_nhan_id`);

--
-- Chỉ mục cho bảng `dot_van_chuyen`
--
ALTER TABLE `dot_van_chuyen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_dot_van_chuyen` (`ma_dot_van_chuyen`),
  ADD KEY `fk_dotvanchuyen_tuyenduong` (`tuyen_duong_id`),
  ADD KEY `fk_dotvanchuyen_taixe` (`tai_xe_id`),
  ADD KEY `fk_dotvanchuyen_xevantai` (`xe_van_tai_id`);

--
-- Chỉ mục cho bảng `giao_hang_tan_noi`
--
ALTER TABLE `giao_hang_tan_noi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_giaohang_donhang` (`don_hang_id`),
  ADD KEY `fk_giaohang_nguoigiaohang` (`nguoi_giao_hang_id`);

--
-- Chỉ mục cho bảng `hanh_trinh_xe`
--
ALTER TABLE `hanh_trinh_xe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_luong_bando` (`loai_hanh_trinh`,`ma_dinh_danh_luong`);

--
-- Chỉ mục cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_hoadon_donhang` (`don_hang_id`);

--
-- Chỉ mục cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sdt_khach_hang` (`so_dien_thoai`),
  ADD KEY `fk_khachhang_nguoidung` (`nguoi_dung_id`);

--
-- Chỉ mục cho bảng `lich_su_trang_thai`
--
ALTER TABLE `lich_su_trang_thai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lichsu_donhang` (`don_hang_id`);

--
-- Chỉ mục cho bảng `loai_hang_hoa`
--
ALTER TABLE `loai_hang_hoa`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `so_dien_thoai` (`so_dien_thoai`);

--
-- Chỉ mục cho bảng `nguoi_giao_hang`
--
ALTER TABLE `nguoi_giao_hang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nguoi_dung_id` (`nguoi_dung_id`),
  ADD KEY `fk_nguoigiaohang_chinhanh` (`chi_nhanh_id`);

--
-- Chỉ mục cho bảng `nhan_vien`
--
ALTER TABLE `nhan_vien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nguoi_dung_id` (`nguoi_dung_id`),
  ADD KEY `fk_nhanvien_chinhanh` (`chi_nhanh_id`);

--
-- Chỉ mục cho bảng `nhat_ky_gui_tin`
--
ALTER TABLE `nhat_ky_gui_tin`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `tai_xe`
--
ALTER TABLE `tai_xe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nguoi_dung_id` (`nguoi_dung_id`),
  ADD KEY `fk_taixe_xevantai` (`xe_van_tai_id`);

--
-- Chỉ mục cho bảng `tuyen_duong`
--
ALTER TABLE `tuyen_duong`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tuyenduong_chinhanhdi` (`chi_nhanh_di_id`),
  ADD KEY `fk_tuyenduong_chinhanhden` (`chi_nhanh_den_id`);

--
-- Chỉ mục cho bảng `vai_tro`
--
ALTER TABLE `vai_tro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ten_vai_tro` (`ten_vai_tro`);

--
-- Chỉ mục cho bảng `vai_tro_nguoi_dung`
--
ALTER TABLE `vai_tro_nguoi_dung`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vaitronguoidung_nguoidung` (`nguoi_dung_id`),
  ADD KEY `fk_vaitronguoidung_vaitro` (`vai_tro_id`);

--
-- Chỉ mục cho bảng `xac_minh_otp`
--
ALTER TABLE `xac_minh_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sdt_otp` (`so_dien_thoai`,`ma_otp`);

--
-- Chỉ mục cho bảng `xe_van_tai`
--
ALTER TABLE `xe_van_tai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bien_so_xe` (`bien_so_xe`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bang_gia_cuoc`
--
ALTER TABLE `bang_gia_cuoc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `bao_cao_su_co`
--
ALTER TABLE `bao_cao_su_co`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `chi_nhanh`
--
ALTER TABLE `chi_nhanh`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `chi_tiet_dot_van_chuyen`
--
ALTER TABLE `chi_tiet_dot_van_chuyen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `chi_tiet_hang_hoa`
--
ALTER TABLE `chi_tiet_hang_hoa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `dot_van_chuyen`
--
ALTER TABLE `dot_van_chuyen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `giao_hang_tan_noi`
--
ALTER TABLE `giao_hang_tan_noi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `hanh_trinh_xe`
--
ALTER TABLE `hanh_trinh_xe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT cho bảng `lich_su_trang_thai`
--
ALTER TABLE `lich_su_trang_thai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT cho bảng `loai_hang_hoa`
--
ALTER TABLE `loai_hang_hoa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT cho bảng `nguoi_giao_hang`
--
ALTER TABLE `nguoi_giao_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `nhan_vien`
--
ALTER TABLE `nhan_vien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `nhat_ky_gui_tin`
--
ALTER TABLE `nhat_ky_gui_tin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `tai_xe`
--
ALTER TABLE `tai_xe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `tuyen_duong`
--
ALTER TABLE `tuyen_duong`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `vai_tro`
--
ALTER TABLE `vai_tro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `vai_tro_nguoi_dung`
--
ALTER TABLE `vai_tro_nguoi_dung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT cho bảng `xac_minh_otp`
--
ALTER TABLE `xac_minh_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `xe_van_tai`
--
ALTER TABLE `xe_van_tai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bao_cao_su_co`
--
ALTER TABLE `bao_cao_su_co`
  ADD CONSTRAINT `fk_suco_donhang` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_suco_dotvanchuyen` FOREIGN KEY (`dot_van_chuyen_id`) REFERENCES `dot_van_chuyen` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_suco_nguoidung` FOREIGN KEY (`nguoi_bao_cao_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `chi_tiet_dot_van_chuyen`
--
ALTER TABLE `chi_tiet_dot_van_chuyen`
  ADD CONSTRAINT `fk_chitietdot_donhang` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chitietdot_dotvanchuyen` FOREIGN KEY (`dot_van_chuyen_id`) REFERENCES `dot_van_chuyen` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `chi_tiet_hang_hoa`
--
ALTER TABLE `chi_tiet_hang_hoa`
  ADD CONSTRAINT `fk_chitiet_donhang` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chitiet_loaihang` FOREIGN KEY (`loai_hang_hoa_id`) REFERENCES `loai_hang_hoa` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  ADD CONSTRAINT `fk_donhang_chinhanhgui` FOREIGN KEY (`chi_nhanh_gui_id`) REFERENCES `chi_nhanh` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_donhang_chinhanhnhan` FOREIGN KEY (`chi_nhanh_nhan_id`) REFERENCES `chi_nhanh` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_donhang_khachgui` FOREIGN KEY (`khach_hang_gui_id`) REFERENCES `khach_hang` (`id`),
  ADD CONSTRAINT `fk_donhang_khachnhan` FOREIGN KEY (`khach_hang_nhan_id`) REFERENCES `khach_hang` (`id`);

--
-- Các ràng buộc cho bảng `dot_van_chuyen`
--
ALTER TABLE `dot_van_chuyen`
  ADD CONSTRAINT `fk_dotvanchuyen_taixe` FOREIGN KEY (`tai_xe_id`) REFERENCES `tai_xe` (`id`),
  ADD CONSTRAINT `fk_dotvanchuyen_tuyenduong` FOREIGN KEY (`tuyen_duong_id`) REFERENCES `tuyen_duong` (`id`),
  ADD CONSTRAINT `fk_dotvanchuyen_xevantai` FOREIGN KEY (`xe_van_tai_id`) REFERENCES `xe_van_tai` (`id`);

--
-- Các ràng buộc cho bảng `giao_hang_tan_noi`
--
ALTER TABLE `giao_hang_tan_noi`
  ADD CONSTRAINT `fk_giaohang_donhang` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_giaohang_nguoigiaohang` FOREIGN KEY (`nguoi_giao_hang_id`) REFERENCES `nguoi_giao_hang` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD CONSTRAINT `fk_hoadon_donhang` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  ADD CONSTRAINT `fk_khachhang_nguoidung` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `lich_su_trang_thai`
--
ALTER TABLE `lich_su_trang_thai`
  ADD CONSTRAINT `fk_lichsu_donhang` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `nguoi_giao_hang`
--
ALTER TABLE `nguoi_giao_hang`
  ADD CONSTRAINT `fk_nguoigiaohang_chinhanh` FOREIGN KEY (`chi_nhanh_id`) REFERENCES `chi_nhanh` (`id`),
  ADD CONSTRAINT `fk_nguoigiaohang_nguoidung` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `nhan_vien`
--
ALTER TABLE `nhan_vien`
  ADD CONSTRAINT `fk_nhanvien_chinhanh` FOREIGN KEY (`chi_nhanh_id`) REFERENCES `chi_nhanh` (`id`),
  ADD CONSTRAINT `fk_nhanvien_nguoidung` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `tai_xe`
--
ALTER TABLE `tai_xe`
  ADD CONSTRAINT `fk_taixe_nguoidung` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_taixe_xevantai` FOREIGN KEY (`xe_van_tai_id`) REFERENCES `xe_van_tai` (`id`);

--
-- Các ràng buộc cho bảng `tuyen_duong`
--
ALTER TABLE `tuyen_duong`
  ADD CONSTRAINT `fk_tuyenduong_chinhanhden` FOREIGN KEY (`chi_nhanh_den_id`) REFERENCES `chi_nhanh` (`id`),
  ADD CONSTRAINT `fk_tuyenduong_chinhanhdi` FOREIGN KEY (`chi_nhanh_di_id`) REFERENCES `chi_nhanh` (`id`);

--
-- Các ràng buộc cho bảng `vai_tro_nguoi_dung`
--
ALTER TABLE `vai_tro_nguoi_dung`
  ADD CONSTRAINT `fk_vaitronguoidung_nguoidung` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vaitronguoidung_vaitro` FOREIGN KEY (`vai_tro_id`) REFERENCES `vai_tro` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
