-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1:3307
-- 生成日期： 2026-05-10 15:09:42
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `webeproject`
--

-- --------------------------------------------------------

--
-- 表的结构 `administrator`
--

CREATE TABLE `administrator` (
  `userID` varchar(100) NOT NULL,
  `admin_name` varchar(100) DEFAULT NULL,
  `admin_department` varchar(100) DEFAULT NULL,
  `admin_position` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `attendance`
--

CREATE TABLE `attendance` (
  `attendanceID` varchar(100) NOT NULL,
  `attendance_QR` varchar(255) DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `attendance_time` time DEFAULT NULL,
  `attendance_status` varchar(20) DEFAULT NULL,
  `eventID` varchar(100) DEFAULT NULL,
  `userID` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `club`
--

CREATE TABLE `club` (
  `clubID` varchar(100) NOT NULL,
  `club_name` varchar(100) DEFAULT NULL,
  `club_desc` varchar(100) DEFAULT NULL,
  `club_advisor_name` varchar(100) DEFAULT NULL,
  `club_operational_status` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `committee`
--

CREATE TABLE `committee` (
  `committeeID` varchar(100) NOT NULL,
  `eventID` varchar(100) DEFAULT NULL,
  `memberID` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `eventregistration`
--

CREATE TABLE `eventregistration` (
  `userID` varchar(100) NOT NULL,
  `eventID` varchar(100) NOT NULL,
  `registration_date` date DEFAULT NULL,
  `registration_status` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `events`
--

CREATE TABLE `events` (
  `eventID` varchar(100) NOT NULL,
  `event_title` varchar(100) DEFAULT NULL,
  `event_desc` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `event_venue` varchar(100) DEFAULT NULL,
  `event_max_participants` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `membership`
--

CREATE TABLE `membership` (
  `memberID` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `userID` varchar(100) DEFAULT NULL,
  `clubID` varchar(100) DEFAULT NULL,
  `roleID` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `membershiprole`
--

CREATE TABLE `membershiprole` (
  `roleID` varchar(100) NOT NULL,
  `m_role_desc` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `points`
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
-- 表的结构 `students`
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

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `userID` varchar(100) NOT NULL,
  `user_username` varchar(50) DEFAULT NULL,
  `user_password` varchar(50) DEFAULT NULL,
  `user_role` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`userID`, `user_username`, `user_password`, `user_role`) VALUES
('U101', 'kunkun', '1234', 'Student');

--
-- 转储表的索引
--

--
-- 表的索引 `administrator`
--
ALTER TABLE `administrator`
  ADD PRIMARY KEY (`userID`);

--
-- 表的索引 `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendanceID`),
  ADD KEY `eventID` (`eventID`),
  ADD KEY `userID` (`userID`);

--
-- 表的索引 `club`
--
ALTER TABLE `club`
  ADD PRIMARY KEY (`clubID`);

--
-- 表的索引 `committee`
--
ALTER TABLE `committee`
  ADD PRIMARY KEY (`committeeID`),
  ADD KEY `eventID` (`eventID`),
  ADD KEY `memberID` (`memberID`);

--
-- 表的索引 `eventregistration`
--
ALTER TABLE `eventregistration`
  ADD PRIMARY KEY (`userID`,`eventID`),
  ADD KEY `eventID` (`eventID`);

--
-- 表的索引 `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`eventID`);

--
-- 表的索引 `membership`
--
ALTER TABLE `membership`
  ADD PRIMARY KEY (`memberID`),
  ADD KEY `userID` (`userID`),
  ADD KEY `clubID` (`clubID`),
  ADD KEY `roleID` (`roleID`);

--
-- 表的索引 `membershiprole`
--
ALTER TABLE `membershiprole`
  ADD PRIMARY KEY (`roleID`);

--
-- 表的索引 `points`
--
ALTER TABLE `points`
  ADD PRIMARY KEY (`pointID`),
  ADD KEY `attendanceID` (`attendanceID`),
  ADD KEY `userID` (`userID`);

--
-- 表的索引 `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`userID`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`);

--
-- 限制导出的表
--

--
-- 限制表 `administrator`
--
ALTER TABLE `administrator`
  ADD CONSTRAINT `administrator_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`eventID`) REFERENCES `events` (`eventID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `students` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `committee`
--
ALTER TABLE `committee`
  ADD CONSTRAINT `committee_ibfk_1` FOREIGN KEY (`eventID`) REFERENCES `events` (`eventID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `committee_ibfk_2` FOREIGN KEY (`memberID`) REFERENCES `membership` (`memberID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `eventregistration`
--
ALTER TABLE `eventregistration`
  ADD CONSTRAINT `eventregistration_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `students` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `eventregistration_ibfk_2` FOREIGN KEY (`eventID`) REFERENCES `events` (`eventID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `membership`
--
ALTER TABLE `membership`
  ADD CONSTRAINT `membership_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `students` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `membership_ibfk_2` FOREIGN KEY (`clubID`) REFERENCES `club` (`clubID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `membership_ibfk_3` FOREIGN KEY (`roleID`) REFERENCES `membershiprole` (`roleID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `points`
--
ALTER TABLE `points`
  ADD CONSTRAINT `points_ibfk_1` FOREIGN KEY (`attendanceID`) REFERENCES `attendance` (`attendanceID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `points_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `students` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
