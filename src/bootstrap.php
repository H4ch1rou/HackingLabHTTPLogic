<?php
/**
 * Punto de entrada común. Cada página pública hace:
 *
 *     require_once 'bootstrap.php';   // (include_path apunta a /src)
 *
 * Carga configuración, base de datos, sesión y toda la lógica compartida.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/labs.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/hints.php';
require_once __DIR__ . '/progress.php';
require_once __DIR__ . '/flags.php';
require_once __DIR__ . '/labstate.php';
require_once __DIR__ . '/layout.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('LABSESSID');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}

// ───────────── Utilidades ─────────────

/** Escapa HTML. */
function h(mixed $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Formato de dinero: $1.234,50 */
function money(float|int $n): string
{
    $sign = $n < 0 ? '-' : '';
    return $sign . '$' . number_format(abs((float) $n), 2, ',', '.');
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/**
 * Inicio estándar de una página de laboratorio: exige alumno identificado,
 * registra la primera visita y devuelve [metadata del lab, alumno].
 */
function lab_begin(string $labId): array
{
    $student = require_student();
    $lab     = lab($labId);
    mark_started((int) $student['id'], $labId);
    return [$lab, $student];
}
