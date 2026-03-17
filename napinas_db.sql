-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 21, 2026 at 06:38 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `CCS_106_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_log`
--

CREATE TABLE `admin_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget`
--

CREATE TABLE `budget` (
  `id` int(11) NOT NULL,
  `type` enum('Income','Expense') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget`
--

INSERT INTO `budget` (`id`, `type`, `category`, `amount`, `date`, `description`, `receipt_path`, `created_at`) VALUES
(1, 'Expense', 'Bassketball Prize', '1000.00', '2025-10-12', 'This fund will be use as a MVP prize', '1762910751_577551428_2425604317835476_4175351584559459184_n.jpg', '2025-11-12 01:25:51'),
(2, 'Income', 'Donation', '20000.00', '2025-02-16', 'Mayor Yips donated for the outreach', '1763099965_dona.jpg', '2025-11-14 05:59:25'),
(3, 'Income', 'Donation', '20000.00', '2025-11-05', 'Donation by Gov. Min', '1763339929_download__1_.jpg', '2025-11-17 00:38:49');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `event_date`, `description`, `image_path`, `created_at`) VALUES
(4, 'December league', '2025-10-22', 'Available for all', '577551428_2425604317835476_4175351584559459184_n.jpg', '2025-11-12 01:06:00'),
(6, 'Chess Day', '2025-11-22', 'All chess players are encouraged to participate.', 'chess.webp', '2025-11-14 05:57:52'),
(7, 'boxing', '2025-11-14', 'ferdinand vs lapu lapu', 'alya.jpg', '2025-11-14 07:00:45'),
(8, 'Hip Hop Dance Contest', '2025-11-19', 'All students are encouraged to participate in this event', 'BM.jpg', '2025-11-17 00:20:45'),
(9, 'Taekwando', '2025-11-20', 'All taekwondo players are encouraged.', 'download.jpg', '2025-11-17 00:35:55'),
(10, 'dance', '2025-07-17', 'haha', 'download (1).jpg', '2025-11-17 00:36:25'),
(11, '  ', '2025-11-21', 'adunno', 'skull_layer5.jpg', '2025-11-17 00:59:42');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `first_name`, `last_name`, `position`, `photo`, `created_at`) VALUES
(7, 'Ritchie Bob', 'Napinas', 'SK Chairman', '../uploads/rb.jpg', '2025-11-14 05:54:59'),
(8, 'Roniel@13', 'Minion', 'SK Treasurer', '../uploads/minio.jpg', '2025-11-14 05:55:28'),
(9, 'michael john', 'cajes', 'secretary', '../uploads/cajes.jpg', '2025-11-14 06:04:57'),
(10, 'Justin', 'Agoncillo', 'SK kagawad', '../uploads/yagami.jpeg', '2025-11-16 23:53:11'),
(13, 'justin', 'gwpa', 'kagawad2', '../uploads/alya.jpg', '2025-11-17 00:27:08'),
(14, 'Monkey', 'Luffy', 'SK kagawad', '../uploads/download (3).jpg', '2025-11-17 00:33:49'),
(16, '', 'haha', '', '../uploads/moon.jpg', '2025-11-17 00:55:32');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `date_posted` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `content`, `date_posted`) VALUES
(4, 'Outstanding Students of Barangay Union', 'Barangay Union proudly recognized its Outstanding Students for their excellence in academics, leadership, and community service. These youth achievers continue to inspire their peers and bring pride to the barangay.', '2025-11-12 07:18:39'),
(5, 'Scholarship Applications Now Open', 'The SK Union Scholarship Program is now accepting applicants. This program aims to support deserving students in pursuing their education and achieving their goals. Interested students may visit the barangay hall or contact the SK office for requirements.', '2025-11-12 07:19:05'),
(7, 'IM 101', 'defended jk ra', '2025-11-17 08:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `otp_attempts`
--

CREATE TABLE `otp_attempts` (
  `email` varchar(255) NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_attempts`
--

INSERT INTO `otp_attempts` (`email`, `attempts`, `last_attempt`) VALUES
('mondonedo.rolando_jr@hnu.edu.ph', 1, '2025-11-18 09:53:46'),
('napinasritchiebob@gmail.com', 2, '2025-11-17 00:45:14');

-- --------------------------------------------------------

--
-- Table structure for table `password_otp`
--

CREATE TABLE `password_otp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_otp`
--

INSERT INTO `password_otp` (`id`, `user_id`, `otp`, `expires_at`, `used`) VALUES
(1, 4, '486300', '2025-11-13 11:04:40', 1),
(2, 4, '791072', '2025-11-13 11:06:19', 0),
(3, 4, '637303', '2025-11-14 10:11:43', 1),
(4, 4, '135953', '2025-11-14 10:21:43', 1),
(5, 8, '534533', '2025-11-14 11:04:23', 0),
(6, 7, '243949', '2025-11-14 12:20:05', 1),
(7, 8, '514684', '2025-11-14 12:23:31', 1),
(8, 8, '431534', '2025-11-14 12:29:13', 1),
(9, 8, '333811', '2025-11-14 12:31:10', 1),
(10, 8, '694100', '2025-11-14 12:33:19', 1),
(11, 13, '850149', '2025-11-17 07:29:01', 0),
(12, 13, '772195', '2025-11-17 07:30:26', 0),
(13, 14, '528190', '2025-11-17 07:32:34', 0),
(14, 14, '865511', '2025-11-17 07:32:42', 1),
(15, 14, '864616', '2025-11-17 07:38:59', 0),
(16, 13, '900564', '2025-11-17 07:40:53', 1),
(17, 17, '121024', '2025-11-17 08:48:06', 1),
(18, 19, '949584', '2025-11-18 17:56:36', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `username`, `email`, `password`, `created_at`, `role`) VALUES
(18, 'Ace Dragnel', 'user1', 'napinas.ritchie_bob@hnu.edu.ph', '$2y$10$aSKbWPNNNs4YGQzdEZKPfOt/MWFKpzyeYPuHKFgJYarIXkb5oKWeG', '2025-11-17 00:32:22', 'user'),
(20, 'John Philip', 'john12345', 'galolo.john_philippe@hnu.edu.ph', '$2y$10$MvsKOdpCgCM8wiX/fUvLguu8cRK1EQLTUJvzGK6Kvt8Nhhk0jTjiO', '2025-11-18 09:55:37', 'user'),
(21, 'RBN', 'admin1', 'napinasritchiebob@gmail.com', '$2y$10$xRdoCqWYQIhRbnalOFkTVONDCa84XYgrhZrv93jZiKsL3JSpK7BvS', '2025-11-18 09:58:05', 'admin'),
(22, 'jun mondonedo', 'junmondonedo', 'mondonedo.rolando_jr@hnu.edu.ph', '$2y$10$/kj.TMPXXsFpXEVX/gm16e0YQwiENt4ZPI0/62J2oM4YLjtwE5t5K', '2025-11-18 09:59:08', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_log`
--
ALTER TABLE `admin_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget`
--
ALTER TABLE `budget`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `otp_attempts`
--
ALTER TABLE `otp_attempts`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `password_otp`
--
ALTER TABLE `password_otp`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `admin_log`
--
ALTER TABLE `admin_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget`
--
ALTER TABLE `budget`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `password_otp`
--
ALTER TABLE `password_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
