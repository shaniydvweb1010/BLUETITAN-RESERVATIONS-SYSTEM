-- phpMyAdmin SQL Dump
-- version 5.2.1
-- Database: `boat_booking_system`
-- Generation Time: Mar 02, 2026

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Create the Database if it doesn't exist
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `boat_booking_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `boat_booking_system`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `users`
-- The password hash below evaluates to: password
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@boatbooking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '1234567890', 'admin', current_timestamp());

-- --------------------------------------------------------
-- Table structure for table `boats`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `boats`;
CREATE TABLE `boats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('Speedboat','Yacht','Fishing Boat','Pontoon','Ship','Sailboat','Catamaran','Other') NOT NULL,
  `capacity` int(11) NOT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default_boat.jpg',
  `available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `boats`
-- --------------------------------------------------------
INSERT INTO `boats` (`id`, `name`, `type`, `capacity`, `price_per_hour`, `description`, `image`, `available`, `created_at`) VALUES
(1, 'Aqua Bullet', 'Speedboat', 4, 2000.00, 'High-speed thrill ride perfect for couples or small groups looking for adventure.', 'boat1.jpg', 1, current_timestamp()),
(2, 'Royal Voyager', 'Yacht', 20, 25000.00, 'Luxury yacht with premium amenities, perfect for corporate events and private parties.', 'boat2.jpg', 1, current_timestamp()),
(3, 'Deep Sea Hunter', 'Fishing Boat', 5, 2500.00, 'Fully equipped fishing vessel with sonar and live wells.', 'boat3.jpg', 1, current_timestamp()),
(4, 'Sunset Relaxer', 'Pontoon', 8, 1800.00, 'Stable and spacious pontoon, ideal for calm waters and family picnics.', 'boat4.jpg', 1, current_timestamp()),
(5, 'Ocean Whisper', 'Sailboat', 6, 3000.00, 'Experience the authentic sailing life with this beautiful wind-powered vessel.', 'boat5.jpg', 1, current_timestamp());

-- --------------------------------------------------------
-- Table structure for table `bookings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `boat_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('Pending','Approved','Confirmed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `boat_id` (`boat_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`boat_id`) REFERENCES `boats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;