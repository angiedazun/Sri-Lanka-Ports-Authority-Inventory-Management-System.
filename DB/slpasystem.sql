-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 11, 2025 at 05:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `slpasystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `severity` enum('info','warning','high','critical') DEFAULT 'info',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `username`, `action`, `details`, `ip_address`, `user_agent`, `severity`, `created_at`) VALUES
(1, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'info', '2025-12-03 23:44:44'),
(2, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'info', '2025-12-04 07:07:06'),
(3, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'info', '2025-12-04 07:29:36'),
(4, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'info', '2025-12-04 07:44:46'),
(5, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'info', '2025-12-04 09:07:38'),
(6, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'info', '2025-12-04 09:13:36'),
(7, 8, 'Anjana', 'login_attempt', '{\"username\":\"Anjana\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'info', '2025-12-04 14:59:51'),
(8, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-06 23:45:58'),
(9, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-07 00:31:36'),
(10, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-07 01:11:14'),
(11, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-07 01:11:14'),
(12, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-07 03:33:26'),
(13, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-07 10:09:13'),
(14, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'info', '2025-12-07 11:33:01'),
(15, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-07 11:34:56'),
(16, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-07 11:55:07'),
(17, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-07 12:00:41'),
(18, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-07 12:01:06'),
(19, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-07 12:32:14'),
(20, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-07 12:54:13'),
(21, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-08 02:45:50'),
(22, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-08 05:30:56'),
(23, NULL, 'guest', 'login_attempt', '{\"username\":\"admin\",\"success\":false,\"reason\":\"Invalid password\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'warning', '2025-12-08 05:35:57'),
(24, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-08 05:36:03'),
(25, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 04:50:31'),
(26, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 06:35:58'),
(27, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 06:42:02'),
(28, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 07:02:00'),
(29, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 07:24:47'),
(30, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 07:46:55'),
(31, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 09:10:02'),
(32, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 09:12:24'),
(33, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 12:07:05'),
(34, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 12:45:18'),
(35, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 12:48:10'),
(36, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 13:14:19'),
(37, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 13:17:59'),
(38, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 13:24:24'),
(39, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 13:48:54'),
(40, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 14:00:01'),
(41, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 16:47:50'),
(42, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 17:25:05'),
(43, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 17:26:57'),
(44, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 17:59:17'),
(45, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 18:15:31'),
(46, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 18:23:16'),
(47, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 18:29:33'),
(48, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 18:43:57'),
(49, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 18:50:08'),
(50, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 19:08:44'),
(51, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 19:27:02'),
(52, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 20:38:44'),
(53, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 20:43:11'),
(54, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 20:44:23'),
(55, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 20:57:27'),
(56, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-09 21:49:28'),
(57, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-10 02:16:27'),
(58, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-10 02:50:52'),
(59, 3, 'admin', 'login_attempt', '{\"username\":\"admin\",\"success\":true,\"reason\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-10 15:16:25');

-- --------------------------------------------------------

--
-- Table structure for table `papers_issuing`
--

CREATE TABLE `papers_issuing` (
  `issue_id` int(11) NOT NULL,
  `paper_id` int(11) NOT NULL,
  `paper_type` varchar(100) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `stock` varchar(50) DEFAULT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `division` varchar(50) NOT NULL,
  `section` varchar(50) NOT NULL,
  `store` varchar(150) DEFAULT NULL,
  `request_officer` varchar(100) DEFAULT NULL,
  `receiver_name` varchar(100) DEFAULT NULL,
  `receiver_emp_no` varchar(20) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `papers_issuing`
--

INSERT INTO `papers_issuing` (`issue_id`, `paper_id`, `paper_type`, `code`, `stock`, `lot`, `division`, `section`, `store`, `request_officer`, `receiver_name`, `receiver_emp_no`, `quantity`, `issue_date`, `remarks`) VALUES
(44, 40, 'Pay slip', 'IS-1001', 'JCT', '2025/LOT1', 'Information Systems Division', 'JCT', '', 'Kamal', 'Anjana', 'EMN-1001', 1, '2025-12-10', 'Urgent');

-- --------------------------------------------------------

--
-- Table structure for table `papers_master`
--

CREATE TABLE `papers_master` (
  `paper_id` int(11) NOT NULL,
  `paper_type` varchar(100) NOT NULL,
  `reorder_level` int(11) DEFAULT 10,
  `jct_stock` int(11) DEFAULT 0,
  `uct_stock` int(11) DEFAULT 0,
  `purchase_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `papers_master`
--

INSERT INTO `papers_master` (`paper_id`, `paper_type`, `reorder_level`, `jct_stock`, `uct_stock`, `purchase_date`) VALUES
(40, 'Pay slip', 10, 9, 10, '2025-12-10');

-- --------------------------------------------------------

--
-- Table structure for table `papers_receiving`
--

CREATE TABLE `papers_receiving` (
  `receive_id` int(11) NOT NULL,
  `paper_id` int(11) NOT NULL,
  `paper_type` varchar(100) NOT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `pr_no` varchar(50) DEFAULT NULL,
  `jct_quantity` int(11) DEFAULT 0,
  `uct_quantity` int(11) DEFAULT 0,
  `lot` varchar(50) DEFAULT NULL,
  `tender_file_no` varchar(50) DEFAULT NULL,
  `invoice` varchar(100) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `receive_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `papers_receiving`
--

INSERT INTO `papers_receiving` (`receive_id`, `paper_id`, `paper_type`, `supplier_name`, `pr_no`, `jct_quantity`, `uct_quantity`, `lot`, `tender_file_no`, `invoice`, `unit_price`, `remarks`, `receive_date`) VALUES
(62, 40, 'Pay slip', 'Narah Computer Forms', '2502470', 10, 10, '2025/LOT1', 'CMS/LP/25/SQ/18/103', '50040101', 3265.00, '4000 Slips per packet', '2025-12-10');

-- --------------------------------------------------------

--
-- Table structure for table `papers_return`
--

CREATE TABLE `papers_return` (
  `return_id` int(11) NOT NULL,
  `paper_id` int(11) NOT NULL,
  `paper_type` varchar(100) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `return_date` date NOT NULL,
  `issue_date` date DEFAULT NULL,
  `receiving_date` date DEFAULT NULL,
  `tender_file_no` varchar(50) DEFAULT NULL,
  `location` varchar(50) NOT NULL,
  `return_by` varchar(100) DEFAULT NULL,
  `invoice` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `division` varchar(100) DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `papers_return`
--

INSERT INTO `papers_return` (`return_id`, `paper_id`, `paper_type`, `code`, `lot`, `supplier_name`, `return_date`, `issue_date`, `receiving_date`, `tender_file_no`, `location`, `return_by`, `invoice`, `quantity`, `division`, `section`, `reason`, `remarks`) VALUES
(26, 40, 'Pay slip', 'IS-1001', '2025/LOT1', 'Narah Computer Forms', '2025-12-10', '2025-12-10', '2025-12-10', 'CMS/LP/25/SQ/18/103', 'JCT', 'Saman', '50040101', 1, 'Information Systems Division', 'JCT', 'Damaged', 'No');

-- --------------------------------------------------------

--
-- Table structure for table `ribbons_issuing`
--

CREATE TABLE `ribbons_issuing` (
  `issue_id` int(11) NOT NULL,
  `ribbon_id` int(11) NOT NULL,
  `ribbon_model` varchar(100) NOT NULL,
  `code` varchar(100) DEFAULT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `stock` varchar(50) DEFAULT NULL,
  `division` varchar(50) NOT NULL,
  `section` varchar(50) NOT NULL,
  `request_officer` varchar(100) DEFAULT NULL,
  `receiver_name` varchar(100) DEFAULT NULL,
  `receiver_emp_no` varchar(20) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ribbons_issuing`
--

INSERT INTO `ribbons_issuing` (`issue_id`, `ribbon_id`, `ribbon_model`, `code`, `lot`, `stock`, `division`, `section`, `request_officer`, `receiver_name`, `receiver_emp_no`, `quantity`, `issue_date`, `remarks`) VALUES
(16, 19, 'Printronix P 8000', 'IS-1002', '2025/LOT1', 'JCT', 'Information Systems Division', 'JCT', 'Kamal', 'Anjana', 'EMN-12510', 1, '2025-12-10', 'Urgent'),
(17, 19, 'Printronix P 8000', 'IS-101010', '2025/LOT1', 'UCT', 'Board of Directors', 'UCT', 'Kamal', 'Mohommed', 'EMP-1001', 1, '2025-12-10', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `ribbons_master`
--

CREATE TABLE `ribbons_master` (
  `ribbon_id` int(11) NOT NULL,
  `ribbon_model` varchar(100) NOT NULL,
  `compatible_printers` text DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 10,
  `jct_stock` int(11) DEFAULT 0,
  `uct_stock` int(11) DEFAULT 0,
  `purchase_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ribbons_master`
--

INSERT INTO `ribbons_master` (`ribbon_id`, `ribbon_model`, `compatible_printers`, `reorder_level`, `jct_stock`, `uct_stock`, `purchase_date`) VALUES
(19, 'Printronix P 8000', 'Printronix A', 10, 9, 9, '2025-12-10');

-- --------------------------------------------------------

--
-- Table structure for table `ribbons_receiving`
--

CREATE TABLE `ribbons_receiving` (
  `receive_id` int(11) NOT NULL,
  `ribbon_id` int(11) NOT NULL,
  `ribbon_model` varchar(100) NOT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `stock` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `pr_no` varchar(50) DEFAULT NULL,
  `tender_file_no` varchar(50) DEFAULT NULL,
  `jct_quantity` int(11) DEFAULT 0,
  `uct_quantity` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `invoice` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `receive_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ribbons_receiving`
--

INSERT INTO `ribbons_receiving` (`receive_id`, `ribbon_id`, `ribbon_model`, `lot`, `stock`, `supplier_name`, `pr_no`, `tender_file_no`, `jct_quantity`, `uct_quantity`, `unit_price`, `invoice`, `remarks`, `receive_date`) VALUES
(16, 19, 'Printronix P 8000', '2025/LOT1', '', 'Metropolitan Technologies', '2502501', 'CMS/LP/25/SQ/20/154', 10, 10, 31500.00, 'MI-152610', 'Urgent', '2025-12-10');

-- --------------------------------------------------------

--
-- Table structure for table `ribbons_return`
--

CREATE TABLE `ribbons_return` (
  `return_id` int(11) NOT NULL,
  `ribbon_id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `ribbon_model` varchar(100) NOT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `tender_file_no` varchar(150) DEFAULT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `invoice` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `division` varchar(100) DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL,
  `return_by` varchar(150) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `return_date` date NOT NULL,
  `issue_date` date DEFAULT NULL,
  `receiving_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ribbons_return`
--

INSERT INTO `ribbons_return` (`return_id`, `ribbon_id`, `code`, `ribbon_model`, `supplier_name`, `tender_file_no`, `lot`, `invoice`, `quantity`, `division`, `section`, `return_by`, `reason`, `remarks`, `return_date`, `issue_date`, `receiving_date`) VALUES
(18, 19, 'IS-1002', 'Printronix P 8000', 'Metropolitan Technologies', 'CMS/LP/25/SQ/20/154', '2025/LOT1', 'MI-152610', 1, 'Information Systems Division', 'JCT', 'Saman', 'Not Want', 'No', '2025-12-09', '2025-12-10', '2025-12-10'),
(19, 19, 'IS-101010', 'Printronix P 8000', 'Metropolitan Technologies', 'CMS/LP/25/SQ/20/154', '2025/LOT1', 'MI-152610', 1, 'Board of Directors', 'UCT', 'Kamal', 'NO', 'NO', '2025-12-10', '2025-12-10', '2025-12-10');

-- --------------------------------------------------------

--
-- Table structure for table `toner_issuing`
--

CREATE TABLE `toner_issuing` (
  `issue_id` int(11) NOT NULL,
  `toner_id` int(11) NOT NULL,
  `toner_model` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `stock` varchar(50) DEFAULT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `printer_model` varchar(50) DEFAULT NULL,
  `printer_no` varchar(50) DEFAULT NULL,
  `division` varchar(50) NOT NULL,
  `section` varchar(50) NOT NULL,
  `store` varchar(50) NOT NULL,
  `request_officer` varchar(100) DEFAULT NULL,
  `receiver_name` varchar(100) DEFAULT NULL,
  `receiver_emp_no` varchar(20) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `toner_issuing`
--

INSERT INTO `toner_issuing` (`issue_id`, `toner_id`, `toner_model`, `code`, `stock`, `lot`, `color`, `printer_model`, `printer_no`, `division`, `section`, `store`, `request_officer`, `receiver_name`, `receiver_emp_no`, `quantity`, `issue_date`, `remarks`) VALUES
(93, 72, 'Amida CF226X', 'IS-1003', 'JCT', '2025/LOT 1', 'Black', 'Amida CF226R', '2507077', 'Information Systems Division', 'JCT', '', 'Kamal', 'Anjana', 'EMN-1000', 1, '2025-12-10', 'Urgent');

-- --------------------------------------------------------

--
-- Table structure for table `toner_master`
--

CREATE TABLE `toner_master` (
  `toner_id` int(11) NOT NULL,
  `toner_model` varchar(100) NOT NULL,
  `compatible_printers` text NOT NULL,
  `reorder_level` int(11) DEFAULT 5,
  `jct_stock` int(11) DEFAULT 0,
  `uct_stock` int(11) DEFAULT 0,
  `color` varchar(50) DEFAULT NULL,
  `purchase_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `toner_master`
--

INSERT INTO `toner_master` (`toner_id`, `toner_model`, `compatible_printers`, `reorder_level`, `jct_stock`, `uct_stock`, `color`, `purchase_date`) VALUES
(72, 'Amida CF226X', 'Amida CF226R', 5, 9, 10, 'Black', '2025-12-09'),
(73, 'Canon CRG-052', 'Brother TN-1030', 5, 0, 0, 'Black', '2025-12-10'),
(74, 'Canon LBP 214DW', 'Canon LBP 214DWa', 5, 0, 0, 'Black', '2025-12-10');

-- --------------------------------------------------------

--
-- Table structure for table `toner_receiving`
--

CREATE TABLE `toner_receiving` (
  `receive_id` int(11) NOT NULL,
  `toner_id` int(11) NOT NULL,
  `toner_model` varchar(100) NOT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `stock` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `pr_no` varchar(50) DEFAULT NULL,
  `tender_file_no` varchar(50) DEFAULT NULL,
  `jct_quantity` int(11) DEFAULT 0,
  `uct_quantity` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `invoice` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `receive_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `toner_receiving`
--

INSERT INTO `toner_receiving` (`receive_id`, `toner_id`, `toner_model`, `lot`, `stock`, `color`, `supplier_name`, `pr_no`, `tender_file_no`, `jct_quantity`, `uct_quantity`, `unit_price`, `invoice`, `remarks`, `receive_date`) VALUES
(80, 72, 'Amida CF226X', '2025/LOT 1', 'JCT/UCT', 'Black', 'Strategix IT Computers', '2507077', 'CMS/LP/25/SQ/10/114', 10, 10, 3500.00, 'VL-1573', 'Urgent', '2025-12-10');

-- --------------------------------------------------------

--
-- Table structure for table `toner_return`
--

CREATE TABLE `toner_return` (
  `return_id` int(11) NOT NULL,
  `toner_id` int(11) NOT NULL,
  `toner_model` varchar(100) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `stock` varchar(50) DEFAULT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `return_date` date NOT NULL,
  `receiving_date` date DEFAULT NULL,
  `tender_file_no` varchar(50) DEFAULT NULL,
  `invoice` varchar(100) DEFAULT NULL,
  `location` varchar(50) NOT NULL,
  `returned_by` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `toner_return`
--

INSERT INTO `toner_return` (`return_id`, `toner_id`, `toner_model`, `code`, `stock`, `lot`, `supplier_name`, `return_date`, `receiving_date`, `tender_file_no`, `invoice`, `location`, `returned_by`, `quantity`, `reason`, `remarks`) VALUES
(62, 72, 'Amida CF226X', 'IS-1003', 'JCT', '2025/LOT 1', '', '2025-12-09', '0000-00-00', '', '', 'JCT', 'Saman', 1, 'Leakage', 'No');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','manager','user') DEFAULT 'user',
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `role`, `department`, `phone`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(3, 'admin', '$2y$10$GrgVdKLNLC3RssEGLt1wb.FBnaiNXQPaSzxW6UPECNXDFFcPa7Qme', 'System Administrator', 'admin@slpa.lk', 'admin', NULL, NULL, 'active', '2025-12-10 20:46:25', '2025-11-12 05:03:12', '2025-12-10 15:16:25'),
(8, 'Anjana', '$2y$10$1j8q6sZmNiekWfvsx5oMFOTGBRx1M6W4voPXumn285cSmwBoYykFG', 'Dissanayake', 'Anjana@gmail.com', 'user', 'IT', '0764324245', 'active', '2025-12-04 20:29:51', '2025-12-04 14:59:41', '2025-12-04 14:59:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `papers_issuing`
--
ALTER TABLE `papers_issuing`
  ADD PRIMARY KEY (`issue_id`),
  ADD KEY `paper_id` (`paper_id`);

--
-- Indexes for table `papers_master`
--
ALTER TABLE `papers_master`
  ADD PRIMARY KEY (`paper_id`);

--
-- Indexes for table `papers_receiving`
--
ALTER TABLE `papers_receiving`
  ADD PRIMARY KEY (`receive_id`),
  ADD KEY `paper_id` (`paper_id`);

--
-- Indexes for table `papers_return`
--
ALTER TABLE `papers_return`
  ADD PRIMARY KEY (`return_id`),
  ADD KEY `paper_id` (`paper_id`);

--
-- Indexes for table `ribbons_issuing`
--
ALTER TABLE `ribbons_issuing`
  ADD PRIMARY KEY (`issue_id`),
  ADD KEY `ribbon_id` (`ribbon_id`);

--
-- Indexes for table `ribbons_master`
--
ALTER TABLE `ribbons_master`
  ADD PRIMARY KEY (`ribbon_id`);

--
-- Indexes for table `ribbons_receiving`
--
ALTER TABLE `ribbons_receiving`
  ADD PRIMARY KEY (`receive_id`),
  ADD KEY `ribbon_id` (`ribbon_id`);

--
-- Indexes for table `ribbons_return`
--
ALTER TABLE `ribbons_return`
  ADD PRIMARY KEY (`return_id`),
  ADD KEY `ribbon_id` (`ribbon_id`);

--
-- Indexes for table `toner_issuing`
--
ALTER TABLE `toner_issuing`
  ADD PRIMARY KEY (`issue_id`),
  ADD KEY `toner_id` (`toner_id`);

--
-- Indexes for table `toner_master`
--
ALTER TABLE `toner_master`
  ADD PRIMARY KEY (`toner_id`);

--
-- Indexes for table `toner_receiving`
--
ALTER TABLE `toner_receiving`
  ADD PRIMARY KEY (`receive_id`),
  ADD KEY `toner_id` (`toner_id`);

--
-- Indexes for table `toner_return`
--
ALTER TABLE `toner_return`
  ADD PRIMARY KEY (`return_id`),
  ADD KEY `toner_id` (`toner_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `papers_issuing`
--
ALTER TABLE `papers_issuing`
  MODIFY `issue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `papers_master`
--
ALTER TABLE `papers_master`
  MODIFY `paper_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `papers_receiving`
--
ALTER TABLE `papers_receiving`
  MODIFY `receive_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `papers_return`
--
ALTER TABLE `papers_return`
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `ribbons_issuing`
--
ALTER TABLE `ribbons_issuing`
  MODIFY `issue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `ribbons_master`
--
ALTER TABLE `ribbons_master`
  MODIFY `ribbon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `ribbons_receiving`
--
ALTER TABLE `ribbons_receiving`
  MODIFY `receive_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `ribbons_return`
--
ALTER TABLE `ribbons_return`
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `toner_issuing`
--
ALTER TABLE `toner_issuing`
  MODIFY `issue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `toner_master`
--
ALTER TABLE `toner_master`
  MODIFY `toner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `toner_receiving`
--
ALTER TABLE `toner_receiving`
  MODIFY `receive_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `toner_return`
--
ALTER TABLE `toner_return`
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `papers_issuing`
--
ALTER TABLE `papers_issuing`
  ADD CONSTRAINT `papers_issuing_ibfk_1` FOREIGN KEY (`paper_id`) REFERENCES `papers_master` (`paper_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `papers_receiving`
--
ALTER TABLE `papers_receiving`
  ADD CONSTRAINT `papers_receiving_ibfk_1` FOREIGN KEY (`paper_id`) REFERENCES `papers_master` (`paper_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `papers_return`
--
ALTER TABLE `papers_return`
  ADD CONSTRAINT `papers_return_ibfk_1` FOREIGN KEY (`paper_id`) REFERENCES `papers_master` (`paper_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ribbons_issuing`
--
ALTER TABLE `ribbons_issuing`
  ADD CONSTRAINT `ribbons_issuing_ibfk_1` FOREIGN KEY (`ribbon_id`) REFERENCES `ribbons_master` (`ribbon_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ribbons_receiving`
--
ALTER TABLE `ribbons_receiving`
  ADD CONSTRAINT `ribbons_receiving_ibfk_1` FOREIGN KEY (`ribbon_id`) REFERENCES `ribbons_master` (`ribbon_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ribbons_return`
--
ALTER TABLE `ribbons_return`
  ADD CONSTRAINT `ribbons_return_ibfk_1` FOREIGN KEY (`ribbon_id`) REFERENCES `ribbons_master` (`ribbon_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `toner_issuing`
--
ALTER TABLE `toner_issuing`
  ADD CONSTRAINT `toner_issuing_ibfk_1` FOREIGN KEY (`toner_id`) REFERENCES `toner_master` (`toner_id`);

--
-- Constraints for table `toner_receiving`
--
ALTER TABLE `toner_receiving`
  ADD CONSTRAINT `toner_receiving_ibfk_1` FOREIGN KEY (`toner_id`) REFERENCES `toner_master` (`toner_id`);

--
-- Constraints for table `toner_return`
--
ALTER TABLE `toner_return`
  ADD CONSTRAINT `toner_return_ibfk_1` FOREIGN KEY (`toner_id`) REFERENCES `toner_master` (`toner_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
