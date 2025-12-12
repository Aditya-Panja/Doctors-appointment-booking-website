-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2025 at 02:37 AM
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
-- Database: `appointment_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('scheduled','confirmed','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `created_at`) VALUES
(3, 6, 5, '2025-09-09', '09:55:00', 'scheduled', '2025-09-09 04:25:39'),
(5, 16, 18, '2025-10-17', '11:14:00', 'scheduled', '2025-10-16 16:44:57'),
(7, 30, 29, '2025-11-10', '10:00:00', 'cancelled', '2025-11-05 04:24:43'),
(8, 30, 29, '2025-11-10', '10:30:00', 'scheduled', '2025-11-05 05:18:31'),
(9, 30, 29, '2025-11-10', '10:00:00', 'cancelled', '2025-11-05 06:45:22'),
(10, 30, 29, '2025-11-10', '10:00:00', 'cancelled', '2025-11-05 07:10:55'),
(11, 30, 29, '2025-11-10', '10:00:00', 'cancelled', '2025-11-05 07:11:47'),
(12, 31, 29, '2025-11-10', '10:00:00', 'cancelled', '2025-11-05 11:23:55'),
(13, 31, 29, '2025-11-10', '10:00:00', 'scheduled', '2025-11-05 11:24:28');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `hero_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `hero_image`) VALUES
(1, 'Cardiology', 'We provide advanced diagnostics and treatments for all heart-related conditions, from common issues to complex cardiovascular diseases.', 'neurology_hero.png'),
(2, 'Neurology', 'Expert care for disorders of the nervous system, including the brain and spinal cord. We treat conditions like stroke, epilepsy, and more.', 'neurology_hero.png'),
(3, 'Orthopedics', 'Our department offers comprehensive care for bone, joint, and muscle injuries and conditions to restore movement and improve quality of life.', 'neurology_hero.png'),
(4, 'Pediatrics', 'Compassionate care for infants, children, and adolescents to ensure their healthy development, from routine checkups to specialized treatments.', 'neurology_hero.png');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `fees` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT 'default_doctor.png',
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `specialization`, `fees`, `image`, `bio`) VALUES
(1, 3, 'Cardiology', 150.00, 'default_doctor.png', 'Expert in cardiovascular diseases.'),
(2, 4, 'Neurology', 180.00, 'default_doctor.png', 'Specializes in brain and nervous system disorders.'),
(3, 5, 'Orthopedics', 130.00, 'default_doctor.png', 'Focuses on bone, joint, and muscle health.'),
(4, 7, 'Pediatrics', 120.00, 'default_doctor.png', NULL),
(6, 18, 'Pediatrics', 1000.00, 'd8ebbdd43edf4f23dd0bc10b455b1b0f.jpg', NULL),
(7, 29, 'Cardiology', 100.00, '839c888ad4af8b9764b57edfc92de450.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_availability`
--

CREATE TABLE `doctor_availability` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_availability`
--

INSERT INTO `doctor_availability` (`id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 29, 'Monday', '10:00:00', '12:00:00'),
(2, 29, 'Wednesday', '14:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('patient','doctor','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `created_at`, `otp`, `otp_expiry`, `is_verified`) VALUES
(1, 'Admin User', 'admin@app.com', '$2y$10$pHnvRbWlGkrbLOQy4oHpsOIGUQniUa69MuFxI5ZvcQdbOsZ24XZhi', 'admin', '2025-09-08 11:16:20', NULL, NULL, 1),
(3, 'Dr. Emily Carter', 'emily.carter@hospital.com', '$2y$10$I0S.15aF581QEbCJs48jbuII/JM9d729O1vj.oYjA4i5e.Ha/oWky', 'doctor', '2025-09-09 03:55:27', NULL, NULL, 0),
(4, 'Dr. Ben Adams', 'ben.adams@hospital.com', '$2y$10$I0S.15aF581QEbCJs48jbuII/JM9d729O1vj.oYjA4i5e.Ha/oWky', 'doctor', '2025-09-09 03:55:27', NULL, NULL, 0),
(5, 'Dr. Chloe Davis', 'chloe.davis@hospital.com', '$2y$10$I0S.15aF581QEbCJs48jbuII/JM9d729O1vj.oYjA4i5e.Ha/oWky', 'doctor', '2025-09-09 03:55:27', NULL, NULL, 0),
(6, 'Aditya Panja', 'adityapanja2@gmail.com', '$2y$10$ZyZ6IWQwYcucwPfPD8dpN.PydovWVOsHSPi6XiDVRAm.KE2K6cYrS', 'patient', '2025-09-09 04:24:52', NULL, NULL, 0),
(7, 'Dr. Sarah Jenkins', 'sarah.j@hospital.com', '$2y$10$Bv3WOGqhwr6.vyupGoeFzufIiD89zTSx6c7vS.3ee0URSJMguUJVq', 'doctor', '2025-09-09 05:42:17', NULL, NULL, 0),
(16, 'Aditya Panja', 'adityapanja3002@gmail.com', '$2y$10$F97M4pR0p6AUxAZj8x9K1OTwFSs71yJfnZXGD1gBBmkP7ViAVqCYW', 'admin', '2025-10-16 15:50:54', NULL, NULL, 1),
(18, 'shyam das', 'shyam@gmail.com', '$2y$10$pPKmW6GEcQtCugiNXjL2Mu0Vv2FvUB6MNv5DebjUG6AzNRxwbpzOG', 'doctor', '2025-10-16 16:41:21', NULL, NULL, 0),
(29, 'Dr. Soumi Chandra', 'adityapanja6519@gmail.com', '$2y$10$5B6IAku580wkzbHbLGL8zuE91TobB/1ebO/vWj6Vw4SsabmSZ2GES', 'doctor', '2025-11-04 14:32:06', NULL, NULL, 1),
(30, 'Aditya Panja', 'adityapanja7439@gmail.com', '$2y$10$Dr383FQ8rdkhcab5Mp/j6ueV8pimTcTl3WbJqQe0qtus0g3tWwPBG', 'patient', '2025-11-04 14:54:45', NULL, NULL, 1),
(31, 'Soumi chandra', 'soumichandra27@gmail.com', '$2y$10$O9nECJgrN6Oe6kr5tLy2Mu.gNgMqIS7wHJ1Y/7i1JP3b71Ra89ozO', 'patient', '2025-11-05 11:18:06', NULL, NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

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
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD CONSTRAINT `fk_doctor_id` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
