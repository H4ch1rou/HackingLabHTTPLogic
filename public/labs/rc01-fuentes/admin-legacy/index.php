<?php
/**
 * RC-01 · Panel heredado (ruta descubierta en js/app.js).
 * Credenciales de QA filtradas en un comentario HTML del sitio.
 */
require_once 'bootstrap.php';

[$lab, $student] = lab_begin('RC-01');

$err = null;
if (is_post()) {
    if (($_POST['user'] ?? '') === 'qa_tester' && ($_POST['pass'] ?? '') === 'Andes#QA2024') {
        $_SESSION['rc01_admin'] = true;
    } else {
        $err = 'Usuario o contraseña incorrectos.';
    }
}
if (isset($_GET['logout'])) {
    unset($_SESSION['rc01_admin']);
}
$logged = !empty($_SESSION['rc01_admin']);

page_header('Panel de gestión (legacy)');
lab_intro($lab, false);
app_open('🛡️ Andes Seguros · Gestión de Pólizas v1.7', 'Sistema heredado');
?>
<?php if (!$logged): ?>
    <div class="box narrow" style="margin:0 auto">
        <h3>Acceso restringido</h3>
        <?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif; ?>
        <form method="post" class="stack">
            <label>Usuario <input name="user" autocomplete="off"></label>
            <label>Contraseña <input name="pass" type="password"></label>
            <button class="btn" type="submit">Ingresar</button>
        </form>
    </div>
<?php else: ?>
    <p>Bienvenido, <strong>qa_tester</strong> (perfil: Control de Calidad). <a href="?logout=1">Cerrar sesión</a></p>
    <table class="table">
        <thead><tr><th>Póliza</th><th>Asegurado</th><th>Producto</th><th>Estado</th></tr></thead>
        <tbody>
            <tr><td class="mono">POL-88123</td><td>Asegurado de prueba 1</td><td>Hogar</td><td>Vigente</td></tr>
            <tr><td class="mono">POL-88124</td><td>Asegurado de prueba 2</td><td>Auto</td><td>En revisión</td></tr>
            <tr><td class="mono">POL-88125</td><td>Asegurado de prueba 3</td><td>Vida</td><td>Vigente</td></tr>
        </tbody>
    </table>
    <?= flag_box($lab['id'], 'Llegaste al panel olvidado. Tu flag:') ?>
    <p><a href="../">← Volver al laboratorio para verificar la flag</a></p>
<?php endif; ?>
<?php
app_close();
page_footer();
