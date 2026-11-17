<?php
/** Guía breve de configuración de Burp Suite. */
require_once 'bootstrap.php';
page_header('¿Cómo configuro Burp?');
$host = h($_SERVER['HTTP_HOST'] ?? 'localhost:8080');
?>
<article class="doc">
    <h1>🛠️ ¿Cómo configuro Burp Suite?</h1>
    <p>Burp Suite actúa como <strong>proxy</strong>: se sitúa entre tu navegador y el servidor, y te deja ver, detener y
       modificar cada petición y respuesta HTTP. Con la edición <em>Community</em> (gratuita) puedes resolver todos los
       laboratorios.</p>

    <div class="callout">
        <strong>⚠️ Ojo con el puerto 8080.</strong> Burp escucha por defecto en <code>127.0.0.1:8080</code>, el mismo
        puerto que suele usar este laboratorio (<code><?= $host ?></code>). Si ambos están en tu equipo, cambia uno:
        en Burp ve a <em>Proxy → Proxy settings → Proxy listeners</em> y usa <code>127.0.0.1:8081</code>, o levanta el
        laboratorio con <code>LAB_PORT=8000 docker compose up</code>.
    </div>

    <h2>Opción A (recomendada): navegador integrado de Burp</h2>
    <ol>
        <li>Abre Burp → pestaña <strong>Proxy → Intercept</strong>.</li>
        <li>Pulsa <strong>Open browser</strong>. Se abre un Chromium ya configurado (proxy + certificado).</li>
        <li>Navega a <code>http://<?= $host ?>/</code>. Las peticiones aparecerán en <em>Proxy → HTTP history</em>.</li>
    </ol>

    <h2>Opción B: tu propio navegador</h2>
    <ol>
        <li>Configura el proxy HTTP del navegador hacia <code>127.0.0.1</code> y el puerto del listener de Burp
            (Firefox: <em>Ajustes → Configuración de red</em>, o la extensión FoxyProxy).</li>
        <li><strong>Importante:</strong> los navegadores no envían <code>localhost</code> al proxy por defecto. En Firefox
            abre <code>about:config</code> y activa <code>network.proxy.allow_hijacking_localhost</code>; o accede al lab
            por la IP de tu red local en lugar de <code>localhost</code>.</li>
        <li>Instalar el certificado CA de Burp (<code>http://burpsuite</code> → <em>CA Certificate</em>) solo es necesario
            para sitios HTTPS. Este laboratorio usa HTTP plano, pero es bueno que aprendas a hacerlo.</li>
    </ol>

    <h2>Tu primera intercepción</h2>
    <ol>
        <li>En <em>Proxy → Intercept</em> activa <strong>Intercept is on</strong>.</li>
        <li>Envía cualquier formulario del laboratorio. La petición queda <strong>detenida</strong> en Burp.</li>
        <li>Modifica lo que quieras (parámetros, cabeceras, cookies) y pulsa <strong>Forward</strong>. Para descartarla,
            <strong>Drop</strong>.</li>
    </ol>

    <h2>Herramientas que usarás</h2>
    <table class="tools">
        <tr><th>Repeater</th><td>Reenvía una petición manualmente tantas veces como quieras, editándola entre envíos
            (clic derecho → <em>Send to Repeater</em> o <kbd>Ctrl</kbd>+<kbd>R</kbd>). Permite agrupar pestañas y
            enviarlas <em>en paralelo</em> (útil para condiciones de carrera).</td></tr>
        <tr><th>Intruder</th><td>Automatiza peticiones variando una o más posiciones (§valor§) con listas de payloads:
            enumerar IDs, descubrir archivos, probar diccionarios.</td></tr>
        <tr><th>Decoder</th><td>Codifica/decodifica Base64, URL, Hex, HTML… Ideal para inspeccionar cookies y tokens.</td></tr>
        <tr><th>Comparer</th><td>Compara dos peticiones o respuestas palabra a palabra o byte a byte.</td></tr>
        <tr><th>Target → Site map</th><td>Árbol de todo lo que el navegador ha pedido al servidor, incluidos JS y CSS.</td></tr>
        <tr><th>Turbo Intruder</th><td>(Extensión BApp) envía grandes volúmenes de peticiones muy rápido o sincronizadas.</td></tr>
    </table>

    <p><a class="btn" href="/">← Volver al portal</a></p>
</article>
<?php
page_footer();
