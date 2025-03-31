-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 09, 2025 at 02:19 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nuloof`
--

-- --------------------------------------------------------

--
-- Table structure for table `reports_table`
--

CREATE TABLE `reports_table` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `QR_CODE` varchar(255) DEFAULT NULL,
  `ITEM_IMAGE` varchar(255) DEFAULT NULL,
  `ITEM_STATUS` varchar(100) DEFAULT NULL,
  `HOLDING_STATUS` varchar(100) DEFAULT NULL,
  `ITEM_NAME` varchar(255) DEFAULT NULL,
  `ITEM_CATEGORY` varchar(100) DEFAULT NULL,
  `ITEM_COLOR` varchar(100) DEFAULT NULL,
  `ITEM_BRAND` varchar(100) DEFAULT NULL,
  `ITEM_DESCRIPTION` text DEFAULT NULL,
  `FLOOR_NUMBER` varchar(100) DEFAULT NULL,
  `ROOM_NUMBER` varchar(100) DEFAULT NULL,
  `ITEM_DATE` date DEFAULT NULL,
  `ITEM_TIME` time DEFAULT NULL,
  `email_add` varchar(255) DEFAULT NULL,
  `non_user_email` VARCHAR(255) DEFAULT NULL,
  `match_status` varchar(50) NOT NULL DEFAULT 'not_found',
  `matched_with` int(11) DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'active',
  `STORAGE_LOCATION` VARCHAR(100) DEFAULT NULL,
  `VERIFIED_STATUS` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`report_id`),
  KEY `email_add` (`email_add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=45;

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

CREATE TABLE `user_info` (
  `id_number` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email_add` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified` TINYINT(1) DEFAULT 0,
  `otp` int(6) NOT NULL,
  PRIMARY KEY (`id_number`),
  UNIQUE KEY `email_add` (`email_add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=512252;

-- --------------------------------------------------------

--
-- Dumping data for table `user_info`
--

INSERT INTO `user_info` (`id_number`, `first_name`, `middle_name`, `last_name`, `password`, `email_add`, `created_at`)
VALUES
(2024330000, 'Juan', '', 'Dela Cruz', '$2y$10$3Z6bLy/vI5/zRIGTIPCPAeGwpSQcVvcXNDRZR3G02aJE8eu7Wg30q', 'delacruzj@students.nu-fairview.edu.ph', '2025-02-10 17:36:11'),
(2024330001, 'Maria', '', 'Leonora', '$2y$10$bMrFC20ZNxFb89Y332oqxuoEp3QPWsYzafkPAI.7Qj6KdwciZYbSu', 'leonoram@students.nu-fairview.edu.ph', '2025-02-10 17:38:14');

-- --------------------------------------------------------

--
-- Foreign Key Constraints
--

ALTER TABLE `reports_table`
  ADD CONSTRAINT `reports_table_ibfk_1` FOREIGN KEY (`email_add`) REFERENCES `user_info` (`email_add`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ALTER TABLE reports_table ADD COLUMN STORAGE_LOCATION VARCHAR(100) DEFAULT NULL;

-- ALTER TABLE `reports_table` ADD COLUMN `non_user_email` VARCHAR(255) DEFAULT NULL;

-- ALTER TABLE reports_table ADD COLUMN status VARCHAR(50) DEFAULT 'active';

-- ALTER TABLE reports_table ADD COLUMN VERIFIED_STATUS TINYINT(1) DEFAULT 0;

-- ALTER TABLE user_info ADD COLUMN verified TINYINT(1) DEFAULT 0;

-- ALTER TABLE user_info ADD COLUMN otp INT(6) NOT NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
