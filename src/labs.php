<?php
/**
 * Registro central de laboratorios: metadata + pistas escalonadas.
 *
 * Para editar textos o pistas basta con modificar este arreglo.
 * Regla: la pista más detallada describe el CÓMO, nunca entrega la flag.
 */

declare(strict_types=1);

const CATEGORIES = [
    'logic'  => ['name' => 'Lógica de negocio', 'icon' => '🧠', 'desc' => 'Intercepta, modifica y reenvía con Burp Suite. Lo que el navegador impide, el servidor quizá no.'],
    'recon'  => ['name' => 'Reconocimiento',    'icon' => '🔎', 'desc' => 'Todo lo que el servidor entrega —y lo que olvidó quitar— es información.'],
    'crypto' => ['name' => 'Criptografía',      'icon' => '🔐', 'desc' => 'Codificar no es cifrar, y una firma solo protege si se verifica bien.'],
];

function labs(): array
{
    static $labs = null;
    if ($labs !== null) {
        return $labs;
    }

    $labs = [
        // ───────────────────────── Lógica de negocio ─────────────────────────
        'BL-01' => [
            'slug'       => 'bl01-transferencia',
            'title'      => 'Transferencia con monto manipulado',
            'category'   => 'logic',
            'sector'     => 'Banca',
            'difficulty' => 'Básico',
            'objective'  => 'Comprobar que una validación hecha en el navegador no protege al servidor: interceptar y modificar el cuerpo de un POST con Burp.',
            'scenario'   => 'Eres cliente de Banco Austral. Tu portal te permite transferir a terceros. El formulario no te deja ingresar montos negativos ni mayores a tu saldo… al menos en el navegador. El banco premia como «Cliente Platinum» a quien supere los $100.000 de saldo.',
            'hints'      => [
                1 => '¿Qué valida el navegador y qué valida el servidor? Intercepta el envío del formulario con el Proxy de Burp (Intercept ON) y observa el cuerpo del POST.',
                2 => 'El campo clave del cuerpo es amount. Envía la petición a Repeater (Ctrl+R) para poder modificarla y reenviarla las veces que quieras.',
                3 => 'En Repeater cambia amount por un valor negativo grande (por ejemplo, -200000) y reenvía. Luego recarga el estado de cuenta y revisa tu saldo.',
            ],
        ],
        'BL-02' => [
            'slug'       => 'bl02-idor',
            'title'      => 'IDOR en estado de cuenta',
            'category'   => 'logic',
            'sector'     => 'Banca',
            'difficulty' => 'Básico',
            'objective'  => 'Reconocer referencias directas inseguras a objetos (IDOR) y enumerar recursos ajenos con Intruder.',
            'scenario'   => 'En Banco Austral cada cliente consulta su estado de cuenta en cuenta.php?account_id=… Tu cuenta es la 1024. Se rumorea que la cuenta de Gerencia General guarda un código de auditoría interno.',
            'hints'      => [
                1 => '¿De qué depende la cuenta que ves? Mira la URL de la petición en el historial del Proxy (HTTP history).',
                2 => 'El parámetro account_id no se valida contra tu sesión: el servidor muestra cualquier cuenta que le pidas.',
                3 => 'Envía la petición a Intruder, marca el valor de account_id como posición y usa un payload «Numbers» de 1000 a 1100. Ordena por longitud o estado de la respuesta y busca la cuenta destacada.',
            ],
        ],
        'BL-03' => [
            'slug'       => 'bl03-checkout',
            'title'      => 'Manipulación de precio y cupones en checkout',
            'category'   => 'logic',
            'sector'     => 'Retail / E-commerce',
            'difficulty' => 'Intermedio',
            'objective'  => 'Identificar datos que el servidor no debería aceptar del cliente (precio, descuento) y errores de lógica como cupones acumulables.',
            'scenario'   => 'TecnoMarket regala un «certificado de comprador experto» a quien logre cerrar un pedido con total $0 o menos. Tienes el cupón BIENVENIDA10 (10% de descuento, uso único… supuestamente).',
            'hints'      => [
                1 => '¿Dónde se decide el precio final: en el servidor o en lo que manda tu navegador? Intercepta las peticiones al agregar productos y al aplicar el cupón.',
                2 => 'Revisa los campos price (al agregar al carrito) y discount (al aplicar el cupón) en el cuerpo de las peticiones.',
                3 => 'Agrega un producto con price=0 desde Repeater, o reenvía la aplicación del cupón varias veces (o con discount=100) hasta que el total llegue a 0. Luego confirma la compra.',
            ],
        ],
        'BL-04' => [
            'slug'       => 'bl04-limite-retiro',
            'title'      => 'Bypass de límite diario de retiro',
            'category'   => 'logic',
            'sector'     => 'Banca',
            'difficulty' => 'Intermedio',
            'objective'  => 'Detectar controles que solo existen en el cliente y campos ocultos manipulables.',
            'scenario'   => 'El cajero web de Banco Austral permite retirar hasta $500 diarios. La política es estricta: ningún cliente puede superar ese tope en el día.',
            'hints'      => [
                1 => 'El límite diario, ¿lo impone el navegador o el servidor? Mira el código fuente del formulario (Ctrl+U) y luego la petición en Burp.',
                2 => 'Busca un campo oculto (type="hidden") en el formulario de retiro. ¿Qué pasa si su valor cambia?',
                3 => 'Intercepta el POST de retiro y cambia max_limit a un valor mayor (por ejemplo 5000) junto con un amount superior a 500. Reenvía y revisa el comprobante.',
            ],
        ],
        'BL-05' => [
            'slug'       => 'bl05-otp-multipaso',
            'title'      => 'Salto de verificación OTP en flujo multi-paso',
            'category'   => 'logic',
            'sector'     => 'Banca / Fintech',
            'difficulty' => 'Avanzado',
            'objective'  => 'Evaluar la integridad de flujos de varias etapas: el servidor debe verificar que cada paso previo se completó.',
            'scenario'   => 'Para inscribir un nuevo beneficiario, PagoSur exige 3 pasos: datos → código OTP enviado por SMS → confirmación. El SMS nunca llegará a tu teléfono de laboratorio…',
            'hints'      => [
                1 => '¿Qué garantiza que completaste el paso anterior? Recorre el flujo con el Proxy activo y compara las peticiones de cada paso.',
                2 => 'Observa el parámetro step que viaja en cada POST. ¿El servidor comprueba realmente que el OTP fue validado antes de confirmar?',
                3 => 'Completa el paso 1 y, en la petición del paso 2, cambia step=2 por step=3 (o reenvía desde Repeater un POST con step=3) sin enviar un OTP válido.',
            ],
        ],
        'BL-06' => [
            'slug'       => 'bl06-race-cupon',
            'title'      => 'Race condition: doble canje de cupón',
            'category'   => 'logic',
            'sector'     => 'Fintech',
            'difficulty' => 'Avanzado',
            'objective'  => 'Comprender las condiciones de carrera (TOCTOU) y explotarlas enviando peticiones concurrentes.',
            'scenario'   => 'La billetera digital BilleteraYa te regaló el cupón REGALO50 por $50, canjeable una sola vez. Si tu saldo supera lo que vale un único cupón, ganas el premio.',
            'hints'      => [
                1 => '¿Qué pasa si dos peticiones de canje llegan al servidor al mismo tiempo? Observa cuánto tarda en responder el canje.',
                2 => 'Envía la petición de canje a Repeater y duplícala varias veces (Ctrl+R repetido); luego agrúpalas en un mismo grupo de pestañas.',
                3 => 'Reinicia el laboratorio (cupón sin usar) y envía el grupo con «Send group in parallel» (o usa Turbo Intruder con race-single-packet-attack). Revisa la última respuesta.',
            ],
        ],

        // ───────────────────────── Reconocimiento ─────────────────────────
        'RC-01' => [
            'slug'       => 'rc01-fuentes',
            'title'      => 'Fugas en el código fuente',
            'category'   => 'recon',
            'sector'     => 'Corporativo',
            'difficulty' => 'Básico',
            'objective'  => 'Revisar todo lo que entrega el servidor al cliente: la información sensible no debe vivir en el HTML ni en el JavaScript.',
            'scenario'   => 'Andes Seguros acaba de lanzar su nuevo sitio institucional. El equipo de desarrollo trabajó contra reloj y quizá olvidó limpiar algunos restos…',
            'hints'      => [
                1 => 'Lee todo lo que entrega el servidor, no solo lo que se ve en pantalla. Revisa el árbol en Target → Site map de Burp.',
                2 => 'Revisa los comentarios HTML (Ctrl+U) y el contenido de los archivos .js que carga la página.',
                3 => 'En el JavaScript hay una ruta de un panel antiguo y en un comentario HTML unas credenciales de prueba. Combínalas.',
            ],
        ],
        'RC-02' => [
            'slug'       => 'rc02-descubrimiento',
            'title'      => 'Descubrimiento de contenido y archivos expuestos',
            'category'   => 'recon',
            'sector'     => 'Corporativo',
            'difficulty' => 'Intermedio',
            'objective'  => 'Enumerar contenido y detectar archivos de respaldo, listados de directorio y cabeceras que no deberían estar publicados.',
            'scenario'   => 'El portal de proveedores de Andes Logística fue migrado de servidor hace poco. El equipo de operaciones dejó «algunas cosas» del despliegue anterior.',
            'hints'      => [
                1 => '¿Qué archivos «de siempre» de un sitio web revelan rutas que no se enlazan? Empieza por la raíz del servidor. Mira también las cabeceras de respuesta.',
                2 => 'Una de las rutas tiene el listado de directorio habilitado; lee lo que hay allí. La otra no lista, pero puede contener copias de respaldo: prueba extensiones como .bak, .old o ~.',
                3 => 'Usa Intruder sobre /labs/rc02-descubrimiento/respaldo/§archivo§ con nombres típicos (index.php, config.php, db.php…) combinados con extensiones de respaldo, y lee el que responda con contenido.',
            ],
        ],

        // ───────────────────────── Criptografía ─────────────────────────
        'CR-01' => [
            'slug'       => 'cr01-cookie',
            'title'      => 'Cookie de sesión débil',
            'category'   => 'crypto',
            'sector'     => 'Fintech',
            'difficulty' => 'Intermedio',
            'objective'  => 'Diferenciar codificación de cifrado y entender por qué un token sin integridad permite escalar privilegios.',
            'scenario'   => 'FinPay usa una cookie de sesión «propietaria» diseñada por su equipo. Inicia sesión con la cuenta de demostración e intenta entrar al panel de administración.',
            'hints'      => [
                1 => 'Mira tu cookie finpay_session después de iniciar sesión: ¿está cifrada o solo codificada?',
                2 => 'Copia el valor en Decoder de Burp y aplica «Decode as → Base64» (si termina en %3D, decodifica primero como URL).',
                3 => 'Cambia role=cliente por role=admin, vuelve a codificar en Base64 y reemplaza la cookie (en Repeater o en el navegador) al pedir admin.php.',
            ],
        ],
        'CR-02' => [
            'slug'       => 'cr02-jwt',
            'title'      => 'JWT con firma débil / alg:none',
            'category'   => 'crypto',
            'sector'     => 'Fintech',
            'difficulty' => 'Avanzado',
            'objective'  => 'Comprender la estructura de un JWT (header.payload.firma) y qué ocurre cuando el servidor no verifica bien la firma.',
            'scenario'   => 'La API de inversiones de CapitalNet autentica con tokens JWT (Authorization: Bearer …). Los reportes de administración solo responden a tokens con rol admin.',
            'hints'      => [
                1 => 'Un JWT tiene tres partes en Base64URL separadas por puntos: header, payload y firma. Decodifica las dos primeras con Decoder (o la pestaña JSON Web Token de Burp).',
                2 => 'Revisa el campo alg del header y el claim role del payload. ¿El servidor exige siempre una firma válida?',
                3 => 'Forja un token con header {"alg":"none","typ":"JWT"}, payload con "role":"admin" y firma vacía (el token termina en punto). Úsalo en GET api/admin.php. Alternativa: la clave HMAC es una palabra de diccionario muy común.',
            ],
        ],
    ];

    foreach ($labs as $id => &$lab) {
        $lab['id']  = $id;
        $lab['url'] = '/labs/' . $lab['slug'] . '/';
    }
    unset($lab);
    return $labs;
}

function lab(string $id): array
{
    $all = labs();
    if (!isset($all[$id])) {
        http_response_code(404);
        exit('Laboratorio no encontrado');
    }
    return $all[$id];
}

function labs_by_category(): array
{
    $out = [];
    foreach (labs() as $id => $lab) {
        $out[$lab['category']][$id] = $lab;
    }
    return $out;
}
