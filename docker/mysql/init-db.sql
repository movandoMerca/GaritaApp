-- Create database if not exists
CREATE DATABASE IF NOT EXISTS garitaapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE garitaapp;

-- Grant privileges to the user
GRANT ALL PRIVILEGES ON garitaapp.* TO 'garita_user'@'%';
FLUSH PRIVILEGES;

-- Create tables based on the existing structure
-- Table structure for table `config`
CREATE TABLE IF NOT EXISTS `config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `path_brand` varchar(200) DEFAULT NULL,
  `path_logo` varchar(200) DEFAULT NULL,
  `enable_fotolicencia` tinyint(1) DEFAULT '0',
  `enable_fotovisitante` tinyint(1) DEFAULT '0',
  `enable_webcam` tinyint(1) DEFAULT '0',
  `enable_accesotel` tinyint(1) DEFAULT '0',
  `enable_tel` tinyint(1) DEFAULT '0',
  `enable_egreso` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `path_config` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `failed_jobs`
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `logs`
CREATE TABLE IF NOT EXISTS `logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `accion` varchar(200) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `migrations`
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `password_resets`
CREATE TABLE IF NOT EXISTS `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `residentes`
CREATE TABLE IF NOT EXISTS `residentes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Codigo` varchar(20) NOT NULL,
  `Nombres` varchar(50) NOT NULL,
  `Nombres2` varchar(100) DEFAULT NULL,
  `Apellidos` varchar(50) NOT NULL,
  `Apellidos2` varchar(45) DEFAULT NULL,
  `accesotel` varchar(12) DEFAULT NULL,
  `Direccion` varchar(100) DEFAULT NULL,
  `Telefono` varchar(8) DEFAULT NULL,
  `estado` tinyint DEFAULT '1',
  `tipoResidente` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Codigo_UNIQUE` (`Codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` int DEFAULT '1',
  `is_admin` tinyint DEFAULT '0',
  `is_superuser` tinyint DEFAULT '0',
  `tipoResidente` tinyint DEFAULT '1',
  `camara_id_licencia` varchar(50) DEFAULT NULL,
  `camara_id_visitante` varchar(50) DEFAULT NULL,
  `camara_id_placa` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `validation`
CREATE TABLE IF NOT EXISTS `validation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `token` varchar(100) DEFAULT NULL,
  `pass` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `visitas`
CREATE TABLE IF NOT EXISTS `visitas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `residente_id` int NOT NULL,
  `numeroDocumento` varchar(8) DEFAULT NULL,
  `cui` varchar(13) DEFAULT NULL,
  `Placa` varchar(8) NOT NULL,
  `Primer_Nombre` varchar(30) NOT NULL,
  `Segundo_Nombre` varchar(70) DEFAULT NULL,
  `Primer_Apellido` varchar(45) DEFAULT NULL,
  `Segundo_Apellido` varchar(70) DEFAULT NULL,
  `Fecha_nac` varchar(20) DEFAULT NULL,
  `Fecha_vencimiento` varchar(20) DEFAULT NULL,
  `cono` varchar(20) DEFAULT '0',
  `tipoLicencia` varchar(2) DEFAULT NULL,
  `fechaingreso` datetime NOT NULL,
  `fechaegreso` datetime DEFAULT NULL,
  `tel_emergencia` varchar(8) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `path_licencia` varchar(150) DEFAULT NULL,
  `path_visitante` varchar(150) DEFAULT NULL,
  `path_placa` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_visitas_residente` (`residente_id`),
  CONSTRAINT `fk_visitas_residente` FOREIGN KEY (`residente_id`) REFERENCES `residentes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default configuration
INSERT INTO `config` (`id`, `path_brand`, `path_logo`, `enable_fotolicencia`, `enable_fotovisitante`, `enable_webcam`, `enable_accesotel`, `enable_tel`, `enable_egreso`, `created_at`, `updated_at`) 
VALUES (1, 'mardysa.png', 'mardysa.png', 1, 1, 1, 1, 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `status`, `is_admin`, `is_superuser`, `tipoResidente`, `created_at`, `updated_at`)
VALUES (1, 'admin', 'Administrador', 'admin@garitaapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Insert sample validation tokens
INSERT INTO `validation` (`token`, `pass`) VALUES 
('demo_token_1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('demo_token_2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE `pass` = VALUES(`pass`);