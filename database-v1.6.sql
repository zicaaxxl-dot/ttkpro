-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 01-Ago-2026 às 15:33
-- Versão do servidor: 10.11.10-MariaDB-log
-- versão do PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ttkpro`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `access_logs`
--

CREATE TABLE `access_logs` (
  `id` int(11) NOT NULL,
  `subscriber_id` int(11) DEFAULT NULL,
  `username_attempt` varchar(60) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `device_id` varchar(64) DEFAULT NULL,
  `action` enum('login_success','login_failed','blocked_user','device_limit_exceeded','device_blocked','subscription_expired') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `access_logs`
--

INSERT INTO `access_logs` (`id`, `subscriber_id`, `username_attempt`, `ip_address`, `device_id`, `action`, `created_at`) VALUES
(1, 1, 'admin', '172.69.138.230', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-10 19:55:33'),
(2, 1, 'admin', '172.71.239.42', 'd4e1ec1db620f1affa89b60395d079ba', 'login_failed', '2026-07-10 20:08:09'),
(3, 1, 'admin', '172.71.239.42', 'd4e1ec1db620f1affa89b60395d079ba', 'login_failed', '2026-07-10 20:08:22'),
(4, 1, 'admin', '172.71.239.42', 'd4e1ec1db620f1affa89b60395d079ba', 'login_failed', '2026-07-10 20:08:42'),
(5, 1, 'admin', '172.71.239.42', 'd4e1ec1db620f1affa89b60395d079ba', 'login_failed', '2026-07-10 20:08:50'),
(6, 1, 'admin', '172.71.239.42', 'd4e1ec1db620f1affa89b60395d079ba', 'login_failed', '2026-07-10 20:09:10'),
(7, 1, 'admin', '172.71.239.42', '327a01d09e117c5f94dd2262074fcd27', 'login_failed', '2026-07-10 20:09:15'),
(8, 1, 'admin', '172.71.239.42', 'd4e1ec1db620f1affa89b60395d079ba', 'blocked_user', '2026-07-10 20:09:32'),
(9, 1, 'admin', '172.69.114.162', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-10 20:18:20'),
(10, 1, 'admin', '104.22.10.55', '5dbd8e2fea9a0e193288f1e0298573cb', 'login_success', '2026-07-10 21:01:08'),
(11, 1, 'admin', '104.23.254.56', '327a01d09e117c5f94dd2262074fcd27', 'login_failed', '2026-07-10 23:46:18'),
(12, 1, 'admin', '172.71.10.123', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-11 01:50:54'),
(13, 1, 'admin', '172.71.239.42', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-11 02:05:43'),
(14, 1, 'admin', '172.71.239.42', 'eb3096dca84eb53706cb711700d07e05', 'device_limit_exceeded', '2026-07-11 02:08:53'),
(15, 1, 'admin', '104.22.10.55', '5dbd8e2fea9a0e193288f1e0298573cb', 'login_failed', '2026-07-13 06:07:31'),
(16, 1, 'admin', '104.22.10.55', '5dbd8e2fea9a0e193288f1e0298573cb', 'login_success', '2026-07-13 06:07:40'),
(17, 1, 'admin', '172.69.138.230', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-13 06:08:33'),
(18, 1, 'admin', '172.71.239.24', '3ecaa8709e6b81d9e9951a0d184fe76c', 'device_limit_exceeded', '2026-07-13 06:11:53'),
(19, 1, 'admin', '104.23.254.57', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-13 06:35:33'),
(20, 1, 'admin', '104.23.254.57', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-13 06:35:55'),
(21, 1, 'admin', '172.69.39.73', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-13 06:37:49'),
(22, 1, 'admin', '104.22.10.88', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-13 18:01:28'),
(26, 1, 'admin', '172.71.2.174', '325cd24005b4a5e1e9cb2e887e0f62c1', 'login_failed', '2026-07-14 07:37:49'),
(27, 1, 'admin', '172.71.2.174', 'f523a202b8e47d3b5437d13898913e1c', 'login_failed', '2026-07-14 07:37:55'),
(28, 1, 'admin', '172.71.2.174', '51b28c4e9391a8046713a1611d6179ff', 'login_failed', '2026-07-14 08:52:22'),
(29, 1, 'admin', '172.71.2.174', 'f78726075602c273a302b7bc316ab94d', 'login_failed', '2026-07-14 08:53:31'),
(30, 1, 'admin', '172.69.138.230', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-15 00:36:07'),
(31, NULL, 'rodrigo', '172.68.18.243', 'bb21bc412142ca0124c7e0e3f380f7f1', 'login_failed', '2026-07-16 14:27:43'),
(32, NULL, 'rodrigo', '172.68.19.177', 'bb21bc412142ca0124c7e0e3f380f7f1', 'login_failed', '2026-07-16 14:27:54'),
(33, NULL, 'rodrigo', '172.71.239.41', 'bb21bc412142ca0124c7e0e3f380f7f1', 'login_failed', '2026-07-17 19:24:03'),
(34, NULL, 'rodrigo', '104.23.254.56', 'bb21bc412142ca0124c7e0e3f380f7f1', 'login_failed', '2026-07-17 19:34:16'),
(35, NULL, 'rodrigo', '172.69.114.161', 'bb21bc412142ca0124c7e0e3f380f7f1', 'login_failed', '2026-07-18 03:26:35'),
(36, NULL, 'rodrigo', '172.71.2.174', 'bb21bc412142ca0124c7e0e3f380f7f1', 'login_failed', '2026-07-18 19:27:08'),
(37, NULL, 'rodrigo', '172.71.2.175', 'bb21bc412142ca0124c7e0e3f380f7f1', 'login_failed', '2026-07-18 21:09:33'),
(38, NULL, 'rodrigo', '172.71.2.175', '327a01d09e117c5f94dd2262074fcd27', 'login_failed', '2026-07-19 19:09:56'),
(39, NULL, 'rodrigo', '172.71.2.175', '327a01d09e117c5f94dd2262074fcd27', 'login_failed', '2026-07-19 19:14:27'),
(40, 1, 'admin', '172.69.114.162', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-23 21:01:41'),
(41, 1, 'admin', '104.23.254.57', 'd4e1ec1db620f1affa89b60395d079ba', 'login_success', '2026-07-23 21:14:22'),
(42, 1, 'admin', '127.0.0.1', 'b57d8f5919c72b455dbeee9826ffdc40', 'login_success', '2026-08-01 05:44:36'),
(43, 1, 'admin', '127.0.0.1', 'b57d8f5919c72b455dbeee9826ffdc40', 'login_success', '2026-08-01 07:19:57'),
(44, 1, 'admin', '127.0.0.1', 'b57d8f5919c72b455dbeee9826ffdc40', 'login_success', '2026-08-01 07:28:48');

-- --------------------------------------------------------

--
-- Estrutura da tabela `gateway_settings`
--

CREATE TABLE `gateway_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `provider` varchar(30) NOT NULL DEFAULT 'nexypay',
  `public_key` varchar(255) NOT NULL DEFAULT '',
  `secret_key` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `gateway_settings`
--

INSERT INTO `gateway_settings` (`id`, `provider`, `public_key`, `secret_key`, `updated_at`) VALUES
(1, 'nexypay', '', '123', '2026-08-01 05:48:52');

-- --------------------------------------------------------

--
-- Estrutura da tabela `order_bumps`
--

CREATE TABLE `order_bumps` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT '',
  `price` decimal(10,2) DEFAULT 0.00,
  `price_original` decimal(10,2) DEFAULT 0.00,
  `description` varchar(500) DEFAULT '',
  `image` varchar(600) DEFAULT '',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `payment_id` varchar(100) NOT NULL,
  `project_id` int(10) UNSIGNED DEFAULT NULL,
  `gateway` varchar(30) NOT NULL,
  `status` enum('pending','paid','cancelled','expired') NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `customer_name` varchar(150) DEFAULT '',
  `customer_email` varchar(150) DEFAULT '',
  `customer_cpf` varchar(20) DEFAULT '',
  `raw_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_payload`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `projects`
--

CREATE TABLE `projects` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `badge` varchar(120) DEFAULT '',
  `title` varchar(255) DEFAULT '',
  `price` decimal(10,2) DEFAULT 0.00,
  `price_original` decimal(10,2) DEFAULT 0.00,
  `discount_text` varchar(255) DEFAULT '',
  `description` text DEFAULT NULL,
  `own_checkout` tinyint(1) NOT NULL DEFAULT 1,
  `external_link` varchar(500) DEFAULT '',
  `shop_name` varchar(150) DEFAULT '',
  `shop_avatar` varchar(500) DEFAULT '',
  `pixel_id` varchar(60) DEFAULT '',
  `tiktok_pixel` varchar(60) DEFAULT '',
  `gtm_id` varchar(60) DEFAULT '',
  `social_proof` tinyint(1) NOT NULL DEFAULT 0,
  `exit_intent` tinyint(1) NOT NULL DEFAULT 0,
  `recommendations_discount` decimal(5,2) DEFAULT 40.00,
  `footer_razao` varchar(255) DEFAULT '',
  `footer_cnpj` varchar(30) DEFAULT '',
  `footer_email` varchar(150) DEFAULT '',
  `footer_zap` varchar(30) DEFAULT '',
  `footer_pol` varchar(500) DEFAULT '',
  `footer_term` varchar(500) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `project_images`
--

CREATE TABLE `project_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `url` varchar(600) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `recommendations`
--

CREATE TABLE `recommendations` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT '',
  `price` decimal(10,2) DEFAULT 0.00,
  `image` varchar(600) DEFAULT '',
  `checkout_url` varchar(600) DEFAULT '',
  `variation_name` varchar(120) DEFAULT 'Modelo',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `recommendation_variations`
--

CREATE TABLE `recommendation_variations` (
  `id` int(10) UNSIGNED NOT NULL,
  `recommendation_id` int(10) UNSIGNED NOT NULL,
  `value` varchar(150) DEFAULT '',
  `price` decimal(10,2) DEFAULT 0.00,
  `image` varchar(600) DEFAULT '',
  `checkout_url` varchar(600) DEFAULT '',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) DEFAULT '',
  `stars` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `text` text DEFAULT NULL,
  `image` varchar(600) DEFAULT '',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `max_devices` tinyint(3) UNSIGNED DEFAULT 2,
  `subscription_expires_at` datetime DEFAULT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `blocked_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `subscribers`
--

INSERT INTO `subscribers` (`id`, `name`, `username`, `password_hash`, `max_devices`, `subscription_expires_at`, `status`, `blocked_reason`, `created_at`, `updated_at`) VALUES
(1, 'Renan', 'admin', '$2y$10$Tz1/0mLPy2lIENN.Y.3QA.klb9stjL.Cx9A.d3lzwxdJCx0wvA7uy', 30, '2026-08-31 02:24:00', 'active', NULL, '2026-07-10 19:47:39', '2026-08-01 05:24:21');

-- --------------------------------------------------------

--
-- Estrutura da tabela `subscriber_devices`
--

CREATE TABLE `subscriber_devices` (
  `id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `device_id` varchar(64) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `first_seen` timestamp NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_blocked` tinyint(1) DEFAULT 0,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `geo_updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `subscriber_devices`
--

INSERT INTO `subscriber_devices` (`id`, `subscriber_id`, `device_id`, `ip_address`, `user_agent`, `first_seen`, `last_seen`, `is_blocked`, `latitude`, `longitude`, `city`, `country`, `geo_updated_at`) VALUES
(8, 1, 'd4e1ec1db620f1affa89b60395d079ba', '104.23.254.57', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-13 18:01:28', '2026-07-23 21:14:22', 0, '-23.550500', '-46.633300', 'São Paulo', 'Brazil', NULL),
(9, 1, 'b57d8f5919c72b455dbeee9826ffdc40', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-08-01 05:44:36', '2026-08-01 07:28:48', 0, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `superadmins`
--

CREATE TABLE `superadmins` (
  `id` int(11) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `superadmins`
--

INSERT INTO `superadmins` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'superadmin', '$2b$10$X1RfaaniANMQVzutYgkcmO1kPEgYf61qZTVFBq/rlTV5r83icQ4F2', '2026-07-10 19:30:58');

-- --------------------------------------------------------

--
-- Estrutura da tabela `variation_groups`
--

CREATE TABLE `variation_groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) DEFAULT '',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `variation_options`
--

CREATE TABLE `variation_options` (
  `id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `value` varchar(150) DEFAULT '',
  `price` decimal(10,2) DEFAULT 0.00,
  `image` varchar(600) DEFAULT '',
  `checkout_url` varchar(600) DEFAULT '',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `access_logs`
--
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscriber_id` (`subscriber_id`);

--
-- Índices para tabela `gateway_settings`
--
ALTER TABLE `gateway_settings`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `subscriber_devices`
--
ALTER TABLE `subscriber_devices`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `access_logs`
--
ALTER TABLE `access_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de tabela `gateway_settings`
--
ALTER TABLE `gateway_settings`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `subscriber_devices`
--
ALTER TABLE `subscriber_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
