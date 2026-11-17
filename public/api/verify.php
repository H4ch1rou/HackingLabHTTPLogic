<?php
/**
 * Endpoint común de verificación de flags (POST lab_id, flag).
 * Toda la comparación ocurre en el servidor (src/flags.php).
 */
require_once 'bootstrap.php';

if (!is_post()) {
    json_out(['ok' => false, 'message' => 'Usa POST.'], 405);
}
$student = require_student(true);
$result  = verify_flag($student, (string) ($_POST['lab_id'] ?? ''), (string) ($_POST['flag'] ?? ''));
json_out($result);
