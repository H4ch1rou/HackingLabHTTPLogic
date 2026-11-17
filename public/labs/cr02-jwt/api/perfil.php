<?php
/** CR-02 · GET api/perfil.php (Bearer) → datos del usuario del token. */
require_once 'bootstrap.php';
require_once dirname(__DIR__) . '/_jwt.php';

require_student(true);
$claims = require_jwt();

json_out([
    'usuario'    => $claims['sub'] ?? null,
    'nombre'     => $claims['name'] ?? null,
    'rol'        => $claims['role'] ?? null,
    'portafolio' => [
        ['instrumento' => 'Fondo Mutuo Conservador A', 'monto' => 1250000],
        ['instrumento' => 'Depósito a plazo 90 días',  'monto' => 3000000],
    ],
]);
