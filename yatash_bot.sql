-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 02, 2023 at 04:26 PM
-- Server version: 10.3.39-MariaDB
-- PHP Version: 8.1.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `yatash_bot`
--

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `parent_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `price` decimal(10,0) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `title`, `parent_id`, `price`) VALUES
(1, '1 کاربره', 0, NULL),
(2, '2 کاربره', 0, NULL),
(3, '5 کاربره', 0, NULL),
(4, '10 کاربره', 0, NULL),
(5, '30 گیگ', 1, NULL),
(6, '50 گیگ', 1, NULL),
(7, '80 گیگ', 1, NULL),
(8, 'یک ماه', 5, 55000),
(9, 'سه ماهه', 5, 148000),
(10, 'شش ماهه', 5, 264000),
(11, 'یک ماه', 6, 65000),
(12, 'سه ماهه', 6, 175000),
(13, 'شش ماهه', 6, 312000),
(14, 'یک ماه', 7, 98000),
(15, 'سه ماهه', 7, 264000),
(16, 'شش ماهه', 7, 470000),
(17, '50 گیگ', 2, NULL),
(18, '80 گیگ', 2, NULL),
(19, '100 گیگ', 2, NULL),
(20, 'یک ماه', 17, 75000),
(21, 'سه ماهه', 17, 202000),
(22, 'شش ماهه', 17, 360000),
(23, 'یک ماه', 18, 108000),
(24, 'سه ماهه', 18, 291000),
(25, 'شش ماهه', 18, 518000),
(26, 'یک ماه', 19, 130000),
(27, 'سه ماهه', 19, 351000),
(28, 'شش ماهه', 19, 624000),
(29, '120 گیگ', 3, NULL),
(30, '150 گیگ', 3, NULL),
(31, '200 گیگ', 3, NULL),
(32, '250 گیگ', 3, NULL),
(33, 'یک ماه', 29, 182000),
(34, 'سه ماهه', 29, 491000),
(35, 'شش ماهه', 29, 873000),
(36, 'یک ماه', 30, 215000),
(37, 'سه ماهه', 30, 580000),
(38, 'شش ماهه', 30, 1032000),
(39, 'یک ماه', 31, 270000),
(40, 'سه ماهه', 31, 729000),
(41, 'شش ماهه', 31, 1296000),
(42, 'یک ماه', 32, 325000),
(43, 'سه ماهه', 32, 877000),
(44, 'شش ماهه', 32, 1560000),
(45, '200 گیگ', 4, NULL),
(46, '250 گیگ', 4, NULL),
(47, '300 گیگ', 4, NULL),
(48, '350 گیگ', 4, NULL),
(49, '500 گیگ', 4, NULL),
(50, 'یک ماه', 45, 320000),
(51, 'سه ماهه', 45, 864000),
(52, 'شش ماهه', 45, 1536000),
(53, 'یک ماه', 46, 375000),
(54, 'سه ماهه', 46, 1012000),
(55, 'شش ماهه', 46, 1800000),
(56, 'یک ماه', 47, 430000),
(57, 'سه ماهه', 47, 1161000),
(58, 'شش ماهه', 47, 2064000),
(59, 'یک ماه', 48, 485000),
(60, 'سه ماهه', 48, 1309000),
(61, 'شش ماهه', 48, 2328000),
(62, 'یک ماه', 49, 650000),
(63, 'سه ماهه', 49, 1755000),
(64, 'شش ماهه', 49, 3120000);

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sellers`
--

INSERT INTO `sellers` (`id`, `user_id`, `username`, `password`) VALUES
(1, 3, 'test', '123456');

-- --------------------------------------------------------

--
-- Table structure for table `story`
--

CREATE TABLE `story` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `story`
--

INSERT INTO `story` (`id`, `user_id`, `title`, `data`) VALUES
(4, 1, 'welcomeSeller', ''),
(8, 2, 'welcomeSeller', ''),
(17, 4, 'sellerLoginPass', 'test'),
(24, 5, 'changePriceLoop', '0'),
(55, 3, 'welcomeSeller', '8'),
(57, 6, 'chooseSellerOrBuyer', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `telegram_id` double UNSIGNED NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `user_reference` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `telegram_id`, `firstname`, `lastname`, `username`, `user_reference`) VALUES
(1, 82992493, 'Amir', '', 'Amiir_9', 0),
(2, 5029047272, 'Negar', '', 'negarnjz', 0),
(3, 885798890, 'MohammadReza', 'Taheri', 'khodeAmooReza', 0),
(4, 1449384379, 'مهلا محمدی', '', '', 0),
(5, 1629803590, 'Hamed', 'Zargar', 'Hamedzargar68', 0),
(6, 5010693176, 'MrMorgan', '', 'x_Mr_Morgan_x', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_packages`
--

CREATE TABLE `user_packages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `price` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `user_packages`
--

INSERT INTO `user_packages` (`id`, `user_id`, `package_id`, `price`) VALUES
(1, 3, 46, 10000),
(2, 3, 53, 400000),
(3, 5, 11, 75000),
(4, 3, 8, 60000),
(5, 2, 8, 60000),
(6, 1, 13, 369000);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `story`
--
ALTER TABLE `story`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `telegram_id` (`telegram_id`);

--
-- Indexes for table `user_packages`
--
ALTER TABLE `user_packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`package_id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `story`
--
ALTER TABLE `story`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_packages`
--
ALTER TABLE `user_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
