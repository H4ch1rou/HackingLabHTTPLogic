<?php
/**
 * CR-01 · Sesión «propietaria» de FinPay (intencionalmente insegura).
 *
 * La cookie finpay_session es simplemente base64("user=juan;role=cliente;exp=…").
 * No hay cifrado ni firma (HMAC): quien la decodifica puede modificarla.
 * (Este archivo solo define funciones; pedirlo directamente no muestra nada.)
 */

const FINPAY_COOKIE = 'finpay_session';
const FINPAY_PATH   = '/labs/cr01-cookie/';

function finpay_issue(string $user, string $role): void
{
    $value = base64_encode("user=$user;role=$role;exp=" . (time() + 3600));
    setcookie(FINPAY_COOKIE, $value, ['path' => FINPAY_PATH, 'httponly' => false, 'samesite' => 'Lax']);
    $_COOKIE[FINPAY_COOKIE] = $value;
}

/** Devuelve ['user'=>…, 'role'=>…] o null. ⚠️ VULN: no verifica integridad. */
function finpay_session(): ?array
{
    $raw = $_COOKIE[FINPAY_COOKIE] ?? '';
    if ($raw === '') {
        return null;
    }
    // PHP decodifica las cookies como URL: un '+' sin codificar llega como espacio.
    $decoded = base64_decode(trim(strtr($raw, ' ', '+')), true);
    if ($decoded === false) {
        return null;
    }
    $data = [];
    foreach (explode(';', $decoded) as $pair) {
        [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
        $data[trim($k)] = trim($v);
    }
    if (empty($data['user']) || empty($data['role'])) {
        return null;
    }
    if (isset($data['exp']) && ctype_digit($data['exp']) && (int) $data['exp'] < time()) {
        return null; // caducada
    }
    return $data;
}

function finpay_logout(): void
{
    setcookie(FINPAY_COOKIE, '', ['expires' => 1, 'path' => FINPAY_PATH]);
    unset($_COOKIE[FINPAY_COOKIE]);
}
