<?php
/**
 * Configuración global del laboratorio.
 *
 * Todo valor sensible se toma de variables de entorno (docker-compose.yml)
 * con un valor por defecto pensado SOLO para desarrollo local.
 */

declare(strict_types=1);

// Raíz del proyecto (/var/www/app dentro del contenedor).
define('APP_ROOT', dirname(__DIR__));

// Secreto del servidor con el que se derivan las flags de cada alumno.
// ¡Cámbialo en cada semestre/curso! (variable LAB_SECRET)
define('LAB_SECRET', getenv('LAB_SECRET') ?: 'dev-secret-cambiame');

// Contraseña del panel del profesor (variable TEACHER_PASSWORD).
define('TEACHER_PASSWORD', getenv('TEACHER_PASSWORD') ?: 'profesor');

// Ruta del archivo SQLite (volumen persistente /data).
define('DB_PATH', getenv('LAB_DB_PATH') ?: APP_ROOT . '/data/lab.sqlite');

// Límite de intentos de verificación de flag por alumno y minuto.
define('FLAG_ATTEMPTS_PER_MINUTE', 10);

// Nombre de la plataforma (portal).
define('APP_NAME', 'HTTP Lab · Laboratorio de Seguridad Web');
