<?php
/** CR-01 · Panel de administración: confía en el rol escrito en la cookie. */
require_once 'bootstrap.php';
require_once __DIR__ . '/_session.php';

[$lab, $student] = lab_begin('CR-01');
$session = finpay_session();
$isAdmin = $session && $session['role'] === 'admin';

if (!$isAdmin) {
    http_response_code($session ? 403 : 401);
}

page_header('Panel de administración');
lab_intro($lab, false);
app_open('💳 FinPay · Administración', 'Uso interno');
?>
<?php if (!$session): ?>
    <p class="err">401 · Debes iniciar sesión.</p>
    <p><a href="./">← Iniciar sesión</a></p>
<?php elseif (!$isAdmin): ?>
    <p class="err">403 · Acceso restringido a administradores. Tu rol actual es «<?= h($session['role']) ?>».</p>
    <p><a href="./">← Volver</a></p>
<?php else: ?>
    <p>Bienvenido/a, <strong><?= h($session['user']) ?></strong> (administrador).</p>
    <table class="table">
        <thead><tr><th>Indicador</th><th class="num">Valor</th></tr></thead>
        <tbody>
            <tr><td>Usuarios activos</td><td class="num">18.204</td></tr>
            <tr><td>Volumen transado hoy</td><td class="num">$48.120.330,00</td></tr>
            <tr><td>Alertas de fraude abiertas</td><td class="num">7</td></tr>
        </tbody>
    </table>
    <?= flag_box($lab['id'], 'Escalaste privilegios modificando la cookie. Tu flag:') ?>
    <p><a href="./">← Volver al laboratorio para verificar la flag</a></p>
<?php endif; ?>
<?php
app_close();
page_footer();
