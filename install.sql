CREATE DATABASE IF NOT EXISTS `envantera` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `envantera`;


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `brands` (`id`, `name`, `created_at`) VALUES
(1, 'Dreame', '2025-08-20 14:19:57');


CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(1, 'Robot Süpürge', '2025-08-20 14:19:50');


CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `created_at`) VALUES
(1, 'Arda Kılıçaslan', NULL, NULL, NULL, '2025-08-20 14:45:40'),
(2, 'Fevzi Ayran', '0539 734 32 43', 'fevziayran@gmail.com', NULL, '2025-08-20 14:45:58'),
(3, 'Ali Yaman', '555', 'aliyaman@mail.com', NULL, '2025-08-20 15:41:42');

CREATE TABLE `failed_login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `models` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `barcode` varchar(255) NOT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ;

INSERT INTO `models` (`id`, `name`, `barcode`, `brand_id`, `category_id`, `purchase_price`, `sale_price`, `created_at`, `image`, `created_by`) VALUES
(1, 'X40 Ultra Robot Süpürge', '678', 1, 1, 45000.00, 54000.00, '2025-08-20 14:36:48', 'product_678.png', 1),
(2, 'L10s Ultra', '987', 1, 1, 24000.00, 26999.00, '2025-08-20 14:40:47', 'product_987.png', 1);

CREATE TABLE `platforms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `platforms` (`id`, `name`, `image_path`, `created_at`) VALUES
(1, 'Trendyol', 'platform_1755703045.png', '2025-08-20 15:17:25'),
(2, 'Hepsiburada', 'platform_1755703057.png', '2025-08-20 15:17:37'),
(3, 'n11', 'platform_1755703069.png', '2025-08-20 15:17:49');

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `sale_date` date NOT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

INSERT INTO `sales` (`id`, `serial_number`, `customer_id`, `platform_id`, `sale_date`, `sale_price`, `created_at`) VALUES
(1, '11', 1, 2, '2025-08-20', 26999.00, '2025-08-20 15:31:01'),
(2, '44', 3, 2, '2025-08-20', 54000.00, '2025-08-20 15:42:09'),
(3, '55', 3, 2, '2025-08-20', 54000.00, '2025-08-20 15:42:09'),
(4, '66', 3, 2, '2025-08-20', 54000.00, '2025-08-20 15:42:09'),
(5, '77', 3, 2, '2025-08-20', 54000.00, '2025-08-20 15:42:09');

CREATE TABLE `serial_numbers` (
  `id` int(11) NOT NULL,
  `barcode` varchar(255) NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `sold` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ;

INSERT INTO `serial_numbers` (`id`, `barcode`, `serial_number`, `sold`, `created_at`, `created_by`) VALUES
(1, '678', '66', 1, '2025-08-20 14:36:48', 1),
(2, '678', '88', 0, '2025-08-20 14:36:48', 1),
(3, '678', '99', 0, '2025-08-20 14:36:48', 1),
(4, '678', '77', 1, '2025-08-20 14:36:48', 1),
(5, '678', '55', 1, '2025-08-20 14:36:48', 1),
(6, '678', '44', 1, '2025-08-20 14:36:48', 1),
(7, '987', '11', 1, '2025-08-20 14:40:47', 1),
(8, '987', '22', 0, '2025-08-20 14:40:47', 1),
(9, '987', '33', 0, '2025-08-20 14:40:47', 1);

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$O1dxa9zMhEjQoXd0WrmoWu4PUpaOpwg/fIVH3fiXH785VuDKZwqjm', 'user', '2025-08-19 14:09:33');

ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_customers_email` (`email`),
  ADD KEY `idx_customers_email` (`email`);

ALTER TABLE `failed_login_attempts`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `models`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `idx_models_brand_id` (`brand_id`),
  ADD KEY `idx_models_category_id` (`category_id`),
  ADD KEY `idx_models_barcode` (`barcode`);

ALTER TABLE `platforms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_platforms_name` (`name`);

ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sales_serial` (`serial_number`),
  ADD KEY `idx_sales_customer_id` (`customer_id`),
  ADD KEY `idx_sales_platform_id` (`platform_id`),
  ADD KEY `idx_sales_date` (`sale_date`);

ALTER TABLE `serial_numbers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD KEY `idx_serial_numbers_barcode` (`barcode`),
  ADD KEY `idx_serial_numbers_sold` (`sold`);

ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uk_users_username` (`username`),
  ADD KEY `idx_users_username` (`username`);

ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;


ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `failed_login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `serial_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `models`
  ADD CONSTRAINT `fk_models_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_models_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_platform` FOREIGN KEY (`platform_id`) REFERENCES `platforms` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_serial` FOREIGN KEY (`serial_number`) REFERENCES `serial_numbers` (`serial_number`) ON UPDATE CASCADE;

ALTER TABLE `serial_numbers`
  ADD CONSTRAINT `fk_serial_barcode` FOREIGN KEY (`barcode`) REFERENCES `models` (`barcode`) ON DELETE CASCADE ON UPDATE CASCADE;

START TRANSACTION;