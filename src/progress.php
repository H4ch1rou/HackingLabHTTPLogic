<?php
/**
 * Registro de progreso: inicio (primera visita) y completado de cada lab.
 */

declare(strict_types=1);

function mark_started(int $studentId, string $labId): void
{
    db()->prepare('INSERT OR IGNORE INTO progress (student_id, lab_id) VALUES (?, ?)')
        ->execute([$studentId, $labId]);
}

function mark_completed(int $studentId, string $labId): void
{
    mark_started($studentId, $labId);
    db()->prepare("UPDATE progress
                   SET completed_at = datetime('now'), hints_at_completion = ?
                   WHERE student_id = ? AND lab_id = ? AND completed_at IS NULL")
        ->execute([hints_used($studentId, $labId), $studentId, $labId]);
}

/** Progreso del alumno indexado por lab_id: started_at, completed_at, hints_used. */
function student_progress(int $studentId): array
{
    $out = [];
    foreach (labs() as $id => $_) {
        $out[$id] = ['started_at' => null, 'completed_at' => null, 'hints_used' => 0];
    }
    $stmt = db()->prepare('SELECT lab_id, started_at, completed_at FROM progress WHERE student_id = ?');
    $stmt->execute([$studentId]);
    foreach ($stmt as $row) {
        if (isset($out[$row['lab_id']])) {
            $out[$row['lab_id']]['started_at']   = $row['started_at'];
            $out[$row['lab_id']]['completed_at'] = $row['completed_at'];
        }
    }
    $stmt = db()->prepare('SELECT lab_id, hints_used FROM hint_usage WHERE student_id = ?');
    $stmt->execute([$studentId]);
    foreach ($stmt as $row) {
        if (isset($out[$row['lab_id']])) {
            $out[$row['lab_id']]['hints_used'] = (int) $row['hints_used'];
        }
    }
    return $out;
}

function completed_count(array $progress): int
{
    return count(array_filter($progress, fn($p) => $p['completed_at'] !== null));
}

/** Borra todo el progreso de un alumno (modo reset). */
function reset_student(int $studentId): void
{
    foreach (['progress', 'hint_usage', 'flag_attempts', 'lab_state'] as $table) {
        db()->prepare("DELETE FROM $table WHERE student_id = ?")->execute([$studentId]);
    }
}
