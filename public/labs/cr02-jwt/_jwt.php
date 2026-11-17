<?php
/**
 * CR-02 · Implementación de JWT intencionalmente defectuosa.
 *
 * VULNERABILIDADES INTENCIONALES:
 *   1) Acepta tokens con "alg":"none" (sin firma) → cualquiera puede forjar claims.
 *   2) La clave HMAC de HS256 es una palabra de diccionario ("secret"),
 *      crackeable con hashcat (-m 16500) o jwt_tool.
 * (Apache deniega el acceso directo a archivos _*.php.)
 */

const JWT_KEY = 'secret';

function b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string|false
{
    $data = strtr($data, '-_', '+/');
    return base64_decode($data . str_repeat('=', (4 - strlen($data) % 4) % 4), true);
}

function jwt_issue(array $claims): string
{
    $h = b64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $p = b64url_encode(json_encode($claims, JSON_UNESCAPED_UNICODE));
    $s = b64url_encode(hash_hmac('sha256', "$h.$p", JWT_KEY, true));
    return "$h.$p.$s";
}

/** Devuelve el payload si el token es «válido», o un string con el motivo del rechazo. */
function jwt_check(string $token): array|string
{
    $parts = explode('.', trim($token));
    if (count($parts) !== 3) {
        return 'Formato de token inválido (se esperan 3 partes separadas por punto).';
    }
    [$h64, $p64, $s64] = $parts;
    $header  = json_decode((string) b64url_decode($h64), true);
    $payload = json_decode((string) b64url_decode($p64), true);
    if (!is_array($header) || !is_array($payload)) {
        return 'No se pudo decodificar el header o el payload.';
    }

    $alg = strtolower((string) ($header['alg'] ?? ''));
    if ($alg === 'none') {
        // ⚠️ VULN 1: token sin firma aceptado.
    } elseif ($alg === 'hs256') {
        $expected = b64url_encode(hash_hmac('sha256', "$h64.$p64", JWT_KEY, true)); // ⚠️ VULN 2: clave débil
        if (!hash_equals($expected, $s64)) {
            return 'Firma inválida.';
        }
    } else {
        return 'Algoritmo no soportado.';
    }

    if (isset($payload['exp']) && is_numeric($payload['exp']) && $payload['exp'] < time()) {
        return 'Token expirado.';
    }
    return $payload;
}

/** Lee el token de la cabecera Authorization: Bearer … */
function bearer_token(): ?string
{
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($hdr === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            if (strcasecmp($k, 'Authorization') === 0) {
                $hdr = $v;
            }
        }
    }
    return preg_match('/^Bearer\s+(\S+)$/i', trim($hdr), $m) ? $m[1] : null;
}

/** Exige un token válido; responde 401 JSON si no lo hay. */
function require_jwt(): array
{
    $token = bearer_token();
    if ($token === null) {
        json_out(['error' => 'unauthorized', 'message' => 'Falta la cabecera Authorization: Bearer <token>.'], 401);
    }
    $result = jwt_check($token);
    if (is_string($result)) {
        json_out(['error' => 'invalid_token', 'message' => $result], 401);
    }
    return $result;
}
