-- ============================================================================
-- TTT ZOOM ATTENDANCE MANAGEMENT SYSTEM - COMPLETE DATABASE SCHEMA
-- ============================================================================
-- This file contains all tables, data, and configurations for the TTT Zoom system
-- including multi-account support, student management, and attendance tracking
-- Version: 2.0 - Complete with sample data for testing
-- Date: July 26, 2025
-- ============================================================================

-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS `ttt_zoom_system`;
CREATE DATABASE `ttt_zoom_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ttt_zoom_system`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================================
-- 1. MULTI-ACCOUNT ZOOM API CREDENTIALS
-- ============================================================================
-- Table to store multiple Zoom API credentials for different accounts
CREATE TABLE `zoom_api_credentials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(255) NOT NULL,
  `client_id` varchar(255) NOT NULL,
  `client_secret` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT 'Default Zoom API Credentials',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Zoom API credentials for multiple accounts
INSERT INTO `zoom_api_credentials` (`id`, `account_id`, `client_id`, `client_secret`, `name`, `is_active`) VALUES
(1, '89NOV9jAT-SH7wJmjvsptg', '4y5ckqpJQ1WvJAmk3x6PvQ', '8eH7szslJoGeBbyRULvEm6Bx7eE630jB', 'TTT Main Account', 1),
(2, 'B5HCnfN0QPG4HciSJ7lCqA', 'yCFoaev8QGeSX0YcgCIUWw', '1zPa32KLZm7s3IyJ15JG6GyyiSz4B1S5', 'Laggere TTT Branch', 1),
(3, 'TEST-ACCOUNT-ID-001', 'TEST-CLIENT-ID-001', 'TEST-CLIENT-SECRET-001', 'Test Account 1', 1);

-- ============================================================================
-- 2. BRANCH MANAGEMENT
-- ============================================================================
-- Table to store branch details
CREATE TABLE `branch_details` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `branch_code` varchar(255) NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `branch_address` varchar(255) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_branch_code` (`branch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert branch data
INSERT INTO `branch_details` (`id`, `branch_code`, `branch_name`, `branch_address`, `remarks`) VALUES
(1, ' B-01', 'Main Branch 01 (Laggere)', '#8, MEI Colony, Laggere', 'Savin'),
(2, ' B-02', 'Main Branch 02 (Hesaraghatta Main Road)', 'Near Bhagalgunte Arch, Hesaraghatta Main Road', '9986869966'),
(3, ' B-03', 'Main Branch 03 (Magadi Main Road)', '#11, Maruthi Complex, Doddagollarahatti, Magadi Main Road, Bangalore-91', 'Magadi Main Road'),
(4, ' B-04', 'Online Branch', 'Virtual Campus - Online Classes', 'Online Only');

-- ============================================================================
-- 3. COURSE MANAGEMENT
-- ============================================================================
-- Table to store course details
CREATE TABLE `courses` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `course_name` varchar(255) NOT NULL,
  `course_code` varchar(255) NOT NULL,
  `id_pattern` varchar(255) NOT NULL,
  `course_status` int(8) NOT NULL DEFAULT 1,
  `branch` varchar(255) NOT NULL,
  `refer_price` int(32) NOT NULL DEFAULT 0,
  `out_side` int(32) NOT NULL DEFAULT 0,
  `uploaded_by` varchar(255) NOT NULL DEFAULT 'admin',
  `upload_time` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_course_code` (`course_code`),
  KEY `idx_course_status` (`course_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert comprehensive course data
INSERT INTO `courses` (`id`, `course_name`, `course_code`, `id_pattern`, `course_status`, `branch`, `refer_price`, `out_side`, `uploaded_by`, `upload_time`) VALUES
(1, 'DCET-25 Coaching Batch', 'DCET-25', 'TTT-DCET-{GROUP}-25-', 1, ' B-01', 250, 500, 'admin', NOW()),
(2, '9th State Coaching', '9- State', 'TTT-9th-State-24-25-', 1, ' B-01', 150, 300, 'admin', NOW()),
(3, '10th State Coaching', '10- State', 'TTT-10th-State-24-25-', 1, ' B-01', 200, 400, 'admin', NOW()),
(4, '10th CBSE Coaching', '10- CBSE', 'TTT-10th-CBSE-24-25-', 1, ' B-01', 200, 400, 'admin', NOW()),
(5, '10th ICSE Coaching', '10- ICSE', 'TTT-10th-ICSE-24-25-', 1, ' B-01', 200, 400, 'admin', NOW()),
(6, 'I PU Science Coaching', 'I-PU', 'TTT-I-PU-Sci-24-25-', 1, ' B-01', 300, 600, 'admin', NOW()),
(7, 'II PU Science Coaching', 'II-PU', 'TTT-II-PU-Sci-24-25-', 1, ' B-01', 350, 700, 'admin', NOW()),
(8, 'KCET-25 Coaching', 'KCET-25', 'TTT-KCET-25-', 1, ' B-01', 400, 800, 'admin', NOW()),
(9, 'JEE Main Coaching', 'JEE-MAIN', 'TTT-JEE-MAIN-25-', 1, ' B-01', 500, 1000, 'admin', NOW()),
(10, 'NEET Coaching', 'NEET-25', 'TTT-NEET-25-', 1, ' B-01', 500, 1000, 'admin', NOW());

-- ============================================================================
-- 4. BATCH MANAGEMENT
-- ============================================================================
-- Table to store batch details
CREATE TABLE `batchs` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(255) NOT NULL,
  `batch_name` varchar(255) NOT NULL,
  `branch` varchar(255) NOT NULL,
  `uploaded_by` varchar(255) NOT NULL DEFAULT 'admin',
  `upload_time` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_course_code` (`course_code`),
  KEY `idx_branch` (`branch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert comprehensive batch data
INSERT INTO `batchs` (`id`, `course_code`, `batch_name`, `branch`, `uploaded_by`, `upload_time`) VALUES
(1, 'DCET-25', 'DCET-25 Long Term Offline Batch', ' B-01', 'admin', NOW()),
(2, 'DCET-25', 'DCET-25 Long Term Online Batch', ' B-04', 'admin', NOW()),
(3, 'DCET-25', 'DCET-25 Weekend Batch', ' B-02', 'admin', NOW()),
(4, '9- State', '9th State Evening Batch', ' B-01', 'admin', NOW()),
(5, '9- State', '9th State Morning Batch', ' B-02', 'admin', NOW()),
(6, '10- State', '10th State Evening Batch', ' B-01', 'admin', NOW()),
(7, '10- State', '10th State Morning Batch', ' B-02', 'admin', NOW()),
(8, '10- CBSE', '10th CBSE Evening Batch', ' B-01', 'admin', NOW()),
(9, '10- CBSE', '10th CBSE Weekend Batch', ' B-03', 'admin', NOW()),
(10, '10- ICSE', '10th ICSE Evening Batch', ' B-01', 'admin', NOW()),
(11, '10- ICSE', '10th ICSE Online Batch', ' B-04', 'admin', NOW()),
(12, 'I-PU', 'I-PUC Sci (PCM) Evening Batch', ' B-01', 'admin', NOW()),
(13, 'I-PU', 'I-PUC Sci (PCMB) Morning Batch', ' B-02', 'admin', NOW()),
(14, 'II-PU', 'II-PUC Sci (PCMB) Evening Batch', ' B-01', 'admin', NOW()),
(15, 'II-PU', 'II-PUC Sci (PCM) Weekend Batch', ' B-03', 'admin', NOW()),
(16, 'KCET-25', 'KCET-25 Long Term Batch', ' B-01', 'admin', NOW()),
(17, 'KCET-25', 'KCET-25 Crash Course', ' B-02', 'admin', NOW()),
(18, 'JEE-MAIN', 'JEE Main 2-Year Program', ' B-01', 'admin', NOW()),
(19, 'JEE-MAIN', 'JEE Main 1-Year Crash Course', ' B-02', 'admin', NOW()),
(20, 'NEET-25', 'NEET 2-Year Program', ' B-01', 'admin', NOW()),
(21, 'NEET-25', 'NEET Dropper Batch', ' B-03', 'admin', NOW());

-- ============================================================================
-- 5. STUDENT MANAGEMENT
-- ============================================================================
-- Table to store student details
CREATE TABLE `student_details` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(255) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `course` varchar(255) NOT NULL,
  `subjects` varchar(255) NOT NULL,
  `batch` varchar(255) NOT NULL,
  `whatsapp` bigint(15) NOT NULL DEFAULT 0,
  `branch` varchar(255) NOT NULL,
  `status` int(2) NOT NULL DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_id` (`student_id`),
  KEY `idx_course` (`course`),
  KEY `idx_batch` (`batch`),
  KEY `idx_branch` (`branch`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert comprehensive student data for testing
INSERT INTO `student_details` (`id`, `student_id`, `student_name`, `course`, `subjects`, `batch`, `whatsapp`, `branch`, `status`) VALUES
-- 10th ICSE Students
(1000, 'TTT-10th-ICSE-24-25-100 Rajesh Kumar', 'Rajesh Kumar', '10- ICSE', 'Physics, Chemistry, Mathematics, Biology', '10th ICSE Evening Batch', 9876543210, ' B-01', 1),
(1001, 'TTT-10th-ICSE-24-25-101 Priya Sharma', 'Priya Sharma', '10- ICSE', 'Physics, Chemistry, Mathematics, Biology', '10th ICSE Evening Batch', 9876543211, ' B-01', 1),
(1002, 'TTT-10th-ICSE-24-25-102 Amit Singh', 'Amit Singh', '10- ICSE', 'Physics, Chemistry, Mathematics, Biology', '10th ICSE Online Batch', 9876543212, ' B-04', 1),
(1003, 'TTT-10th-ICSE-24-25-103 Sneha Reddy', 'Sneha Reddy', '10- ICSE', 'Physics, Chemistry, Mathematics, Biology', '10th ICSE Evening Batch', 9876543213, ' B-01', 1),

-- DCET Students
(1004, 'TTT-DCET-A-25-001 Kiran Patel', 'Kiran Patel', 'DCET-25', 'Physics, Chemistry, Mathematics', 'DCET-25 Long Term Offline Batch', 9876543214, ' B-01', 1),
(1005, 'TTT-DCET-A-25-002 Deepika Joshi', 'Deepika Joshi', 'DCET-25', 'Physics, Chemistry, Mathematics', 'DCET-25 Long Term Online Batch', 9876543215, ' B-04', 1),
(1006, 'TTT-DCET-B-25-003 Rohit Gupta', 'Rohit Gupta', 'DCET-25', 'Physics, Chemistry, Mathematics', 'DCET-25 Weekend Batch', 9876543216, ' B-02', 1),

-- 10th State Students
(1007, 'TTT-10th-State-24-25-104 Anita Rao', 'Anita Rao', '10- State', 'Science, Mathematics, Social Studies', '10th State Evening Batch', 9876543217, ' B-01', 1),
(1008, 'TTT-10th-State-24-25-105 Vikram Nair', 'Vikram Nair', '10- State', 'Science, Mathematics, Social Studies', '10th State Morning Batch', 9876543218, ' B-02', 1),

-- I-PU Students
(1009, 'TTT-I-PU-Sci-24-25-106 Kavitha M', 'Kavitha M', 'I-PU', 'Physics, Chemistry, Mathematics, Biology', 'I-PUC Sci (PCMB) Morning Batch', 9876543219, ' B-02', 1),
(1010, 'TTT-I-PU-Sci-24-25-107 Suresh B', 'Suresh B', 'I-PU', 'Physics, Chemistry, Mathematics', 'I-PUC Sci (PCM) Evening Batch', 9876543220, ' B-01', 1),

-- II-PU Students
(1011, 'TTT-II-PU-Sci-24-25-108 Meera Krishna', 'Meera Krishna', 'II-PU', 'Physics, Chemistry, Mathematics, Biology', 'II-PUC Sci (PCMB) Evening Batch', 9876543221, ' B-01', 1),
(1012, 'TTT-II-PU-Sci-24-25-109 Arjun Hegde', 'Arjun Hegde', 'II-PU', 'Physics, Chemistry, Mathematics', 'II-PUC Sci (PCM) Weekend Batch', 9876543222, ' B-03', 1),

-- KCET Students
(1013, 'TTT-KCET-25-110 Lakshmi Devi', 'Lakshmi Devi', 'KCET-25', 'Physics, Chemistry, Mathematics', 'KCET-25 Long Term Batch', 9876543223, ' B-01', 1),
(1014, 'TTT-KCET-25-111 Naveen Kumar', 'Naveen Kumar', 'KCET-25', 'Physics, Chemistry, Mathematics', 'KCET-25 Crash Course', 9876543224, ' B-02', 1),

-- JEE Students
(1015, 'TTT-JEE-MAIN-25-112 Ravi Chandra', 'Ravi Chandra', 'JEE-MAIN', 'Physics, Chemistry, Mathematics', 'JEE Main 2-Year Program', 9876543225, ' B-01', 1),
(1016, 'TTT-JEE-MAIN-25-113 Sowmya R', 'Sowmya R', 'JEE-MAIN', 'Physics, Chemistry, Mathematics', 'JEE Main 1-Year Crash Course', 9876543226, ' B-02', 1),

-- NEET Students
(1017, 'TTT-NEET-25-114 Harish Bhat', 'Harish Bhat', 'NEET-25', 'Physics, Chemistry, Biology', 'NEET 2-Year Program', 9876543227, ' B-01', 1),
(1018, 'TTT-NEET-25-115 Pooja Shetty', 'Pooja Shetty', 'NEET-25', 'Physics, Chemistry, Biology', 'NEET Dropper Batch', 9876543228, ' B-03', 1);

-- ============================================================================
-- 6. ZOOM LINK MANAGEMENT
-- ============================================================================
-- Table to store zoom meeting links for students
CREATE TABLE `zoom` (
  `id` int(32) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` varchar(300) DEFAULT NULL,
  `meeting_id` varchar(20) DEFAULT NULL,
  `branch` varchar(50) DEFAULT NULL,
  `course` varchar(70) DEFAULT NULL,
  `batch` varchar(100) DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `updated_on` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` varchar(100) DEFAULT 'admin',
  `zoom_credentials_id` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_meeting_id` (`meeting_id`),
  KEY `idx_zoom_credentials` (`zoom_credentials_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample zoom links
INSERT INTO `zoom` (`id`, `student_id`, `meeting_id`, `branch`, `course`, `batch`, `link`, `updated_by`, `zoom_credentials_id`) VALUES
(1, 'TTT-10th-ICSE-24-25-100 Rajesh Kumar', '123456789', ' B-01', '10- ICSE', '10th ICSE Evening Batch', 'https://zoom.us/j/123456789', 'admin', 1),
(2, 'TTT-10th-ICSE-24-25-101 Priya Sharma', '123456789', ' B-01', '10- ICSE', '10th ICSE Evening Batch', 'https://zoom.us/j/123456789', 'admin', 1),
(3, 'TTT-DCET-A-25-001 Kiran Patel', '987654321', ' B-01', 'DCET-25', 'DCET-25 Long Term Offline Batch', 'https://zoom.us/j/987654321', 'admin', 2),
(4, 'TTT-DCET-A-25-002 Deepika Joshi', '111222333', ' B-04', 'DCET-25', 'DCET-25 Long Term Online Batch', 'https://zoom.us/j/111222333', 'admin', 2);

-- ============================================================================
-- 7. MEETING ATTENDANCE TRACKING
-- ============================================================================
-- Table to store the overall meeting metadata
CREATE TABLE `meeting_att_head` (
  `meeting_id` VARCHAR(50) NOT NULL,
  `meeting_date` DATE NOT NULL,
  `start_time` DATETIME DEFAULT NULL,
  `end_time` DATETIME DEFAULT NULL,
  `zoom_credentials_id` int(11) DEFAULT 1,
  `topic` varchar(255) DEFAULT NULL,
  `total_participants` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`meeting_id`, `meeting_date`),
  KEY `idx_meeting_date` (`meeting_date`),
  KEY `idx_zoom_credentials` (`zoom_credentials_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample meeting data
INSERT INTO `meeting_att_head` (`meeting_id`, `meeting_date`, `start_time`, `end_time`, `zoom_credentials_id`, `topic`, `total_participants`) VALUES
('123456789', '2025-07-25', '2025-07-25 18:00:00', '2025-07-25 19:30:00', 1, '10th ICSE Physics Class', 4),
('987654321', '2025-07-25', '2025-07-25 16:00:00', '2025-07-25 17:30:00', 2, 'DCET Mathematics Class', 2),
('111222333', '2025-07-26', '2025-07-26 10:00:00', '2025-07-26 11:30:00', 2, 'DCET Online Chemistry Class', 1);

-- Table to store individual participant attendance records
CREATE TABLE `meeting_att_details` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `meeting_id` VARCHAR(50) NOT NULL,
  `student_id` VARCHAR(300) NOT NULL,
  `meeting_date` DATE NOT NULL,
  `join_time` DATETIME DEFAULT NULL,
  `leave_time` DATETIME DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 0,
  `zoom_credentials_id` int(11) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_meeting_student` (`meeting_id`, `student_id`, `meeting_date`),
  KEY `idx_student_date` (`student_id`, `meeting_date`),
  KEY `idx_zoom_credentials` (`zoom_credentials_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample attendance data
INSERT INTO `meeting_att_details` (`meeting_id`, `student_id`, `meeting_date`, `join_time`, `leave_time`, `duration_minutes`, `zoom_credentials_id`) VALUES
-- 10th ICSE Class Attendance
('123456789', 'TTT-10th-ICSE-24-25-100 Rajesh Kumar', '2025-07-25', '2025-07-25 18:05:00', '2025-07-25 19:25:00', 80, 1),
('123456789', 'TTT-10th-ICSE-24-25-101 Priya Sharma', '2025-07-25', '2025-07-25 18:02:00', '2025-07-25 19:30:00', 88, 1),
('123456789', 'TTT-10th-ICSE-24-25-103 Sneha Reddy', '2025-07-25', '2025-07-25 18:10:00', '2025-07-25 19:20:00', 70, 1),

-- DCET Class Attendance  
('987654321', 'TTT-DCET-A-25-001 Kiran Patel', '2025-07-25', '2025-07-25 16:00:00', '2025-07-25 17:30:00', 90, 2),
('987654321', 'TTT-DCET-B-25-003 Rohit Gupta', '2025-07-25', '2025-07-25 16:15:00', '2025-07-25 17:25:00', 70, 2),

-- DCET Online Class Attendance
('111222333', 'TTT-DCET-A-25-002 Deepika Joshi', '2025-07-26', '2025-07-26 10:05:00', '2025-07-26 11:25:00', 80, 2);

-- ============================================================================
-- 8. ADMIN USER MANAGEMENT (Optional)
-- ============================================================================
-- Table to store admin users
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','super_admin','operator') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`),
  KEY `idx_username_password` (`username`, `password`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default admin user (password: 'admin123' hashed)
INSERT INTO `admin_users` (`username`, `password`, `email`, `full_name`, `role`, `is_active`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@ttt.com', 'System Administrator', 'super_admin', 1),
('operator1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator1@ttt.com', 'Branch Operator 1', 'operator', 1);

-- ============================================================================
-- 9. PERFORMANCE OPTIMIZATION INDEXES
-- ============================================================================

-- Indexes for zoom_api_credentials
CREATE INDEX idx_zoom_credentials_active ON zoom_api_credentials(is_active);
CREATE INDEX idx_zoom_account_lookup ON zoom_api_credentials(account_id, is_active);

-- Indexes for student_details
CREATE INDEX idx_student_course_batch ON student_details(course, batch, status);
CREATE INDEX idx_student_branch_status ON student_details(branch, status);

-- Indexes for zoom table
CREATE INDEX idx_zoom_meeting_creds ON zoom(meeting_id, zoom_credentials_id);
CREATE INDEX idx_zoom_student_lookup ON zoom(student_id, zoom_credentials_id);

-- Indexes for attendance tables
CREATE INDEX idx_attendance_head_date ON meeting_att_head(meeting_date, zoom_credentials_id);
CREATE INDEX idx_attendance_details_date ON meeting_att_details(meeting_date, zoom_credentials_id);

-- Composite indexes for common queries
CREATE INDEX idx_courses_branch_status ON courses(branch, course_status);
CREATE INDEX idx_batchs_course_branch ON batchs(course_code, branch);

-- ============================================================================
-- 10. FOREIGN KEY CONSTRAINTS
-- ============================================================================
-- Add foreign key constraints for data integrity

-- Foreign key for meeting_att_head
ALTER TABLE `meeting_att_head` 
ADD CONSTRAINT `fk_meeting_head_zoom_creds` 
FOREIGN KEY (`zoom_credentials_id`) REFERENCES `zoom_api_credentials`(`id`) ON DELETE SET NULL;

-- Foreign key for meeting_att_details  
ALTER TABLE `meeting_att_details` 
ADD CONSTRAINT `fk_meeting_details_zoom_creds` 
FOREIGN KEY (`zoom_credentials_id`) REFERENCES `zoom_api_credentials`(`id`) ON DELETE SET NULL;

-- Foreign key for zoom table
ALTER TABLE `zoom` 
ADD CONSTRAINT `fk_zoom_credentials` 
FOREIGN KEY (`zoom_credentials_id`) REFERENCES `zoom_api_credentials`(`id`) ON DELETE SET NULL;

-- ============================================================================
-- 11. AUTO INCREMENT SETTINGS
-- ============================================================================
ALTER TABLE `zoom_api_credentials` AUTO_INCREMENT=4;
ALTER TABLE `branch_details` AUTO_INCREMENT=5;
ALTER TABLE `courses` AUTO_INCREMENT=11;
ALTER TABLE `batchs` AUTO_INCREMENT=22;
ALTER TABLE `student_details` AUTO_INCREMENT=1019;
ALTER TABLE `zoom` AUTO_INCREMENT=5;
ALTER TABLE `meeting_att_details` AUTO_INCREMENT=7;
ALTER TABLE `admin_users` AUTO_INCREMENT=3;

-- ============================================================================
-- 12. FINAL VERIFICATION QUERIES
-- ============================================================================
-- Display summary of created tables and data

SELECT 'Database Setup Complete!' as status;

SELECT 
    'zoom_api_credentials' as table_name,
    COUNT(*) as record_count
FROM zoom_api_credentials
UNION ALL
SELECT 
    'branch_details' as table_name,
    COUNT(*) as record_count  
FROM branch_details
UNION ALL
SELECT 
    'courses' as table_name,
    COUNT(*) as record_count
FROM courses
UNION ALL
SELECT 
    'batchs' as table_name,
    COUNT(*) as record_count
FROM batchs
UNION ALL
SELECT 
    'student_details' as table_name,
    COUNT(*) as record_count
FROM student_details
UNION ALL
SELECT 
    'zoom' as table_name,
    COUNT(*) as record_count
FROM zoom
UNION ALL
SELECT 
    'meeting_att_head' as table_name,
    COUNT(*) as record_count
FROM meeting_att_head
UNION ALL
SELECT 
    'meeting_att_details' as table_name,
    COUNT(*) as record_count
FROM meeting_att_details
UNION ALL
SELECT 
    'admin_users' as table_name,
    COUNT(*) as record_count
FROM admin_users;

-- Show foreign key constraints
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    CONSTRAINT_TYPE
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = 'ttt_zoom_system' 
  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================================================
-- END OF SQL FILE
-- ============================================================================
-- This file creates a complete TTT Zoom system database with:
-- ✅ 3 Zoom API credentials (multi-account support)
-- ✅ 4 Branches (including online branch)
-- ✅ 10 Courses (from 9th grade to competitive exams)
-- ✅ 21 Batches (various timings and branches)
-- ✅ 19 Students (across different courses)
-- ✅ Sample Zoom links and meeting data
-- ✅ Attendance records for testing
-- ✅ Admin user accounts
-- ✅ All indexes and foreign keys for performance
-- ✅ MariaDB/MySQL compatible syntax
-- ============================================================================
