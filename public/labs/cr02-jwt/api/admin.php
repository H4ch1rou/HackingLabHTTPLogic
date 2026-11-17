<?php
/** CR-02 · GET api/admin.php (Bearer, role=admin) → reportes de administración + flag. */
require_once 'bootstrap.php';
require_once dirname(__DIR__) . '/_jwt.php';

$student = require_student(true);
mark_started((int) $student['id'], 'CR-02');
$claims = require_jwt();

if (($claims['role'] ?? '') !== 'admin') {
    json_out([
        'error'   => 'forbidden',
        'message' => 'Se requiere rol admin. Tu token tiene rol "' . ($claims['role'] ?? '?') . '".',
    ], 403);
}

json_out([
    'reporte'  => 'Resumen diario de operaciones',
    'clientes' => 5120,
    'aum_usd'  => 84200000,
    'alertas'  => ['3 operaciones marcadas para revisión AML'],
    'flag'     => current_flag('CR-02'),
]);
