<?php
/**
 * Herramienta del profesor (CLI, no accesible por web): muestra las flags
 * esperadas de un alumno, útil para soporte en clase.
 *
 *   docker compose exec lab php seed/flags-alumno.php <student_token>
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Solo por línea de comandos.\n");
}
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/labs.php';
require_once __DIR__ . '/../src/flags.php';

$token = strtolower(trim($argv[1] ?? ''));
if ($token === '') {
    exit("Uso: php seed/flags-alumno.php <student_token>\n");
}
foreach (labs() as $id => $lab) {
    printf("%-6s %-55s %s\n", $id, $lab['title'], flag_for($token, $id));
}
