<?php
/**
 * Inicialización / reseteo de la base de datos del laboratorio.
 *
 *   php seed/init.php           → crea esquema y datos semilla si faltan (idempotente).
 *   php seed/init.php --reset   → BORRA la base (alumnos, progreso, estado) y la recrea.
 *
 * Se ejecuta automáticamente en cada arranque del contenedor (docker/entrypoint.sh).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Solo por línea de comandos.\n");
}

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';

$files = [DB_PATH, DB_PATH . '-wal', DB_PATH . '-shm'];

if (in_array('--reset', $argv, true)) {
    foreach ($files as $f) {
        if (file_exists($f)) {
            unlink($f);
        }
    }
    echo "[seed] Base de datos eliminada.\n";
}

$pdo = db();      // crea el esquema si la base es nueva
init_db($pdo);    // re-aplica esquema y semillas (CREATE IF NOT EXISTS / INSERT OR IGNORE)

// Si se ejecuta como root (docker exec), devolver la propiedad a Apache.
foreach (array_merge([dirname(DB_PATH)], $files) as $f) {
    if (file_exists($f)) {
        @chown($f, 'www-data');
        @chgrp($f, 'www-data');
    }
}

$students = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$accounts = (int) $pdo->query('SELECT COUNT(*) FROM bl02_accounts')->fetchColumn();
echo "[seed] OK · " . DB_PATH . " · alumnos: $students · cuentas BL-02: $accounts\n";
if (LAB_SECRET === 'dev-secret-cambiame') {
    echo "[seed] ⚠️  LAB_SECRET usa el valor por defecto. Defínelo en docker-compose/.env para tu curso.\n";
}
