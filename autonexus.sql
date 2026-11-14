-- ========================================================
-- Auto Nexus - Relational Database Schema
-- Database: `autonexus`
-- Compatible with XAMPP, phpMyAdmin, MySQL 8.x, and MariaDB
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `autonexus` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `autonexus`;

-- --------------------------------------------------------
-- Table: `users`
-- User accounts for customers and administrators
-- --------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `accessories`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `vehicles`
-- Showroom inventory and new cars catalogue
-- --------------------------------------------------------
CREATE TABLE `vehicles` (
  `id` varchar(64) NOT NULL,
  `title` varchar(255) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `year` int(11) NOT NULL,
  `price` varchar(100) NOT NULL,
  `badge` varchar(100) DEFAULT 'FEATURED',
  `badge_class` varchar(100) DEFAULT 'b-new',
  `specs` text DEFAULT NULL,
  `image` longtext DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vehicles_brand` (`brand`),
  KEY `idx_vehicles_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `accessories`
-- Automotive accessories and performance upgrades catalogue
-- --------------------------------------------------------
CREATE TABLE `accessories` (
  `id` varchar(64) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` varchar(100) NOT NULL,
  `badge` varchar(100) DEFAULT 'UPGRADE',
  `specs` text DEFAULT NULL,
  `image` longtext DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'In Stock',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_accessories_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `orders`
-- Orders, customer inquiries, test drives, import requests,
-- car sale requests, and valuation discussions
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` varchar(64) NOT NULL,
  `user_id` varchar(64) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `type` varchar(100) NOT NULL,
  `vehicle_id` varchar(64) DEFAULT NULL,
  `accessory_id` varchar(64) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `items_json` text DEFAULT NULL,
  `total_price` varchar(100) DEFAULT NULL,
  `image` longtext DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_orders_customer_email` (`customer_email`),
  KEY `idx_orders_type` (`type`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_user_id` (`user_id`),
  KEY `idx_orders_vehicle_id` (`vehicle_id`),
  KEY `idx_orders_accessory_id` (`accessory_id`),
  CONSTRAINT `fk_orders_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_vehicles` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_accessories` FOREIGN KEY (`accessory_id`) REFERENCES `accessories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
