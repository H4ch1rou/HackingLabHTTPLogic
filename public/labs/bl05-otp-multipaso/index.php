<?php
/**
 * BL-05 · Salto de verificación OTP en flujo multi-paso (Banca/Fintech, Avanzado)
 *
 * Flujo: step=1 (datos) → step=2 (OTP) → step=3 (confirmación).
 *
 * VULNERABILIDAD INTENCIONAL: el paso 3 solo comprueba que exista una operación
 * pendiente; NO comprueba que el OTP haya sido validado (`otp_verified`).
 * Basta con enviar step=3 después del paso 1.
 *
 * El OTP es aleatorio y nunca se muestra (el «SMS» no llega), y se cancela la
 * operación tras 3 intentos fallidos, para que la fuerza bruta no sea el camino.
 */
require_once 'bootstrap.php';

[$lab, $student] = lab_begin('BL-05');
$sid = (int) $student['id'];
$L   = $lab['id'];

$banks   = ['Banco Austral', 'Banco Sur', 'Banco Pacífico', 'Cooperativa Andina'];
$pending = state_get($sid, $L, 'pending');      // operación en curso o null
$view    = $pending ? 2 : 1;                     // paso a mostrar por defecto
$err     = null;
$result  = null;

if (is_post()) {
    $step = (int) ($_POST['step'] ?? 1);

    if ($step === 1) {
        $data = [
            'name'    => trim((string) ($_POST['name'] ?? '')),
            'rut'     => trim((string) ($_POST['rut'] ?? '')),
            'bank'    => (string) ($_POST['bank'] ?? ''),
            'account' => trim((string) ($_POST['account'] ?? '')),
            'email'   => trim((string) ($_POST['email'] ?? '')),
        ];
        if ($data['name'] === '' || $data['account'] === '' || !in_array($data['bank'], $banks, true)) {
            $err  = 'Completa nombre, banco y número de cuenta.';
            $view = 1;
        } else {
            $pending = [
                'data'         => $data,
                'otp_hash'     => password_hash((string) random_int(0, 999999), PASSWORD_DEFAULT),
                'otp_verified' => false,
                'attempts'     => 0,
                'ref'          => 'OP-' . strtoupper(bin2hex(random_bytes(3))),
            ];
            state_set($sid, $L, 'pending', $pending);
            $view = 2;
        }
    } elseif ($step === 2) {
        if (!$pending) {
            $err  = 'No hay una operación en curso. Comienza de nuevo.';
            $view = 1;
        } else {
            $pending['attempts']++;
            if (password_verify(trim((string) ($_POST['otp'] ?? '')), $pending['otp_hash'])) {
                $pending['otp_verified'] = true;
                state_set($sid, $L, 'pending', $pending);
                $view = 3;
            } elseif ($pending['attempts'] >= 3) {
                state_set($sid, $L, 'pending', null);
                $pending = null;
                $err  = 'Demasiados intentos fallidos. La operación fue cancelada por seguridad.';
                $view = 1;
            } else {
                state_set($sid, $L, 'pending', $pending);
                $err  = 'Código incorrecto. Te quedan ' . (3 - $pending['attempts']) . ' intento(s).';
                $view = 2;
            }
        }
    } elseif ($step === 3) {
        // ⚠️ VULN: solo se verifica que haya una operación pendiente.
        //          Falta: if (!$pending['otp_verified']) { rechazar }
        if (!$pending) {
            $err  = 'No hay una operación en curso. Comienza de nuevo.';
            $view = 1;
        } else {
            $result = $pending;
            state_set($sid, $L, 'pending', null);
            $list   = state_get($sid, $L, 'beneficiaries', []);
            $list[] = $pending['data'];
            state_set($sid, $L, 'beneficiaries', $list);
            $pending = null;
            $view    = 'done';
        }
    } elseif ($step === 0) { // cancelar
        state_set($sid, $L, 'pending', null);
        $pending = null;
        $view    = 1;
    }
}

$beneficiaries = state_get($sid, $L, 'beneficiaries', []);

page_header($lab['title']);
lab_intro($lab);
app_open('💸 PagoSur', 'Inscripción de nuevo beneficiario');
?>
<div class="steps">
    <span class="<?= $view === 1 ? 'on' : '' ?>">1 · Datos</span>
    <span class="<?= $view === 2 ? 'on' : '' ?>">2 · Código OTP</span>
    <span class="<?= $view === 3 || $view === 'done' ? 'on' : '' ?>">3 · Confirmación</span>
</div>
<?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif; ?>

<div class="grid-2">
<div class="tile">
<?php if ($view === 1): ?>
    <form method="post" class="stack">
        <input type="hidden" name="step" value="1">
        <label>Nombre del beneficiario <input name="name" required maxlength="80"></label>
        <label>RUT / Documento <input name="rut" maxlength="20" placeholder="11.111.111-1"></label>
        <label>Banco
            <select name="bank"><?php foreach ($banks as $b): ?><option><?= h($b) ?></option><?php endforeach; ?></select>
        </label>
        <label>N° de cuenta <input name="account" required maxlength="30"></label>
        <label>Correo (opcional) <input name="email" type="email" maxlength="80"></label>
        <button class="btn" type="submit">Continuar →</button>
    </form>

<?php elseif ($view === 2): ?>
    <p>📱 Enviamos un código de 6 dígitos por SMS al <strong>+56 9 ****-4821</strong>.</p>
    <p class="muted small">Operación <?= h($pending['ref']) ?> · Beneficiario: <?= h($pending['data']['name']) ?>
        (<?= h($pending['data']['bank']) ?>, cta. <?= h($pending['data']['account']) ?>)</p>
    <form method="post" class="stack">
        <input type="hidden" name="step" value="2">
        <label>Código OTP <input name="otp" inputmode="numeric" pattern="\d{6}" maxlength="6" required class="mono"></label>
        <button class="btn" type="submit">Validar código →</button>
    </form>
    <form method="post" style="margin-top:.6rem">
        <input type="hidden" name="step" value="0">
        <button class="btn btn-ghost btn-sm">Cancelar operación</button>
    </form>

<?php elseif ($view === 3): ?>
    <p>✅ Código verificado. Revisa los datos y confirma la inscripción.</p>
    <p><?= h($pending['data']['name']) ?> · <?= h($pending['data']['bank']) ?> · cta. <?= h($pending['data']['account']) ?></p>
    <form method="post">
        <input type="hidden" name="step" value="3">
        <button class="btn" type="submit">Confirmar inscripción</button>
    </form>

<?php else: /* done */ ?>
    <h3>🎉 Beneficiario inscrito</h3>
    <p>Operación <span class="mono"><?= h($result['ref']) ?></span>: <?= h($result['data']['name']) ?> fue agregado a tus destinatarios.</p>
    <?php if (!$result['otp_verified']): ?>
        <div class="restricted">
            🚨 <strong>Registro de auditoría:</strong> la operación se confirmó <em>sin</em> un OTP validado.
            <?= flag_box($L) ?>
        </div>
    <?php endif; ?>
    <p><a class="btn btn-ghost btn-sm" href="./">Inscribir otro</a></p>
<?php endif; ?>
</div>

<div class="tile">
    <h3>👥 Mis beneficiarios</h3>
    <?php if (!$beneficiaries): ?>
        <p class="muted">Aún no tienes beneficiarios inscritos.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Nombre</th><th>Banco</th><th>Cuenta</th></tr></thead>
            <tbody>
            <?php foreach ($beneficiaries as $b): ?>
                <tr><td><?= h($b['name']) ?></td><td><?= h($b['bank']) ?></td><td class="mono"><?= h($b['account']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</div>
<?php
app_close();
lab_panel($lab, $student);
page_footer();
