<?php
/**
 * CR-02 · JWT con firma débil / alg:none (Fintech, Avanzado)
 * Cliente web de la API (vanilla JS). La vulnerabilidad está en _jwt.php.
 */
require_once 'bootstrap.php';

[$lab, $student] = lab_begin('CR-02');

page_header($lab['title']);
lab_intro($lab);
app_open('📈 CapitalNet', 'API de inversiones · cliente web');
?>
<div class="grid-2">
    <div class="tile">
        <h3>1 · Autenticación</h3>
        <form id="login-form" class="stack">
            <label>Usuario <input name="username" value="ana"></label>
            <label>Contraseña <input name="password" type="password" value="ana2024"></label>
            <button class="btn" type="submit">Obtener token</button>
        </form>
        <p class="muted small">Cuenta de demostración: <code>ana</code> / <code>ana2024</code></p>
        <h3 style="margin-top:1rem">Token actual</h3>
        <pre id="token" class="small" style="white-space:pre-wrap;word-break:break-all">(sin token)</pre>
    </div>
    <div class="tile">
        <h3>2 · Consultas a la API</h3>
        <p>
            <button class="btn btn-sm" id="btn-perfil">GET api/perfil.php</button>
            <button class="btn btn-ghost btn-sm" id="btn-admin">GET api/admin.php</button>
        </p>
        <pre id="out" class="small" style="white-space:pre-wrap;min-height:10rem">Las respuestas aparecerán aquí.</pre>
    </div>
</div>
<script>
(function () {
    const tokenBox = document.getElementById('token');
    const out = document.getElementById('out');
    let token = sessionStorage.getItem('capitalnet_token') || '';
    if (token) tokenBox.textContent = token;

    document.getElementById('login-form').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const f = ev.target;
        const res = await fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: f.username.value, password: f.password.value }),
        });
        const data = await res.json();
        out.textContent = res.status + ' ' + res.statusText + '\n' + JSON.stringify(data, null, 2);
        if (data.token) {
            token = data.token;
            sessionStorage.setItem('capitalnet_token', token);
            tokenBox.textContent = token;
        }
    });

    async function call(path) {
        const res = await fetch(path, { headers: token ? { 'Authorization': 'Bearer ' + token } : {} });
        const data = await res.json();
        out.textContent = 'HTTP ' + res.status + '\n' + JSON.stringify(data, null, 2);
    }
    document.getElementById('btn-perfil').addEventListener('click', () => call('api/perfil.php'));
    document.getElementById('btn-admin').addEventListener('click', () => call('api/admin.php'));
})();
</script>
<?php
app_close();
lab_panel($lab, $student);
page_footer();
