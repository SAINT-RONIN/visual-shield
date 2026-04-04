-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Apr 04, 2026 at 08:35 PM
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
(1, 1, 0, 0, 2.65, 39.67, 0),
(2, 1, 1, 4, 7.95, 123.13, 1),
(3, 1, 2, 8, 15.46, 114, 1),
(4, 1, 3, 19, 48.66, 108.65, 1),
(5, 1, 4, 9, 34.49, 90.74, 1),
(6, 1, 5, 7, 31.42, 84.28, 1),
(7, 1, 6, 15, 39.09, 111.45, 1),
(8, 1, 7, 2, 9.94, 124.4, 0),
(9, 1, 8, 1, 3.47, 76.37, 0),
(10, 1, 9, 0, 2.92, 41.47, 0),
(11, 1, 10, 8, 17.41, 149.76, 1),
(12, 1, 11, 7, 13.14, 39.69, 1),
(63, 6, 0, 1, 11.33, 188.96, 0),
(64, 6, 1, 1, 26.4, 140.8, 0),
(65, 6, 2, 0, 3.16, 18.63, 0),
(66, 6, 3, 1, 32.18, 47.29, 0),
(67, 6, 4, 1, 10.16, 190.1, 0),
(68, 6, 5, 1, 26.31, 153.24, 0),
(69, 6, 6, 3, 19.23, 143.57, 1),
(70, 6, 7, 2, 9.6, 104.48, 0),
(71, 6, 8, 4, 26.51, 92.88, 1),
(72, 6, 9, 1, 14.23, 147.27, 0),
(73, 6, 10, 0, 5.49, 151.28, 0),
(74, 6, 11, 1, 7.67, 193.74, 0),
(75, 6, 12, 1, 17.7, 157.18, 0),
(76, 6, 13, 2, 15.87, 184.15, 0),
(77, 6, 14, 0, 13.11, 103.92, 0),
(78, 6, 15, 1, 15.59, 141.58, 0),
(79, 6, 16, 0, 19.93, 132.74, 0),
(80, 6, 17, 1, 14.73, 59.73, 0),
(81, 6, 18, 0, 16.99, 23.71, 0),
(82, 6, 19, 1, 17.3, 85.18, 0),
(83, 6, 20, 1, 15.28, 60.19, 0),
(84, 6, 21, 1, 24.63, 61.1, 0),
(85, 6, 22, 0, 11.01, 77.36, 0),
(86, 6, 23, 0, 21.22, 78.65, 0),
(87, 6, 24, 0, 23.8, 78.44, 0);

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
(1, 1, 703, 84, 19, 18.88, 60, '2026-04-04 19:27:38'),
(2, 6, 364, 25, 4, 16.78, 15, '2026-04-04 20:27:28');

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
(1, 1, '10a35a21cd737a175fc398ae018f10e68ccaac13b41bcb07e7352f7453e4a962', '2026-04-05 19:14:06', '2026-04-04 19:14:06'),
(2, 1, '2379f6930b5057d6599194b7df8c2f4ffb16d1142572dbd49677179c0d92335b', '2026-04-05 19:15:18', '2026-04-04 19:15:18'),
(3, 1, '783fb1dc2fbcc17ca9d87b1cfb388f4251bd24309dd5a3c4f9b866c946a7267a', '2026-04-05 19:22:59', '2026-04-04 19:22:59'),
(4, 1, 'e1d07903128cdc7577396e0c8fee3c90340c345df44f7ec877751088c74c5bda', '2026-04-05 19:22:59', '2026-04-04 19:22:59'),
(5, 1, '3d576a6c9054988bc3e87a09bf14122d214425a3722d326243c16f5bd0b3aa78', '2026-04-05 19:23:09', '2026-04-04 19:23:09'),
(6, 1, 'f034470616b045baca32cc094ccc486992c1ac496d9f780de4d4edacba18f290', '2026-04-05 19:23:56', '2026-04-04 19:23:57'),
(7, 2, '7201bd9385bc418c0c179e25b85a392af7aed0d097d334323ef68f65060d768e', '2026-04-05 20:00:23', '2026-04-04 20:00:23'),
(8, 2, '185fb0868401a0dc0c54bc9c4f294556cfe90ed8e5a06947241962d02bb8bf79', '2026-04-05 20:00:33', '2026-04-04 20:00:33'),
(19, 1, 'f48940120b03b083179ac4f20a64fcfba2da49a073d04f8efb566c22de40e732', '2026-04-05 20:19:10', '2026-04-04 20:19:10');

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
(1, 1, 1, 7, 'flash', 'high', 19, '2026-04-04 19:27:38'),
(2, 1, 10, 12, 'flash', 'medium', 8, '2026-04-04 19:27:38'),
(3, 1, 3, 7, 'motion', 'low', 48.66, '2026-04-04 19:27:38'),
(4, 6, 6, 7, 'flash', 'low', 3, '2026-04-04 20:27:28'),
(5, 6, 8, 9, 'flash', 'low', 4, '2026-04-04 20:27:28'),
(6, 6, 3, 4, 'motion', 'low', 32.18, '2026-04-04 20:27:28');

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
(1, 'admin', '$argon2id$v=19$m=65536,t=4,p=1$c1EzREJ3eWdSc3B6bWNDRw$jniTKxQn8+wzRsyGgPFqoveTKihZ71FZpEeBy9Mo5xA', 'Administrator', 'admin', '2026-04-04 19:13:43', '2026-04-04 19:23:35'),
(2, 'codextest', '$argon2id$v=19$m=65536,t=4,p=1$a2FQVFdRNWE1UUpYSEVVZg$XQT9WmQmq2CupAxT/IRgbG/k+KoaaRcnGY9aGRhbrhA', 'Codex Test', 'viewer', '2026-04-04 19:59:48', '2026-04-04 19:59:48'),
(3, 'TestUser1', '$argon2id$v=19$m=65536,t=4,p=1$Y1JtQmxRZ1VraDlzcGgubA$kscmQjq9nKLXz3fOWWDf2FBr1AFlkjbn37+Jl//rX6Y', 'TestUser1', 'viewer', '2026-04-04 20:04:20', '2026-04-04 20:04:20');

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
(1, 1, 'Pokémon - Cyber Soldier Porygon_Electric Soldier Porygon - Seizure Scene Clip [1997] - VideoMaster78 (240p, h264).mp4', 'storage/videos/d8224abf33dce786e17c6f746962c5d7.mp4', 353510, 11.807, 'completed', 60, 60, '2026-04-04 19:27:02', '2026-04-04 19:27:43', 100, 'Completed', NULL),
(6, 1, 'hero-video-compressed.mp4', 'storage/videos/f70ffb2f6e51c6315fcaff208c7f3011.mp4', 9657230, 24.32, 'completed', 15, 15, '2026-04-04 20:19:23', '2026-04-04 20:27:31', 100, 'Completed', NULL);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `analysis_results`
--
ALTER TABLE `analysis_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `flagged_segments`
--
ALTER TABLE `flagged_segments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
