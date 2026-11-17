<?php
/**
 * RC-02 · "config.php" del portal de proveedores.
 *
 * - Pedido como config.php → se "ejecuta" y no devuelve nada (como un config real).
 * - Pedido como copia de respaldo (config.php.bak, config.php~, …; ver .htaccess)
 *   → Apache no lo interpretaría como PHP, así que simulamos que devuelve el
 *   código fuente en texto plano. REQUEST_URI conserva la URL original.
 */
require_once 'bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (!preg_match('/config\.php(\.bak|\.old|\.orig|\.save|\.backup|\.copy|\.swp|~)$/', rawurldecode($path))) {
    exit; // respuesta vacía: comportamiento normal de un archivo de configuración
}

$student = current_student();
if ($student) {
    mark_started((int) $student['id'], 'RC-02');
}
$flag = current_flag('RC-02');

header('Content-Type: text/plain; charset=utf-8');
echo <<<PHP
<?php
/*
 * config.php — Portal de Proveedores Andes Logística
 * Respaldo previo a la migración (2024-03-02). NO PUBLICAR.
 */

// Base de datos (credenciales ficticias del laboratorio)
define('DB_HOST', 'db-interno.andeslogistica.local');
define('DB_NAME', 'portal_proveedores');
define('DB_USER', 'portal_rw');
define('DB_PASS', 'NoEsReal-Lab-2024');

// SMTP
define('SMTP_HOST', 'smtp.andeslogistica.local');
define('SMTP_USER', 'notificaciones');
define('SMTP_PASS', 'TampocoEsReal-123');

// Clave interna de integración (laboratorio: esta es tu flag)
define('INTEGRATION_KEY', '$flag');

define('DEBUG', true);

PHP;
