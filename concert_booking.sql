-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2026 at 10:10 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `concert_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `artist` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `artist_bio` text DEFAULT NULL,
  `artist_image` varchar(255) DEFAULT NULL,
  `event_image` varchar(255) DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `venue` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_sale` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `artist`, `description`, `artist_bio`, `artist_image`, `event_image`, `event_date`, `venue`, `location`, `image`, `status`, `created_at`, `is_sale`) VALUES
(1, 'DICE YEAR TWO', 'DICE YEAR TWO', 'งานคอนเสิร์ตสุดยิ่งใหญ่แห่งปี พบกับศิลปินชื่อดังมากมาย', 'DICE เป็นบอยกรุ๊ปไทยภายใต้สังกัด SONRAY MUSIC เปิดตัวอย่างเป็นทางการในปี 2023 มีสมาชิกทั้งหมด 10 คน โดดเด่นด้าน Performance การเต้น และพลังบนเวที\r\n\r\nสมาชิกประกอบด้วย MIN, ALEX, CHEESE, JAY, OBO, MADDOC, OTTO, FRAME, APO และ JISANG\r\n\r\nแนวเพลงของวงเน้น POP / Dance / R&B ผสมความเป็นสากล พร้อมภาพลักษณ์ทันสมัยและเอกลักษณ์เฉพาะตัว\r\n\r\nผลงานเด่นของวงได้รับความนิยมอย่างรวดเร็ว และมีฐานแฟนคลับเพิ่มขึ้นต่อเนื่องทั่วประเทศ\r\n\r\nDICE YEAR TWO คือคอนเสิร์ตใหญ่ที่รวบรวมโชว์สุดพิเศษ โปรดักชันเต็มรูปแบบ และเซอร์ไพรส์สำหรับแฟนๆ\r\n', 'DICE 2.jpg', 'DICE 2.jpg', '2025-02-21 19:00:00', 'THUNDER DOME', 'กรุงเทพมหานคร', NULL, 'upcoming', '2026-03-12 07:53:45', 0),
(2, 'Three Man Down Live At', 'Three Man Down', 'คอนเสิร์ตสุดพิเศษจาก Three Man Down', 'สมาชิกวง\r\n\r\nในช่วงต่าง ๆ สมาชิกประกอบด้วย:\r\n\r\nกิต (กฤตย์ จีรพัฒนานุวงศ์) – นักร้องนำ\r\n\r\nตูน (พีรพล เอี่ยมจำรัส) – กีตาร์\r\n\r\nเต (เตธนันท์ วงศ์ปรีชาโชค) – กลอง\r\n\r\nเส็ง (วิศรุต ปฐมสิริไพศาล) – คีย์บอร์ด\r\n\r\n(อดีต) โอม (กิจฎิเมธ ชาญพานิช) – เบส\r\n\r\n\r\n\r\nThree Man Down เป็น วงดนตรีป็อปร็อก (Pop-Rock / T-POP) จาก กรุงเทพมหานคร, ประเทศไทย ก่อตั้งขึ้นประมาณปี พ.ศ. 2559 (ค.ศ. 2016) โดยสมาชิกเริ่มรู้จักกันตอนเรียนมหาวิทยาลัยและรวมตัวกันเป็นวงดนตรีอย่างจริงจัง\r\n\r\n👉 จุดเริ่มต้น\r\n\r\nสมาชิกวงเริ่มจากเพื่อน ๆ ที่เรียนอยู่มหาวิทยาลัยกรุงเทพ และมีวงดนตรีของตัวเองอยู่แล้วหลายวงก่อนรวมตัวกันเป็นวงนี้\r\n\r\nชื่อ Three Man Down มาจากการรวมตัวของคนที่เคยอยู่ใน 3 วงดนตรีเดิม ก่อนหน้านั้น\r\n\r\n🎶 แนวเพลงและแนวทาง\r\n\r\nเพลงของวงอยู่ในแนว ป็อป ป็อปร็อก และ T-POP ที่เข้าถึงได้ง่ายและแต่งเองด้วยความเป็นตัวตนของสมาชิก\r\n\r\nมีเนื้อเพลงที่เน้นเรื่องความรัก ความรู้สึก และชีวิตคนรุ่นใหม่ ซึ่งช่วยให้วงได้รับความนิยมอย่างรวดเร็ว\r\n\r\n🎤 ผลงานเพลงเด่น\r\n\r\nวงนี้มีหลายผลงานที่ฮิตติดหู เช่น:\r\n\r\nฝนตกไหม\r\n\r\nถ้าเธอรักฉันจริง\r\n\r\nข้างกัน\r\n\r\nเลือกคนที่เขารักเรา\r\n\r\nฝันถึงแฟนเก่า\r\nเพลงเหล่านี้ถูกนำไปเล่นและสตรีมเป็นจำนวนมากทั้งบนแพลตฟอร์มออนไลน์และออกอากาศทั่วไป\r\n', 'https://www.matichon.co.th/wp-content/uploads/2022/08/%E0%B8%97%E0%B8%A3%E0%B8%B5%E0%B9%81%E0%B8%A1%E0%B8%99%E0%B8%94%E0%B8%B2%E0%B8%A7%E0%B8%99%E0%B9%8C-2.jpg', 'https://res.theconcert.com/c_thumb/c22b42eb37691e527052ba138f1c8a0a1/TCC-Pick-Three-Man-Down-04.jpg', '2025-07-07 18:00:00', 'อิมแพ็คอารีน่า', 'เมืองทองธานี', '', 'upcoming', '2026-03-12 07:53:45', 0),
(3, 'DEPT\r\nnot smoke, but cloud album launch concert', 'DEPT', 'คอนเสิร์ตเปิดอัลบั้มที่ 3 not smoke, but cloud จาก DEPT\r\nมาเป็นส่วนนึงบนท้องฟ้าของพวกเรา ที่เต็มไปด้วย', 'Dept เป็นวงดนตรีอินดี้ป๊อปจากประเทศไทย ที่มีเอกลักษณ์ด้านดนตรีซินธ์ป๊อปและเมโลดี้ฟังสบาย ถ่ายทอดเรื่องราวความรักและความรู้สึกผ่านบทเพลงอย่างลงตัว วงประกอบด้วย เบนซ์ และ ลุค โดยมีผลงานเพลงที่ได้รับความนิยมมากมาย เช่น ลา ลา ลา และ หรือไม่ใช่ จนกลายเป็นหนึ่งในวงอินดี้รุ่นใหม่ที่มีแฟนเพลงจำนวนมากในประเทศไทย', 'Dept.jpg', 'Dept.jpg', '2025-02-22 19:00:00', 'At Moon Star Studio 8', 'กรุงเทพมหานคร', NULL, 'upcoming', '2026-03-12 07:53:45', 1),
(5, 'NUVO ORIGINAL ต้นตำรับเลย', 'NUVO', 'คอนเสิร์ตใหญ่จากวง NUVO ในคอนเสิร์ต ORIGINAL ต้นตำรับเลย', 'NUVO เป็นวงดนตรีป๊อปร็อกชื่อดังของประเทศไทย ก่อตั้งขึ้นในปี พ.ศ. 2531 ประกอบด้วยสมาชิก ได้แก่ จอห์น รัตนเวโรจน์, โจ ก้องเกียรติ จตุรัสพร, ก้อง สหรัถ สังคปรีชา, มาร์ค อัครเดช โยธาจันทร์, เต๋อ เรวัต พุทธินันทน์ และสุเทพ วงศ์กำแหง วงมีผลงานเพลงฮิตมากมาย อาทิ หลอกกันเล่นเลย, สุดสุดไปเลย และ เป็นอย่างงี้ตั้งแต่เกิดเลย จนได้รับการยกย่องให้เป็นหนึ่งในวงดนตรีระดับตำนานของไทย\r\n', 'nuvo.jpg', 'nuvo.jpg', '2026-08-01 19:00:00', 'PARAGON HALL', 'กรุงเทพ', NULL, 'upcoming', '2026-05-23 06:40:27', 0),
(6, 'BTS WORLD TOUR ARIRANG', 'BTS', '\r\n', 'BTS (방탄소년단) วงบอยแบนด์จากเกาหลีใต้ สังกัด HYBE เดบิวต์ปี 2013 มีสมาชิก 7 คน ได้แก่ RM, Jin, SUGA, J-Hope, Jimin, V และ Jungkook เริ่มต้นจากวงฮิปฮอปเล็ก ๆ ก่อนโตขึ้นเป็นศิลปินที่มียอดขายอัลบั้มสูงสุดในประวัติศาสตร์เกาหลี เพลงดังอย่าง Dynamite, Butter, Boy With Luv และ DNA ติดชาร์ต Billboard Hot 100 หลายครั้ง แฟนคลับรู้จักในชื่อ ARMY หยุดพักวงชั่วคราวปี 2022 เพื่อรับราชการทหาร และกลับมารวมตัวครบวงในปี 2025', 'bts.png', 'bts.png', '2026-12-03 00:00:00', 'ราชมังคลากีฬาสถาน', NULL, NULL, 'upcoming', '2026-06-04 09:04:46', 0);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','cancelled','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_slip` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `event_id`, `total_amount`, `status`, `payment_method`, `payment_slip`, `created_at`, `paid_at`) VALUES
(1, 'ORD-69B64AD03BAFE', 2, 1, 1500.00, 'paid', 'พร้อมเพย์', 'slip_1_1773554649.jpg', '2026-03-15 05:59:44', '2026-03-15 06:04:09'),
(2, 'ORD-69B652A3DE9E3', 2, 1, 1500.00, 'paid', 'พร้อมเพย์', 'slip_2_1773556396.jpg', '2026-03-15 06:33:07', '2026-03-15 06:33:16'),
(3, 'ORD-69F575BA7E090', 3, 1, 1600.00, 'paid', 'พร้อมเพย์', 'slip_3_1777694173.jpg', '2026-05-02 03:55:38', '2026-05-02 03:56:13'),
(4, 'ORD-69F57651EA666', 3, 2, 2400.00, 'paid', 'พร้อมเพย์', 'slip_4_1777694295.jpg', '2026-05-02 03:58:09', '2026-05-02 03:58:15'),
(5, 'ORD-69FDC825CF1F8', 2, 3, 1500.00, 'paid', 'พร้อมเพย์', 'slip_5_1778239531.jpg', '2026-05-08 11:25:25', '2026-05-08 11:25:31'),
(6, 'ORD-69FEAF242B523', 2, 2, 1000.00, 'pending', NULL, NULL, '2026-05-09 03:51:00', NULL),
(7, 'ORD-69FEB0B264E6A', 2, 1, 800.00, 'pending', NULL, NULL, '2026-05-09 03:57:38', NULL),
(8, 'ORD-69FEB29EB327A', 2, 3, 1500.00, 'pending', NULL, NULL, '2026-05-09 04:05:50', NULL),
(9, 'ORD-69FEB84E53737', 2, 1, 800.00, 'pending', NULL, NULL, '2026-05-09 04:30:06', NULL),
(10, 'ORD-69FEB8E71AB0D', 2, 1, 800.00, 'pending', NULL, NULL, '2026-05-09 04:32:39', NULL),
(11, 'ORD-69FEB8F1E73FA', 2, 3, 800.00, 'paid', 'พร้อมเพย์', 'slip_11_1778301176.jpg', '2026-05-09 04:32:49', '2026-05-09 04:32:56'),
(12, 'ORD-69FEB9B9BEEF6', 2, 1, 800.00, 'paid', 'พร้อมเพย์', 'slip_12_1778301374.jpg', '2026-05-09 04:36:09', '2026-05-09 04:36:14'),
(13, 'ORD-69FEB9C849FC5', 2, 2, 1000.00, 'paid', 'พร้อมเพย์', 'slip_13_1778301388.jpg', '2026-05-09 04:36:24', '2026-05-09 04:36:28'),
(14, 'ORD-69FEBF168DB15', 2, 3, 800.00, 'paid', 'พร้อมเพย์', 'slip_14_1780565263.jpg', '2026-05-09 04:59:02', '2026-06-04 09:27:43'),
(15, 'ORD-69FEC0767582F', 2, 3, 1500.00, 'paid', 'พร้อมเพย์', 'slip_15_1778303117.jpg', '2026-05-09 05:04:54', '2026-05-09 05:05:17'),
(16, 'ORD-6A2144094323B', 2, 6, 12000.00, 'paid', 'พร้อมเพย์', 'slip_16_1780565007.jpg', '2026-06-04 09:23:21', '2026-06-04 09:23:27'),
(17, 'ORD-6A22A817429D3', 2, 3, 1600.00, 'pending', NULL, NULL, '2026-06-05 10:42:31', NULL),
(18, 'ORD-6A5F2681413DC', 2, 3, 1500.00, 'paid', 'พร้อมเพย์', 'slip_18_1784620695.jpg', '2026-07-21 07:57:53', '2026-07-21 07:58:15'),
(19, 'ORD-6AACDC9DA34EB', 2, 3, 800.00, 'pending', NULL, NULL, '2026-09-18 06:39:25', NULL),
(20, 'ORD-6AACDCCCF2213', 2, 3, 800.00, 'pending', NULL, NULL, '2026-09-18 06:40:12', NULL),
(21, 'ORD-6AACE03AC25FF', 2, 6, 6000.00, 'pending', NULL, NULL, '2026-09-18 06:54:50', NULL),
(22, 'ORD-6AACE16FC763B', 2, 2, 2500.00, 'pending', NULL, NULL, '2026-09-18 06:59:59', NULL),
(23, 'ORD-6AACE199DBAE9', 2, 2, 2500.00, 'pending', NULL, NULL, '2026-09-18 07:00:41', NULL),
(24, 'ORD-6AACE3171B286', 2, 2, 2500.00, 'pending', NULL, NULL, '2026-09-18 07:07:03', NULL),
(25, 'ORD-6AACE3F12C2E2', 2, 2, 2500.00, 'pending', NULL, NULL, '2026-09-18 07:10:41', NULL),
(26, 'ORD-6AACE58946DE0', 2, 2, 2500.00, 'pending', NULL, NULL, '2026-09-18 07:17:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `ticket_type_id` int(11) NOT NULL,
  `resale_listing_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `ticket_type_id`, `resale_listing_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 2, NULL, 1, 1500.00, 1500.00),
(2, 2, 2, NULL, 1, 1500.00, 1500.00),
(3, 3, 3, NULL, 2, 800.00, 1600.00),
(4, 4, 6, NULL, 2, 1200.00, 2400.00),
(5, 5, 24, NULL, 1, 1500.00, 1500.00),
(6, 6, 21, NULL, 1, 1000.00, 1000.00),
(7, 7, 3, NULL, 1, 800.00, 800.00),
(8, 8, 24, NULL, 1, 1500.00, 1500.00),
(9, 9, 18, NULL, 1, 800.00, 800.00),
(10, 10, 18, NULL, 1, 800.00, 800.00),
(11, 11, 23, NULL, 1, 800.00, 800.00),
(12, 12, 18, NULL, 1, 800.00, 800.00),
(13, 13, 21, NULL, 1, 1000.00, 1000.00),
(14, 14, 23, NULL, 1, 800.00, 800.00),
(15, 15, 24, NULL, 1, 1500.00, 1500.00),
(16, 16, 40, NULL, 2, 6000.00, 12000.00),
(17, 17, 23, NULL, 2, 800.00, 1600.00),
(18, 18, 24, NULL, 1, 1500.00, 1500.00),
(19, 19, 23, NULL, 1, 800.00, 800.00),
(20, 20, 23, NULL, 1, 800.00, 800.00),
(21, 21, 40, NULL, 1, 6000.00, 6000.00),
(22, 22, 17, NULL, 1, 2500.00, 2500.00),
(23, 23, 4, NULL, 1, 2500.00, 2500.00),
(24, 24, 17, NULL, 1, 2500.00, 2500.00),
(25, 25, 17, NULL, 1, 2500.00, 2500.00),
(26, 26, 17, NULL, 1, 2500.00, 2500.00);

-- --------------------------------------------------------

--
-- Table structure for table `resale_listings`
--

CREATE TABLE `resale_listings` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `face_price` decimal(10,2) NOT NULL,
  `asking_price` decimal(10,2) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `payout_amount` decimal(10,2) NOT NULL,
  `sell_separately` tinyint(1) NOT NULL DEFAULT 1,
  `bundle_id` varchar(36) DEFAULT NULL,
  `status` enum('active','sold','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `ticket_code` varchar(50) NOT NULL,
  `seat_number` varchar(20) DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `is_split` tinyint(1) DEFAULT 0,
  `split_details` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_types`
--

CREATE TABLE `ticket_types` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_seats` int(11) NOT NULL,
  `available_seats` int(11) NOT NULL,
  `color` varchar(20) DEFAULT '#6C63FF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_types`
--

INSERT INTO `ticket_types` (`id`, `event_id`, `type_name`, `price`, `total_seats`, `available_seats`, `color`) VALUES
(1, 1, 'GOLD', 3000.00, 500, 450, '#FFD700'),
(2, 1, 'SILVER', 1500.00, 1000, 848, '#C0C0C0'),
(3, 1, 'STANDING', 800.00, 2000, 1797, '#6C63FF'),
(4, 2, 'VIP', 2500.00, 200, 179, '#FF6B6B'),
(5, 2, 'GOLD', 1800.00, 600, 550, '#FFD700'),
(6, 2, 'SILVER', 1200.00, 1200, 998, '#C0C0C0'),
(7, 1, '', 800.00, 0, 1798, '#6C63FF'),
(8, 1, '', 1500.00, 0, 848, '#6C63FF'),
(9, 1, '', 3000.00, 0, 450, '#6C63FF'),
(10, 1, '', 800.00, 0, 1798, '#6C63FF'),
(11, 1, '', 1500.00, 0, 848, '#6C63FF'),
(12, 1, '', 3000.00, 0, 450, '#6C63FF'),
(13, 1, '', 800.00, 0, 1798, '#6C63FF'),
(14, 1, '', 1500.00, 0, 848, '#6C63FF'),
(15, 1, '', 3000.00, 0, 450, '#6C63FF'),
(16, 2, '', 1200.00, 0, 500, '#6C63FF'),
(17, 2, '', 2500.00, 0, 196, '#6C63FF'),
(18, 1, '', 800.00, 0, 1795, '#6C63FF'),
(19, 1, '', 1500.00, 0, 848, '#6C63FF'),
(20, 1, '', 3000.00, 0, 450, '#6C63FF'),
(21, 2, '', 1000.00, 0, 498, '#6C63FF'),
(22, 2, '', 2000.00, 0, 300, '#6C63FF'),
(23, 3, '', 800.00, 0, 1792, '#6C63FF'),
(24, 3, '', 1500.00, 0, 844, '#6C63FF'),
(25, 1, '', 3000.00, 0, 450, '#6C63FF'),
(26, 5, 'Zone A', 6000.00, 200, 200, '#FFD700'),
(27, 5, 'Zone B', 5500.00, 200, 200, '#FFC300'),
(28, 5, 'Zone C', 5000.00, 300, 300, '#C0C0C0'),
(29, 5, 'Zone D', 4500.00, 300, 300, '#B0B0B0'),
(30, 5, 'Zone E', 4000.00, 400, 400, '#6C63FF'),
(31, 5, 'Zone F', 3500.00, 400, 400, '#5A54E0'),
(32, 5, 'Zone G', 2500.00, 500, 500, '#4CAF50'),
(33, 5, 'Zone H', 1900.00, 800, 800, '#9E9E9E'),
(40, 6, 'VIP', 6000.00, 200, 197, '#FFD700'),
(41, 6, 'GOLD', 4500.00, 500, 500, '#FFC300'),
(42, 6, 'SILVER', 3000.00, 800, 800, '#C0C0C0'),
(43, 6, 'STANDING', 1500.00, 2000, 2000, '#6C63FF');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `created_at`, `updated_at`) VALUES
(1, 'testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', '0912345678', '2026-03-12 07:53:45', '2026-03-12 07:53:45'),
(2, 'Khasidit', 'khasidit6666@gmail.com', '$2y$10$dHv2pKieY1CIMdAhRHxe8uqWLEmcHpiTojH69XFXi7me1Z0a4s5jm', 'กษิดิศ ชุมแวงวาปี', '0916553254', '2026-03-12 08:21:52', '2026-03-12 08:21:52'),
(3, 'Peem', 'khasidit18@gmail.com', '$2y$10$.90yp/AsYzKx2ky1.hApZeyAodIQdyIYcFPhKaWH62u/DDFkKjccy', 'กษิดิศ ชุมแวงวาปี', '0916553254', '2026-05-02 03:54:19', '2026-05-02 03:54:19'),
(4, 'qqq', 'khasidit1111@gmail.com', '$2y$10$QTO0EsqfMjoW16tMYNV4BOYukkLBbfQ30bu0LRuHQ4iEOA71bOzxG', 'กษิดิศ ชุมแวงาปี', '0916553254', '2026-07-21 07:57:29', '2026-07-21 07:57:29'),
(5, '', '', '$2y$10$ZR5y3I048XEp2mLd/8FK1.8/0YtGLsDpMEID6VPN31O5nI8cuDdEG', '', '', '2026-09-18 06:34:28', '2026-09-18 06:34:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `ticket_type_id` (`ticket_type_id`);

--
-- Indexes for table `resale_listings`
--
ALTER TABLE `resale_listings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `idx_bundle` (`bundle_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_code` (`ticket_code`),
  ADD KEY `order_item_id` (`order_item_id`);

--
-- Indexes for table `ticket_types`
--
ALTER TABLE `ticket_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `resale_listings`
--
ALTER TABLE `resale_listings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_types`
--
ALTER TABLE `ticket_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types` (`id`);

--
-- Constraints for table `resale_listings`
--
ALTER TABLE `resale_listings`
  ADD CONSTRAINT `resale_listings_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  ADD CONSTRAINT `resale_listings_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `resale_listings_ibfk_3` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`);

--
-- Constraints for table `ticket_types`
--
ALTER TABLE `ticket_types`
  ADD CONSTRAINT `ticket_types_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
