-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2026 at 03:06 AM
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
-- Database: `employee_work_tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `daily_updates`
--

CREATE TABLE `daily_updates` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `completed_work` text DEFAULT NULL,
  `planned_work` text DEFAULT NULL,
  `pending_work` text DEFAULT NULL,
  `blockers` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_updates`
--

INSERT INTO `daily_updates` (`id`, `employee_id`, `work_date`, `completed_work`, `planned_work`, `pending_work`, `blockers`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-10-14', 'Implemented login system and PDO auth', 'Continue task management UI', 'Bug fixes', 'Waiting on API response', 'Initial auth setup complete', '2026-10-14 15:16:17', '2026-10-14 15:16:17'),
(2, 3, '2026-10-14', 'Designed database schema with InnoDB', 'Implement daily updates form', NULL, NULL, 'Schema per master prompt', '2026-10-14 15:16:17', '2026-10-14 15:16:17'),
(3, 4, '2026-10-13', 'Built task CRUD interface', 'Fix responsive design issues', 'CSS alignment', NULL, 'Mobile view needs work', '2026-10-14 15:16:17', '2026-10-14 15:16:17');

-- --------------------------------------------------------

--
-- Table structure for table `daily_update_tasks`
--

CREATE TABLE `daily_update_tasks` (
  `daily_update_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_update_tasks`
--

INSERT INTO `daily_update_tasks` (`daily_update_id`, `task_id`) VALUES
(1, 1),
(2, 2),
(3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `manager_feedback`
--

CREATE TABLE `manager_feedback` (
  `id` int(11) NOT NULL,
  `daily_update_id` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `feedback` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `manager_feedback`
--

INSERT INTO `manager_feedback` (`id`, `daily_update_id`, `manager_id`, `feedback`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'zz', '2026-10-14 15:16:17', '2026-10-15 01:49:10'),
(2, 2, 1, 'Schema looks solid. Add indexes on foreign keys as discussed.', '2026-10-14 15:16:17', '2026-10-14 15:16:17');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `status` enum('To Do','In Progress','Blocked','Completed') NOT NULL DEFAULT 'To Do',
  `start_date` date NOT NULL,
  `due_date` date NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `assigned_to`, `created_by`, `manager_id`, `priority`, `status`, `start_date`, `due_date`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'Implement login system', 'Build authentication with PDO and password_hash ok', 2, 1, 1, 'High', 'In Progress', '2026-10-01', '2026-10-15', NULL, '2026-10-14 15:16:17', '2026-10-15 01:48:51'),
(2, 'Design database schema', 'Create normalized tables for employees, tasks, updates', 2, 1, 1, 'Medium', 'To Do', '2026-10-01', '2026-10-20', NULL, '2026-10-14 15:16:17', '2026-10-15 01:29:02'),
(3, 'Develop task management UI', 'Create CRUD interface for task creation and editing', 2, 1, 1, 'High', 'In Progress', '2026-10-05', '2026-10-18', NULL, '2026-10-14 15:16:17', '2026-10-15 01:29:02'),
(4, 'Implement daily updates form', 'Allow employees to submit daily work reports', 3, 1, 1, 'Medium', 'To Do', '2026-10-01', '2026-10-16', NULL, '2026-10-14 15:16:17', '2026-10-15 01:29:02'),
(5, 'Build manager dashboard', 'Display team progress, overdue tasks, reports', 2, 1, 1, 'High', 'Blocked', '2026-10-01', '2026-10-25', NULL, '2026-10-14 15:16:17', '2026-10-15 01:29:02'),
(6, 'Performance evaluation metrics', 'Calculate completion rates and overdue stats', 2, 1, 1, 'Low', 'To Do', '2026-10-10', '2026-11-10', NULL, '2026-10-14 15:16:17', '2026-10-15 01:29:02');

-- --------------------------------------------------------

--
-- Table structure for table `task_activity`
--

CREATE TABLE `task_activity` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `old_status` enum('To Do','In Progress','Blocked','Completed') DEFAULT NULL,
  `new_status` enum('To Do','In Progress','Blocked','Completed') NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_activity`
--

INSERT INTO `task_activity` (`id`, `task_id`, `changed_by`, `old_status`, `new_status`, `comment`, `created_at`) VALUES
(1, 1, 1, 'To Do', 'In Progress', 'Started work on login system', '2026-10-14 15:16:17'),
(2, 3, 1, 'To Do', 'In Progress', 'Began developing task management UI', '2026-10-14 15:16:17'),
(3, 1, 1, 'In Progress', 'In Progress', 'aaa', '2026-10-15 01:48:14'),
(4, 1, 1, 'In Progress', 'In Progress', 'Task edited', '2026-10-15 01:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','manager','employee') NOT NULL DEFAULT 'employee',
  `manager_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `manager_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Alice Manager', 'manager@local.test', '$2y$10$0oKFWuFyjxvpY2yOLX.ZyeNJH0Yk3YQApTQUAwrLlBVsSlnxChno.', 'manager', NULL, 1, '2026-10-14 15:16:16', '2026-10-14 16:04:26'),
(2, 'Bob Employee', 'employee1@local.test', '$2y$10$0oKFWuFyjxvpY2yOLX.ZyeNJH0Yk3YQApTQUAwrLlBVsSlnxChno.', 'employee', 1, 1, '2026-10-14 15:16:16', '2026-10-14 16:04:26'),
(3, 'Carol Employee', 'employee2@local.test', '$2y$10$0oKFWuFyjxvpY2yOLX.ZyeNJH0Yk3YQApTQUAwrLlBVsSlnxChno.', 'employee', 1, 1, '2026-10-14 15:16:16', '2026-10-14 16:04:26'),
(4, 'Dave Employee', 'employee3@local.test', '$2y$10$0oKFWuFyjxvpY2yOLX.ZyeNJH0Yk3YQApTQUAwrLlBVsSlnxChno.', 'employee', 1, 1, '2026-10-14 15:16:16', '2026-10-14 16:04:26'),
(5, 'Eve Admin', 'admin@local.test', '$2y$10$0oKFWuFyjxvpY2yOLX.ZyeNJH0Yk3YQApTQUAwrLlBVsSlnxChno.', 'admin', NULL, 1, '2026-10-14 15:16:16', '2026-10-14 16:05:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_updates`
--
ALTER TABLE `daily_updates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_employee_date` (`employee_id`,`work_date`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_work_date` (`work_date`);

--
-- Indexes for table `daily_update_tasks`
--
ALTER TABLE `daily_update_tasks`
  ADD PRIMARY KEY (`daily_update_id`,`task_id`),
  ADD KEY `idx_task_id` (`task_id`);

--
-- Indexes for table `manager_feedback`
--
ALTER TABLE `manager_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_daily_update` (`daily_update_id`),
  ADD KEY `idx_manager` (`manager_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_manager` (`manager_id`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_start_date` (`start_date`);

--
-- Indexes for table `task_activity`
--
ALTER TABLE `task_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task` (`task_id`),
  ADD KEY `idx_changed_by` (`changed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `fk_manager` (`manager_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `daily_updates`
--
ALTER TABLE `daily_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `manager_feedback`
--
ALTER TABLE `manager_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_activity`
--
ALTER TABLE `task_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `daily_updates`
--
ALTER TABLE `daily_updates`
  ADD CONSTRAINT `fk_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `daily_update_tasks`
--
ALTER TABLE `daily_update_tasks`
  ADD CONSTRAINT `fk_du_id` FOREIGN KEY (`daily_update_id`) REFERENCES `daily_updates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `manager_feedback`
--
ALTER TABLE `manager_feedback`
  ADD CONSTRAINT `fk_mf_daily_update` FOREIGN KEY (`daily_update_id`) REFERENCES `daily_updates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mf_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tasks_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `task_activity`
--
ALTER TABLE `task_activity`
  ADD CONSTRAINT `fk_act_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_act_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
