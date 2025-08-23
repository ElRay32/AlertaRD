-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-08-2025 a las 05:18:44
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `alertard`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `barrios`
--

CREATE TABLE `barrios` (
  `id` int(11) NOT NULL,
  `municipality_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `barrios`
--

INSERT INTO `barrios` (`id`, `municipality_id`, `name`) VALUES
(10, 1, 'Centro Ciudad'),
(1, 1, 'Naco'),
(6, 2, 'Alma Rosa'),
(7, 2, 'Ensanche Ozama'),
(3, 2, 'Los Mina'),
(8, 3, 'Villa Mella'),
(9, 4, 'Herrera'),
(5, 5, 'Gazcue'),
(4, 5, 'Piantini'),
(11, 7, 'Centro'),
(13, 9, 'Malecón');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `occurrence_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `validated_at` datetime DEFAULT NULL,
  `province_id` int(11) DEFAULT NULL,
  `municipality_id` int(11) DEFAULT NULL,
  `barrio_id` int(11) DEFAULT NULL,
  `latitude` decimal(9,6) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  `deaths` int(11) DEFAULT NULL,
  `injuries` int(11) DEFAULT NULL,
  `loss_estimate_rd` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','published','rejected','merged','applied') NOT NULL DEFAULT 'pending',
  `reporter_user_id` int(11) DEFAULT NULL,
  `validated_by` int(11) DEFAULT NULL,
  `merged_into_id` int(11) DEFAULT NULL,
  `rejection_note` text DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `incidents`
--

INSERT INTO `incidents` (`id`, `title`, `description`, `occurrence_at`, `created_at`, `validated_at`, `province_id`, `municipality_id`, `barrio_id`, `latitude`, `longitude`, `deaths`, `injuries`, `loss_estimate_rd`, `status`, `reporter_user_id`, `validated_by`, `merged_into_id`, `rejection_note`, `deleted_at`, `deleted_by`) VALUES
(1, 'Prueba1', 'hola', '2025-08-22 03:04:00', '2025-08-22 03:05:19', NULL, 1, 5, 4, 18.472514, -69.864785, 1, 3, 2000.00, '', NULL, NULL, NULL, NULL, NULL, NULL),
(2, '123', '123', '2025-08-22 03:27:00', '2025-08-22 03:27:27', NULL, 1, 5, 4, 18.496280, -69.899820, 3, 3, 3.00, 'published', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Prueba3', '222', '2025-08-22 03:41:00', '2025-08-22 03:41:40', '2025-08-22 09:24:23', 1, 5, 4, 18.433756, -69.938283, 2, 2, 2.00, 'published', NULL, 3, NULL, NULL, NULL, NULL),
(5, 'Prueba 5', 'hola', '2025-08-22 12:57:00', '2025-08-22 12:57:49', NULL, 1, 5, 4, 18.470622, -69.861948, 3, 3, 3.00, 'merged', NULL, NULL, 2, NULL, NULL, NULL),
(7, 'Prueba89', 'holaaaaaa', '2025-08-22 22:57:00', '2025-08-22 22:58:09', NULL, 1, 5, 5, 18.469116, -69.927285, 2, 2, 2.00, 'published', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Prueba99', '323', '2025-08-21 22:58:00', '2025-08-22 22:59:14', '2025-08-22 23:09:28', 2, 2, 6, 18.505136, -69.857743, 2, 2, 2.00, 'published', NULL, 3, NULL, NULL, NULL, NULL),
(9, 'Choque en Santo domingo este', 'Hubo un choque', '2025-08-22 23:04:00', '2025-08-22 23:06:16', '2025-08-22 23:08:46', 2, 2, 6, 18.494978, -69.843561, 3, 5, 100000.00, 'published', NULL, 3, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incident_comments`
--

CREATE TABLE `incident_comments` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('visible','hidden') NOT NULL DEFAULT 'visible',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incident_corrections`
--

CREATE TABLE `incident_corrections` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `new_deaths` int(11) DEFAULT NULL,
  `new_injuries` int(11) DEFAULT NULL,
  `new_loss_estimate_rd` decimal(12,2) DEFAULT NULL,
  `new_latitude` decimal(9,6) DEFAULT NULL,
  `new_longitude` decimal(9,6) DEFAULT NULL,
  `new_province_id` int(11) DEFAULT NULL,
  `new_municipality_id` int(11) DEFAULT NULL,
  `new_barrio_id` int(11) DEFAULT NULL,
  `status` enum('pending','applied','rejected') NOT NULL DEFAULT 'pending',
  `validator_user_id` int(11) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incident_incident_type`
--

CREATE TABLE `incident_incident_type` (
  `incident_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `incident_incident_type`
--

INSERT INTO `incident_incident_type` (`incident_id`, `type_id`) VALUES
(2, 5),
(7, 1),
(8, 5),
(9, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incident_photos`
--

CREATE TABLE `incident_photos` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `path_or_url` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incident_social_links`
--

CREATE TABLE `incident_social_links` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `url` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `incident_social_links`
--

INSERT INTO `incident_social_links` (`id`, `incident_id`, `platform`, `url`, `created_at`) VALUES
(1, 1, 'www.youtube.com', 'https://www.youtube.com/', '2025-08-22 03:05:19'),
(2, 2, '', 'chagpt', '2025-08-22 03:27:27'),
(3, 3, '', 'ray', '2025-08-22 03:41:40'),
(5, 2, '', 'youtube', '2025-08-22 12:57:49'),
(7, 8, '', 'robo', '2025-08-22 22:59:14'),
(8, 9, 'www.youtube.com', 'https://www.youtube.com/watch?v=7A8lRq11SSE', '2025-08-22 23:06:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incident_types`
--

CREATE TABLE `incident_types` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `incident_types`
--

INSERT INTO `incident_types` (`id`, `name`) VALUES
(1, 'Accidente'),
(4, 'Desastre'),
(5, 'Incendio'),
(2, 'Pelea'),
(3, 'Robo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_tokens`
--

CREATE TABLE `login_tokens` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `code` varchar(10) NOT NULL,
  `verify_attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt_at` datetime DEFAULT NULL,
  `provider` varchar(30) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `token` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `login_tokens`
--

INSERT INTO `login_tokens` (`id`, `email`, `ip_address`, `code`, `verify_attempts`, `last_attempt_at`, `provider`, `created_at`, `expires_at`, `used`, `used_at`, `token`) VALUES
(1, 'raymelguerreroleb@gmail.com', '::1', '369707', 0, NULL, 'google', '2025-08-21 19:29:11', '2025-08-21 19:39:11', 0, NULL, NULL),
(2, 'okgracias600@gmail.com', '::1', '255512', 0, NULL, 'google', '2025-08-21 19:31:17', '2025-08-21 19:41:17', 1, NULL, NULL),
(3, 'okgracias600@gmail.com', '::1', '579876', 0, NULL, 'google', '2025-08-21 19:38:11', '2025-08-21 19:48:11', 1, NULL, NULL),
(4, 'okgracias600@gmail.com', '::1', '537653', 0, NULL, 'google', '2025-08-21 19:41:06', '2025-08-21 19:51:06', 1, NULL, NULL),
(5, 'okgracias600@gmail.com', '::1', '154771', 0, NULL, 'google', '2025-08-21 19:43:20', '2025-08-21 19:53:20', 1, NULL, NULL),
(6, 'okgracias600@gmail.com', '::1', '980717', 0, NULL, 'google', '2025-08-21 19:48:01', '2025-08-21 19:58:01', 1, NULL, NULL),
(7, 'guerrero20191@outlook.es', '::1', '871754', 0, NULL, 'email', '2025-08-21 19:48:21', '2025-08-21 19:58:21', 1, NULL, NULL),
(8, 'okgracias600@gmail.com', '::1', '728160', 0, NULL, 'google', '2025-08-21 20:04:47', '2025-08-21 20:14:47', 1, '2025-08-21 20:04:55', NULL),
(9, 'okgracias600@gmail.com', '::1', '136363', 0, NULL, 'google', '2025-08-21 20:07:58', '2025-08-21 20:17:58', 1, '2025-08-21 20:08:08', NULL),
(10, 'guerrero20191@outlook.es', '::1', '345601', 0, NULL, 'email', '2025-08-21 20:09:34', '2025-08-21 20:19:34', 1, '2025-08-21 20:09:46', NULL),
(11, 'guerrero20191@outlook.es', '::1', '803379', 0, NULL, 'email', '2025-08-21 20:11:36', '2025-08-21 20:21:36', 1, '2025-08-21 20:11:51', NULL),
(12, 'guerrero20191@outlook.es', '::1', '857823', 0, NULL, 'email', '2025-08-21 20:24:42', '2025-08-21 20:34:42', 1, NULL, NULL),
(13, 'guerrero20191@outlook.es', '::1', '976687', 0, NULL, 'email', '2025-08-21 20:24:47', '2025-08-21 20:34:47', 1, '2025-08-21 20:25:11', NULL),
(14, 'guerrero20191@outlook.es', '::1', '994150', 0, NULL, 'email', '2025-08-21 20:28:17', '2025-08-21 20:38:17', 1, '2025-08-21 20:28:59', NULL),
(15, 'okgracias600@gmail.com', '::1', '164833', 0, NULL, 'google', '2025-08-21 21:39:30', '2025-08-21 21:49:30', 1, '2025-08-21 21:40:03', NULL),
(16, 'guerrero20191@outlook.es', '::1', '499857', 2, '2025-08-21 21:40:37', 'email', '2025-08-21 21:40:16', '2025-08-21 21:50:16', 1, '2025-08-21 21:40:52', NULL),
(17, 'guerrero20191@outlook.es', '::1', '546012', 0, NULL, 'email', '2025-08-21 22:10:50', '2025-08-21 22:20:50', 1, '2025-08-21 22:11:06', NULL),
(18, 'guerrero20191@outlook.es', '::1', '097313', 0, NULL, 'email', '2025-08-21 22:44:06', '2025-08-21 22:54:06', 1, '2025-08-21 22:44:25', NULL),
(19, 'guerrero20191@outlook.es', '::1', '974535', 0, NULL, 'email', '2025-08-21 22:55:46', '2025-08-21 23:05:46', 1, '2025-08-21 22:56:21', NULL),
(20, 'guerrero20191@outlook.es', '::1', '134181', 0, NULL, 'email', '2025-08-21 23:28:04', '2025-08-21 23:38:04', 1, '2025-08-21 23:28:19', NULL),
(21, 'okgracias600@gmail.com', '::1', '529949', 0, NULL, 'google', '2025-08-22 12:56:03', '2025-08-22 13:06:03', 1, '2025-08-22 12:56:59', NULL),
(22, 'okgracias600@gmail.com', '::1', '425894', 0, NULL, 'google', '2025-08-22 21:52:30', '2025-08-22 22:02:30', 1, NULL, NULL),
(23, 'okgracias600@gmail.com', '::1', '702359', 0, NULL, 'google', '2025-08-22 21:58:31', '2025-08-22 22:08:31', 1, NULL, NULL),
(24, 'guerrero20191@outlook.es', '::1', '153586', 0, NULL, 'email', '2025-08-22 22:02:49', '2025-08-22 22:12:49', 1, '2025-08-22 22:03:32', NULL),
(25, 'guerrero20191@outlook.es', '::1', '256804', 0, NULL, 'email', '2025-08-22 22:59:37', '2025-08-22 23:09:37', 1, NULL, NULL),
(26, 'okgracias600@gmail.com', '::1', '601546', 0, NULL, 'google', '2025-08-22 23:03:27', '2025-08-22 23:13:27', 1, '2025-08-22 23:03:43', NULL),
(27, 'guerrero20191@outlook.es', '::1', '206972', 0, NULL, 'email', '2025-08-22 23:06:34', '2025-08-22 23:16:34', 1, '2025-08-22 23:06:49', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `municipalities`
--

CREATE TABLE `municipalities` (
  `id` int(11) NOT NULL,
  `province_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `municipalities`
--

INSERT INTO `municipalities` (`id`, `province_id`, `name`) VALUES
(5, 1, 'Santo Domingo de Guzmán'),
(2, 2, 'Santo Domingo Este'),
(3, 2, 'Santo Domingo Norte'),
(4, 2, 'Santo Domingo Oeste'),
(1, 3, 'Santiago de los Caballeros'),
(7, 4, 'La Romana'),
(8, 5, 'La Vega'),
(9, 8, 'Puerto Plata');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provinces`
--

CREATE TABLE `provinces` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `provinces`
--

INSERT INTO `provinces` (`id`, `name`) VALUES
(1, 'Distrito Nacional'),
(4, 'La Romana'),
(5, 'La Vega'),
(8, 'Puerto Plata'),
(3, 'Santiago'),
(2, 'Santo Domingo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` tinyint(4) NOT NULL,
  `name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(3, 'admin'),
(1, 'reporter'),
(2, 'validator');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` tinyint(4) NOT NULL DEFAULT 1,
  `provider` varchar(30) DEFAULT NULL,
  `provider_sub` varchar(255) DEFAULT NULL,
  `picture_url` varchar(500) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `role_id`, `provider`, `provider_sub`, `picture_url`, `last_login_at`, `password_hash`, `is_active`) VALUES
(1, 'Admin', 'admin@example.com', 3, NULL, NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
(2, 'Okgracias600', 'okgracias600@gmail.com', 1, 'google', NULL, NULL, NULL, NULL, 1),
(3, 'Guerrero20191', 'guerrero20191@outlook.es', 3, 'google', NULL, NULL, NULL, NULL, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `barrios`
--
ALTER TABLE `barrios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_barrio` (`municipality_id`,`name`),
  ADD UNIQUE KEY `ux_barrios_name` (`name`),
  ADD KEY `idx_barrio_muni` (`municipality_id`);

--
-- Indices de la tabla `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inc_status` (`status`),
  ADD KEY `idx_inc_occurrence` (`occurrence_at`),
  ADD KEY `idx_inc_prov` (`province_id`),
  ADD KEY `fk_inc_muni` (`municipality_id`),
  ADD KEY `fk_inc_barrio` (`barrio_id`),
  ADD KEY `fk_inc_reporter` (`reporter_user_id`),
  ADD KEY `fk_inc_validator` (`validated_by`),
  ADD KEY `fk_inc_merged` (`merged_into_id`);
ALTER TABLE `incidents` ADD FULLTEXT KEY `ft_title_desc` (`title`,`description`);

--
-- Indices de la tabla `incident_comments`
--
ALTER TABLE `incident_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cmt_user` (`user_id`),
  ADD KEY `idx_cmt_inc` (`incident_id`),
  ADD KEY `idx_cmt_status` (`status`);

--
-- Indices de la tabla `incident_corrections`
--
ALTER TABLE `incident_corrections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cor_inc` (`incident_id`),
  ADD KEY `fk_cor_user` (`user_id`);

--
-- Indices de la tabla `incident_incident_type`
--
ALTER TABLE `incident_incident_type`
  ADD PRIMARY KEY (`incident_id`,`type_id`),
  ADD KEY `fk_iit_type` (`type_id`);

--
-- Indices de la tabla `incident_photos`
--
ALTER TABLE `incident_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ph_inc` (`incident_id`);

--
-- Indices de la tabla `incident_social_links`
--
ALTER TABLE `incident_social_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sl_inc` (`incident_id`);

--
-- Indices de la tabla `incident_types`
--
ALTER TABLE `incident_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `ux_incident_types_name` (`name`);

--
-- Indices de la tabla `login_tokens`
--
ALTER TABLE `login_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_login_tokens_token` (`token`),
  ADD KEY `idx_email_used` (`email`,`used`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_tokens_ip_time` (`ip_address`,`created_at`);

--
-- Indices de la tabla `municipalities`
--
ALTER TABLE `municipalities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_muni` (`province_id`,`name`),
  ADD UNIQUE KEY `ux_munis_name` (`name`),
  ADD KEY `idx_muni_prov` (`province_id`);

--
-- Indices de la tabla `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `ux_provinces_name` (`name`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_roles` (`role_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `barrios`
--
ALTER TABLE `barrios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `incident_comments`
--
ALTER TABLE `incident_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `incident_corrections`
--
ALTER TABLE `incident_corrections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `incident_photos`
--
ALTER TABLE `incident_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `incident_social_links`
--
ALTER TABLE `incident_social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `incident_types`
--
ALTER TABLE `incident_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `login_tokens`
--
ALTER TABLE `login_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `municipalities`
--
ALTER TABLE `municipalities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `provinces`
--
ALTER TABLE `provinces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `barrios`
--
ALTER TABLE `barrios`
  ADD CONSTRAINT `fk_barrio_muni` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `fk_inc_barrio` FOREIGN KEY (`barrio_id`) REFERENCES `barrios` (`id`),
  ADD CONSTRAINT `fk_inc_merged` FOREIGN KEY (`merged_into_id`) REFERENCES `incidents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inc_muni` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`),
  ADD CONSTRAINT `fk_inc_prov` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`),
  ADD CONSTRAINT `fk_inc_reporter` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_inc_validator` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `incident_comments`
--
ALTER TABLE `incident_comments`
  ADD CONSTRAINT `fk_cmt_inc` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cmt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `incident_corrections`
--
ALTER TABLE `incident_corrections`
  ADD CONSTRAINT `fk_cor_inc` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `incident_incident_type`
--
ALTER TABLE `incident_incident_type`
  ADD CONSTRAINT `fk_iit_inc` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_iit_type` FOREIGN KEY (`type_id`) REFERENCES `incident_types` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `incident_photos`
--
ALTER TABLE `incident_photos`
  ADD CONSTRAINT `fk_ph_inc` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `incident_social_links`
--
ALTER TABLE `incident_social_links`
  ADD CONSTRAINT `fk_sl_inc` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `municipalities`
--
ALTER TABLE `municipalities`
  ADD CONSTRAINT `fk_muni_prov` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
