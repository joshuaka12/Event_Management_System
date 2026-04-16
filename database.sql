-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2026 at 11:40 AM
-- Server version: 5.7.14
-- PHP Version: 7.0.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `campus_ems`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_date` datetime NOT NULL,
  `venue` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT '100',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `poster_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `venue`, `capacity`, `created_by`, `created_at`, `deleted_at`, `rejection_reason`, `status`, `poster_image`, `video_url`, `video_file`, `category`) VALUES
(1, 'Tech Innovation Summit 2026', 'Annual summit bringing together students, faculty, and industry leaders to explore cutting-edge technology trends. Keynotes, workshops, and networking sessions.', '2026-04-15 09:00:00', 'Main Auditorium', 300, 2, '2026-04-08 14:12:20', NULL, NULL, 'approved', NULL, NULL, NULL, 'General'),
(2, 'Cultural Night 2026', 'A vibrant celebration of campus diversity featuring performances, food stalls, art exhibitions, and music from around the world.', '2026-04-22 18:00:00', 'Campus Grounds', 500, 2, '2026-04-08 14:12:20', NULL, NULL, 'pending', NULL, NULL, NULL, 'General'),
(3, 'Career Fair – Engineering & IT', 'Meet recruiters from 50+ top companies. Bring your CV and be ready for on-spot interviews. Open to all engineering and IT students.', '2026-05-05 10:00:00', 'Sports Hall', 400, 2, '2026-04-08 14:12:20', NULL, NULL, 'approved', NULL, NULL, NULL, 'General'),
(4, 'Mental Health Awareness Workshop', 'An interactive workshop on stress management, mindfulness, and maintaining well-being during academic life. Free refreshments provided.', '2026-05-10 14:00:00', 'Lecture Hall B', 80, 2, '2026-04-08 14:12:20', NULL, NULL, 'pending', NULL, NULL, NULL, 'General'),
(5, 'Startup Pitch Competition', 'Present your startup idea to a panel of investors and mentors. Cash prizes and mentorship opportunities for top 3 teams.', '2026-05-20 09:00:00', 'Business School Atrium', 150, 2, '2026-04-08 14:12:20', NULL, NULL, 'pending', NULL, NULL, NULL, 'General'),
(6, 'Easter production', 'Easter Easter Easter Easter Easter', '2026-02-12 07:44:00', 'church compound', 30, 6, '2026-04-11 17:18:17', NULL, NULL, 'pending', NULL, 'https://www.youtube.com/live/sv7KNGVihoE?si=KgAAZvSdj5IolOa4', NULL, 'church'),
(7, 'Easter production', 'Easter Easter Easter Easter Easter', '2026-04-12 07:44:00', 'church compound', 30, 6, '2026-04-11 17:27:38', NULL, 'past', 'rejected', NULL, 'https://www.youtube.com/live/sv7KNGVihoE?si=KgAAZvSdj5IolOa4', NULL, 'church'),
(8, 'IT DAY', 'ASCC  SFEVSB', '2026-04-05 07:36:00', 'church compound', 400, 6, '2026-04-11 17:37:22', NULL, NULL, 'approved', '/campus_ems/uploads/events/69da5ca2d7cf0.jpg', 'https://www.youtube.com/live/sv7KNGVihoE?si=KgAAZvSdj5IolOa4', NULL, 'church'),
(9, 'AFF', 'AFF', '2026-04-12 05:44:00', 'church compoundt', 224, 6, '2026-04-11 17:45:34', NULL, NULL, 'pending', NULL, 'https://www.youtube.com/live/sv7KNGVihoE?si=KgAAZvSdj5IolOa4', NULL, 'AAF'),
(10, 'Birthday', 'Celabrating my sweet 16, Dress code White', '2026-04-21 12:29:00', 'AFRU CONFRENCE HALL', 60, 8, '2026-04-13 12:31:45', NULL, NULL, 'pending', '/campus_ems/uploads/events/69dcb8017d090.jpg', '', NULL, 'Birthday'),
(11, 'big day', 'errrr', '2026-05-06 17:36:00', 'church compound', 23, 6, '2026-04-13 17:37:53', NULL, NULL, 'pending', '/campus_ems/uploads/events/69dd0777dcc64.png', 'https://youtu.be/EgAF39NoF_I?si=2Woluhx0YU-PpeX9', NULL, 'church');

-- --------------------------------------------------------

--
-- Table structure for table `event_deletion_log`
--

CREATE TABLE `event_deletion_log` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `deleted_by` int(11) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_images`
--

CREATE TABLE `event_images` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_images`
--

INSERT INTO `event_images` (`id`, `event_id`, `image_path`, `uploaded_at`) VALUES
(1, 7, '/campus_ems/uploads/events/69da5a5a77245.jpg', '2026-04-11 17:27:38'),
(2, 8, '/campus_ems/uploads/events/69da5ca3008f2.jpg', '2026-04-11 17:37:22'),
(5, 11, '/campus_ems/uploads/events/69dd0a3555c4d.jpg', '2026-04-13 18:22:29');

-- --------------------------------------------------------

--
-- Table structure for table `event_rejection_log`
--

CREATE TABLE `event_rejection_log` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `rejected_by` int(11) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rejected_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_rejection_log`
--

INSERT INTO `event_rejection_log` (`id`, `event_id`, `rejected_by`, `reason`, `rejected_at`) VALUES
(1, 7, 5, 'past', '2026-04-13 12:20:11');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `status` enum('registered','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registered',
  `registered_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `user_id`, `event_id`, `status`, `registered_at`) VALUES
(1, 3, 1, 'registered', '2026-04-08 14:12:20'),
(2, 3, 3, 'registered', '2026-04-08 14:12:20'),
(3, 5, 1, 'cancelled', '2026-04-10 19:30:32'),
(6, 5, 2, 'cancelled', '2026-04-10 19:34:54'),
(8, 7, 4, 'registered', '2026-04-13 12:25:25'),
(9, 9, 11, 'registered', '2026-04-14 16:20:29'),
(10, 9, 1, 'registered', '2026-04-14 16:20:44'),
(11, 9, 4, 'registered', '2026-04-14 16:20:54'),
(12, 9, 5, 'registered', '2026-04-14 16:21:03');

-- --------------------------------------------------------

--
-- Table structure for table `student_notifications`
--

CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('capacity','upcoming') COLLATE utf8mb4_unicode_ci DEFAULT 'upcoming',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_notifications`
--

INSERT INTO `student_notifications` (`id`, `user_id`, `event_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 9, 1, '📢 \'Tech Innovation Summit 2026\' is coming up on 15 Apr 2026', 'upcoming', 0, '2026-04-14 17:29:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','organizer','student') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `contact_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_pic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `deleted_at`, `is_approved`, `rejection_reason`, `contact_number`, `profile_pic`, `position`) VALUES
(1, 'Super Admin', 'admin@campus.edu', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-04-08 14:12:20', NULL, 0, NULL, NULL, NULL, NULL),
(2, 'Event Organizer', 'organizer@campus.edu', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organizer', '2026-04-08 14:12:20', NULL, 1, NULL, NULL, NULL, NULL),
(3, 'Jane Student', 'student@campus.edu', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2026-04-08 14:12:20', NULL, 0, NULL, NULL, NULL, NULL),
(4, 'member1', 'member1@gmail.com', '$2y$10$V8rBHZaUOeFFJbwN43ko4uZ0HSJawGL1tu8b8mjXHKu2cvYW53IzK', 'student', '2026-04-08 15:46:15', NULL, 0, NULL, NULL, NULL, NULL),
(5, 'admin1', 'admin1@gmail.com', '$2y$10$Hn08u22vWqKexsuyE7c83eEjEVSwxE8gpRRdNVy7QNRFmaoaJf0Sm', 'admin', '2026-04-10 19:26:16', NULL, 0, NULL, NULL, NULL, NULL),
(6, 'Jo', 'jo@gmail.com', '$2y$10$FSJX4xyRa9g/dyhqhpZWv.fxJ3YtzzH/RxmFaMoL74pmSmgKNNBmu', 'organizer', '2026-04-10 20:21:06', NULL, 1, 'i dont want in my system', NULL, NULL, NULL),
(7, 'Bwire Emmanuel', 'emmer@gmail.com', '$2y$10$WzmMqPV9CM5tvh8marWWIeJqyhY8frQITPUDg6nDzNNovJuPQJEuq', 'student', '2026-04-13 12:24:25', NULL, 0, NULL, NULL, NULL, NULL),
(8, 'Tumwebaze Ruth', 'tumwebazeruth70@gmail.com', '$2y$10$i6Ukbl3jbs3KYjP8wUv3V.ZkZiDX0MBbE9Wz.DWql1oNgJ.ju2K2e', 'organizer', '2026-04-13 12:27:45', NULL, 1, NULL, NULL, NULL, NULL),
(9, 'Jemimah Hererah', 'jem@gmail.com', '$2y$10$OWMKECzEwS3FWrud4yTUPe7HPyHzjApZcw0OWgwxi4kM0vJCdxSSO', 'student', '2026-04-14 16:19:43', NULL, 0, NULL, NULL, NULL, NULL),
(10, 'student1', 'student1@gmail.com', '$2y$10$WuIPEzabtHSRKGhbLPgO/uluUbPUbZ.wXhnks0rs/udv036oMTvT2', 'student', '2026-04-15 19:30:08', NULL, 0, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `event_deletion_log`
--
ALTER TABLE `event_deletion_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `deleted_by` (`deleted_by`);

--
-- Indexes for table `event_images`
--
ALTER TABLE `event_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `event_rejection_log`
--
ALTER TABLE `event_rejection_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `rejected_by` (`rejected_by`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `event_deletion_log`
--
ALTER TABLE `event_deletion_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `event_images`
--
ALTER TABLE `event_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `event_rejection_log`
--
ALTER TABLE `event_rejection_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `student_notifications`
--
ALTER TABLE `student_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
