<?php
/**
 * Pistas escalonadas: se desbloquean una a una y se registra su uso.
 */

declare(strict_types=1);

function hints_used(int $studentId, string $labId): int
{
    $stmt = db()->prepare('SELECT hints_used FROM hint_usage WHERE student_id = ? AND lab_id = ?');
    $stmt->execute([$studentId, $labId]);
    return (int) ($stmt->fetchColumn() ?: 0);
}

/** Pistas ya desbloqueadas por el alumno (para pintarlas al recargar). */
function revealed_hints(int $studentId, string $labId): array
{
    $all  = lab($labId)['hints'];
    $used = hints_used($studentId, $labId);
    return array_slice($all, 0, $used, true);
}

/**
 * Desbloquea la siguiente pista.
 * @return array{ok:bool, index?:int, text?:string, used:int, total:int, message?:string}
 */
function reveal_next_hint(int $studentId, string $labId): array
{
    $all   = lab($labId)['hints'];
    $total = count($all);
    $used  = hints_used($studentId, $labId);

    if ($used >= $total) {
        return ['ok' => false, 'used' => $used, 'total' => $total, 'message' => 'Ya desbloqueaste todas las pistas.'];
    }

    $next = $used + 1;
    db()->prepare("INSERT INTO hint_usage (student_id, lab_id, hints_used, updated_at)
                   VALUES (?, ?, ?, datetime('now'))
                   ON CONFLICT(student_id, lab_id)
                   DO UPDATE SET hints_used = excluded.hints_used, updated_at = excluded.updated_at")
        ->execute([$studentId, $labId, $next]);

    return ['ok' => true, 'index' => $next, 'text' => $all[$next], 'used' => $next, 'total' => $total];
}
