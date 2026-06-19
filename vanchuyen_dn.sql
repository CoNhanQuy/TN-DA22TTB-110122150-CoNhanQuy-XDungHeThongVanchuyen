-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 19, 2026 lúc 03:36 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `vanchuyen_dn`
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
  `gia_theo_moi_ki_lo_met` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bang_gia_cuoc`
--

INSERT INTO `bang_gia_cuoc` (`id`, `khoi_luong_tu_kg`, `khoi_luong_den_kg`, `gia_co_ban`, `gia_theo_moi_ki_lo_met`) VALUES
(1, 0.00, 4.99, 30000.00, 5000.00),
(2, 5.00, 19.99, 60000.00, 4000.00),
(3, 20.00, 999.00, 120000.00, 2000.00);

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
(1, 'CN_TRAVINH', 'Chi nhanh Tra Vinh', '123 Le Loi, Phuong 1, TP Tra Vinh', '02943123456', 106.34654300, 9.93456300),
(2, 'CN_VINHLONG', 'Chi nhanh Vinh Long', '456 Nguyen Hue, Phuong 2, TP Vinh Long', '02703456789', 105.96443200, 10.25345200);

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
(1, 1, 1, 'da_giao_kho_dich');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_hang_hoa`
--

CREATE TABLE `chi_tiet_hang_hoa` (
  `id` int(11) NOT NULL,
  `don_hang_id` int(11) NOT NULL,
  `ten_mat_hang` varchar(255) NOT NULL,
  `so_luong` int(11) NOT NULL DEFAULT 1,
  `khoi_luong_uoc_tinh_kg` decimal(10,2) DEFAULT 0.00,
  `ghi_chu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_hang_hoa`
--

INSERT INTO `chi_tiet_hang_hoa` (`id`, `don_hang_id`, `ten_mat_hang`, `so_luong`, `khoi_luong_uoc_tinh_kg`, `ghi_chu`) VALUES
(1, 1, 'Thung dung Linh kien May tinh', 1, 2.50, 'Hang gia tri cao'),
(2, 1, 'Day cap sac bọc chống sốc', 2, 0.50, NULL),
(3, 2, 'Thung Cam Sanh Tra Vinh', 1, 12.00, 'Hang thuc pham can di nhanh');

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
(1, 'DH001', 1, 2, 1, 2, 3.00, 150000.00, 150000.00, 'da_den_kho_dich', '2026-06-19 01:34:19'),
(2, 'DH002', 1, 2, 1, 2, 12.00, 250000.00, 0.00, 'da_nhap_kho', '2026-06-19 01:34:19');

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
(1, 'DOT_001', 1, 1, 1, 'da_den_kho_nhan', '2026-06-19 07:00:00');

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
  `ngay_gio_giao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `giao_hang_tan_noi`
--

INSERT INTO `giao_hang_tan_noi` (`id`, `don_hang_id`, `nguoi_giao_hang_id`, `trang_thai_giao_hang`, `nguoi_nhan_thuc_te`, `ngay_gio_giao`) VALUES
(1, 1, 1, 'dang_giao', NULL, NULL);

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
(1, 'shipper_giao_khach', 1, 10.25123400, 105.96123400, '2026-06-19 01:34:19');

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
(1, 1, 0.00, 'qr_code', 'da_thanh_toan'),
(2, 2, 250000.00, 'tien_mat', 'chua_thanh_toan');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khach_hang`
--

CREATE TABLE `khach_hang` (
  `id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) DEFAULT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `so_can_cuoc_cong_dan` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `dia_chi` text NOT NULL,
  `toa_do_kinh_do` decimal(11,8) DEFAULT NULL,
  `toa_do_vi_do` decimal(10,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khach_hang`
--

INSERT INTO `khach_hang` (`id`, `nguoi_dung_id`, `ho_ten`, `so_dien_thoai`, `so_can_cuoc_cong_dan`, `email`, `dia_chi`, `toa_do_kinh_do`, `toa_do_vi_do`) VALUES
(1, NULL, 'Nguyen Van Khach Gui', '0911222333', '084001002003', 'khachgui@gmail.com', 'Phuong 4, TP Tra Vinh', 106.34000000, 9.93000000),
(2, NULL, 'Le Thi Khach Nhan', '0944555666', '0840010020004', 'khachnhan@gmail.com', 'Phuong 1, TP Vinh Long', 105.96000000, 10.25000000);

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
(1, 1, 'cho_tiep_nhan', '2026-06-19 01:34:19', 'Le Thi Tiep Nhan', 'Da tiep nhan va dong goi tai quay'),
(2, 1, 'da_nhap_kho', '2026-06-19 01:34:19', 'Le Thi Tiep Nhan', 'Hang da nhap kho cho khoi hanh'),
(3, 1, 'dang_van_chuyen', '2026-06-19 01:34:19', 'Tran Van Dieu Phoi', 'Da xep len xe tai va bat dau roi ben'),
(4, 1, 'da_den_kho_dich', '2026-06-19 01:34:19', 'Nguyen Van Tai', 'Xe tai da den chi nhanh Vinh Long va ha hang');

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
(1, '0900000001', 'e10adc3949ba59abbe56e057f20f883e', 'Nguyen Admin', 1, '2026-06-19 01:34:18'),
(2, '0900000002', 'e10adc3949ba59abbe56e057f20f883e', 'Le Thi Tiep Nhan', 1, '2026-06-19 01:34:18'),
(3, '0900000003', 'e10adc3949ba59abbe56e057f20f883e', 'Tran Van Dieu Phoi', 1, '2026-06-19 01:34:18'),
(4, '0909123456', 'e10adc3949ba59abbe56e057f20f883e', 'Nguyen Van Tai ', 1, '2026-06-19 01:34:18'),
(5, '0901112221', 'e10adc3949ba59abbe56e057f20f883e', 'Pham Van Giao', 1, '2026-06-19 01:34:18');

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
(1, 5, 2, 'Trung tam Thanh pho Vinh Long');

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
(2, 3, 1);

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
(1, 4, 1, 'Bang C');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tuyen_duong`
--

CREATE TABLE `tuyen_duong` (
  `id` int(11) NOT NULL,
  `chi_nhanh_di_id` int(11) NOT NULL,
  `chi_nhanh_den_id` int(11) NOT NULL,
  `khoang_cach_ki_lo_met` decimal(10,2) NOT NULL,
  `thoi_gian_di_chuyen_uoc_tinh_phut` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tuyen_duong`
--

INSERT INTO `tuyen_duong` (`id`, `chi_nhanh_di_id`, `chi_nhanh_den_id`, `khoang_cach_ki_lo_met`, `thoi_gian_di_chuyen_uoc_tinh_phut`) VALUES
(1, 1, 2, 60.00, 90);

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
(1, 'admin', 'Quan tri vien - Toan quyen he thong'),
(2, 'nhan_vien_tiep_nhan', 'Nhan vien tiep nhan - Tao don hang tai quay'),
(3, 'nhan_vien_dieu_phoi', 'Nhan vien dieu phoi - Lap tuyen va gom dot'),
(4, 'tai_xe', 'Tai xe - Chay xe tai trung chuyen lien kho'),
(5, 'shipper', 'Nguoi giao hang - Phat hang den nha khach');

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
(5, 5, 5);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, '84C-123.45', 1500.00, 'xe_tai_trung', 1),
(2, '84A-999.99', 500.00, 'xe_tai_nho', 1);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bang_gia_cuoc`
--
ALTER TABLE `bang_gia_cuoc`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `fk_chitiet_donhang` (`don_hang_id`);

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
-- AUTO_INCREMENT cho bảng `chi_nhanh`
--
ALTER TABLE `chi_nhanh`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `chi_tiet_dot_van_chuyen`
--
ALTER TABLE `chi_tiet_dot_van_chuyen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `chi_tiet_hang_hoa`
--
ALTER TABLE `chi_tiet_hang_hoa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `dot_van_chuyen`
--
ALTER TABLE `dot_van_chuyen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `giao_hang_tan_noi`
--
ALTER TABLE `giao_hang_tan_noi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `hanh_trinh_xe`
--
ALTER TABLE `hanh_trinh_xe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `lich_su_trang_thai`
--
ALTER TABLE `lich_su_trang_thai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `nguoi_giao_hang`
--
ALTER TABLE `nguoi_giao_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `nhan_vien`
--
ALTER TABLE `nhan_vien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `tai_xe`
--
ALTER TABLE `tai_xe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `tuyen_duong`
--
ALTER TABLE `tuyen_duong`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `vai_tro`
--
ALTER TABLE `vai_tro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `vai_tro_nguoi_dung`
--
ALTER TABLE `vai_tro_nguoi_dung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `xac_minh_otp`
--
ALTER TABLE `xac_minh_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `xe_van_tai`
--
ALTER TABLE `xe_van_tai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Các ràng buộc cho các bảng đã đổ
--

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
  ADD CONSTRAINT `fk_chitiet_donhang` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE;

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
