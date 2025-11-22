-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 22, 2025 lúc 09:44 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `flowershopdb`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `category_id` varchar(10) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `category_description`, `created_at`) VALUES
('DM002', 'Hoa lẻ', 'Hoa bán theo bông', '2025-11-11 09:49:39'),
('DM003', 'Bó hoa', 'Hoa gói sẵn', '2025-11-11 09:49:39'),
('DM004', 'Giỏ hoa', 'Hoa trong giỏ', '2025-11-11 09:49:39'),
('DM005', 'Hoa khai trương', 'Chúc khai trương', '2025-11-11 09:49:39'),
('DM006', 'Hoa chúc mừng', 'Tặng chúc mừng', '2025-11-11 09:49:39'),
('DM007', 'Cây cảnh mini', 'Cây nhỏ trang trí', '2025-11-11 09:49:39'),
('DM008', 'Bánh kem', 'Bánh kem tươi', '2025-11-11 09:49:39'),
('DM009', 'Gấu bông', 'Quà dễ thương', '2025-11-11 09:49:39'),
('DM010', 'Giỏ trái cây', ' Trái cây trong giỏ', '2025-11-11 09:49:39'),
('DM011', 'hà ngu', 'hà rất ngu', '2025-11-12 16:20:25');

--
-- Bẫy `categories`
--
DELIMITER $$
CREATE TRIGGER `before_insert_category` BEFORE INSERT ON `categories` FOR EACH ROW SET NEW.category_id = CONCAT('DM', LPAD((SELECT IFNULL(MAX(CAST(SUBSTRING(category_id, 3) AS UNSIGNED)), 0) + 1 FROM Categories), 3, '0'))
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `delivery_methods`
--

CREATE TABLE `delivery_methods` (
  `delivery_method_id` varchar(10) NOT NULL,
  `method_name` varchar(50) NOT NULL,
  `delivery_description` text DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `delivery_methods`
--

INSERT INTO `delivery_methods` (`delivery_method_id`, `method_name`, `delivery_description`, `fee`) VALUES
('GH001', 'Giao nhanh', 'Giao hàng trong vòng 1–2 giờ sau khi đặt. Phù hợp với các đơn cần gấp hoặc tặng hoa bất ngờ.', 50000.00),
('GH002', 'Giao tiêu chuẩn', 'Giao hàng trong ngày theo khung giờ của cửa hàng. Tiết kiệm chi phí vận chuyển.', 20000.00),
('GH003', 'Giao theo lịch hẹn', 'Khách hàng chọn ngày và giờ giao cụ thể (thường cho các dịp lễ, sinh nhật, hoặc sự kiện).', 40000.00),
('GH004', 'Giao trong ngày', 'Chỉ áp dụng cho khu vực nội thành, đảm bảo giao trong ngày đặt hàng.', 30000.00);

--
-- Bẫy `delivery_methods`
--
DELIMITER $$
CREATE TRIGGER `before_insert_delivery` BEFORE INSERT ON `delivery_methods` FOR EACH ROW SET NEW.delivery_method_id = CONCAT('GH', LPAD((SELECT IFNULL(MAX(CAST(SUBSTRING(delivery_method_id, 3) AS UNSIGNED)), 0) + 1 FROM Delivery_Methods), 3, '0'))
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` varchar(10) NOT NULL,
  `user_id` varchar(10) NOT NULL,
  `product_id` varchar(10) NOT NULL,
  `feedback_content` text DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL CHECK (`rating` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Bẫy `feedback`
--
DELIMITER $$
CREATE TRIGGER `before_insert_feedback` BEFORE INSERT ON `feedback` FOR EACH ROW SET NEW.feedback_id = CONCAT('FB', LPAD((SELECT IFNULL(MAX(CAST(SUBSTRING(feedback_id, 3) AS UNSIGNED)), 0) + 1 FROM Feedback), 3, '0'))
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `order_id` varchar(10) NOT NULL,
  `user_id` varchar(10) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `status` enum('Chờ xác nhận','Đang xử lý','Đang giao hàng','Đã giao','Đã hủy') DEFAULT 'Chờ xác nhận',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `payment_status` enum('Chưa thanh toán','Đã thanh toán','Đã hoàn tiền') DEFAULT 'Chưa thanh toán',
  `ship_date` datetime DEFAULT NULL,
  `payment_method_id` varchar(10) DEFAULT NULL,
  `delivery_method_id` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `status`, `total_amount`, `note`, `payment_status`, `ship_date`, `payment_method_id`, `delivery_method_id`) VALUES
('HD001', 'KH003', '2025-11-13 11:33:11', 'Đã giao', 100000.00, NULL, 'Chưa thanh toán', NULL, 'TT001', 'GH001'),
('HD002', 'KH003', '2025-11-13 13:33:20', 'Đã giao', 2000.00, '', 'Chưa thanh toán', NULL, 'TT001', 'GH002'),
('HD003', 'KH003', '2025-11-13 13:48:58', 'Đã giao', 2000.00, '', 'Chưa thanh toán', NULL, 'TT002', 'GH002'),
('HD004', 'KH003', '2025-11-13 14:38:17', 'Đã giao', 2000.00, '', 'Chưa thanh toán', NULL, 'TT002', 'GH002'),
('HD005', 'KH003', '2025-11-13 15:14:21', 'Đã giao', 22000.00, '', 'Chưa thanh toán', NULL, 'TT002', 'GH002'),
('HD006', 'KH003', '2025-11-13 15:23:14', 'Đã giao', 22000.00, '', 'Đã thanh toán', NULL, 'TT002', 'GH002'),
('HD007', 'KH003', '2025-11-13 15:26:58', 'Đã giao', 22000.00, '', 'Đã thanh toán', NULL, 'TT002', 'GH002'),
('HD008', 'KH003', '2025-11-18 08:00:05', 'Đã hủy', 60000.00, '', 'Chưa thanh toán', NULL, 'TT002', 'GH002'),
('HD009', 'KH003', '2025-11-22 10:57:30', 'Đang xử lý', 611000.00, '', 'Chưa thanh toán', NULL, 'TT001', 'GH002'),
('HD010', 'KH004', '2025-11-22 11:02:13', 'Đang xử lý', 489000.00, '', 'Chưa thanh toán', NULL, 'TT001', 'GH002'),
('HD011', 'KH004', '2025-11-22 15:02:37', 'Chờ xác nhận', 2000.00, 'ok', 'Chưa thanh toán', NULL, 'TT001', 'GH002');

--
-- Bẫy `orders`
--
DELIMITER $$
CREATE TRIGGER `before_insert_order` BEFORE INSERT ON `orders` FOR EACH ROW SET NEW.order_id = CONCAT('HD', LPAD((SELECT IFNULL(MAX(CAST(SUBSTRING(order_id, 3) AS UNSIGNED)), 0) + 1 FROM Orders), 3, '0'))
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_details`
--

CREATE TABLE `order_details` (
  `order_detail_id` varchar(10) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `product_id` varchar(10) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_details`
--

INSERT INTO `order_details` (`order_detail_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
('CTH001', 'HD001', 'SP001', 1, 40000.00),
('CTH002', 'HD001', 'SP002', 1, 28000.00),
('CTH003', 'HD002', 'SP261', 1, 2000.00),
('CTH004', 'HD003', 'SP261', 1, 2000.00),
('CTH005', 'HD004', 'SP261', 1, 2000.00),
('CTH006', 'HD005', 'SP261', 1, 2000.00),
('CTH007', 'HD006', 'SP261', 1, 2000.00),
('CTH008', 'HD007', 'SP261', 1, 2000.00),
('CTH009', 'HD008', 'SP001', 1, 40000.00),
('CTH010', 'HD001', 'SP076', 1, 20000.00),
('CTH011', 'HD009', 'SP242', 1, 611000.00),
('CTH012', 'HD010', 'SP237', 1, 489000.00),
('CTH013', 'HD011', 'SP261', 1, 2000.00);

--
-- Bẫy `order_details`
--
DELIMITER $$
CREATE TRIGGER `before_insert_order_detail` BEFORE INSERT ON `order_details` FOR EACH ROW SET NEW.order_detail_id = CONCAT('CTH', LPAD((SELECT IFNULL(MAX(CAST(SUBSTRING(order_detail_id, 4) AS UNSIGNED)), 0) + 1 FROM Order_Details), 3, '0'))
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_methods`
--

CREATE TABLE `payment_methods` (
  `payment_method_id` varchar(10) NOT NULL,
  `method_name` varchar(50) NOT NULL,
  `payment_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `payment_methods`
--

INSERT INTO `payment_methods` (`payment_method_id`, `method_name`, `payment_description`) VALUES
('TT001', 'Thanh toán khi nhận hàng', 'Khách hàng thanh toán tiền mặt trực tiếp cho nhân viên giao hoa khi nhận hàng.'),
('TT002', 'Thanh toán qua mã QR', 'Khách hàng quét mã QR để thanh toán nhanh qua ngân hàng hoặc ví điện tử.');

--
-- Bẫy `payment_methods`
--
DELIMITER $$
CREATE TRIGGER `before_insert_payment` BEFORE INSERT ON `payment_methods` FOR EACH ROW SET NEW.payment_method_id = CONCAT('TT', LPAD((SELECT IFNULL(MAX(CAST(SUBSTRING(payment_method_id, 3) AS UNSIGNED)), 0) + 1 FROM Payment_Methods), 3, '0'))
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `product_id` varchar(10) NOT NULL,
  `category_id` varchar(10) DEFAULT NULL,
  `product_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `price`, `stock`, `image_url`, `created_at`, `updated_at`) VALUES
('SP001', 'DM002', 'Hoa Cẩm Tú Cầu Trắng ', 40000.00, 29, '1lrOeoMZ9jb4WyRxa0eu_IakEG8vKooM2', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP002', 'DM002', 'Hoa Đồng Tiền Hồng', 28000.00, 37, '1WfsAXOlAuXrdMsDsPNX03H9nOw3ihpNV', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP003', 'DM002', 'Hoa Mõm Sói Trắng', 25000.00, 9, '18OidEZX5z82mBjhbckYk2cm27fKlygWB', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP004', 'DM002', 'Hoa Mõm Sói Vàng ', 34000.00, 30, '1uCeeHJ3dtKL8OXjUXuPBZ3NupPbdN_Xg', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP005', 'DM002', 'Hoa Lan Phượng Vỹ', 47000.00, 32, '15qfoc-t4AtZVFdIAINh0yXNSO0Wpt9qC', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP006', 'DM002', 'Hoa Lan Moka Tím ', 41000.00, 20, '1wlz5cVrP3TDbjxzDqq5j_fLdWi2OTqD0', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP007', 'DM002', 'Hoa Lan Thái Trắng', 79000.00, 30, '1yEoQNghxsKFtNgClyrEjM5k9JZjsTID6', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP008', 'DM002', 'Hoa Cát Tường', 33000.00, 47, '1OsEA45TmAb469p7Y1RDMTQPtxgTeC5jU', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP009', 'DM002', 'Hoa Cẩm Tú Cầu Xanh Lá ', 47000.00, 26, '1lyIFtJgNTCUTapl8FH7z0EJ2r4yIIAkQ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP010', 'DM002', 'Hoa Cẩm Tú Cầu Tím', 33000.00, 46, '15lOGu1z8kyJvcoYja8Z2w0HC7LFZAJVj', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP011', 'DM002', 'Hoa Cẩm Chướng Hồng', 17000.00, 26, '1OsEA45TmAb469p7Y1RDMTQPtxgTeC5jU', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP012', 'DM002', 'Hoa Tulip Trắng', 17000.00, 30, '1BhD5iQW3ibkNcK_tfLs1mNVpwqAbt_0G', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP013', 'DM002', 'Hoa Tulip Hồng', 35000.00, 14, '18fE2U09pq8-ae9VmdtJ3keZ_KEbJUkBJ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP014', 'DM002', 'Hoa Trắng Nhỏ', 17000.00, 48, '1M_kUJz1SmF0K_RB2D61KK7b8RUc1o0Q-', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP015', 'DM002', 'Hoa Cẩm Chướng Tím', 31000.00, 10, '1yIrhRUO8qGTY-2fVguB8H0QiBC5vi5UZ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP016', 'DM002', 'Hoa Đồng Tiền Đỏ', 36000.00, 44, '1HoNaGM6wtxiIM8muViqwkFXeuRBJSGCO', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP017', 'DM002', 'Hoa Cẩm Chướng Đỏ', 19000.00, 42, '1T-_PjX4wAFp2xbgd3ED-RusKJ0yerk8P', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP019', 'DM002', 'Hoa Tulip Cam ', 20000.00, 39, '15q2lTleA5lWStpgzKBC2duVsIXU_T6p5', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP020', 'DM002', 'Hoa Hướng Dương ', 41000.00, 30, '1paxM2lcUAfjE9lPGbcnrfXC99wg3qUqM', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP021', 'DM002', 'Hoa Ly Hồng', 33000.00, 7, '14XenafwuWKBQPYQa3qtWqKSbutdVnKrW', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP022', 'DM002', 'Hoa Đồng Tiền', 33000.00, 36, '1ftQGmyd0Q5OTO0GXWWRATtMdlFXiKnzz', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP024', 'DM002', 'Hoa Baby', 44000.00, 16, '1MkW8wZQaMv_HGTdd0VglvZEuxl0QaAke', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP025', 'DM002', 'Hoa Hồng Vàng', 49000.00, 37, '1rz1taYXPlz0akbCjGPm3KVQ1x83FfnRR', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP026', 'DM002', 'Hoa Ly Trắng ', 27000.00, 44, '1uo72uT_e7qUqR8T4JtVSKEkmI_DZncC6', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP027', 'DM002', 'Hoa Hồng Cam', 17000.00, 14, '1xeiU1lX6KWeCKmorogAnvDxJuuIxYeN4', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP028', 'DM002', 'Hoa Ly Vàng', 33000.00, 13, '1jbWcIiUjZkW6vyB7cUnmr6i6eZth1aaP', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP029', 'DM002', 'Hoa Sen', 28000.00, 48, '1GXEzHT5UEzbrH8r6GKVkglwiWNVC4161', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP030', 'DM002', 'Hoa Hồng Đỏ ', 20000.00, 35, '13YOFYOH1AN5mnhdxT4wv8WjH1dAewvT8', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP031', 'DM003', 'Bó Hồn Nhiên ', 560000.00, 50, '1Mrq0tqOtQfNgvm0Pu9H0b5uHwXMaOq--', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP032', 'DM003', 'Bó Hồng Mix Đằm Thắm ', 363000.00, 13, '1fS84mpd-7casqAGl3UDfe3DFkjbYyzQv', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP033', 'DM003', 'Bó Hồng Vàng ', 460000.00, 10, '1zpOwSp2_f0AaFM_CHIywCJ2jxEG3Uz0U', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP034', 'DM003', 'Bó Cẩm Tú Cầu Mix ', 386000.00, 20, '1gdnIPmen-FnGt4zfbgEdZd47QPeGkPjp', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP035', 'DM003', 'Bó Đồng Tiền Mix Hoa Hồng', 326000.00, 36, '1N4w92ft06DbNak2Vre61P9S3tlVLQiNn', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP036', 'DM003', 'Bó Hồng Mix', 542000.00, 23, '1hkUdHsIcEPUUvq1Co5fnECn0WMc9VV3_', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP037', 'DM003', 'Bó Hồng Pastel ', 475000.00, 25, '1w-3kYzBChQx1WeMD_ZbvOEzmMQQiG0_Q', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP038', 'DM003', 'Bó Cẩm Tú Cầu Xanh', 284000.00, 29, '1LyWTNaRuKraWW5QonZLZIG7YjMH_8BF9', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP039', 'DM003', 'Bó Hồng Pastel Hồng Nhạt', 465000.00, 29, '1bJasZXOEXpkDnxPHGbQLUPgq2Rfl4YGF', '2025-11-11 10:13:46', '2025-11-11 11:06:31'),
('SP040', 'DM003', 'Bó Hồng Mix Ngọt Ngào ', 536000.00, 45, '12Fnx_bnIMWGO8mW6AY73IYuHPbDizMQW', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP041', 'DM003', 'Bó Baby Trắng Hồng ', 344000.00, 10, '1arZMFOAtJo8C8snj52K7ND9GjcP3kzXt', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP042', 'DM003', 'Bó Đồng Tiền Mix', 510000.00, 11, '1Um5_AF4zjRqH34HrebBDiqsm54QrtdMb', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP043', 'DM003', 'Bó Cát Tường ', 412000.00, 16, '1n8tu6KxCe6gRB96h4wx25Oxsz3-A8NmI', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP044', 'DM003', 'Bó Hồng Dịu Dàng', 432000.00, 23, '1EvG4qydetnqYB0nyAErBZdhO8IHpxyfk', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP045', 'DM003', 'Bó Baby Xanh ', 427000.00, 38, '1ZmUcIqtSZ0_Ant1g6MdGCeFxPZeY-L4e', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP046', 'DM003', 'Bó Baby trắng lớn', 435000.00, 45, '1wxr7QKJYjdoA3eM1lNuQmIB1b5oYAtYi', '2025-11-11 10:13:46', '2025-11-11 11:03:15'),
('SP047', 'DM003', 'Bó Hoa Cúc Tana', 491000.00, 43, '1NHLh-tXNEFrsqC5BDbI5jSRB-bTvefAN', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP048', 'DM003', 'Bó Hồng Kem Dâu', 589000.00, 47, '160JrnjDDsjUBInAnAqU5_uEGJ12_JPgW', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP049', 'DM003', 'Bó Hồng Đỏ Qúi Phái', 289000.00, 39, '1G3hZUz9dSfazUQys7WGKnPWm23xNdd9w', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP050', 'DM003', 'Bó Hướng Dương Mix', 282000.00, 39, '1Tv-rtAyw3DKtulV2Vbj1zR7fdgfTJIJQ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP051', 'DM003', 'Bó Tốt Nghiệp', 429000.00, 20, '1kQYkZzuN1R0jdvezJLuse_w1orYbPA9X', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP052', 'DM003', 'Bó Hướng Dương Mix Hoa Hồng', 478000.00, 13, '1FSyBIwp1KyrOd-pjcBv2fa4Xn_wgoTsO', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP053', 'DM003', 'Bó Hồng Đi Tiệc', 559000.00, 7, '17kmppQ-qTbnIyP_CmvEFwWPdpLWdCTP7', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP054', 'DM003', 'Bó Hồng Sang Trọng', 398000.00, 15, '1mhC_G3ehwkRCHP3PrDqwxuudPByW5SXh', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP055', 'DM003', 'Bó Cẩm Tú Cầu Mix Hoa Hồng ', 564000.00, 25, '178fe8rRP0YMBQMzkONcgTTB0GrNxd_1H', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP056', 'DM003', 'Bó Baby Trắng ', 427000.00, 7, '1Z0nXhMR8D24WDx0RrZ2YRW-eU4YhpWuv', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP057', 'DM003', 'Bó Ngọt Ngào', 349000.00, 40, '1zI9OL3MV6J2YG3y8MsiSWZXd29lrEaNE', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP058', 'DM003', 'Bó Hồng Đỏ ', 256000.00, 43, '1-K9fPhjrgzdn8eKiJKVX32HXn6T96_XT', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP059', 'DM003', 'Bó Hồng Mix Tình Yêu ', 334000.00, 23, '1Mrq0tqOtQfNgvm0Pu9H0b5uHwXMaOq--', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP060', 'DM003', 'Bó Baby Hồng ', 372000.00, 18, '14MgMFzZ6AKRKo5gbS8bMWdXnmKEomEXY', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP061', 'DM004', 'Giỏ hoa Nắng Sớm 6', 838000.00, 30, '1JtHV8z2hUh3xcD_A3bU2A02bDNKS9KJh', '2025-11-11 10:13:46', '2025-11-11 11:14:26'),
('SP062', 'DM004', 'Giỏ hoa Nắng Sớm ', 897000.00, 25, '16nnvypiYPoFExFTLL7EPHGr2g4m1-nbC', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP063', 'DM004', 'Giỏ hoa Thuần Khiết ', 568000.00, 32, '1e7zsQgFFyPV3REbTHk9BHeW9QyxnOu0K', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP064', 'DM004', 'Giỏ hoa Mộng Mơ 3', 764000.00, 18, '1pWyCGB7N8f_xMP3PWFp3Qkm_dsMpncBM', '2025-11-11 10:13:46', '2025-11-11 11:10:47'),
('SP065', 'DM004', 'Giỏ hoa Thuần Khiết 3', 529000.00, 28, '1i4CmRTxu_Eor1K0qhKW5pgkxI4AgOny4', '2025-11-11 10:13:46', '2025-11-11 11:16:23'),
('SP066', 'DM004', 'Giỏ hoa Duyên Dáng ', 484000.00, 11, '1U6bw7ExbCgkFK2W9t_nnciYn-LmceLfh', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP067', 'DM004', 'Giỏ hoa Yêu Thương ', 893000.00, 27, '1qD7HD3hHKcqoj9lBHUYghlvZQUdTdbuT', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP068', 'DM004', 'Giỏ hoa Bình Minh ', 554000.00, 6, '1nCy3tb07dKSsmN79uyz-Cz3U5NRSP-6j', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP069', 'DM004', 'Giỏ hoa Thuần Khiết 5', 767000.00, 30, '12hrgSuLZXly3i3v-n3ozFUhhjaVHq-DK', '2025-11-11 10:13:46', '2025-11-11 11:17:16'),
('SP070', 'DM004', 'Giỏ hoa Gấu', 492000.00, 9, '1qnldJIACbSbQywo6ZhipDIb-0XxoFrmu', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP071', 'DM004', 'Giỏ hoa Gấu Tốt Nghiệp', 467000.00, 21, '1Ny5HKvN3V08ICIwqFRZvn9uMCg2YusiZ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP072', 'DM004', 'Giỏ hoa Bình Minh 2 ', 473000.00, 13, '1V_zjH4jSK_ze12bzREUZdw3FwnWYK4fV', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP073', 'DM004', 'Giỏ hoa Thuần Khiết 7', 673000.00, 14, '1JdX_WvkluSmClsnS9-4fAW5d6NNGuI6h', '2025-11-11 10:13:46', '2025-11-11 11:17:39'),
('SP074', 'DM004', 'Giỏ hoa Duyên Dáng 2', 430000.00, 44, '1_ZO14be4EE4NqzyBDMCi_5fITheJVCGy', '2025-11-11 10:13:46', '2025-11-11 11:08:47'),
('SP075', 'DM004', 'Giỏ hoa Mộng Mơ 2', 436000.00, 8, '13TEyf5x90GR0BTzQHah9I83i6CfEaS60', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP076', 'DM004', 'Giỏ hoa Yêu Thương 5', 668000.00, 1516, '16dsMpw4qeYT0pCf7_mO9oIHK2HyxOoxO', '2025-11-11 10:13:46', '2025-11-11 10:34:48'),
('SP077', 'DM004', 'Giỏ hoa Nắng Sớm 3', 882000.00, 22, '1HlGWqZpzvVIsLgRv9VsS9Pz9p08xoXmf', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP078', 'DM004', 'Giỏ hoa Nắng Sớm 2', 706000.00, 7, '1hh-4XJaRLbYeAlooj07PaUvtmj3JcwJB', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP079', 'DM004', 'Giỏ hoa Nắng Sớm 1', 788000.00, 39, '1_7Ri-1UIqnanPHeNnxTmT1AV30m3h58L', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP080', 'DM004', 'Giỏ hoa Nắng Sớm 7', 697000.00, 15, '1iViqdfcMmxTeTn0eagNSKyPRAI6eI0_h', '2025-11-11 10:13:46', '2025-11-11 11:14:41'),
('SP081', 'DM004', 'Giỏ hoa Mộng Mơ 1', 856000.00, 13, '1QVkyEd5wY5A89Xt-LVx7X3BNy3_FcNBB', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP082', 'DM004', 'Giỏ hoa Thuần Khiết 4', 565000.00, 5, '1sdsQvVtos53aYAVB9YC4kJGLZVWZASPa', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP083', 'DM004', 'Giỏ hoa Nắng Sớm 5 ', 459000.00, 28, '10E9juC-bpfGSNT9M_AlkV5WzqpkPCArk', '2025-11-11 10:13:46', '2025-11-11 17:33:09'),
('SP084', 'DM004', 'Giỏ hoa Yêu Thương 3', 584000.00, 50, '1QVkyEd5wY5A89Xt-LVx7X3BNy3_FcNBB', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP085', 'DM004', 'Giỏ hoa Nắng Sớm 4 ', 666000.00, 48, '1iPdDW5mtnAUmfdqndvbWrKDv6NB0_sJE', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP086', 'DM004', 'Giỏ hoa Mộng Mơ ', 659000.00, 19, '10E9juC-bpfGSNT9M_AlkV5WzqpkPCArk', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP087', 'DM004', 'Giỏ hoa Duyên Dáng 3', 711000.00, 32, '1u2a-q9oycYqaUj0X46IP668gavpHolvT', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP088', 'DM004', 'Giỏ hoa Thuần Khiết 2', 424000.00, 16, '1MCxvURLVKz_qUF_3mQ483LnXrGrYSKO5', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP089', 'DM004', 'Giỏ hoa Thuần Khiết 1', 509000.00, 26, '1oXrKnAURLS2tTMwO4it9mIf7pk-JO5H-', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP090', 'DM004', 'Giỏ hoa Cẩm Tú Cầu Mix Hoa Hồng ', 771000.00, 10, '1dcSbL981_pXrsxYwCZ64bwc8BvDiRTGZ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP091', 'DM005', 'Kệ hoa Thành Công 1', 1095000.00, 48, '1Ts8K9DLlG2m_h6HKgLogxulAv3HHGfWg', '2025-11-11 10:13:46', '2025-11-11 11:35:43'),
('SP092', 'DM005', 'Kệ hoa Hồng Phát 9', 707000.00, 12, '1Xqn4-k2_mRIa_Oms0Ufwd2-riBAhEufx', '2025-11-11 10:13:46', '2025-11-11 11:43:43'),
('SP093', 'DM005', 'Kệ hoa Hồng Phát 3', 960000.00, 20, '15B9eGXdPTiooC7r7k9SjcRSbBChRZV0k', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP094', 'DM005', 'Kệ hoa Hồng Phát 2', 813000.00, 36, '110i7CtR9qKyg3wkiQdCdKe9U2qwA59Gq', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP095', 'DM005', 'Kệ hoa Hồng Phát 1', 972000.00, 6, '1kFY9qmKDuMroHRAb4HM63CZ1F4mavxQ3', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP096', 'DM005', 'Kệ hoa Thành Công 2', 906000.00, 27, '18GerN2MGNe-WH7g6m9sZBKWc-4kt-NTy', '2025-11-11 10:13:46', '2025-11-11 11:35:43'),
('SP097', 'DM005', 'Kệ hoa Rạng Đông ', 1015000.00, 45, '1A__YG-NCDoNrq6evgIxdXKKrCXF_AQmQ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP098', 'DM005', 'Kệ hoa Phát Tài 1', 1179000.00, 5, '1NJp0uw1Ivozf8KCttfUmubkDPh3kSA30', '2025-11-11 10:13:46', '2025-11-11 11:21:10'),
('SP099', 'DM005', 'Kệ hoa Thành Công 3', 864000.00, 37, '1WINiNbYQ9JQd5QWFU1yqfMAYtTIgT9k0', '2025-11-11 10:13:46', '2025-11-11 11:35:43'),
('SP100', 'DM005', 'Kệ hoa Rạng Đông 3', 863000.00, 26, '1W5gPydCyPbhBAs5B5EUPvPfpF2B6x-kF', '2025-11-11 10:13:46', '2025-11-11 11:20:47'),
('SP101', 'DM005', 'Kệ hoa Phát Tài 2', 888000.00, 18, '1XlXDBGQi3g5AJMZVDFadnXVAWQI_MKH-', '2025-11-11 10:13:46', '2025-11-11 11:21:26'),
('SP102', 'DM005', 'Kệ hoa Rạng Đông 1', 756000.00, 50, '15hnLoVzBI0fP_m6xvRHmwVL0cMWUCsU1', '2025-11-11 10:13:46', '2025-11-11 11:20:14'),
('SP103', 'DM005', 'Kệ hoa Hồng Phát 7', 1037000.00, 13, '1O1n1V8ZHuDultfWoTldTgDnBJK9K21wK', '2025-11-11 10:13:46', '2025-11-11 11:43:00'),
('SP104', 'DM005', 'Kệ hoa Thành Công 4', 1164000.00, 50, '11pt8hHJgW_HEl4d1XT9vAdQkS_W9L2YE', '2025-11-11 10:13:46', '2025-11-11 11:35:43'),
('SP105', 'DM005', 'Kệ hoa Khởi Sắc 1', 801000.00, 34, '1HLwFMedm_2XeFHPl2MGU037tHCtyy32l', '2025-11-11 10:13:46', '2025-11-11 11:21:43'),
('SP106', 'DM005', 'Kệ hoa Khởi Sắc 2', 828000.00, 42, '1QNZNYyqPgmgYoIDJ6eycZxGA1ETOmkY-', '2025-11-11 10:13:46', '2025-11-11 11:21:50'),
('SP107', 'DM005', 'Kệ hoa Vạn Lộc ', 840000.00, 45, '1YkU37oOrZb6_LoyrD4JR_OUiqSrlsXHS', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP108', 'DM005', 'Kệ hoa Thành Công 5', 885000.00, 34, '1RGtdWcu9297Uvm99HqHcEKF7viZltHL_', '2025-11-11 10:13:46', '2025-11-11 11:35:43'),
('SP109', 'DM005', 'Kệ hoa Thành Công 6', 830000.00, 6, '1RvHPx0UDfAShZQj-Y0YUpbSifQqnVS9Y', '2025-11-11 10:13:46', '2025-11-11 11:35:43'),
('SP110', 'DM005', 'Kệ hoa Khởi Sắc ', 1107000.00, 42, '1lfwZSajSkCUHYU2y8VGo43WnDZIhdKZE', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP111', 'DM005', 'Kệ hoa Hồng Phát 8', 964000.00, 42, '1lWcZsxo-mna5WEXoYFVQpPVBBbxGi0wr', '2025-11-11 10:13:46', '2025-11-11 11:43:12'),
('SP112', 'DM005', 'Kệ hoa Thành Công 7', 969000.00, 5, '1uw9MErVxrEqqIW8H2MViWCNhmYGoJtKC', '2025-11-11 10:13:46', '2025-11-11 11:35:43'),
('SP113', 'DM005', 'Kệ hoa Thành Công 8', 773000.00, 30, '1diAffrXJTal5kMuzXCj2Ne9DlNAzBrQG', '2025-11-11 10:13:46', '2025-11-11 11:35:43'),
('SP114', 'DM005', 'Kệ hoa Vạn Lộc 1', 860000.00, 21, '1OuwkuFCJlzukagGsoXcDE7Igtn0Clpl_', '2025-11-11 10:13:46', '2025-11-11 11:19:18'),
('SP115', 'DM005', 'Kệ hoa Hồng Phát 4', 1079000.00, 21, '1d-anNGwiQkHkHgr0JUozCVWop6c5Zf3Q', '2025-11-11 10:13:46', '2025-11-11 11:38:53'),
('SP116', 'DM005', 'Kệ hoa Hồng Phát 5', 1112000.00, 42, '1TdMLtUWC2C7KLIIDF4JKOol78MEp8FAU', '2025-11-11 10:13:46', '2025-11-11 11:38:53'),
('SP117', 'DM005', 'Kệ hoa Thành Công 9', 1080000.00, 20, '1U--co5ud0Lr59di4MfrL0p99bHcqp5KA', '2025-11-11 10:13:46', '2025-11-11 11:35:43'),
('SP118', 'DM005', 'Kệ hoa Rạng Đông 2', 930000.00, 11, '1-4UNZ2D4HhBnBCkS3wsAD5AlKiXErduX', '2025-11-11 10:13:46', '2025-11-11 11:20:28'),
('SP119', 'DM005', 'Kệ hoa Hồng Phát 6', 814000.00, 7, '1Mgf4HgDSkmBbG-HE979bYPhH4Seu6x7w', '2025-11-11 10:13:46', '2025-11-11 11:38:53'),
('SP120', 'DM005', 'Kệ hoa Phát Tài ', 1175000.00, 42, '1XT0QxwU3G8g6_uKpnE3wNcdh8Uxn1ZVJ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP121', 'DM006', 'Hoa Hạnh Phúc ', 665000.00, 29, '1s0mz2cooigpCnYbfz3RQeZQOhAtT9HYo', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP122', 'DM006', 'Hoa Rạng Rỡ ', 733000.00, 7, '1yv96PCiHfjKru2LkK2OMD4q3axFzaF3s', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP123', 'DM006', 'Hoa Rạng Rỡ 1', 415000.00, 33, '1cB7hG9SrjpsUEt3RpdrbXKxPbWMZzrOx', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP124', 'DM006', 'Hoa Chúc Phúc 1', 470000.00, 46, '1uw9MErVxrEqqIW8H2MViWCNhmYGoJtKC', '2025-11-11 10:13:46', '2025-11-11 11:38:17'),
('SP125', 'DM006', 'Hoa Tươi Sắc 1', 595000.00, 49, '1lWcZsxo-mna5WEXoYFVQpPVBBbxGi0wr', '2025-11-11 10:13:46', '2025-11-11 11:38:30'),
('SP126', 'DM006', 'Hoa Rạng Rỡ 3', 685000.00, 24, '1diAffrXJTal5kMuzXCj2Ne9DlNAzBrQG', '2025-11-11 10:13:46', '2025-11-11 11:33:27'),
('SP127', 'DM006', 'Hoa Hạnh Phúc 1', 759000.00, 27, '1Xqn4-k2_mRIa_Oms0Ufwd2-riBAhEufx', '2025-11-11 10:13:46', '2025-11-11 11:26:47'),
('SP128', 'DM006', 'Hoa Tươi Sắc 2', 699000.00, 5, '11pt8hHJgW_HEl4d1XT9vAdQkS_W9L2YE', '2025-11-11 10:13:46', '2025-11-11 11:38:30'),
('SP129', 'DM006', 'Hoa Chúc Phúc 2', 795000.00, 27, '1CvgvXi_LOscpTXAP3fra4rVKLZlZUvue', '2025-11-11 10:13:46', '2025-11-11 11:38:17'),
('SP130', 'DM006', 'Hoa Tươi Sắc 3', 695000.00, 12, '1EWUJSJH9k7n0vJQ5o-i8BLEiPeWkfvLP', '2025-11-11 10:13:46', '2025-11-11 11:38:30'),
('SP131', 'DM006', 'Hoa Chúc Phúc 3', 733000.00, 47, '1s0mz2cooigpCnYbfz3RQeZQOhAtT9HYo', '2025-11-11 10:13:46', '2025-11-11 11:38:17'),
('SP132', 'DM006', 'Hoa Niềm Vui 6', 572000.00, 33, '1OZs3ZV4BxZXvdAy_OdKzx27y0YpDN6Xq', '2025-11-11 10:13:46', '2025-11-11 11:30:32'),
('SP133', 'DM006', 'Bó hoa Niềm Vui 1', 798000.00, 32, '178fe8rRP0YMBQMzkONcgTTB0GrNxd_1H', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP134', 'DM006', 'Bó hoa Hạnh Phúc', 673000.00, 49, '1BowF2WUxiY3ajDXlFtwaRLwRb9QFe9Wd', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP135', 'DM006', 'Bó hoa Niềm Vui Hồng', 492000.00, 13, '165ELfsRsg7e65sU_W1zxEnBhQFr3T9vt', '2025-11-11 10:13:46', '2025-11-11 11:05:06'),
('SP136', 'DM006', 'Bó hoa Hạnh Phúc 2', 763000.00, 40, '1EvG4qydetnqYB0nyAErBZdhO8IHpxyfk', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP137', 'DM006', 'Hoa Vui Mừng ', 579000.00, 27, '1WINiNbYQ9JQd5QWFU1yqfMAYtTIgT9k0', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP138', 'DM006', 'Bó hoa Chúc Phúc ', 720000.00, 43, '12Fnx_bnIMWGO8mW6AY73IYuHPbDizMQW', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP139', 'DM006', 'Bó hoa Hạnh Phúc 1', 736000.00, 32, '14MgMFzZ6AKRKo5gbS8bMWdXnmKEomEXY', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP140', 'DM006', 'Hoa Rạng Rỡ 2', 709000.00, 43, '1fKUIwEYAgMPB_sXSBw1DPxbjcuZ-7C_p', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP141', 'DM006', 'Hoa Niềm Vui 1', 526000.00, 35, '1oXrKnAURLS2tTMwO4it9mIf7pk-JO5H-', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP142', 'DM006', 'Hoa Niềm Vui 2', 494000.00, 5, '1MCxvURLVKz_qUF_3mQ483LnXrGrYSKO5', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP143', 'DM006', 'Hoa Niềm Vui 3', 781000.00, 15, '10E9juC-bpfGSNT9M_AlkV5WzqpkPCArk', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP144', 'DM006', 'Hoa Niềm Vui 4', 646000.00, 20, '1Ny5HKvN3V08ICIwqFRZvn9uMCg2YusiZ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP145', 'DM006', 'Hoa Chúc Phúc 4', 578000.00, 26, '1JtHV8z2hUh3xcD_A3bU2A02bDNKS9KJh', '2025-11-11 10:13:46', '2025-11-11 11:38:17'),
('SP146', 'DM006', 'Hoa Niềm Vui 5', 563000.00, 45, '1qnldJIACbSbQywo6ZhipDIb-0XxoFrmu', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP147', 'DM006', 'Bó hoa Tươi Sắc ', 436000.00, 35, '1Z0nXhMR8D24WDx0RrZ2YRW-eU4YhpWuv', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP148', 'DM006', 'Bó hoa Vui Mừng ', 624000.00, 21, '1Mrq0tqOtQfNgvm0Pu9H0b5uHwXMaOq--', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP149', 'DM006', 'Bó hoa Rạng Rỡ ', 641000.00, 39, '1Um5_AF4zjRqH34HrebBDiqsm54QrtdMb', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP150', 'DM006', 'Bó hoa Niềm Vui ', 581000.00, 22, '1hkUdHsIcEPUUvq1Co5fnECn0WMc9VV3_', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP151', 'DM007', 'Sen Đá Chuỗi Ngọc', 150000.00, 10, '1bz_P0Vl_mBYXu8BFv0QUkkC61Y57dVIv', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP152', 'DM007', 'Sen Đá Trung', 81000.00, 10, '1bTu5tqpxE0iWPZjH1gwqt-WpRQV8UOTn', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP153', 'DM007', 'Sen Đá Nhỏ', 150000.00, 10, '1y_IhdXY4RsL7aDdDnSH6rPXRESbNN6HZ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP154', 'DM007', 'Xương Rồng Thanh Sơn ', 165000.00, 10, '1i27rL0LPqPy7rxvpHHsX0EYVOtV9xQy_', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP155', 'DM007', 'Sen Đá Ngọc Bích', 150000.00, 10, '1KJwKJ-XAr7c2WWcwO9vHBGAcLk9k3_9L', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP156', 'DM007', 'Sen Đá Phát Tài', 150000.00, 10, '1n00go5FbULVhdQQk2w6ITl1CDpOKquh_', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP157', 'DM007', 'Trầu Bà', 135000.00, 10, '1IsM5WZXeqAEDapHBF64pmi_KTe0cYqmz', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP158', 'DM007', 'Chuỗi Hạt Ngọc', 150000.00, 10, '1BUJX3dPt7vGewX60wPx_VI15wDr7Z-Eb', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP159', 'DM007', 'Xương Rồng Cầu Vồng', 150000.00, 10, '1jsVuz16CqT2hgTLPwMY89TTrW9amgclO', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP160', 'DM007', 'Cây Hạnh Phúc', 150000.00, 10, '14C_QPOKtVXAaWFtjwatNrD0jcf-HIbud', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP161', 'DM007', 'Cây Kim Ngân', 150000.00, 10, '1XMRPgCOTmJncXS2WrnbtU2HLWAp9blWf', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP162', 'DM007', 'Cây Kim Tiền', 150000.00, 10, '1pfKyJZ4dhv4MyL93-JwWVN8HurJ0W76s', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP163', 'DM007', 'Cây Lưỡi Hổ', 150000.00, 10, '1x96Ku2UckiaF6bHR4WCbj0GsFNXY27_F', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP164', 'DM007', 'Sen Đá Mix', 150000.00, 10, '1LqwPRV1xP3Wos2xojiI8jYWrN78eOc2M', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP165', 'DM007', 'Trúc Phú Qúy Mini', 150000.00, 10, '1ivFtuRP8FHOqdpAr1J1g2TLx_D1mUs18', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP166', 'DM007', 'Xương Rồng Len Bụi ', 150000.00, 10, '1vZOSwIyQ_e2zVzmkAxfraCySiM2zVf98', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP167', 'DM007', 'Bàng Singapore', 150000.00, 10, '1VT7L9OP5K5vcy6EfR6_RcnfLLqjq4tsv', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP168', 'DM007', 'Trúc Phát Lộc', 150000.00, 10, '1XiSDIS2Yb4mtqGslUYB1Nc_Eb5OeqHpX', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP169', 'DM007', 'Trầu Kim Cương', 150000.00, 10, '1aFrpvx1oxf7Th4pgwo7AIKu9qAsvrRsd', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP170', 'DM007', 'Cây Hồng Môn Mini', 150000.00, 10, '1DsZ0wjmcoxX783gmxhcQFpjs34PHEVZa', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP171', 'DM008', 'Bánh Kem Vani Dừa ', 351000.00, 26, '16Xxnsu9pYl34R6SlsbGle6MPpk5J0KIG', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP172', 'DM008', 'Bánh Kem Matcha Dâu ', 325000.00, 25, '1rOVtM8W_OyxZ1sJV28leW7249MjAwr5D', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP173', 'DM008', 'Bánh Kem Dâu Nho ', 373000.00, 19, '1L6BFDh5LWjKhR31BRa7Kns2ou-3BVUIm', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP174', 'DM008', 'Bánh Kem Dâu ', 390000.00, 33, '1x_iRIKwxUMGswIBJvauFXQ_GD9WzidWe', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP175', 'DM008', 'Bánh Kem Caramel 1 ', 227000.00, 20, '1T1bEfepHtNpcXk-oYynRFVPfweSz8rU9', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP176', 'DM008', 'Bánh Kem Vani ', 371000.00, 8, '1E6Zn8PN6c4-o3MGdmPef19HYyXMwAn-e', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP177', 'DM008', 'Bánh Kem Vàng ', 272000.00, 25, '11WB-pyHEdpqLWvRRwTINtd5-ghNJzC6p', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP178', 'DM008', 'Bánh Kem Chanh Leo ', 204000.00, 17, '1uHYpzjvL8I8O5mRFdvKppt2thIW7A1gI', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP179', 'DM008', 'Bánh Bông Lan Trứng Muối ', 278000.00, 44, '1uHYpzjvL8I8O5mRFdvKppt2thIW7A1gI', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP180', 'DM008', 'Bánh Kem Mứt ', 247000.00, 28, '1fwosahxUJe5O6Xf3a3-g_SxIJxNLkym9', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP181', 'DM008', 'Bánh Kem Bắp ', 263000.00, 22, '1X4PDQ1h05Umnb_1ne4t8EExPS8tatOtp', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP182', 'DM008', 'Bánh Kem trái cây ', 331000.00, 25, '1A1MjV_dtr-uaFcf_7oqlhzrdbR_ved4h', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP183', 'DM008', 'Bánh Kem trái cây tươi', 214000.00, 34, '19tf6XPxt6sLgAKGUj8GuYguJ240zW5Ic', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP184', 'DM008', 'Bánh Kem Tiramisu 1 ', 228000.00, 46, '1oELxEH5LoF_ic4T2jX3x3UrAP3u78MZ3', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP185', 'DM008', 'Bánh Kem Socola ', 254000.00, 17, '1o8f9mz4CnTwnnD8jj1wLg3VpNzdYPKcQ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP186', 'DM008', 'Bánh Kem Caramel ', 209000.00, 41, '1Cp0MkgCJ3sv4D7VdkfXdawsOF6SA8H_r', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP187', 'DM008', 'Bánh Kem Socola tròn', 337000.00, 46, '19MjsQAHOFxd6rKMXVB4G8EeHV_rzOMtM', '2025-11-11 10:13:46', '2025-11-11 11:01:07'),
('SP188', 'DM008', 'Bánh Kem Dâu Socola ', 323000.00, 44, '1VuX_r6izj_xsrN5lLmM1D80dLOdIleaC', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP189', 'DM008', 'Bánh Kem Su Kem ', 375000.00, 38, '13F0DKCsyWgCR-Sf13UTzIDE2G0RoQQb-', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP190', 'DM008', 'Bánh Kem Caramel 3 ', 246000.00, 38, '1VuX_r6izj_xsrN5lLmM1D80dLOdIleaC', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP191', 'DM008', 'Bánh Kem Tiramisu 2 ', 340000.00, 40, '1TX8w5BLImmfnUGDp5yR6qn3OLn4B9HBT', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP192', 'DM008', 'Bánh Kem Caramel 2', 351000.00, 39, '1WAaoX0Ng_6Z2h4kKSizrPdL-yiRhS3x2', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP193', 'DM008', 'Bánh Kem Tiramisu Dâu ', 230000.00, 43, '1s0q4KRPcTCYxnMebuBaO4emF_p7u5NlV', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP194', 'DM008', 'Bánh Kem Matcha ', 222000.00, 22, '1HRq86OJACGHMyVEYcNV5i66_XQtasqWZ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP195', 'DM008', 'Bánh Kem Socola Vụn ', 304000.00, 22, '1Ekvo0woz2AJ09VrxmzRQRantK3OxKSj3', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP196', 'DM008', 'Bánh Kem Tươi ', 344000.00, 42, '1sDAeKRlqTSioKuPVfPBVXcOvki0RUc0r', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP197', 'DM008', 'Bánh Kem Vani Kem Cheese ', 371000.00, 33, '1IkcLyFJQZTk85qMlVtSJu3J7ynhJMSPu', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP198', 'DM008', 'Bánh Kem Socola dâu', 329000.00, 25, '1vtMs-fnKcAhZSdELtt6cpv67m-NI0isb', '2025-11-11 10:13:46', '2025-11-11 10:59:55'),
('SP199', 'DM008', 'Bánh Tiramisu Chanh Leo ', 299000.00, 7, '103QzDCibEb3eC2FelkkTuWlImkCCmOWU', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP200', 'DM008', 'Bánh Kem Tiramisu 3', 280000.00, 48, '1bThWDHsPLUQjmn9G9Hyw06CnE2FPlniA', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP201', 'DM009', 'Puppy', 167000.00, 10, '15FI0GEuXmxBjKMfOuA8w66qFVmUEqeaY', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP202', 'DM009', 'Gấu Dưa Hấu', 173000.00, 49, '1A7TaseKMEpgjDUNz3Pdz-zg7NhEySUyh', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP203', 'DM009', 'Gấu Áo Len ', 100000.00, 26, '1gT1Acjx0rALrx6Ci6QsI_HtLqDRXWJzm', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP204', 'DM009', 'Gấu Lazy', 337000.00, 27, '1cdOH1P4t_uSU7w8INHpeffZNKzkFCdtn', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP205', 'DM009', 'Gấu Ôm Hoa ', 179000.00, 49, '10SeahB-dhgsWMO_j2nN33YjnQ8oqJHPQ', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP206', 'DM009', 'Gấu Ôm Qùa ', 123000.00, 46, '13yBTDSmGiOcnK0Qf_v190bY-MFg1nscE', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP207', 'DM009', 'Gấu Capybara Balo', 260000.00, 41, '1QmeGpmoepQKIyGVCnRh6lOObXgyIXoYh', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP208', 'DM009', 'Chó Lạp Xưởng Hamburger', 128000.00, 8, '1tthoWKkmKqVKdYfvhpWAyhB3ci0XO4qG', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP209', 'DM009', 'Chó Lạp Xưởng Đầu Bếp ', 138000.00, 25, '1NLfmfVvwaDyyyTdVGBxxkoV8sZjEzfF9', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP210', 'DM009', 'Gấu Capylulu', 146000.00, 26, '137jsvmTI3dOsfoMVrRYZSwHw84wJF-CS', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP211', 'DM009', 'Gấu Cánh Cụt Trái Cây', 274000.00, 12, '1x6LApRjgc2qXtZ3Icu2B7t-aM0bzOTNk', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP212', 'DM009', 'Gấu Hải Cẩu Ôm Hải Cẩu ', 342000.00, 45, '14zYKa6N9lKkvQQ2DlzdXj00ZLnqB0pS0', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP213', 'DM009', 'Gấu Hải Cẩu Mũm Mĩm ', 330000.00, 44, '1X_1llbOHVrUyQIrzex0Hg5aDGrlQTlR6', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP214', 'DM009', 'Gấu Hải Cẩu ', 180000.00, 32, '196M2rNgZmVpt2V6LXS4xmpqk2-AHSedk', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP215', 'DM009', 'Gấu Hải Cẩu Đeo Tai Nghe ', 213000.00, 39, '1LYXZaBJTNYTCzpR4l87T46nEgWb_TQW0', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP216', 'DM009', 'Gấu Hải Cẩu Chiên ', 261000.00, 20, '1j1qAh_Q8kFAh-NhHe4wrPz6J4f6RYcs9', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP217', 'DM009', 'Ca Heo ', 220000.00, 14, '1fIipG2hjQKKz5oXwfyDBrGY3o8NOb0tw', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP218', 'DM009', 'Cánh Cụt ', 316000.00, 49, '1lE9uFlFQQq2SSUNaaRpNAtwgz-WCrFqe', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP219', 'DM009', 'Gấu Trắng Dễ Thương ', 241000.00, 9, '1XrqHQ8OI5hRzzehzSxjDvkBbMY5hMC1J', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP220', 'DM009', 'Gấu Heo Hồng Dưa Hấu ', 163000.00, 24, '1uWAN3KYdHJe36IFNOCGqpnwHgxwjXFzo', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP221', 'DM009', 'Gấu Capybara Couple', 213000.00, 28, '1nUK6aQvk-kAiKbKWaiwTK12EeeyrjC6U', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP222', 'DM009', 'Gấu Labubu Hồng ', 273000.00, 47, '1u2dvQKPjhwYej5dyr910idtXXLNXoZUr', '2025-11-11 10:13:46', '2025-11-11 10:31:29'),
('SP223', 'DM009', 'Gấu Bánh Kem Mèo', 251000.00, 14, '13I4jJUS6mlTd-K2Hdk296ryxoOT8rPnl', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP224', 'DM009', 'Bear Hug ', 259000.00, 20, '1pee0da61wzMnAt1vCybXB0HgqIsQpPDs', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP225', 'DM009', 'Gấu Mũ Len ', 135000.00, 32, '1YHtI4ScSGBjBRm_etOWjWWq7NTo-ckVx', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP226', 'DM009', 'Hươu Cao Cổ ', 271000.00, 46, '1RA_02jBOH8RVY5vpQeRH4QDgQBEnf-IM', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP227', 'DM009', 'Gấu Heo Hồng Gặm Bánh Mì', 175000.00, 29, '1jjLJwG9E0EoDEuJThC_qZfcChYmn0Zvg', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP228', 'DM009', 'Gấu Heo Hồng Nơ ', 227000.00, 50, '1CFnvdsbAumyi1rthoyh8obRqEtnCfAZj', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP229', 'DM009', 'Gấu Heo Hồng Mặc Váy  ', 333000.00, 46, '14EcRRURESteZf1abuO4G_pZfzdP6-DCT', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP230', 'DM009', 'Gấu Capybara Dừa', 103000.00, 7, '1KCu4Uo9lOLHZfq7KE2unP0ap-Lo_Xio4', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP231', 'DM010', 'Giỏ Trái Cây Cao Cấp 1', 526000.00, 45, '1Y53IjSCg7WJFjQ62QULyoKeOIqruXgYv', '2025-11-11 10:13:46', '2025-11-11 11:37:13'),
('SP232', 'DM010', 'Giỏ Trái Cây Nhập 1', 442000.00, 24, '1nUXN4KwcgVLW0QleB55hIjg1Uqc1tzvL', '2025-11-11 10:13:46', '2025-11-11 11:37:22'),
('SP233', 'DM010', 'Giỏ Quà Biếu Sếp ', 627000.00, 7, '1zqflvgk7KTseldq6GRCdzDswhQMgnkec', '2025-11-11 10:13:46', '2025-11-11 10:13:46'),
('SP234', 'DM010', 'Giỏ Vitamin Xanh 1', 719000.00, 31, '1YEwSKnP_f4sLFbUo-dt66kPwGY7ay7Xz', '2025-11-11 10:13:46', '2025-11-11 11:37:36'),
('SP235', 'DM010', 'Giỏ Trái Cây Nhập 2', 420000.00, 25, '1xVfugg0k6oPGPxVm6_K-Nvma-6-X869A', '2025-11-11 10:13:47', '2025-11-11 11:37:22'),
('SP236', 'DM010', 'Giỏ Vitamin Xanh 6', 436000.00, 7, '1uW9v-EMVpbfFE9EuQesn6PqiJZjZaqiU', '2025-11-11 10:13:47', '2025-11-11 11:42:11'),
('SP237', 'DM010', 'Giỏ Trái Cây Nhập 3', 489000.00, 39, '1WxCEtHAqTw7A9O53CnrXgMuJrbh3Dv12', '2025-11-11 10:13:47', '2025-11-22 11:02:13'),
('SP238', 'DM010', 'Giỏ Vitamin Xanh 3', 738000.00, 14, '1zqflvgk7KTseldq6GRCdzDswhQMgnkec', '2025-11-11 10:13:47', '2025-11-11 11:37:36'),
('SP239', 'DM010', 'Giỏ Quà Biếu đồng nghiệp ', 595000.00, 11, '1vcfG5_zvGOObcKxTuII1Pk4Agtm-spE-', '2025-11-11 10:13:47', '2025-11-11 10:13:47'),
('SP240', 'DM010', 'Giỏ Quà Biếu 3 ', 735000.00, 28, '1qxr05WoPKVbT01P8NmetDDdoxjFnVFNo', '2025-11-11 10:13:47', '2025-11-11 10:13:47'),
('SP241', 'DM010', 'Giỏ Trái Cây Tươi ', 554000.00, 21, '1VNXWSawyZXFL9dP5QhtQ19eC3GEZonAs', '2025-11-11 10:13:47', '2025-11-11 10:13:47'),
('SP242', 'DM010', 'Giỏ Trái Cây Nhập 4', 611000.00, 40, '1uW9v-EMVpbfFE9EuQesn6PqiJZjZaqiU', '2025-11-11 10:13:47', '2025-11-22 10:57:30'),
('SP243', 'DM010', 'Giỏ Quà Sức Khỏe 1', 615000.00, 21, '1YEwSKnP_f4sLFbUo-dt66kPwGY7ay7Xz', '2025-11-11 10:13:47', '2025-11-11 11:24:05'),
('SP244', 'DM010', 'Giỏ Vitamin Xanh 4', 621000.00, 6, '1vcfG5_zvGOObcKxTuII1Pk4Agtm-spE-', '2025-11-11 10:13:47', '2025-11-11 11:37:36'),
('SP245', 'DM010', 'Giỏ Quà Biếu 6', 769000.00, 48, '1WxCEtHAqTw7A9O53CnrXgMuJrbh3Dv12', '2025-11-11 10:13:47', '2025-11-11 11:41:09'),
('SP246', 'DM010', 'Giỏ Quà Biếu 7', 459000.00, 22, '1Y53IjSCg7WJFjQ62QULyoKeOIqruXgYv', '2025-11-11 10:13:47', '2025-11-11 11:41:18'),
('SP247', 'DM010', 'Giỏ Vitamin Xanh 5', 675000.00, 26, '1ohbYKabFWf2SnEk_IWDp2cTL1J__OQ6R', '2025-11-11 10:13:47', '2025-11-11 11:41:54'),
('SP248', 'DM010', 'Giỏ Quà Biếu 8', 512000.00, 38, '17s1bmSAVT78MZ6nOPVfih_wqccOV1plc', '2025-11-11 10:13:47', '2025-11-11 11:41:29'),
('SP249', 'DM010', 'Giỏ Quà Biếu 4', 601000.00, 44, '1RYTUyvw9aNFVWh-NWECyrd75Px69nfY4', '2025-11-11 10:13:47', '2025-11-11 11:37:00'),
('SP250', 'DM010', 'Giỏ Trái Cây Cao Cấp 2', 584000.00, 25, '1xdSRO9nL0Cm6U2v5YuP24BT9UyzZTgZI', '2025-11-11 10:13:47', '2025-11-11 11:37:13'),
('SP251', 'DM010', 'Giỏ Vitamin Xanh 2 ', 648000.00, 25, '12_r8zHEHkPsrlEhzdWv0rR4ifc7w5u3F', '2025-11-11 10:13:47', '2025-11-11 10:13:47'),
('SP252', 'DM010', 'Giỏ Trái Cây Nhập 5', 589000.00, 34, '1k05jSq9yLiRwl3Rc5eT6hnOTMzvYLT12', '2025-11-11 10:13:47', '2025-11-11 11:37:22'),
('SP253', 'DM010', 'Giỏ Quà Sức Khỏe ', 522000.00, 19, '1FL3ntZn6pcIelOU5mNCNbUv33inHPfzk', '2025-11-11 10:13:47', '2025-11-11 10:13:47'),
('SP254', 'DM010', 'Giỏ Trái Cây Tươi 1 ', 798000.00, 13, '1XjPjHMXW7HSaD8j6lg01tq8d9Dwav4xW', '2025-11-11 10:13:47', '2025-11-11 10:13:47'),
('SP255', 'DM010', 'Giỏ Trái Cây Tươi 2 ', 728000.00, 35, '1pLSQHCvxCTf64yAzWBJGngyjAYRYcX4y', '2025-11-11 10:13:47', '2025-11-11 10:13:47'),
('SP256', 'DM010', 'Giỏ Trái Cây Cao Cấp 3', 636000.00, 39, '1bCrrMtBjxiUObgPk_kqmXePTZtX8mQrD', '2025-11-11 10:13:47', '2025-11-11 11:37:13'),
('SP257', 'DM010', 'Giỏ Trái Cây Tươi 3', 722000.00, 34, '16uQLKUT3Pc7K4P3HEf4QrkS3TeAD1QLX', '2025-11-11 10:13:47', '2025-11-11 11:23:04'),
('SP258', 'DM010', 'Giỏ Quà Biếu 1 ', 456000.00, 36, '1OCPzPYF9EjoKSMLrWNTjdl-wdkga8MgQ', '2025-11-11 10:13:47', '2025-11-11 10:13:47'),
('SP259', 'DM010', 'Giỏ Quà Biếu 2 ', 543000.00, 17, '1wAYMdo1VPTgYqWAyvgxfEn2ALmq2JX58', '2025-11-11 10:13:47', '2025-11-11 10:13:47'),
('SP260', 'DM010', 'Giỏ Quà Biếu 5', 725000.00, 29, '14-kPEBAevjajBPD96cvUY4TOqkP5v3hO', '2025-11-11 10:13:47', '2025-11-11 11:37:00'),
('SP261', 'DM011', 'thịnh ngu', 2000.00, 7, 'chiha.jpg', '2025-11-12 16:22:39', '2025-11-22 15:02:37');

--
-- Bẫy `products`
--
DELIMITER $$
CREATE TRIGGER `before_insert_product` BEFORE INSERT ON `products` FOR EACH ROW SET NEW.product_id = CONCAT('SP', LPAD((SELECT IFNULL(MAX(CAST(SUBSTRING(product_id, 3) AS UNSIGNED)), 0) + 1 FROM Products), 3, '0'))
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `user_id` varchar(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` enum('user','admin') DEFAULT 'user',
  `shipping_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `phone_number`, `created_at`, `updated_at`, `role`, `shipping_address`) VALUES
('KH001', 'dung', '123', 'Nguyễn Mạnh Dũng', 'dung@gmail.com', '1234567891', '2025-11-11 15:38:01', '2025-11-22 10:26:56', 'user', 'hi'),
('KH002', 'admin', 'admin', 'Anh Dũng Nè', 'dungadmin@gmail.com', '1234567891', '2025-11-11 15:57:05', '2025-11-22 10:27:21', 'admin', 'hi'),
('KH003', 'dung123', 'dung123', 'Mạnh Dũng', 'dung123@gmail.com', '1234567891', '2025-11-12 10:27:27', '2025-11-22 10:57:30', 'user', NULL),
('KH004', '123123', '123123', 'khách hàng vãng lai', '123@gmail.com', '0123456789', '2025-11-21 04:19:52', '2025-11-22 15:02:37', 'user', 'hẹ hẹ');

--
-- Bẫy `users`
--
DELIMITER $$
CREATE TRIGGER `before_insert_user` BEFORE INSERT ON `users` FOR EACH ROW SET NEW.user_id = CONCAT('KH', LPAD((SELECT IFNULL(MAX(CAST(SUBSTRING(user_id, 3) AS UNSIGNED)), 0) + 1 FROM Users), 3, '0'))
$$
DELIMITER ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Chỉ mục cho bảng `delivery_methods`
--
ALTER TABLE `delivery_methods`
  ADD PRIMARY KEY (`delivery_method_id`);

--
-- Chỉ mục cho bảng `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_method_id` (`payment_method_id`),
  ADD KEY `delivery_method_id` (`delivery_method_id`);

--
-- Chỉ mục cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`order_detail_id`),
  ADD UNIQUE KEY `order_id` (`order_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`payment_method_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`payment_method_id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`delivery_method_id`) REFERENCES `delivery_methods` (`delivery_method_id`);

--
-- Các ràng buộc cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
