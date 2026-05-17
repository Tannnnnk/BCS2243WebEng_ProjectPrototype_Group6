-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 17, 2026 at 10:35 AM
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
-- Database: `webeproject`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrator`
--

CREATE TABLE `administrator` (
  `userID` varchar(100) NOT NULL,
  `admin_name` varchar(100) DEFAULT NULL,
  `admin_department` varchar(100) DEFAULT NULL,
  `admin_position` varchar(50) DEFAULT NULL,
  `admin_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `administrator`
--

INSERT INTO `administrator` (`userID`, `admin_name`, `admin_department`, `admin_position`, `admin_photo`) VALUES
('U201', 'Dr. Chua Teck Kunt', 'Human Resources', 'HR Manager', 'uploads/U201_admin.png'),
('U301', 'Dr. Peter', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendanceID` varchar(100) NOT NULL,
  `attendance_date` date DEFAULT NULL,
  `attendance_time` time DEFAULT NULL,
  `attendance_status` varchar(20) DEFAULT NULL,
  `eventID` varchar(100) DEFAULT NULL,
  `userID` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendanceID`, `attendance_date`, `attendance_time`, `attendance_status`, `eventID`, `userID`) VALUES
('A101', '2026-05-17', '14:34:04', 'Volunteer', 'E101', 'U101');

-- --------------------------------------------------------

--
-- Table structure for table `club`
--

CREATE TABLE `club` (
  `clubID` varchar(100) NOT NULL,
  `club_name` varchar(100) DEFAULT NULL,
  `club_desc` varchar(100) DEFAULT NULL,
  `userID` varchar(100) DEFAULT NULL,
  `club_operational_status` varchar(100) DEFAULT NULL,
  `club_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club`
--

INSERT INTO `club` (`clubID`, `club_name`, `club_desc`, `userID`, `club_operational_status`, `club_photo`) VALUES
('C101', 'Badminton Club', 'The club that gather badminton lovers and professions.', 'U201', 'Active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `committee`
--

CREATE TABLE `committee` (
  `committeeID` varchar(100) NOT NULL,
  `eventID` varchar(100) DEFAULT NULL,
  `memberID` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `committee`
--

INSERT INTO `committee` (`committeeID`, `eventID`, `memberID`) VALUES
('CM101', 'E101', 'M101');

-- --------------------------------------------------------

--
-- Table structure for table `eventregistration`
--

CREATE TABLE `eventregistration` (
  `userID` varchar(100) NOT NULL,
  `eventID` varchar(100) NOT NULL,
  `registration_date` date DEFAULT NULL,
  `registration_status` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eventregistration`
--

INSERT INTO `eventregistration` (`userID`, `eventID`, `registration_date`, `registration_status`) VALUES
('U101', 'E101', '2026-05-12', 'Confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `eventID` varchar(100) NOT NULL,
  `event_title` varchar(100) DEFAULT NULL,
  `event_desc` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `event_venue` varchar(100) DEFAULT NULL,
  `event_max_participants` int(11) DEFAULT NULL,
  `attendance_qr` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`eventID`, `event_title`, `event_desc`, `event_date`, `event_time`, `event_venue`, `event_max_participants`, `attendance_qr`) VALUES
('E101', 'Badminton Championship Cup 25/26', 'Badminton Competition', '2026-09-27', '07:00:00', 'Dewan Serbaguna, UMPSA Pekan', 64, NULL),
('E102', 'Chess Competition', 'hi', '2026-05-23', '18:00:00', 'Library', 64, 'qrcodes/E102_qr.png'),
('E103', 'hihih', 'hihih', '2026-05-17', '14:27:00', 'Library', 77, 'qrcodes/E103_qr.png');

-- --------------------------------------------------------

--
-- Table structure for table `membership`
--

CREATE TABLE `membership` (
  `memberID` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `userID` varchar(100) DEFAULT NULL,
  `clubID` varchar(100) DEFAULT NULL,
  `roleID` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership`
--

INSERT INTO `membership` (`memberID`, `start_date`, `end_date`, `userID`, `clubID`, `roleID`) VALUES
('M101', '2026-05-12', '2027-05-12', 'U101', 'C101', 'R01');

-- --------------------------------------------------------

--
-- Table structure for table `membershiprole`
--

CREATE TABLE `membershiprole` (
  `roleID` varchar(100) NOT NULL,
  `m_role_desc` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membershiprole`
--

INSERT INTO `membershiprole` (`roleID`, `m_role_desc`) VALUES
('R01', 'President'),
('R02', 'Vice President'),
('R03', 'Secretary'),
('R04', 'Treasurer'),
('R05', 'Stor Manager'),
('R06', 'Logistics'),
('R07', 'Ordinary Committee Member'),
('R08', 'Club Members');

-- --------------------------------------------------------

--
-- Table structure for table `points`
--

CREATE TABLE `points` (
  `pointID` varchar(100) NOT NULL,
  `total_points` int(11) DEFAULT NULL,
  `stu_enforce` varchar(100) DEFAULT NULL,
  `point_value` int(11) DEFAULT NULL,
  `attendanceID` varchar(100) DEFAULT NULL,
  `userID` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `userID` varchar(100) NOT NULL,
  `stu_ID` varchar(10) DEFAULT NULL,
  `stu_name` varchar(100) DEFAULT NULL,
  `stu_email` varchar(100) DEFAULT NULL,
  `stu_address` varchar(255) DEFAULT NULL,
  `stu_role` varchar(50) DEFAULT NULL,
  `stu_contact_no` varchar(15) DEFAULT NULL,
  `stu_profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`userID`, `stu_ID`, `stu_name`, `stu_email`, `stu_address`, `stu_role`, `stu_contact_no`, `stu_profile_photo`) VALUES
('U101', 'CB24096', 'Chua Teck Kunt', 'CB24096@adab.umpsa.edu.my', '11, Jalan Putera 5, Taman Putera Indah, Jalan Salleh, 84000 Muar, Johor.', 'committee', '016-7259523', 'uploads/U101_profile.jpg'),
('U102', 'CB24099', 'Tan Wei Ming', 'CB24099@adab.email.ump.my', 'C-10-22', 'committee', '012-3456789', 'uploads/U102_profile.jpeg'),
('U103', 'CB24105', 'Lee Jun Xian', 'CB24105@adab.umpsa.edu.my', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` varchar(100) NOT NULL,
  `user_username` varchar(50) DEFAULT NULL,
  `user_password` varchar(50) DEFAULT NULL,
  `user_role` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `user_username`, `user_password`, `user_role`) VALUES
('U101', 'kunkun', '123', 'Student'),
('U102', 'weiming', '123', 'Student'),
('U103', 'junxian', '123', 'Student'),
('U201', 'kunkunadmin', '123', 'Administrator'),
('U301', 'kk', '123', 'Administrator');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrator`
--
ALTER TABLE `administrator`
  ADD PRIMARY KEY (`userID`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendanceID`),
  ADD KEY `eventID` (`eventID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `club`
--
ALTER TABLE `club`
  ADD PRIMARY KEY (`clubID`),
  ADD KEY `club_advisor_fk` (`userID`);

--
-- Indexes for table `committee`
--
ALTER TABLE `committee`
  ADD PRIMARY KEY (`committeeID`),
  ADD KEY `eventID` (`eventID`),
  ADD KEY `memberID` (`memberID`);

--
-- Indexes for table `eventregistration`
--
ALTER TABLE `eventregistration`
  ADD PRIMARY KEY (`userID`,`eventID`),
  ADD KEY `eventID` (`eventID`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`eventID`);

--
-- Indexes for table `membership`
--
ALTER TABLE `membership`
  ADD PRIMARY KEY (`memberID`),
  ADD KEY `userID` (`userID`),
  ADD KEY `clubID` (`clubID`),
  ADD KEY `roleID` (`roleID`);

--
-- Indexes for table `membershiprole`
--
ALTER TABLE `membershiprole`
  ADD PRIMARY KEY (`roleID`);

--
-- Indexes for table `points`
--
ALTER TABLE `points`
  ADD PRIMARY KEY (`pointID`),
  ADD KEY `attendanceID` (`attendanceID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`userID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `administrator`
--
ALTER TABLE `administrator`
  ADD CONSTRAINT `administrator_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`eventID`) REFERENCES `events` (`eventID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `students` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `club`
--
ALTER TABLE `club`
  ADD CONSTRAINT `club_advisor_fk` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `committee`
--
ALTER TABLE `committee`
  ADD CONSTRAINT `committee_ibfk_1` FOREIGN KEY (`eventID`) REFERENCES `events` (`eventID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `committee_ibfk_2` FOREIGN KEY (`memberID`) REFERENCES `membership` (`memberID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `eventregistration`
--
ALTER TABLE `eventregistration`
  ADD CONSTRAINT `eventregistration_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `students` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `eventregistration_ibfk_2` FOREIGN KEY (`eventID`) REFERENCES `events` (`eventID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `membership`
--
ALTER TABLE `membership`
  ADD CONSTRAINT `membership_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `students` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `membership_ibfk_2` FOREIGN KEY (`clubID`) REFERENCES `club` (`clubID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `membership_ibfk_3` FOREIGN KEY (`roleID`) REFERENCES `membershiprole` (`roleID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `points`
--
ALTER TABLE `points`
  ADD CONSTRAINT `points_ibfk_1` FOREIGN KEY (`attendanceID`) REFERENCES `attendance` (`attendanceID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `points_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `students` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
