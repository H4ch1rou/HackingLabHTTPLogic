<?php
/**
 * Identificación del alumno (sin contraseñas: es un laboratorio).
 *
 * El alumno ingresa su nombre y recibe un student_token aleatorio que
 * identifica su progreso y del que se derivan sus flags. Puede retomar
 * su sesión desde otro navegador ingresando ese token.
 */

declare(strict_types=1);

function current_student(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $cache = null;
    if (!empty($_SESSION['student_id'])) {
        $stmt = db()->prepare('SELECT * FROM students WHERE id = ?');
        $stmt->execute([(int) $_SESSION['student_id']]);
        $cache = $stmt->fetch() ?: null;
        // Si la base se reseteó, el mismo id puede pertenecer a otro alumno: se compara el token.
        if ($cache === null || !hash_equals($cache['token'], (string) ($_SESSION['student_token'] ?? ''))) {
            $cache = null;
            unset($_SESSION['student_id'], $_SESSION['student_token']);
        }
    }
    return $cache;
}

/** Exige alumno identificado; si no, redirige al login (o 401 JSON en APIs). */
function require_student(bool $json = false): array
{
    $student = current_student();
    if ($student) {
        return $student;
    }
    if ($json) {
        json_out(['ok' => false, 'message' => 'Primero identifícate en el portal.'], 401);
    }
    $next = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: /login.php?next=' . urlencode($next));
    exit;
}

function create_student(string $name): array
{
    $token = bin2hex(random_bytes(6)); // 12 hex, fácil de anotar
    $stmt  = db()->prepare('INSERT INTO students (name, token) VALUES (?, ?)');
    $stmt->execute([$name, $token]);
    return ['id' => (int) db()->lastInsertId(), 'name' => $name, 'token' => $token];
}

function find_student_by_token(string $token): ?array
{
    $stmt = db()->prepare('SELECT * FROM students WHERE token = ?');
    $stmt->execute([strtolower(trim($token))]);
    return $stmt->fetch() ?: null;
}

function login_student(array $student): void
{
    session_regenerate_id(true);
    $_SESSION['student_id']    = (int) $student['id'];
    $_SESSION['student_token'] = $student['token'];
}

function logout_student(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

// ───────────── Profesor ─────────────

function is_teacher(): bool
{
    return !empty($_SESSION['teacher']);
}
