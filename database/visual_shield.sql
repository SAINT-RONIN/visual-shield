-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Apr 03, 2026 at 08:17 PM
-- Server version: 8.0.45
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `visual_shield`
--

-- --------------------------------------------------------

--
-- Table structure for table `analysis_datapoints`
--

CREATE TABLE `analysis_datapoints` (
  `id` int NOT NULL,
  `video_id` int NOT NULL,
  `time_point` float NOT NULL,
  `flash_frequency` float DEFAULT '0',
  `motion_intensity` float DEFAULT '0',
  `luminance` float DEFAULT '0',
  `flash_detected` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `analysis_datapoints`
--

INSERT INTO `analysis_datapoints` (`id`, `video_id`, `time_point`, `flash_frequency`, `motion_intensity`, `luminance`, `flash_detected`) VALUES
(1, 3, 0, 0, 1.04, 29.21, 0),
(2, 3, 1, 0, 0.88, 27.98, 0),
(3, 3, 2, 0, 2.62, 27.52, 0),
(4, 3, 3, 0, 3.35, 25.86, 0),
(5, 3, 4, 0, 1.18, 25.85, 0),
(6, 3, 5, 0, 2.59, 26.69, 0),
(7, 3, 6, 0, 1.1, 26.03, 0),
(8, 3, 7, 0, 2.65, 27.79, 0),
(9, 3, 8, 0, 3.26, 27.35, 0),
(10, 3, 9, 0, 2.91, 27.08, 0),
(11, 3, 10, 0, 1.21, 26.31, 0),
(12, 3, 11, 0, 0.4, 26.75, 0),
(13, 3, 12, 0, 0.79, 26.51, 0),
(14, 3, 13, 0, 0.29, 26.81, 0),
(15, 3, 14, 0, 1.37, 26.75, 0),
(16, 3, 15, 0, 1.45, 26.4, 0),
(17, 3, 16, 0, 2.59, 27.46, 0),
(18, 3, 17, 0, 2.68, 32.51, 0),
(19, 3, 18, 0, 1.94, 35.08, 0),
(20, 3, 19, 0, 4.4, 33.74, 0),
(21, 3, 20, 0, 4, 34.73, 0),
(22, 3, 21, 0, 1.76, 31.91, 0),
(23, 3, 22, 0, 1.97, 30.22, 0),
(24, 3, 23, 0, 2.2, 29.42, 0),
(25, 4, 0, 0, 13.55, 46.41, 0),
(26, 4, 1, 0, 13.04, 45.1, 0),
(27, 4, 2, 0, 13.1, 43.59, 0),
(28, 4, 3, 0, 12.88, 41.58, 0),
(29, 4, 4, 0, 12.43, 39.11, 0),
(30, 4, 5, 0, 12.3, 37.47, 0),
(31, 4, 6, 0, 12.18, 35.87, 0),
(32, 4, 7, 0, 11.89, 34.69, 0),
(33, 4, 8, 0, 11.7, 33.51, 0),
(34, 4, 9, 0, 11.47, 32.28, 0),
(35, 4, 10, 0, 11.52, 31.3, 0),
(36, 4, 11, 0, 11.49, 30.37, 0),
(37, 4, 12, 0, 11.54, 29.81, 0),
(38, 4, 13, 0, 11.51, 29.02, 0),
(39, 4, 14, 0, 11.15, 27.91, 0),
(40, 4, 15, 0, 10.9, 27, 0),
(41, 4, 16, 0, 10.55, 25.82, 0),
(42, 4, 17, 0, 10.2, 24.88, 0),
(43, 4, 18, 0, 9.84, 23.87, 0),
(44, 4, 19, 0, 9.54, 22.93, 0),
(45, 4, 20, 0, 9.17, 21.8, 0),
(46, 4, 21, 0, 9.08, 20.94, 0),
(47, 4, 22, 0, 9.25, 20.59, 0),
(48, 4, 23, 0, 9.54, 20.36, 0),
(49, 4, 24, 0, 9.45, 19.73, 0),
(50, 4, 25, 0, 8.75, 18.55, 0),
(51, 4, 26, 0, 7.8, 17.02, 0),
(52, 4, 27, 0, 7.04, 15.63, 0),
(53, 4, 28, 0, 6.66, 14.58, 0),
(54, 4, 29, 0, 6.06, 13.3, 0),
(55, 4, 30, 0, 5.22, 11.84, 0),
(56, 4, 31, 0, 4.54, 10.4, 0),
(57, 4, 32, 0, 3.98, 9.23, 0),
(58, 4, 33, 0, 3.46, 8.3, 0),
(59, 4, 34, 0, 2.99, 7.39, 0),
(60, 4, 35, 0, 2.49, 6.49, 0),
(61, 4, 36, 0, 2.16, 5.78, 0),
(62, 4, 37, 0, 1.85, 5.09, 0),
(63, 4, 38, 0, 1.59, 4.32, 0),
(64, 4, 39, 0, 1.36, 3.61, 0),
(65, 4, 40, 0, 1.24, 3.26, 0),
(66, 4, 41, 0, 1.17, 3.08, 0),
(67, 4, 42, 0, 1.14, 2.95, 0),
(68, 4, 43, 0, 1.07, 2.8, 0),
(69, 4, 44, 0, 1.04, 2.67, 0),
(70, 4, 45, 0, 0.96, 2.52, 0),
(71, 4, 46, 0, 0.89, 2.37, 0),
(72, 4, 47, 0, 0.86, 2.25, 0),
(73, 4, 48, 0, 0.84, 2.14, 0),
(74, 4, 49, 0, 0.73, 2, 0),
(75, 4, 50, 0, 0.64, 1.82, 0),
(76, 4, 51, 0, 0.57, 1.61, 0),
(77, 4, 52, 0, 0.49, 1.45, 0),
(78, 4, 53, 0, 0.42, 1.33, 0),
(79, 4, 54, 0, 0.35, 1.2, 0),
(80, 4, 55, 0, 0.33, 1.09, 0),
(81, 4, 56, 0, 0.29, 1.03, 0),
(82, 4, 57, 0, 0.27, 0.96, 0),
(83, 4, 58, 0, 0.25, 0.9, 0),
(84, 4, 59, 0, 0.25, 0.89, 0),
(85, 4, 60, 0, 0.24, 0.89, 0),
(86, 4, 61, 0, 0.25, 0.91, 0),
(87, 4, 62, 0, 0.26, 0.96, 0),
(88, 4, 63, 0, 0.28, 1.03, 0),
(89, 4, 64, 0, 0.32, 1.09, 0),
(90, 4, 65, 0, 0.35, 1.2, 0),
(91, 4, 66, 0, 0.41, 1.33, 0),
(92, 4, 67, 0, 0.48, 1.45, 0),
(93, 4, 68, 0, 0.57, 1.61, 0),
(94, 4, 69, 0, 0.64, 1.81, 0),
(95, 4, 70, 0, 0.73, 2, 0),
(96, 4, 71, 0, 0.83, 2.14, 0),
(97, 4, 72, 0, 0.87, 2.25, 0),
(98, 4, 73, 0, 0.89, 2.36, 0),
(99, 4, 74, 0, 0.96, 2.52, 0),
(100, 4, 75, 0, 1.04, 2.67, 0),
(101, 4, 76, 0, 1.07, 2.79, 0),
(102, 4, 77, 0, 1.13, 2.94, 0),
(103, 4, 78, 0, 1.17, 3.08, 0),
(104, 4, 79, 0, 1.23, 3.25, 0),
(105, 4, 80, 0, 1.35, 3.59, 0),
(106, 4, 81, 0, 1.57, 4.27, 0),
(107, 4, 82, 0, 1.81, 5.05, 0),
(108, 4, 83, 0, 2.12, 5.75, 0),
(109, 4, 84, 0, 2.44, 6.44, 0),
(110, 4, 85, 0, 2.93, 7.35, 0),
(111, 4, 86, 0, 3.41, 8.26, 0),
(112, 4, 87, 0, 3.93, 9.18, 0),
(113, 4, 88, 0, 4.49, 10.34, 0),
(114, 4, 89, 0, 5.13, 11.78, 0),
(115, 4, 90, 0, 5.98, 13.24, 0),
(116, 4, 91, 0, 6.63, 14.53, 0),
(117, 4, 92, 0, 7, 15.59, 0),
(118, 4, 93, 0, 7.7, 16.93, 0),
(119, 4, 94, 0, 8.66, 18.48, 0),
(120, 4, 95, 0, 9.41, 19.69, 0),
(121, 4, 96, 0, 9.55, 20.34, 0),
(122, 4, 97, 0, 9.29, 20.58, 0),
(123, 4, 98, 0, 9.08, 20.9, 0),
(124, 4, 99, 0, 9.15, 21.74, 0),
(125, 4, 100, 0, 9.51, 22.86, 0),
(126, 4, 101, 0, 9.82, 23.81, 0),
(127, 4, 102, 0, 10.18, 24.83, 0),
(128, 4, 103, 0, 10.55, 25.74, 0),
(129, 4, 104, 0, 10.91, 26.93, 0),
(130, 4, 105, 0, 11.17, 27.85, 0),
(131, 4, 106, 0, 11.54, 28.96, 0),
(132, 4, 107, 0, 11.59, 29.78, 0),
(133, 4, 108, 0, 11.52, 30.3, 0),
(134, 4, 109, 0, 11.57, 31.25, 0),
(135, 4, 110, 0, 11.5, 32.2, 0),
(136, 4, 111, 0, 11.74, 33.43, 0),
(137, 4, 112, 0, 11.91, 34.62, 0),
(138, 4, 113, 0, 12.18, 35.78, 0),
(139, 4, 114, 0, 12.34, 37.37, 0),
(140, 4, 115, 0, 12.43, 38.99, 0),
(141, 4, 116, 0, 12.87, 41.45, 0),
(142, 4, 117, 0, 13.16, 43.51, 0),
(143, 4, 118, 0, 13.12, 45.04, 0),
(144, 4, 119, 0, 13.3, 46.36, 0),
(145, 5, 0, 0, 9.87, 40.38, 0),
(146, 5, 1, 4, 31.2, 118.97, 1),
(147, 5, 2, 7, 46.5, 111.33, 1),
(148, 5, 3, 12, 95.99, 111.06, 1),
(149, 5, 4, 7, 81.03, 92.09, 1),
(150, 5, 5, 4, 86.69, 84.14, 1),
(151, 5, 6, 11, 126.72, 114.14, 1),
(152, 5, 7, 1, 20.61, 123.31, 0),
(153, 5, 8, 1, 12.63, 74.7, 0),
(154, 5, 9, 1, 11.04, 41.72, 0),
(155, 5, 10, 5, 56.02, 153.32, 1),
(156, 5, 11, 4, 34.91, 33.64, 1),
(157, 6, 0, 0, 2.65, 39.67, 0),
(158, 6, 1, 4, 7.95, 123.13, 1),
(159, 6, 2, 8, 15.46, 114, 1),
(160, 6, 3, 19, 48.66, 108.65, 1),
(161, 6, 4, 9, 34.49, 90.74, 1),
(162, 6, 5, 7, 31.42, 84.28, 1),
(163, 6, 6, 15, 39.09, 111.45, 1),
(164, 6, 7, 2, 9.94, 124.4, 0),
(165, 6, 8, 1, 3.47, 76.37, 0),
(166, 6, 9, 0, 2.92, 41.47, 0),
(167, 6, 10, 8, 17.41, 149.76, 1),
(168, 6, 11, 7, 13.14, 39.69, 1);

-- --------------------------------------------------------

--
-- Table structure for table `analysis_results`
--

CREATE TABLE `analysis_results` (
  `id` int NOT NULL,
  `video_id` int NOT NULL,
  `total_frames_analyzed` int DEFAULT '0',
  `total_flash_events` int DEFAULT '0',
  `highest_flash_frequency` float DEFAULT '0',
  `average_motion_intensity` float DEFAULT '0',
  `effective_sampling_rate` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `analysis_results`
--

INSERT INTO `analysis_results` (`id`, `video_id`, `total_frames_analyzed`, `total_flash_events`, `highest_flash_frequency`, `average_motion_intensity`, `effective_sampling_rate`, `created_at`) VALUES
(2, 3, 347, 0, 0, 2.03, 15, '2026-03-06 15:05:54'),
(3, 4, 1800, 0, 0, 5.98, 15, '2026-03-06 15:08:53'),
(4, 5, 176, 61, 12, 51.1, 15, '2026-03-06 15:09:02'),
(5, 6, 703, 84, 19, 18.88, 60, '2026-03-06 15:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `auth_tokens`
--

INSERT INTO `auth_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 1, 'e12420877b8f9ce1656f95b8a0078d586c2ee071258e2d1bf57a66c46be1705d', '2026-03-05 22:38:00', '2026-03-04 22:38:00'),
(2, 1, 'dbf4403946e5d195f2e7d6f2f9a7e49ea7254eac2f700ccee85b0a052762663e', '2026-03-05 22:39:10', '2026-03-04 22:39:10'),
(4, 1, '008abf8387c6f18a21084e203c04b2a62928869221fda3784644a0e5c06f99a3', '2026-03-05 23:13:03', '2026-03-04 23:13:03'),
(5, 1, 'cc1f2e6a3a6b0a473a7edbd944dd80455a4bd051621e0560a8f318c84cbe5701', '2026-03-05 23:13:55', '2026-03-04 23:13:55'),
(6, 1, '72e44a5a8bb4e0f13c2d4396acc63e75a7bac2ea6192f607c71b8be946c3f0bf', '2026-03-05 23:14:33', '2026-03-04 23:14:33'),
(7, 1, 'fa5eb2521a8d4024b494c2a8ffb7afcc4ddf324a70194a751e1c9789849c218c', '2026-03-05 23:14:52', '2026-03-04 23:14:52'),
(9, 1, 'd15d367ac808dc9dab51701430c7d0712144ade08f906633bda1bbb2dd671885', '2026-03-06 18:19:46', '2026-03-05 18:19:46'),
(10, 1, 'e85f7699ba48970e69b55379e1c8b39c069db1fb8bcba55d9ca0aa78aff41659', '2026-03-06 18:19:51', '2026-03-05 18:19:51'),
(15, 1, 'd01bfc0aaec32d7ea8cd3b0fdda0d558c60d0e7267f021741ae4dfcb51215a58', '2026-03-12 21:23:47', '2026-03-11 21:23:47'),
(20, 3, 'b0d76e766e7198404e1f832e6a938e8974b0f78757911e2a5a59cb4dd3f350b8', '2026-03-12 23:30:26', '2026-03-11 23:30:26'),
(21, 4, 'ebd75a776cd7ea36d5742e5d4e4ff0f24c0c83d108a50968186a3ef07392a187', '2026-03-12 23:34:50', '2026-03-11 23:34:50'),
(22, 2, '30abb58b16facbf5bf16b24e8a447900d108360532590b8d8abd49be63086e17', '2026-03-14 08:33:26', '2026-03-13 08:33:26'),
(23, 2, '5963984c92de4de369e3078afeaa389a480f947c73702c00dcdec9af9a55c205', '2026-03-14 09:34:37', '2026-03-13 09:34:37'),
(24, 2, '9af7f31198320666917a4cc8ce9beb94f276818aac1aab44c03ff98f15285826', '2026-03-28 18:04:48', '2026-03-27 18:04:48');

-- --------------------------------------------------------

--
-- Table structure for table `flagged_segments`
--

CREATE TABLE `flagged_segments` (
  `id` int NOT NULL,
  `video_id` int NOT NULL,
  `start_time` float NOT NULL,
  `end_time` float NOT NULL,
  `segment_type` enum('flash','motion') NOT NULL,
  `severity` enum('low','medium','high') NOT NULL,
  `metric_value` float DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `flagged_segments`
--

INSERT INTO `flagged_segments` (`id`, `video_id`, `start_time`, `end_time`, `segment_type`, `severity`, `metric_value`, `created_at`) VALUES
(1, 5, 1, 7, 'flash', 'high', 12, '2026-03-06 15:09:02'),
(2, 5, 10, 12, 'flash', 'low', 5, '2026-03-06 15:09:02'),
(3, 5, 1, 7, 'motion', 'high', 126.72, '2026-03-06 15:09:02'),
(4, 5, 10, 12, 'motion', 'low', 56.02, '2026-03-06 15:09:02'),
(5, 6, 1, 7, 'flash', 'high', 19, '2026-03-06 15:40:34'),
(6, 6, 10, 12, 'flash', 'medium', 8, '2026-03-06 15:40:34'),
(7, 6, 3, 7, 'motion', 'low', 48.66, '2026-03-06 15:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'viewer',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `display_name`, `role`, `created_at`, `updated_at`) VALUES
(1, 'testuser', '$argon2id$v=19$m=65536,t=4,p=1$Y2lrbG9EYVV4OWg5N01zNg$wna47msj3DLZo7l+f2Uzbe87tM2kdJ38sdtchK7MwKQ', 'Updated Name', 'admin', '2026-03-04 22:36:01', '2026-03-11 21:23:42'),
(2, 'DRAWHOLIC', '$argon2id$v=19$m=65536,t=4,p=1$aHhVRXpoUkQ1RjFsVTdPQQ$33EN1sMLQMzQiPW7XuOAgx2aaciZgIwxAfKeo/Mpzss', 'DRAWHOLIC', 'viewer', '2026-03-05 18:11:45', '2026-03-05 18:11:45'),
(3, 'testuser2', '$argon2id$v=19$m=65536,t=4,p=1$T3E3aFdvSC9lLmRpQ3VDeg$z7gvqtK/JrkIHly/1kyuO1za/bbLZp+32DPKmIXivwk', 'Test User', 'viewer', '2026-03-11 23:30:03', '2026-03-11 23:30:03'),
(4, 'postman_test', '$argon2id$v=19$m=65536,t=4,p=1$eHl3em9aMkZTUURrLkd1bQ$8y4csrdZvvCrbwe4vNPC8OD4UjjTa+FkXJMWTDMyINg', 'Postman Test', 'viewer', '2026-03-11 23:34:42', '2026-03-11 23:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_path` varchar(500) NOT NULL,
  `file_size` bigint NOT NULL,
  `duration_seconds` float DEFAULT NULL,
  `status` enum('queued','processing','completed','failed') DEFAULT 'queued',
  `sampling_rate` int NOT NULL DEFAULT '15',
  `effective_rate` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `progress` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `progress_message` varchar(100) DEFAULT NULL,
  `error_message` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `user_id`, `original_name`, `stored_path`, `file_size`, `duration_seconds`, `status`, `sampling_rate`, `effective_rate`, `created_at`, `updated_at`, `progress`, `progress_message`, `error_message`) VALUES
(3, 2, 'leandro-memoji-ln.mp4', 'storage/videos/ebe4ebcf29e93a3f862fc4ee5c73d397.mp4', 1047095, 23.1333, 'completed', 15, 15, '2026-03-06 14:58:18', '2026-03-06 15:05:56', 0, NULL, NULL),
(4, 2, '14882119_1920_1080_24fps.mp4', 'storage/videos/6954b826ea2da36725a6ab50eb244609.mp4', 259974479, 120, 'completed', 15, 15, '2026-03-06 15:06:42', '2026-03-06 15:08:58', 0, NULL, NULL),
(5, 2, 'Pokémon - Cyber Soldier Porygon_Electric Soldier Porygon - Seizure Scene Clip [1997] - VideoMaster78 (240p, h264).mp4', 'storage/videos/b4ab375517167b95daf8ae6f65407acb.mp4', 353510, 11.807, 'completed', 15, 15, '2026-03-06 15:08:26', '2026-03-06 15:09:03', 0, NULL, NULL),
(6, 2, 'Pokémon - Cyber Soldier Porygon_Electric Soldier Porygon - Seizure Scene Clip [1997] - VideoMaster78 (240p, h264).mp4', 'storage/videos/5f7cc904f8f3fec2a143c0ef170a5d6b.mp4', 353510, 11.807, 'completed', 60, 60, '2026-03-06 15:40:19', '2026-03-06 15:40:37', 100, 'Completed', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `analysis_datapoints`
--
ALTER TABLE `analysis_datapoints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_datapoints_video` (`video_id`);

--
-- Indexes for table `analysis_results`
--
ALTER TABLE `analysis_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `video_id` (`video_id`);

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `flagged_segments`
--
ALTER TABLE `flagged_segments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `video_id` (`video_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `analysis_datapoints`
--
ALTER TABLE `analysis_datapoints`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `analysis_results`
--
ALTER TABLE `analysis_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `flagged_segments`
--
ALTER TABLE `flagged_segments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `analysis_datapoints`
--
ALTER TABLE `analysis_datapoints`
  ADD CONSTRAINT `analysis_datapoints_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `analysis_results`
--
ALTER TABLE `analysis_results`
  ADD CONSTRAINT `analysis_results_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `flagged_segments`
--
ALTER TABLE `flagged_segments`
  ADD CONSTRAINT `flagged_segments_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
