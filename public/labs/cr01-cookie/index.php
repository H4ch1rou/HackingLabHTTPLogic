<?php
/**
 * CR-01 · Cookie de sesión débil (Fintech, Intermedio)
 * Login + dashboard de FinPay. Ver _session.php para la vulnerabilidad.
 */
require_once 'bootstrap.php';
require_once __DIR__ . '/_session.php';

[$lab, $student] = lab_begin('CR-01');

// Usuarios de demostración (ficticios).
$users = ['juan' => ['pass' => 'juan123', 'role' => 'cliente']];

$err = null;
if (is_post()) {
    if (($_POST['action'] ?? '') === 'logout') {
        finpay_logout();
    } else {
        $u = strtolower(trim((string) ($_POST['user'] ?? '')));
        $p = (string) ($_POST['pass'] ?? '');
        if (isset($users[$u]) && hash_equals($users[$u]['pass'], $p)) {
            finpay_issue($u, $users[$u]['role']);
        } else {
            $err = 'Credenciales inválidas.';
        }
    }
}
$session = finpay_session();

page_header($lab['title']);
lab_intro($lab);
app_open('💳 FinPay', 'Pagos simples para todos');
?>
<?php if (!$session): ?>
    <div class="grid-2">
        <div class="tile">
            <h3>Iniciar sesión</h3>
            <?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif; ?>
            <form method="post" class="stack">
                <label>Usuario <input name="user" autocomplete="off"></label>
                <label>Contraseña <input name="pass" type="password"></label>
                <button class="btn" type="submit">Ingresar</button>
            </form>
        </div>
        <div class="tile">
            <h3>Cuenta de demostración</h3>
            <p>Usuario: <code>juan</code><br>Contraseña: <code>juan123</code></p>
            <p class="muted small">Nuestro equipo diseñó un formato de sesión propio, «más liviano que los JWT».</p>
        </div>
    </div>
<?php else: ?>
    <div class="grid-2">
        <div class="tile">
            <p>Hola, <strong><?= h($session['user']) ?></strong> · rol: <span class="badge"><?= h($session['role']) ?></span></p>
            <div class="muted small">Saldo FinPay</div>
            <div class="balance">$1.284,30</div>
            <form method="post" style="margin-top:1rem">
                <input type="hidden" name="action" value="logout">
                <button class="btn btn-ghost btn-sm">Cerrar sesión</button>
            </form>
        </div>
        <div class="tile">
            <h3>Accesos</h3>
            <ul>
                <li>📄 Mis pagos (próximamente)</li>
                <li>👥 Mis contactos (próximamente)</li>
                <li>🛠️ <a href="admin.php">Panel de administración</a> <span class="muted small">(solo administradores)</span></li>
            </ul>
        </div>
    </div>
<?php endif; ?>
<?php
app_close();
lab_panel($lab, $student);
page_footer();
