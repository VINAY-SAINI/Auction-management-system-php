-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 12, 2024 at 12:37 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `auction_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `ams_auctionitem`
--

CREATE TABLE `ams_auctionitem` (
  `ID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `min_price` decimal(10,2) NOT NULL,
  `max_price` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `added_time` datetime DEFAULT current_timestamp(),
  `updated_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` tinyint(4) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ams_auctionitem`
--

INSERT INTO `ams_auctionitem` (`ID`, `name`, `description`, `min_price`, `max_price`, `created_by`, `is_deleted`, `added_time`, `updated_time`, `start_time`, `end_time`, `status`, `updated_by`) VALUES
(2, 'qw', 'qw', 1.00, 22.00, 10, 1, '2024-08-12 10:40:09', '2024-08-12 09:07:32', NULL, NULL, 1, NULL),
(3, 'qq', 'qq', 1.00, 3.00, 10, 0, '2024-08-12 11:07:40', '2024-08-12 10:18:12', NULL, NULL, 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ams_bid`
--

CREATE TABLE `ams_bid` (
  `ID` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `buyer_id` int(11) DEFAULT NULL,
  `auction_item_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ams_roles`
--

CREATE TABLE `ams_roles` (
  `ID` int(11) NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `can_create_user` tinyint(1) DEFAULT 0,
  `can_create_manager` tinyint(1) DEFAULT 0,
  `can_create_item` tinyint(1) DEFAULT 0,
  `can_delete_item` tinyint(1) DEFAULT 0,
  `can_start_auction` tinyint(1) DEFAULT 0,
  `can_place_bid` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ams_roles`
--

INSERT INTO `ams_roles` (`ID`, `role_name`, `can_create_user`, `can_create_manager`, `can_create_item`, `can_delete_item`, `can_start_auction`, `can_place_bid`) VALUES
(1, 'Admin', 1, 1, 1, 1, 1, 0),
(2, 'Manager', 1, 0, 0, 1, 1, 0),
(3, 'Buyer', 0, 0, 0, 0, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `ams_users`
--

CREATE TABLE `ams_users` (
  `ID` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_on` datetime DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ams_users`
--

INSERT INTO `ams_users` (`ID`, `username`, `password`, `email`, `role_id`, `created_by`, `is_deleted`, `created_on`, `updated_by`, `updated_on`) VALUES
(10, 'username', '$2y$10$5dkx.SHW3yw.Y2IHw1.09uj3ZALke8HuYZ2JGyoFUPy7VkX2/HHcO', 'VINAY@GMAIL.COM', 1, NULL, 0, '2024-08-12 13:47:44', NULL, '2024-08-12 13:47:44'),
(19, 'rohit', '$2y$10$DKlh9xvWKfM1TFmbGOJiIekD09jXGfpWHxYMllen2NEv8G3Nsn1Qe', 'rohit@gmail.com', 1, NULL, 0, '2024-08-12 16:04:20', NULL, '2024-08-12 16:04:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ams_auctionitem`
--
ALTER TABLE `ams_auctionitem`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `ams_bid`
--
ALTER TABLE `ams_bid`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `auction_item_id` (`auction_item_id`);

--
-- Indexes for table `ams_roles`
--
ALTER TABLE `ams_roles`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `ams_users`
--
ALTER TABLE `ams_users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ams_auctionitem`
--
ALTER TABLE `ams_auctionitem`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ams_bid`
--
ALTER TABLE `ams_bid`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ams_roles`
--
ALTER TABLE `ams_roles`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ams_users`
--
ALTER TABLE `ams_users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ams_auctionitem`
--
ALTER TABLE `ams_auctionitem`
  ADD CONSTRAINT `ams_auctionitem_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `ams_users` (`ID`),
  ADD CONSTRAINT `ams_auctionitem_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `ams_users` (`ID`);

--
-- Constraints for table `ams_bid`
--
ALTER TABLE `ams_bid`
  ADD CONSTRAINT `ams_bid_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `ams_users` (`ID`),
  ADD CONSTRAINT `ams_bid_ibfk_2` FOREIGN KEY (`auction_item_id`) REFERENCES `ams_auctionitem` (`ID`);

--
-- Constraints for table `ams_users`
--
ALTER TABLE `ams_users`
  ADD CONSTRAINT `ams_users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `ams_roles` (`ID`),
  ADD CONSTRAINT `ams_users_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `ams_users` (`ID`),
  ADD CONSTRAINT `ams_users_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `ams_users` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
