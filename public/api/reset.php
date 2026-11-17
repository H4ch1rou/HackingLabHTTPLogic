<?php
/**
 * Reinicio para reintentar:
 *   - lab_id=XX-00 → vuelve el laboratorio a su estado inicial (saldo, carrito…).
 *                    No borra el progreso ni las pistas usadas.
 *   - scope=all    → borra TODO el progreso del alumno (desde el portal).
 */
require_once 'bootstrap.php';

if (!is_post()) {
    json_out(['ok' => false, 'message' => 'Usa POST.'], 405);
}
$student = require_student(true);
$sid     = (int) $student['id'];

if (($_POST['scope'] ?? '') === 'all') {
    reset_student($sid);
    redirect('/');
}

$labId = (string) ($_POST['lab_id'] ?? '');
if (!isset(labs()[$labId])) {
    json_out(['ok' => false, 'message' => 'Laboratorio desconocido.'], 404);
}
state_reset($sid, $labId);

// Algunos labs guardan estado en cookies o en la sesión: también se limpian.
if ($labId === 'CR-01') {
    setcookie('finpay_session', '', ['expires' => 1, 'path' => '/labs/cr01-cookie/']);
}
if ($labId === 'RC-01') {
    unset($_SESSION['rc01_admin']);
}
json_out(['ok' => true, 'message' => 'Laboratorio reiniciado.']);
