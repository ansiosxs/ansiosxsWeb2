-- Biblioteca Insectaria / Laravel 12
-- Import this once into a NEW, EMPTY MySQL/MariaDB database using phpMyAdmin.
-- It mirrors all checked-in Laravel migrations and marks them as already run.
-- It includes only the default public links; no users or private application data.

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
    `email` varchar(255) DEFAULT NULL,
    `email_verified_at` timestamp NULL DEFAULT NULL,
    `password` varchar(255) NOT NULL,
    `role` varchar(255) NOT NULL DEFAULT 'voluntario',
    `activo` tinyint(1) NOT NULL DEFAULT 1,
    `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
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

CREATE TABLE `user_action_logs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `actor_id` bigint unsigned NOT NULL,
    `action` varchar(255) NOT NULL,
    `resource_type` varchar(255) DEFAULT NULL,
    `resource_id` bigint unsigned DEFAULT NULL,
    `details` json DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_action_logs_user_id_foreign` (`user_id`),
    KEY `user_action_logs_actor_id_foreign` (`actor_id`),
    CONSTRAINT `user_action_logs_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `user_action_logs_actor_id_foreign`
        FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
    `autor` varchar(255) DEFAULT NULL,
    `isbn` varchar(20) DEFAULT NULL,
    `editorial` varchar(255) DEFAULT NULL,
    `anio` smallint unsigned DEFAULT NULL,
    `paginas` smallint unsigned DEFAULT NULL,
    `idioma` varchar(100) DEFAULT NULL,
    `descripcion` text DEFAULT NULL,
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

CREATE TABLE `noticias` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `titulo` varchar(255) NOT NULL,
    `resumen` varchar(500) DEFAULT NULL,
    `contenido` longtext NOT NULL,
    `imagen` varchar(255) DEFAULT NULL,
    `user_id` bigint unsigned NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `noticias_created_at_index` (`created_at`),
    KEY `noticias_user_id_foreign` (`user_id`),
    CONSTRAINT `noticias_user_id_foreign`
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

CREATE TABLE `configuracion_recordatorios` (
    `id` tinyint unsigned NOT NULL,
    `automaticos_activos` tinyint(1) NOT NULL DEFAULT 0,
    `preguntar_antes` tinyint(1) NOT NULL DEFAULT 1,
    `prestamo_asunto` varchar(255) NOT NULL,
    `prestamo_cuerpo` text NOT NULL,
    `membresia_asunto` varchar(255) NOT NULL,
    `membresia_cuerpo` text NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracion_recordatorios` (`id`, `automaticos_activos`, `preguntar_antes`, `prestamo_asunto`, `prestamo_cuerpo`, `membresia_asunto`, `membresia_cuerpo`, `created_at`, `updated_at`) VALUES
    (1, 0, 1, 'Tu préstamo está vencido - Biblioteca Insectaria', 'Hola {{nombre}},\n\nTe recordamos que el préstamo de {{libro}} venció el {{fecha_vencimiento}}. Por favor, coordina la devolución con la biblioteca.', 'Te invitamos a renovar tu membresía - Biblioteca Insectaria', 'Hola {{nombre}},\n\nTu membresía venció el {{fecha_vencimiento}}. Nos encantaría que vuelvas a ser parte de nuestra comunidad. ¡Te esperamos!', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

CREATE TABLE `configuracion_general` (
    `id` tinyint unsigned NOT NULL,
    `plazo_prestamo_dias` smallint unsigned NOT NULL,
    `max_prestamos_activos` smallint unsigned NOT NULL,
    `permitir_membresia_vencida` tinyint(1) NOT NULL DEFAULT 0,
    `dias_recordatorio` smallint unsigned NOT NULL,
    `secciones_visibles` json NOT NULL,
    `estadisticas_dashboard` json NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracion_general` (`id`, `plazo_prestamo_dias`, `max_prestamos_activos`, `permitir_membresia_vencida`, `dias_recordatorio`, `secciones_visibles`, `estadisticas_dashboard`, `created_at`, `updated_at`) VALUES
    (1, 14, 2, 0, 2, '{"admin":["dashboard","libros","socios","prestamos","morosidad","turnos","reportes","anuncios","noticias","enlaces","estanterias","recordatorios","usuarios","logs"],"editor":["dashboard","libros","socios","prestamos","morosidad","turnos","reportes","anuncios","noticias","enlaces","estanterias"],"voluntario":["dashboard","libros","socios","prestamos","morosidad","turnos","reportes","anuncios","estanterias"]}', '["socios_vigentes","unidades_vencidas","unidades_prestadas","prestamos_por_mes","libros_mas_prestados","socios_por_comuna"]', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

CREATE TABLE `recordatorios_automaticos` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tipo` varchar(30) NOT NULL,
    `referencia_id` bigint unsigned NOT NULL,
    `socio_id` bigint unsigned NOT NULL,
    `prestamo_id` bigint unsigned DEFAULT NULL,
    `fecha_evento` date NOT NULL,
    `email` varchar(255) NOT NULL,
    `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
    `autorizado_por_user_id` bigint unsigned DEFAULT NULL,
    `solicitado_at` datetime NOT NULL,
    `enviado_at` datetime DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `recordatorios_automaticos_evento_unique` (`tipo`, `referencia_id`, `fecha_evento`),
    KEY `recordatorios_automaticos_estado_solicitado_at_index` (`estado`, `solicitado_at`),
    KEY `recordatorios_automaticos_socio_id_foreign` (`socio_id`),
    KEY `recordatorios_automaticos_prestamo_id_foreign` (`prestamo_id`),
    KEY `recordatorios_automaticos_autorizado_por_user_id_foreign` (`autorizado_por_user_id`),
    CONSTRAINT `recordatorios_automaticos_socio_id_foreign`
        FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `recordatorios_automaticos_prestamo_id_foreign`
        FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `recordatorios_automaticos_autorizado_por_user_id_foreign`
        FOREIGN KEY (`autorizado_por_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `turno_schedules` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `year` smallint unsigned NOT NULL,
    `month` tinyint unsigned NOT NULL,
    `volunteers_per_day` tinyint unsigned NOT NULL DEFAULT 2,
    `availability_closes_at` datetime DEFAULT NULL,
    `status` varchar(255) NOT NULL DEFAULT 'draft',
    `created_by` bigint unsigned NOT NULL,
    `published_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `turno_schedules_year_month_unique` (`year`, `month`),
    KEY `turno_schedules_created_by_foreign` (`created_by`),
    CONSTRAINT `turno_schedules_created_by_foreign`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `turno_availabilities` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `turno_schedule_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NOT NULL,
    `weekdays` json NOT NULL,
    `date_overrides` json NOT NULL,
    `participates` tinyint(1) NOT NULL DEFAULT 0,
    `monthly_turns` tinyint unsigned NOT NULL DEFAULT 2,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `turno_availabilities_schedule_user_unique` (`turno_schedule_id`, `user_id`),
    KEY `turno_availabilities_user_id_foreign` (`user_id`),
    CONSTRAINT `turno_availabilities_turno_schedule_id_foreign`
        FOREIGN KEY (`turno_schedule_id`) REFERENCES `turno_schedules` (`id`) ON DELETE CASCADE,
    CONSTRAINT `turno_availabilities_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `turno_volunteer_profiles` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `enabled` tinyint(1) NOT NULL DEFAULT 0,
    `weekdays` json NOT NULL,
    `monthly_turns` tinyint unsigned NOT NULL DEFAULT 2,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `turno_volunteer_profiles_user_id_unique` (`user_id`),
    CONSTRAINT `turno_volunteer_profiles_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `turno_days` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `turno_schedule_id` bigint unsigned NOT NULL,
    `date` date NOT NULL,
    `is_open` tinyint(1) NOT NULL DEFAULT 1,
    `assignment_notes` text DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `turno_days_schedule_date_unique` (`turno_schedule_id`, `date`),
    KEY `turno_days_date_is_open_index` (`date`, `is_open`),
    CONSTRAINT `turno_days_turno_schedule_id_foreign`
        FOREIGN KEY (`turno_schedule_id`) REFERENCES `turno_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `turno_asignaciones` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `turno_dia_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NOT NULL,
    `assigned_by` bigint unsigned DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `turno_asignaciones_day_user_unique` (`turno_dia_id`, `user_id`),
    KEY `turno_asignaciones_user_id_foreign` (`user_id`),
    KEY `turno_asignaciones_assigned_by_foreign` (`assigned_by`),
    CONSTRAINT `turno_asignaciones_turno_dia_id_foreign`
        FOREIGN KEY (`turno_dia_id`) REFERENCES `turno_days` (`id`) ON DELETE CASCADE,
    CONSTRAINT `turno_asignaciones_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `turno_asignaciones_assigned_by_foreign`
        FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `turno_actividades` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `turno_schedule_id` bigint unsigned NOT NULL,
    `date` date NOT NULL,
    `title` varchar(160) NOT NULL,
    `responsible` varchar(255) DEFAULT NULL,
    `description` text NOT NULL,
    `convocatoria` text DEFAULT NULL,
    `created_by` bigint unsigned NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `turno_actividades_schedule_date_index` (`turno_schedule_id`, `date`),
    KEY `turno_actividades_created_by_foreign` (`created_by`),
    CONSTRAINT `turno_actividades_turno_schedule_id_foreign`
        FOREIGN KEY (`turno_schedule_id`) REFERENCES `turno_schedules` (`id`) ON DELETE CASCADE,
    CONSTRAINT `turno_actividades_created_by_foreign`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `volunteer_reports` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(160) NOT NULL,
    `category` varchar(80) NOT NULL,
    `description` text NOT NULL,
    `status` varchar(255) NOT NULL DEFAULT 'open',
    `reported_by` bigint unsigned NOT NULL,
    `resolved_by` bigint unsigned DEFAULT NULL,
    `resolved_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `volunteer_reports_status_created_at_index` (`status`, `created_at`),
    KEY `volunteer_reports_reported_by_foreign` (`reported_by`),
    KEY `volunteer_reports_resolved_by_foreign` (`resolved_by`),
    CONSTRAINT `volunteer_reports_reported_by_foreign`
        FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `volunteer_reports_resolved_by_foreign`
        FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `enlaces_publicos` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `titulo` varchar(120) NOT NULL,
    `descripcion` varchar(300) DEFAULT NULL,
    `seccion` varchar(120) DEFAULT NULL,
    `url` varchar(2048) DEFAULT NULL,
    `orden` smallint unsigned NOT NULL DEFAULT 0,
    `activo` tinyint(1) NOT NULL DEFAULT 1,
    `user_id` bigint unsigned DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `enlaces_publicos_activo_orden_index` (`activo`, `orden`),
    KEY `enlaces_publicos_user_id_foreign` (`user_id`),
    CONSTRAINT `enlaces_publicos_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `enlaces_publicos` (`titulo`, `descripcion`, `seccion`, `url`, `orden`, `activo`, `user_id`, `created_at`, `updated_at`) VALUES
    ('🌈 Intereses de la comunidad de Biblioteca Insectaria', NULL, NULL, NULL, 0, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Únete a la Comunidad Insectaria en Whatsapp', 'WhatsApp Community · Free to join', NULL, NULL, 1, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('🖋️ ¡Envíanos tu reseña de libros!', NULL, NULL, NULL, 2, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('📚 🖍️ Club de Narrativa Gráfica: Te leo, te dibujo', NULL, 'Formularios de Actividades', NULL, 3, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('🖍️ Charla: ¡No Zorro, no te lleves mi inspiración!', NULL, 'Formularios de Actividades', NULL, 4, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('🎬 Cineclub Biblioteca Insectaria: Scanners (1981)', NULL, 'Formularios de Actividades', NULL, 5, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('📚 📖 Club de lectura: Libros contra la discriminación', NULL, 'Formularios de Actividades', NULL, 6, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('📕 Instagram 📕', NULL, 'Redes Sociales', 'https://www.instagram.com/ansiosxs', 7, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('🟦 Facebook 🟦', NULL, 'Redes Sociales', 'https://www.facebook.com/Ansiosxs/', 8, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('🟥 Youtube 🟥', NULL, 'Redes Sociales', 'https://www.youtube.com/channel/UCMWvXVIqLlI72pXkdeWM62g', 9, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('🟩 Linkedin 🟩', NULL, 'Redes Sociales', 'https://www.linkedin.com/company/ong-ansiosxs-nuevas-lecturas/?viewAsMember=true', 10, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('👾 Discord 👾', NULL, 'Redes Sociales', NULL, 11, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('🖤 ☕ ¡Apoyanos en Ko-fi! ☕ 🖤', NULL, 'Redes Sociales', NULL, 12, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

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
    ('2026_10_18_000001_add_registrar_to_socios_table', 1),
    ('2026_10_18_000002_replace_individual_copies_with_inventory_quantities', 1),
    ('2026_10_20_000001_add_role_and_active_to_users_table', 1),
    ('2026_10_20_000002_create_user_action_logs_table', 1),
    ('2026_10_28_000001_create_turnos_and_reportes_tables', 1),
    ('2026_10_28_000002_add_responsible_to_turno_actividades', 1),
    ('2026_10_28_000003_add_assignment_notes_to_turno_days', 1),
    ('2026_10_29_000001_allow_null_book_authors', 1),
    ('2026_10_29_000002_normalize_missing_book_authors', 1),
    ('2026_11_02_000001_add_book_metadata_columns', 1),
    ('2026_11_02_000001_add_account_setup_to_users_table', 2),
    ('2026_11_02_000002_create_noticias_table', 3),
    ('2026_11_03_000001_add_volunteer_roster_to_turnos', 4),
    ('2026_11_03_000002_create_enlaces_publicos_table', 5),
    ('2026_11_03_000003_add_sections_and_default_public_links', 6),
    ('2026_11_03_000004_add_social_media_urls_to_public_links', 7),
    ('2026_11_07_000001_create_configuracion_y_recordatorios_automaticos', 8),
    ('2026_11_07_000002_create_configuracion_general_table', 9);
