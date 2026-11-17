<?php
/**
 * Conexión PDO a SQLite + creación del esquema y datos semilla.
 *
 * init_db() es idempotente: puede ejecutarse en cada arranque del contenedor
 * (seed/init.php) y también se invoca de forma perezosa si la base no existe.
 */

declare(strict_types=1);

const SCHEMA_VERSION = 1;

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // WAL + busy_timeout: permite peticiones concurrentes (necesario para BL-06).
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA busy_timeout = 8000');
    $pdo->exec('PRAGMA foreign_keys = ON');

    $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    if ($version < SCHEMA_VERSION) {
        init_db($pdo);
    }
    return $pdo;
}

/** Crea tablas y datos semilla. Seguro de ejecutar varias veces. */
function init_db(PDO $pdo): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS students (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            name        TEXT NOT NULL,
            token       TEXT NOT NULL UNIQUE,
            created_at  TEXT NOT NULL DEFAULT (datetime('now'))
        );

        -- Progreso por alumno y laboratorio.
        CREATE TABLE IF NOT EXISTS progress (
            student_id    INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
            lab_id        TEXT NOT NULL,
            started_at    TEXT NOT NULL DEFAULT (datetime('now')),
            completed_at  TEXT,
            hints_at_completion INTEGER,
            PRIMARY KEY (student_id, lab_id)
        );

        -- Pistas desbloqueadas por alumno y laboratorio.
        CREATE TABLE IF NOT EXISTS hint_usage (
            student_id  INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
            lab_id      TEXT NOT NULL,
            hints_used  INTEGER NOT NULL DEFAULT 0,
            updated_at  TEXT NOT NULL DEFAULT (datetime('now')),
            PRIMARY KEY (student_id, lab_id)
        );

        -- Intentos de verificación de flags (para rate limit y auditoría).
        CREATE TABLE IF NOT EXISTS flag_attempts (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id  INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
            lab_id      TEXT NOT NULL,
            ok          INTEGER NOT NULL,
            created_at  TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_attempts ON flag_attempts(student_id, created_at);

        -- Estado clave/valor de cada laboratorio por alumno (saldos, carrito...).
        CREATE TABLE IF NOT EXISTS lab_state (
            student_id  INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
            lab_id      TEXT NOT NULL,
            k           TEXT NOT NULL,
            v           TEXT,
            PRIMARY KEY (student_id, lab_id, k)
        );

        -- BL-02: cuentas bancarias ficticias (compartidas, solo lectura).
        CREATE TABLE IF NOT EXISTS bl02_accounts (
            id          INTEGER PRIMARY KEY,
            holder      TEXT NOT NULL,
            type        TEXT NOT NULL,
            branch      TEXT NOT NULL,
            balance     REAL NOT NULL,
            is_target   INTEGER NOT NULL DEFAULT 0
        );
    SQL);

    seed_bl02_accounts($pdo);

    $pdo->exec('PRAGMA user_version = ' . SCHEMA_VERSION);
}

/** Cuentas ficticias para el laboratorio de IDOR (INSERT OR IGNORE = idempotente). */
function seed_bl02_accounts(PDO $pdo): void
{
    $names = [
        'Camila Rojas Fuentes', 'Matías Soto Pérez', 'Valentina Muñoz Díaz', 'Benjamín Contreras Silva',
        'Isidora Morales Vega', 'Joaquín Herrera Castro', 'Florencia Navarro Ríos', 'Tomás Araya Pino',
        'Antonia Sepúlveda León', 'Lucas Fernández Mora', 'Martina Espinoza Lagos', 'Agustín Carrasco Gil',
        'Josefa Tapia Bravo', 'Vicente Reyes Ortiz', 'Emilia Vargas Núñez', 'Maximiliano Rivas Paz',
        'Sofía Cáceres Molina', 'Diego Alarcón Sáez', 'Catalina Bustos Vera', 'Felipe Gutiérrez Ibáñez',
        'Renata Pizarro Cruz', 'Gaspar Leiva Salas', 'Amanda Figueroa Toro', 'Simón Valenzuela Rey',
    ];
    $branches = ['Sucursal Centro', 'Sucursal Norte', 'Sucursal Oriente', 'Sucursal Puerto', 'Banca Digital'];
    $types    = ['Cuenta Corriente', 'Cuenta Vista', 'Cuenta de Ahorro'];

    $stmt = $pdo->prepare('INSERT OR IGNORE INTO bl02_accounts (id, holder, type, branch, balance, is_target)
                           VALUES (?, ?, ?, ?, ?, ?)');

    // IDs repartidos entre 1000 y 1099 (con huecos, para que la enumeración
    // con Intruder muestre respuestas 404 y 200 distintas).
    $id = 1001;
    foreach ($names as $i => $name) {
        $stmt->execute([
            $id,
            $name,
            $types[$i % count($types)],
            $branches[$i % count($branches)],
            round(150000 + (($i * 7919) % 4200000) + ($i * 13.37), 2),
            0,
        ]);
        $id += ($i % 3) + 3; // saltos de 3, 4 o 5
        if ($id === 1024) { $id++; } // la 1024 es la del alumno
    }

    // Cuenta del alumno (el titular se reemplaza por su nombre al mostrarla).
    $stmt->execute([1024, '(alumno)', 'Cuenta Corriente', 'Banca Digital', 845320.50, 0]);

    // Cuenta objetivo: Gerencia General.
    $stmt->execute([1089, 'Gerencia General — Patricia Lagos Montt', 'Cuenta Corriente Institucional',
                    'Casa Matriz', 98450000.00, 1]);
}
