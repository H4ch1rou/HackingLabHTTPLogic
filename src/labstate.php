<?php
/**
 * Estado clave/valor de cada laboratorio por alumno (saldo, carrito, flujo…).
 * Los valores se guardan como JSON.
 */

declare(strict_types=1);

function state_get(int $studentId, string $labId, string $key, mixed $default = null): mixed
{
    $stmt = db()->prepare('SELECT v FROM lab_state WHERE student_id = ? AND lab_id = ? AND k = ?');
    $stmt->execute([$studentId, $labId, $key]);
    $v = $stmt->fetchColumn();
    return $v === false ? $default : json_decode((string) $v, true);
}

function state_set(int $studentId, string $labId, string $key, mixed $value): void
{
    db()->prepare('INSERT INTO lab_state (student_id, lab_id, k, v) VALUES (?, ?, ?, ?)
                   ON CONFLICT(student_id, lab_id, k) DO UPDATE SET v = excluded.v')
        ->execute([$studentId, $labId, $key, json_encode($value, JSON_UNESCAPED_UNICODE)]);
}

/** Crea la clave solo si no existe (seguro ante peticiones concurrentes). */
function state_init(int $studentId, string $labId, string $key, mixed $value): void
{
    db()->prepare('INSERT OR IGNORE INTO lab_state (student_id, lab_id, k, v) VALUES (?, ?, ?, ?)')
        ->execute([$studentId, $labId, $key, json_encode($value, JSON_UNESCAPED_UNICODE)]);
}

/** Incremento atómico de un valor numérico (cada petición suma lo suyo). */
function state_incr(int $studentId, string $labId, string $key, float $delta): void
{
    db()->prepare('UPDATE lab_state SET v = CAST(v AS REAL) + ? WHERE student_id = ? AND lab_id = ? AND k = ?')
        ->execute([$delta, $studentId, $labId, $key]);
}

/** Vuelve el laboratorio a su estado inicial (no borra el progreso ni las pistas). */
function state_reset(int $studentId, string $labId): void
{
    db()->prepare('DELETE FROM lab_state WHERE student_id = ? AND lab_id = ?')->execute([$studentId, $labId]);
}
