-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 01, 2025 at 12:00 PM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u397030529_ttt_zoom_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `batchs`
--

CREATE TABLE `batchs` (
  `id` int(32) NOT NULL,
  `course_code` varchar(255) NOT NULL,
  `batch_name` varchar(255) NOT NULL,
  `branch` varchar(255) NOT NULL,
  `uploaded_by` varchar(255) NOT NULL,
  `upload_time` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batchs`
--

INSERT INTO `batchs` (`id`, `course_code`, `batch_name`, `branch`, `uploaded_by`, `upload_time`) VALUES
(1, 'DCET-25', 'DCET-25 Long Term Offline Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(2, 'DCET-25', 'DCET-25 Long Term Offline-Online Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(3, '9- State', '9th State Evening Batch', ' B-01', 'savin@9986', '06/08/2024 10:58:41'),
(4, '9-ICSE', '9th ICSE Evening Batch', ' B-01', 'savin@9986', '06/08/2024 10:58:55'),
(5, '10- State', '10th State Evening Batch', ' B-01', 'savin@9986', '06/08/2024 10:59:32'),
(6, '10- CBSE', '10th CBSE Evening Batch', ' B-01', 'savin@9986', '06/08/2024 10:59:52'),
(7, '10- ICSE', '10th ICSE Evening Batch', ' B-01', 'savin@9986', '06/08/2024 11:00:10'),
(8, 'I-PU', 'I-PUC Sci (PCM) Evening Batch', ' B-01', 'savin@9986', '06/08/2024 11:00:49'),
(9, 'II-PU', 'II-PUC Sci (PCMB) Evening Batch', ' B-01', 'savin@9986', '06/08/2024 11:01:20'),
(10, 'II-PU Comm', 'II-PUC Commerce Evening Batch', ' B-01', 'savin@9986', '06/08/2024 11:03:37'),
(11, 'KCET-25', 'KCET-25 Long Term Batch', ' B-01', 'savin@9986', '06/08/2024 11:03:53'),
(12, 'BE-M3', 'BE - August to Dec-24 Batch  CS & IS Allied Branch', ' B-01', 'savin@9986', '22/08/2024 11:32:45'),
(13, 'Diploma', 'Diploma August to Dec-24 Batch', ' B-01', 'savin@9986', '06/08/2024 11:05:17'),
(14, 'DCET-26', 'DCET-26 Long Term Batch', ' B-01', 'savin@9986', '08/08/2024 16:29:12'),
(15, 'DCET-25', 'DCET-25 Long Term Online Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(16, 'BE-M3', 'BE - August to Dec-24 Batch  EC & EE Allied Branch', ' B-01', 'savin@9986', '22/08/2024 11:32:45'),
(17, 'BE-M3', 'BE - August to Dec-24 Batch  Other ', ' B-01', 'savin@9986', '22/08/2024 11:32:45'),
(18, 'DCET-25', 'DCET-25 Short Term Offline Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(19, 'Dcet 25 Online', 'DCET-25 Online Long Term Batch', ' B-01', 'savin@9986', '24/08/2024 15:07:31'),
(20, 'Dcet 25 Online', 'DCET-25 Online Short Term Batch', ' B-01', 'savin@9986', '24/08/2024 15:07:31'),
(21, 'Dcet 25 Online', 'DCET-25 Online Crash Course Batch', ' B-01', 'savin@9986', '24/08/2024 15:07:31'),
(22, 'DCET-25 Recorded', 'DCET-25 Recorded Long Term Batch', ' B-01', 'savin@9986', '24/08/2024 15:08:45'),
(23, 'DCET-25 Recorded', 'DCET-25 Recorded Short Term Batch', ' B-01', 'savin@9986', '24/08/2024 15:08:45'),
(24, 'DCET-25 Recorded', 'DCET-25 Recorded Crash Course Batch', ' B-01', 'savin@9986', '24/08/2024 15:08:45'),
(25, 'DCET-25 Online-Offline', 'DCET-25 Online-Offline Long Term Batch', ' B-01', 'savin@9986', '31/08/2024 12:23:13'),
(26, 'DCET-25 Online-Offline', 'DCET-25 Online-Offline Short Term Batch', ' B-01', 'savin@9986', '31/08/2024 12:23:13'),
(27, 'DCET-25 Online-Offline', 'DCET-25 Online-Offline Crash Course', ' B-01', 'savin@9986', '31/08/2024 12:23:13'),
(28, 'DCET-25', 'DCET-25 Short Term Offline-Online Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(29, 'DCET-25', 'DCET-25 Short Term Online Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(30, 'DCET-25', 'DCET-25 Crash Course Offline Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(31, 'DCET-25', 'DCET-25 Crash Course Offline-Online Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(32, 'DCET-25', 'DCET-25 Crash Course Online Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(33, 'DCET-25', 'DCET-25 Week-End (Sat&Sunday) Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(34, 'DCET-25', 'DCET-25 Morning B-1 7AM Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(35, 'DCET-25', 'DCET-25 Evening B-2 5PM Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(36, 'DCET-25', 'DCET-25 Evening B-3  7PM Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(37, 'II-PU Sci 25-26', 'Evening Batch', ' B-01', 'savin@9986', '31/01/2025 21:25:48'),
(38, '10 - State 25-26', 'Evening Batch', ' B-01', 'savin@9986', '31/01/2025 21:28:54'),
(39, '10 State 2026', 'Evening Batch', ' B-02', 'savin@9986', '09/02/2025 21:11:52'),
(40, '10 State 25-26', 'Evening Batch', ' B-03', 'savin@9986', '19/02/2025 20:04:22'),
(41, 'KCET-2025', 'Crash Course', ' B-03', 'savin@9986', '19/02/2025 20:04:42'),
(42, 'II PUC Sci 26', 'Evening Batch', ' B-03', 'savin@9986', '04/03/2025 13:50:04'),
(43, '10-ICSE-2026', 'Evening Batch', ' B-01', 'savin@9986', '05/03/2025 15:59:30'),
(44, '10-CBSE-2026', 'Evening Batch', ' B-01', 'Savin@9986', '08/03/2025 11:50:46'),
(45, 'KCET-25', 'Crash Course', ' B-01', 'savin@9986', '11/03/2025 09:31:08'),
(46, 'II PUC Sci 26.', 'Evening Batch', ' B-02', 'savin@9986', '12/03/2025 20:23:38'),
(47, 'KCET-26.', 'Long Term Batch', ' B-02', 'savin@9986', '12/03/2025 20:30:00'),
(48, 'I PUC Sci 26.', 'Evening Batch', ' B-02', 'savin@9986', '12/03/2025 20:46:19'),
(49, 'KCET-26', 'Long Term Course', ' B-01', 'savin@9986', '13/03/2025 16:34:36'),
(50, 'DCET-25', '6-8PM Short + Crash Course Batch', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(51, 'DCET-25', 'Crash Course - 6:30AM Batch  (Laggere Branch)  M.B-1', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(52, 'DCET-25', 'Crash Course - 10:00AM Batch  (Laggere Branch)  D.B-1', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(53, 'DCET-25', 'Crash Course - 05:00PM Batch  (Laggere Branch)  E.B-1', ' B-01', 'savin@9986', '11/04/2025 13:54:11'),
(54, 'II PUC Comm 26', 'Evening Batch', ' B-01', 'savin@9986', '09/04/2025 21:32:18'),
(55, 'DCET-25', 'Crash Course - 10:00AM Batch  (Hesaraghtaa Branch)  D.B-2', ' B-01', 'savin@9986', '11/04/2025 13:55:37'),
(56, 'DCET-25', 'Crash Course - 6:30AM Batch  (Hesaraghtaa Branch)  M.B-2', ' B-01', 'savin@9986', '11/04/2025 13:55:37'),
(57, '10-CBSE-26', 'Evening Batch', ' B-03', 'rakesh@3', '11/04/2025 21:28:37'),
(58, '9 state 25-26 ', 'Evening Batch', ' B-02', 'savin@9986', '15/04/2025 19:27:50'),
(59, 'ICSE-10', 'Morning ', ' B-03', 'rakesh@3', '22/04/2025 18:46:26'),
(60, 'ICSE-10', 'Evening', ' B-03', 'rakesh@3', '22/04/2025 18:46:26'),
(61, '9-CBSE-26', 'Evening Batch', ' B-01', 'Chaitanyaps9955', '02/06/2025 19:35:38'),
(62, '9-State-26', 'Evening Batch', ' B-01', 'Chaitanyaps9955', '02/06/2025 19:42:50'),
(63, 'I PU-25-26 Sci', 'Evening Batch', ' B-01', 'Chaitanyaps9955', '09/06/2025 20:13:19'),
(64, 'DCET-27', 'Long Term Course', ' B-01', 'savin@9986', '16/06/2025 13:30:29'),
(65, 'II-PU Comm 26.', 'Evening Batch', ' B-02', 'savin@9986', '16/06/2025 21:20:47');

-- --------------------------------------------------------

--
-- Table structure for table `branch_details`
--

CREATE TABLE `branch_details` (
  `id` int(32) NOT NULL,
  `branch_code` varchar(255) NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `branch_address` varchar(255) NOT NULL,
  `remarks` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch_details`
--

INSERT INTO `branch_details` (`id`, `branch_code`, `branch_name`, `branch_address`, `remarks`) VALUES
(1, ' B-01', 'Main Branch 01  (Laggere)', '#8, MEI Colony, Laggere', 'Savin'),
(2, ' B-02', 'Main Branch 02  (Hesaraghatta Main Road)', 'Near BHagalgunte Arch, Hesaraghatta Main Road', '9986869966'),
(3, ' B-03', 'Main Branch 03  (Magadi Main Road)', '#11, Maruthi Complex, Doddagollarahatti, Magadi Main Road, Bangalore-91', 'Magadi Main Road');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(32) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_code` varchar(255) NOT NULL,
  `id_pattern` varchar(255) NOT NULL,
  `course_status` int(8) NOT NULL,
  `branch` varchar(255) NOT NULL,
  `refer_price` int(32) NOT NULL,
  `out_side` int(32) NOT NULL,
  `uploaded_by` varchar(255) NOT NULL,
  `upload_time` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_name`, `course_code`, `id_pattern`, `course_status`, `branch`, `refer_price`, `out_side`, `uploaded_by`, `upload_time`) VALUES
(1, 'DCET-25 Coaching Batch', 'DCET-25', 'TTT-DCET-{GROUP}-25-', 0, ' B-01', 250, 500, 'savin@9986', '28/07/2024 22:24:09'),
(2, '9th State Coaching', '9- State', 'TTT-9th-State-24-25-', 0, ' B-01', 0, 0, 'savin@9986', '03/08/2024 11:22:04'),
(3, '9th ICSE Coaching', '9-ICSE', 'TTT-9th-ICSE-24-25-', 0, ' B-01', 0, 0, 'savin@9986', '03/08/2024 11:23:56'),
(4, '10th State Coaching', '10- State', 'TTT-10th-State-24-25-', 0, ' B-01', 0, 0, 'savin@9986', '03/08/2024 11:25:04'),
(5, '10th CBSE Coaching', '10- CBSE', 'TTT-10th-CBSE-24-25-', 0, ' B-01', 0, 0, 'savin@9986', '03/08/2024 11:26:17'),
(6, '10th ICSE Coaching', '10- ICSE', 'TTT-10th-ICSE-24-25-', 1, ' B-01', 0, 0, 'savin@9986', '03/08/2024 11:28:05'),
(7, 'I PU Science Coaching', 'I-PU', 'TTT-I-PU-Sci-24-25-', 0, ' B-01', 0, 0, 'savin@9986', '03/08/2024 11:30:28'),
(8, 'II PU Science Coaching', 'II-PU', 'TTT-II-PU-Sci-24-25-', 0, ' B-01', 0, 0, 'savin@9986', '03/08/2024 11:32:11'),
(9, 'II PU Commerce Coaching', 'II-PU Comm', 'TTT-II-PU-Commerce-24-25-', 0, ' B-01', 0, 0, 'savin@9986', '03/08/2024 11:36:42'),
(10, 'KCET-25 Coaching', 'KCET-25', 'TTT-KCET-25-', 0, ' B-01', 0, 0, 'savin@9986', '03/08/2024 11:39:13'),
(11, 'ENGINEERING MATHEMATICS-3 Coaching', 'BE-M3', 'TTT-M3-24-25-', 0, ' B-01', 0, 0, 'savin@9986', '03/08/2024 11:47:09'),
(12, 'Diploma coaching', 'Diploma', 'TTT-Diploma-24-25-', 0, ' B-01', 0, 0, 'savin@9986', '03/08/2024 11:48:55'),
(13, 'TTT Coaching Admins', 'TTT Coaching', 'TTT-E-ID-', 0, ' B-01', 0, 0, 'savin@9986', '05/08/2024 13:07:15'),
(14, 'DCET-26 Long Term Coaching Batch', 'DCET-26', 'TTT-DCET-{GROUP}-26-', 1, ' B-01', 250, 500, 'savin@9986', '08/08/2024 16:26:30'),
(15, 'Diploma CET Recorded', 'DCET-25 Recorded', 'TTT-DCET-Rec-{GROUP}-25-', 0, ' B-01', 250, 500, 'savin@9986', '08/08/2024 19:06:39'),
(16, 'Dcet 25 Online Batch', 'Dcet 25 Online', 'TTT-DCET-25-Online{GROUP}-', 0, ' B-01', 250, 500, 'savin@9986', '23/08/2024 13:31:59'),
(17, 'DCET-25 Online-Offline Long Term Coaching Batch', 'DCET-25 Online-Offline', 'TTT-DCET-On-Off-{GROUP}-25-', 0, ' B-01', 250, 500, 'savin@9986', '31/08/2024 12:22:00'),
(18, 'DCET-25 Long Term Coaching Batch', 'DCET-25 Offline', 'TTT-DCET-Offline-{GROUP}-25-', 0, ' B-01', 250, 500, 'savin@9986', '31/08/2024 12:29:35'),
(19, 'BEL Recruitment 2024 - EAT Electronics Dept. Post', 'BEL Recruitment 2024 - EAT', 'TTT-BEL-24-EC-001', 0, ' B-01', 250, 500, 'savin@9986', '04/12/2024 12:43:33'),
(20, 'II-PU Science 2025-26 Batch', 'II-PU Sci 25-26', 'TTT-PUSci-{GROUP}-26-001', 1, ' B-01', 2500, 4000, 'savin@9986', '31/01/2025 13:53:28'),
(21, '10-State 2025-26 Batch', '10 - State 25-26', 'TTT-10-State-{GROUP}-26-001', 1, ' B-01', 1000, 2000, 'savin@9986', '31/01/2025 21:28:41'),
(22, 'KCET 25 Crash Course Coaching', 'KCET-2025', 'TTT-KCET-25{GROUP}-001', 1, ' B-03', 1000, 2000, 'savin@9986', '03/02/2025 22:00:36'),
(23, '10 State 2026 Coaching Class', '10 State 2026', 'TTT-10-State-{GROUP}-26-', 1, ' B-02', 1000, 2000, 'savin@9986', '09/02/2025 21:11:32'),
(24, '10-State 2025-26 Batch', '10 State 25-26', 'TTT-10-State-{GROUP}-25-001', 1, ' B-03', 500, 1000, 'savin@9986', '19/02/2025 20:03:59'),
(25, 'TTT-PU-Science-26 Coaching Batch', 'II PUC Sci 26', 'TTT-PU-Sci-26-01', 1, ' B-03', 1000, 2000, 'savin@9986', '04/03/2025 13:49:44'),
(26, '10-ICSE 2025-26 Batch', '10-ICSE-2026', 'TTT-10-ICSE-26-01', 1, ' B-01', 1000, 2000, 'savin@9986', '04/03/2025 21:31:22'),
(27, '10-CBSE 2025-26 Batch', '10-CBSE-2026', 'TTT-10-CBSE-26-01', 1, ' B-01', 1000, 2000, 'Savin@9986', '08/03/2025 11:50:25'),
(28, 'TTT-PU-Science-26 Coaching Batch', 'II PUC Sci 26.', 'TTT-PU-Sci-26-01', 1, ' B-02', 1000, 2000, 'savin@9986', '12/03/2025 20:23:07'),
(29, 'TTT-KCET-26 Coaching.', 'KCET-26.', 'TTT-KCET-26-{GROUP}-', 1, ' B-02', 1000, 2000, 'savin@9986', '12/03/2025 20:29:30'),
(30, 'TTT-I-PU-Science-26 Coaching Batch', 'I PUC Sci 26.', 'TTT-I-PU-Sci-01', 1, ' B-02', 1000, 2000, 'savin@9986', '12/03/2025 20:44:03'),
(31, 'KCET 26 Long Course Coaching', 'KCET-26', 'TTT-KCET-{GROUP}-26-001', 1, ' B-01', 1000, 2000, 'savin@9986', '13/03/2025 16:34:13'),
(32, 'CBSE-10', 'CBSE-25', 'TTT-CBSE-25', 0, ' B-03', 1000, 1500, 'vinodn@123', '20/03/2025 18:01:05'),
(33, 'ICSE-25', 'ICSE-10', 'TTT-ICSE-25-', 1, ' B-03', 2000, 3000, 'rakesh@3', '22/04/2025 18:45:43'),
(34, 'TTT-PU-Comm-26 Coaching Batch', 'II PUC Comm 26', 'TTT-PU-Comm-26-01', 1, ' B-01', 2000, 1000, 'savin@9986', '09/04/2025 21:32:07'),
(35, '10th CBSE 2026 Coaching', '10-CBSE-26', 'TTT-10-CBSE-26-001-', 1, ' B-03', 0, 0, 'rakesh@3', '11/04/2025 21:28:13'),
(36, '9 state 25-26 Coaching', '9 state 25-26 ', 'TTT-9-State-26-01', 1, ' B-02', 1000, 500, 'savin@9986', '15/04/2025 19:27:42'),
(37, '9th CBSE 2026 Coaching', '9-CBSE-26', 'TTT-9-CBSE-26-001-', 1, ' B-01', 0, 0, 'rakesh@1', '16/04/2025 19:58:52'),
(38, '9th State Coaching', '9-State-26', 'TTT-9-State-26-001-', 1, ' B-01', 0, 0, 'Chaitanyaps9955', '29/05/2025 20:37:30'),
(39, 'I PU Sci 25-26', 'I PU-25-26 Sci', 'TTT-I PU-26-001-', 1, ' B-01', 0, 0, 'Chaitanyaps9955', '09/06/2025 20:13:08'),
(40, 'DCET-27 Coaching Batch', 'DCET-27', 'TTT-DCET-{GROUP}-27-001', 1, ' B-01', 500, 250, 'savin@9986', '16/06/2025 13:30:04'),
(41, 'II PU Commerce Coaching', 'II-PU Comm 26.', 'TTT-Comm-A-26-01', 1, ' B-02', 2000, 1000, 'savin@9986', '16/06/2025 21:20:33');

-- --------------------------------------------------------

--
-- Table structure for table `student_details`
--

CREATE TABLE `student_details` (
  `id` int(32) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `course` varchar(255) NOT NULL,
  `subjects` varchar(255) NOT NULL,
  `batch` varchar(255) NOT NULL,
  `whatsapp` int(8) NOT NULL,
  `branch` varchar(255) NOT NULL,
  `status` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_details`
--

INSERT INTO `student_details` (`id`, `student_id`, `student_name`, `course`, `subjects`, `batch`, `whatsapp`, `branch`, `status`) VALUES
(625, 'TTT-10th-ICSE-24-25-100 Kishan S', 'Kishan S', '10- ICSE', 'Physics, Chemistry, Mathematics, Biology, History, Civics & Geography, Language - I, Language - II, Computer Science', '10th ICSE Evening Batch', 0, ' B-01', 1),
(627, 'TTT-10th-ICSE-24-25-101 Kailash', 'Kailash', '10- ICSE', 'Physics, Chemistry, Mathematics, History, Civics & Geography, Biology, Language - I, Language - II, Computer Science', '10th ICSE Evening Batch', 0, ' B-01', 1),
(628, 'TTT-10th-ICSE-24-25-102 Hiba Syed', 'Hiba Syed', '10- ICSE', 'Chemistry, Mathematics, Biology, History, Civics & Geography, Language - I, Language - II, ', '10th ICSE Evening Batch', 0, ' B-01', 1),
(633, 'TTT-9th-ICSE-24-25-103 Rohith C', 'Rohith C', '9-ICSE', 'Physics, Chemistry, Mathematics, Biology, History and Civics, Geography, English, Hindi, Kannada', '9th ICSE Evening Batch', 0, ' B-01', 1),
(635, 'TTT-9th-ICSE-24-25-104 Manjunath', 'Manjunath', '9-ICSE', 'Physics, Mathematics, Biology, History and Civics, Geography, English, Hindi, Kannada', '9th ICSE Evening Batch', 0, ' B-01', 1),
(637, 'TTT-9th-ICSE-24-25-105 Aryan Kumar ', 'Aryan Kumar ', '9-ICSE', 'Physics, Chemistry, Mathematics, History and Civics, Geography, English, Hindi, Kannada', '9th ICSE Evening Batch', 0, ' B-01', 1),
(641, 'TTT-10th-ICSE-24-25-106 Tanushree Guggal', 'Tanushree Guggal', '10- ICSE', 'Chemistry, Physics, Mathematics, Biology, History, Civics & Geography, Language - I, Language - II, Computer Science', '10th ICSE Evening Batch', 0, ' B-01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `zoom`
--

CREATE TABLE `zoom` (
  `id` int(32) UNSIGNED NOT NULL,
  `student_id` varchar(100) DEFAULT NULL,
  `meeting_id` varchar(20) DEFAULT NULL,
  `branch` varchar(50) DEFAULT NULL,
  `course` varchar(70) DEFAULT NULL,
  `batch` varchar(100) DEFAULT NULL,
  `link` varchar(250) DEFAULT NULL,
  `updated_on` varchar(22) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zoom`
--

INSERT INTO `zoom` (`id`, `student_id`, `meeting_id`, `branch`, `course`, `batch`, `link`, `updated_on`, `updated_by`) VALUES
(4, 'TTT-DCET-A-26-001 Sandeep Kumbar2', '89777404685', ' B-01', 'DCET-26', 'DCET-26 Long Term Batch', 'https://us06web.zoom.us/w/89777404685?tk=I5LurHoTFNe7HgNco7Ruc75CNTLuAWuvLrKGcLAgtpg.DQgAAAAU5yZ7DRZ6TVJ3eWxMRlQ2dWd5MzZmR0UxSnRnAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA&pwd=qfDDP47dQc0aC8nTjdRYbhn0nz9rly.1', '29/06/2025 22:15:22', 'NeedToUpdate'),
(5, 'TTT-DCET-A-26-005 Sandeep Kumbar5', '89777404685', ' B-01', 'DCET-26', 'DCET-26 Long Term Batch', 'https://us06web.zoom.us/w/89777404685?tk=duq86UmJ-0-7zc7KTlDqw-GdsNrP9AtMq9clNJh_y50.DQgAAAAU5yZ7DRZqMUloazE0S1FZYW5xbjk2VFlBOGJnAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA&pwd=qfDDP47dQc0aC8nTjdRYbhn0nz9rly.1', '29/06/2025 22:16:15', 'NeedToUpdate'),
(10, 'TTT-10th-ICSE-24-25-101 Kailash', '89777404685', ' B-01', '10- ICSE', '10th ICSE Evening Batch', 'https://us06web.zoom.us/w/89777404685?tk=sxcrPk49eZvFd0J-dDvDsX-LtQ8LV1wQ_Pb8g4_gEM4.DQgAAAAU5yZ7DRZ2UEMydUY3SFJCS09zeXF1MG4wdFB3AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA&pwd=qfDDP47dQc0aC8nTjdRYbhn0nz9rly.1', '30/06/2025 15:06:26', 'NeedToUpdate'),
(20, 'TTT-10th-ICSE-24-25-106 Tanushree Guggal', '83373509537', ' B-01', '10- ICSE', '10th ICSE Evening Batch', 'https://us06web.zoom.us/w/83373509537?tk=m1h9dDoy685QL1QxRmIji9wcvOJ2tbbUs4dzw3gP6Ro.DQgAAAATaXLLoRZRZlp1RGRsd1RmS2RieFZyRHhQbnhRAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA&pwd=wUdtTWbH2LFHmT1iqnmWDwdt28heeP.1', '30/06/2025 19:57:27', 'NeedToUpdate'),
(21, 'TTT-10th-ICSE-24-25-102 Hiba Syed', '83373509537', ' B-01', '10- ICSE', '10th ICSE Evening Batch', 'https://us06web.zoom.us/w/83373509537?tk=_7MWezs8ZtaO43Y_GlfG-KUR_S-3qfjKf_Q-wqkaSLs.DQgAAAATaXLLoRZ5RFRuRDR0TVRYVzlXcWpEYUtfVFV3AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA&pwd=wUdtTWbH2LFHmT1iqnmWDwdt28heeP.1', '30/06/2025 19:57:28', 'NeedToUpdate'),
(22, 'TTT-10th-ICSE-24-25-101 Kailash', '83373509537', ' B-01', '10- ICSE', '10th ICSE Evening Batch', 'https://us06web.zoom.us/w/83373509537?tk=C2lj8CRW5YSOfVSZ0b9T-NfcscqJns503uQ7rAAJhI0.DQgAAAATaXLLoRZXV09NaDdid1J5U3dOc3hRSGVQOW9BAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA&pwd=wUdtTWbH2LFHmT1iqnmWDwdt28heeP.1', '30/06/2025 19:57:29', 'NeedToUpdate'),
(23, 'TTT-10th-ICSE-24-25-100 Kishan S', '83373509537', ' B-01', '10- ICSE', '10th ICSE Evening Batch', 'https://us06web.zoom.us/w/83373509537?tk=xF-n4Qn0PGb8vul4_kqnal10N7y6tA2lbjDM3J9W1eQ.DQgAAAATaXLLoRZoY2RvRy00UVNneVRfMEhVXzJhRFBBAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA&pwd=wUdtTWbH2LFHmT1iqnmWDwdt28heeP.1', '30/06/2025 19:57:30', 'NeedToUpdate');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `batchs`
--
ALTER TABLE `batchs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `branch_details`
--
ALTER TABLE `branch_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_details`
--
ALTER TABLE `student_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `zoom`
--
ALTER TABLE `zoom`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `batchs`
--
ALTER TABLE `batchs`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `branch_details`
--
ALTER TABLE `branch_details`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `student_details`
--
ALTER TABLE `student_details`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=678;

--
-- AUTO_INCREMENT for table `zoom`
--
ALTER TABLE `zoom`
  MODIFY `id` int(32) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
