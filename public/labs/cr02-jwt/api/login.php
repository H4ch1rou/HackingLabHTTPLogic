<?php
/** CR-02 · POST api/login.php  {"username":"ana","password":"ana2024"} → {"token": "…"} */
require_once 'bootstrap.php';
require_once dirname(__DIR__) . '/_jwt.php';

require_student(true);
if (!is_post()) {
    json_out(['error' => 'method_not_allowed', 'message' => 'Usa POST.'], 405);
}

$body = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($body)) {
    $body = $_POST;
}
$users = ['ana' => ['pass' => 'ana2024', 'name' => 'Ana Torres', 'role' => 'usuario']];

$u = strtolower(trim((string) ($body['username'] ?? '')));
$p = (string) ($body['password'] ?? '');
if (!isset($users[$u]) || !hash_equals($users[$u]['pass'], $p)) {
    json_out(['error' => 'invalid_credentials', 'message' => 'Usuario o contraseña incorrectos.'], 401);
}

$token = jwt_issue([
    'sub'  => $u,
    'name' => $users[$u]['name'],
    'role' => $users[$u]['role'],
    'iat'  => time(),
    'exp'  => time() + 7200,
]);
json_out(['token' => $token, 'token_type' => 'Bearer', 'expires_in' => 7200]);
