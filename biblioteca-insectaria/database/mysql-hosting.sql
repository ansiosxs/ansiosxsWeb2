-- Biblioteca Insectaria / Laravel 12
-- Import this once into a NEW, EMPTY MySQL/MariaDB database using phpMyAdmin.
-- It mirrors the checked-in Laravel migrations and marks them as already run.
-- It intentionally contains no users, passwords, or other application data.

SET NAMES utf8mb4;

CREATE TABLE `migrations` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `migration` varchar(255) NOT NULL,
    `batch` int NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `email_verified_at` timestamp NULL DEFAULT NULL,
    `password` varchar(255) NOT NULL,
    `remember_token` varchar(100) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
    `email` varchar(255) NOT NULL,
    `token` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
    `id` varchar(255) NOT NULL,
    `user_id` bigint unsigned DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `payload` longtext NOT NULL,
    `last_activity` int NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
    `key` varchar(255) NOT NULL,
    `value` mediumtext NOT NULL,
    `expiration` int NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
    `key` varchar(255) NOT NULL,
    `owner` varchar(255) NOT NULL,
    `expiration` int NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `queue` varchar(255) NOT NULL,
    `payload` longtext NOT NULL,
    `attempts` tinyint unsigned NOT NULL,
    `reserved_at` int unsigned DEFAULT NULL,
    `available_at` int unsigned NOT NULL,
    `created_at` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
    `id` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `total_jobs` int NOT NULL,
    `pending_jobs` int NOT NULL,
    `failed_jobs` int NOT NULL,
    `failed_job_ids` longtext NOT NULL,
    `options` mediumtext DEFAULT NULL,
    `cancelled_at` int DEFAULT NULL,
    `created_at` int NOT NULL,
    `finished_at` int DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `uuid` varchar(255) NOT NULL,
    `connection` text NOT NULL,
    `queue` text NOT NULL,
    `payload` longtext NOT NULL,
    `exception` longtext NOT NULL,
    `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `muebles` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `nombre` varchar(255) NOT NULL,
    `descripcion` text DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `muebles_nombre_unique` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `libros` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `titulo` varchar(255) NOT NULL,
    `autor` varchar(255) NOT NULL,
    `isbn` varchar(20) DEFAULT NULL,
    `editorial` varchar(255) DEFAULT NULL,
    `anio` smallint unsigned DEFAULT NULL,
    `portada_url` varchar(255) DEFAULT NULL,
    `mueble_id` bigint unsigned DEFAULT NULL,
    `cantidad_total` int unsigned NOT NULL DEFAULT 0,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `libros_mueble_id_foreign` (`mueble_id`),
    FULLTEXT KEY `libros_titulo_autor_fulltext` (`titulo`, `autor`),
    CONSTRAINT `libros_mueble_id_foreign`
        FOREIGN KEY (`mueble_id`) REFERENCES `muebles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `socios` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned DEFAULT NULL,
    `run` varchar(12) NOT NULL,
    `nombres` varchar(255) NOT NULL,
    `apellidos` varchar(255) NOT NULL,
    `telefono` varchar(20) NOT NULL,
    `email` varchar(255) DEFAULT NULL,
    `comuna` varchar(255) NOT NULL,
    `ocupacion` varchar(255) DEFAULT NULL,
    `fecha_inscripcion` date NOT NULL,
    `vigente_hasta` date NOT NULL,
    `activo` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `socios_run_unique` (`run`),
    KEY `socios_apellidos_nombres_index` (`apellidos`, `nombres`),
    KEY `socios_user_id_foreign` (`user_id`),
    CONSTRAINT `socios_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prestamos` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `socio_id` bigint unsigned NOT NULL,
    `libro_id` bigint unsigned DEFAULT NULL,
    `cantidad` int unsigned NOT NULL DEFAULT 1,
    `cantidad_devuelta` int unsigned NOT NULL DEFAULT 0,
    `user_id` bigint unsigned NOT NULL,
    `fecha_prestamo` datetime NOT NULL,
    `fecha_vencimiento` date NOT NULL,
    `fecha_devolucion` datetime DEFAULT NULL,
    `devuelto_por_user_id` bigint unsigned DEFAULT NULL,
    `estado` enum('activo','devuelto') NOT NULL DEFAULT 'activo',
    `operacion_uuid` char(36) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `prestamos_operacion_uuid_unique` (`operacion_uuid`),
    KEY `prestamos_estado_fecha_vencimiento_index` (`estado`, `fecha_vencimiento`),
    KEY `prestamos_socio_id_estado_index` (`socio_id`, `estado`),
    KEY `prestamos_libro_id_foreign` (`libro_id`),
    KEY `prestamos_user_id_foreign` (`user_id`),
    KEY `prestamos_devuelto_por_user_id_foreign` (`devuelto_por_user_id`),
    CONSTRAINT `prestamos_socio_id_foreign`
        FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `prestamos_libro_id_foreign`
        FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `prestamos_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `prestamos_devuelto_por_user_id_foreign`
        FOREIGN KEY (`devuelto_por_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `anuncios` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `titulo` varchar(255) NOT NULL,
    `cuerpo` text NOT NULL,
    `publicar_desde` date NOT NULL,
    `publicar_hasta` date DEFAULT NULL,
    `user_id` bigint unsigned NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `anuncios_publicar_desde_publicar_hasta_index` (`publicar_desde`, `publicar_hasta`),
    KEY `anuncios_user_id_foreign` (`user_id`),
    CONSTRAINT `anuncios_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recordatorios` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `prestamo_id` bigint unsigned NOT NULL,
    `tipo` enum('vencimiento') NOT NULL,
    `enviado_at` datetime NOT NULL,
    `canal` enum('email') NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `recordatorios_prestamo_id_tipo_unique` (`prestamo_id`, `tipo`),
    CONSTRAINT `recordatorios_prestamo_id_foreign`
        FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('0001_01_01_000000_create_users_table', 1),
    ('0001_01_01_000001_create_cache_table', 1),
    ('0001_01_01_000002_create_jobs_table', 1),
    ('2026_10_16_000001_create_muebles_table', 1),
    ('2026_10_16_000002_create_libros_table', 1),
    ('2026_10_16_000003_create_ejemplares_table', 1),
    ('2026_10_16_000004_create_socios_table', 1),
    ('2026_10_16_000005_create_prestamos_table', 1),
    ('2026_10_16_000006_create_anuncios_table', 1),
    ('2026_10_16_000007_create_recordatorios_table', 1),
    ('2026_10_18_000001_add_registrar_to_socios_table', 2),
    ('2026_10_18_000002_replace_individual_copies_with_inventory_quantities', 3);
