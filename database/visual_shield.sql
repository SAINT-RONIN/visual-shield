-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Apr 12, 2026 at 07:00 PM
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
(87, 6, 24, 0, 23.8, 78.44, 0),
(222, 8, 0, 0, 9.87, 40.38, 0),
(223, 8, 1, 4, 31.2, 118.97, 1),
(224, 8, 2, 7, 46.5, 111.33, 1),
(225, 8, 3, 12, 95.99, 111.06, 1),
(226, 8, 4, 7, 81.03, 92.09, 1),
(227, 8, 5, 4, 86.69, 84.14, 1),
(228, 8, 6, 11, 126.72, 114.14, 1),
(229, 8, 7, 1, 20.61, 123.31, 0),
(230, 8, 8, 1, 12.63, 74.7, 0),
(231, 8, 9, 1, 11.04, 41.72, 0),
(232, 8, 10, 5, 56.02, 153.32, 1),
(233, 8, 11, 4, 34.91, 33.64, 1),
(354, 11, 0, 1, 11.33, 188.96, 0),
(355, 11, 1, 1, 26.4, 140.8, 0),
(356, 11, 2, 0, 3.16, 18.63, 0),
(357, 11, 3, 1, 32.18, 47.29, 0),
(358, 11, 4, 1, 10.16, 190.1, 0),
(359, 11, 5, 1, 26.31, 153.24, 0),
(360, 11, 6, 3, 19.23, 143.57, 1),
(361, 11, 7, 2, 9.6, 104.48, 0),
(362, 11, 8, 4, 26.51, 92.88, 1),
(363, 11, 9, 1, 14.23, 147.27, 0),
(364, 11, 10, 0, 5.49, 151.28, 0),
(365, 11, 11, 1, 7.67, 193.74, 0),
(366, 11, 12, 1, 17.7, 157.18, 0),
(367, 11, 13, 2, 15.87, 184.15, 0),
(368, 11, 14, 0, 13.11, 103.92, 0),
(369, 11, 15, 1, 15.59, 141.58, 0),
(370, 11, 16, 0, 19.93, 132.74, 0),
(371, 11, 17, 1, 14.73, 59.73, 0),
(372, 11, 18, 0, 16.99, 23.71, 0),
(373, 11, 19, 1, 17.3, 85.18, 0),
(374, 11, 20, 1, 15.28, 60.19, 0),
(375, 11, 21, 1, 24.63, 61.1, 0),
(376, 11, 22, 0, 11.01, 77.36, 0),
(377, 11, 23, 0, 21.22, 78.65, 0),
(378, 11, 24, 0, 23.8, 78.44, 0);

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
(2, 6, 364, 25, 4, 16.78, 15, '2026-04-04 20:27:28'),
(6, 8, 176, 61, 12, 51.1, 15, '2026-04-06 19:58:34'),
(8, 11, 364, 25, 4, 16.78, 15, '2026-04-12 18:24:27');

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
(6, 6, 3, 4, 'motion', 'low', 32.18, '2026-04-04 20:27:28'),
(11, 8, 1, 7, 'flash', 'high', 12, '2026-04-06 19:58:34'),
(12, 8, 10, 12, 'flash', 'low', 5, '2026-04-06 19:58:34'),
(13, 8, 1, 7, 'motion', 'high', 126.72, '2026-04-06 19:58:34'),
(14, 8, 10, 12, 'motion', 'low', 56.02, '2026-04-06 19:58:34'),
(15, 11, 6, 7, 'flash', 'low', 3, '2026-04-12 18:24:27'),
(16, 11, 8, 9, 'flash', 'low', 4, '2026-04-12 18:24:27'),
(17, 11, 3, 4, 'motion', 'low', 32.18, '2026-04-12 18:24:27');

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
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `display_name`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$argon2id$v=19$m=65536,t=4,p=1$c1EzREJ3eWdSc3B6bWNDRw$jniTKxQn8+wzRsyGgPFqoveTKihZ71FZpEeBy9Mo5xA', 'Administrator', 'admin', 1, '2026-04-04 19:13:43', '2026-04-04 19:23:35'),
(3, 'TestUser1', '$argon2id$v=19$m=65536,t=4,p=1$Y1JtQmxRZ1VraDlzcGgubA$kscmQjq9nKLXz3fOWWDf2FBr1AFlkjbn37+Jl//rX6Y', 'TestUser1', 'viewer', 1, '2026-04-04 20:04:20', '2026-04-06 19:53:10'),
(4, 'kian@test', '$argon2id$v=19$m=65536,t=4,p=1$TEVLNFExczcuL25BL29tRw$R8MD+51z86czNgKiLV72+IQkKwDDxIoItufWqUj8lVE', 'kian', 'viewer', 1, '2026-04-06 14:17:44', '2026-04-06 14:17:44'),
(5, 'Axel', '$argon2id$v=19$m=65536,t=4,p=1$WkNxSnpKZk1UYjZsZzl0Wg$hZ8zyM6Tml0XwF3u3Bx+rYDzRYXDQ+lS26vORx9hGWs', 'Axel Doe', 'member', 1, '2026-04-12 18:22:02', '2026-04-12 18:22:02');

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
(6, 1, 'hero-video-compressed.mp4', 'storage/videos/f70ffb2f6e51c6315fcaff208c7f3011.mp4', 9657230, 24.32, 'completed', 15, 15, '2026-04-04 20:19:23', '2026-04-04 20:27:31', 100, 'Completed', NULL),
(8, 1, 'Pokémon - Cyber Soldier Porygon_Electric Soldier Porygon - Seizure Scene Clip [1997] - VideoMaster78 (240p, h264).mp4', 'storage/videos/5b4ff1decf54f514df3780b5344fd383.mp4', 353510, 11.807, 'completed', 15, 15, '2026-04-06 17:38:44', '2026-04-06 19:58:39', 100, 'Completed', NULL),
(11, 5, 'hero-video-compressed.mp4', 'storage/videos/cedc49b1af70b790f5e41ec21bc74088.mp4', 9657230, 24.32, 'completed', 15, 15, '2026-04-12 18:22:42', '2026-04-12 18:24:30', 100, 'Completed', NULL);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=379;

--
-- AUTO_INCREMENT for table `analysis_results`
--
ALTER TABLE `analysis_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `flagged_segments`
--
ALTER TABLE `flagged_segments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
