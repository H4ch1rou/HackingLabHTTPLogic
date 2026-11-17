<?php
/** Desbloquea la siguiente pista de un laboratorio (POST lab_id). */
require_once 'bootstrap.php';

if (!is_post()) {
    json_out(['ok' => false, 'message' => 'Usa POST.'], 405);
}
$student = require_student(true);
$labId   = (string) ($_POST['lab_id'] ?? '');
if (!isset(labs()[$labId])) {
    json_out(['ok' => false, 'message' => 'Laboratorio desconocido.'], 404);
}
json_out(reveal_next_hint((int) $student['id'], $labId));
