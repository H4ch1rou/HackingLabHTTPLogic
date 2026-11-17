<?php
/**
 * BL-06 · Race condition: doble canje de cupón (Fintech, Avanzado)
 *
 * VULNERABILIDAD INTENCIONAL (TOCTOU): se comprueba «¿ya se usó?» y, tras una
 * demora simulada (validación con el emisor), se abona el saldo y recién
 * entonces se marca el cupón como usado. Varias peticiones concurrentes pasan
 * la comprobación antes de que cualquiera lo marque.
 *
 * Nota técnica: se cierra la sesión PHP de inmediato; si no, el bloqueo del
 * archivo de sesión serializaría las peticiones del mismo alumno y no habría
 * carrera posible.
 */
require_once 'bootstrap.php';

if (!is_post()) {
    json_out(['ok' => false, 'message' => 'Usa POST.'], 405);
}
$student = require_student(true);
session_write_close(); // libera el lock de sesión → permite concurrencia

$sid = (int) $student['id'];
$L   = 'BL-06';
const COUPON_CODE  = 'REGALO50';
const COUPON_VALUE = 50.0;

mark_started($sid, $L);

if (strtoupper(trim((string) ($_POST['code'] ?? ''))) !== COUPON_CODE) {
    json_out(['ok' => false, 'message' => 'Cupón inexistente.']);
}

// Inicializa el saldo si es la primera vez (para que el UPDATE atómico tenga fila).
state_init($sid, $L, 'balance', 0.0);

// 1) CHECK
if (state_get($sid, $L, 'redeemed', false)) {
    json_out(['ok' => false, 'message' => 'Este cupón ya fue utilizado.']);
}

// ⚠️ VENTANA DE CARRERA: «validación con el emisor» (latencia simulada).
usleep(600_000);

// 2) USE — abono atómico y luego marca de uso (demasiado tarde).
state_incr($sid, $L, 'balance', COUPON_VALUE);
state_set($sid, $L, 'redeemed', true);

$balance = (float) state_get($sid, $L, 'balance', 0);
$history = state_get($sid, $L, 'history', []);
$history[] = date('H:i:s') . ' · Canje cupón ' . COUPON_CODE . ' +' . money(COUPON_VALUE);
state_set($sid, $L, 'history', $history);

$resp = [
    'ok'          => true,
    'message'     => 'Cupón canjeado: +' . money(COUPON_VALUE) . '. Saldo actual: ' . money($balance),
    'balance'     => $balance,
    'balance_fmt' => money($balance),
];
if ($balance > COUPON_VALUE) {
    $resp['message'] .= ' — ¡El saldo supera el valor de un único cupón!';
    $resp['flag'] = current_flag($L);
}
json_out($resp);
