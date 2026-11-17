<?php
/**
 * Flags dinámicas por alumno.
 *
 *   flag_raw = HMAC_SHA256(key = LAB_SECRET, msg = student_token + ':' + lab_id)
 *   flag     = FLAG{ primeros 16 hex de flag_raw }
 *
 * No se almacenan: el servidor las recalcula para verificar. Como dependen del
 * token del alumno, compartir una flag no le sirve a otro alumno.
 */

declare(strict_types=1);

function flag_for(string $studentToken, string $labId): string
{
    $raw = hash_hmac('sha256', $studentToken . ':' . $labId, LAB_SECRET);
    return 'FLAG{' . substr($raw, 0, 16) . '}';
}

/** Flag del alumno de la sesión actual (para mostrarla tras un exploit exitoso). */
function current_flag(string $labId): string
{
    $student = current_student();
    if (!$student) {
        return '(identifícate en el portal para ver tu flag)';
    }
    return flag_for($student['token'], $labId);
}

/** Cantidad de intentos del alumno en el último minuto. */
function flag_attempts_last_minute(int $studentId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM flag_attempts
                           WHERE student_id = ? AND created_at >= datetime('now', '-60 seconds')");
    $stmt->execute([$studentId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Verifica una flag enviada por el alumno. Siempre del lado del servidor.
 * @return array{ok:bool, message:string}
 */
function verify_flag(array $student, string $labId, string $submitted): array
{
    if (!isset(labs()[$labId])) {
        return ['ok' => false, 'message' => 'Laboratorio desconocido.'];
    }
    if (flag_attempts_last_minute((int) $student['id']) >= FLAG_ATTEMPTS_PER_MINUTE) {
        return ['ok' => false, 'message' => '⏳ Demasiados intentos. Espera un minuto antes de volver a probar.'];
    }

    $expected = flag_for($student['token'], $labId);
    $ok       = hash_equals($expected, trim($submitted));

    db()->prepare('INSERT INTO flag_attempts (student_id, lab_id, ok) VALUES (?, ?, ?)')
        ->execute([(int) $student['id'], $labId, $ok ? 1 : 0]);

    if (!$ok) {
        return ['ok' => false, 'message' => '❌ Flag incorrecta, sigue intentando.'];
    }
    mark_completed((int) $student['id'], $labId);
    return ['ok' => true, 'message' => '✅ Correcto, laboratorio completado.'];
}
