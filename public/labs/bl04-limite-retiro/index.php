<?php
/**
 * BL-04 · Bypass de límite diario de retiro (Banca, Intermedio)
 *
 * VULNERABILIDAD INTENCIONAL: el límite diario se valida con JavaScript y con
 * un campo oculto `max_limit` que el servidor usa tal cual llega. La política
 * oficial (500) solo se usa para decidir si mostrar la flag.
 */
require_once 'bootstrap.php';

[$lab, $student] = lab_begin('BL-04');
$sid = (int) $student['id'];
$L   = $lab['id'];

const BL04_OFFICIAL_LIMIT = 500.00;

$balance   = state_get($sid, $L, 'balance', 25000.00);
$withdrawn = state_get($sid, $L, 'withdrawn_today', 0.0);
$receipt   = null;
$err       = null;

if (is_post()) {
    $amount = (float) ($_POST['amount'] ?? 0);
    // ⚠️ VULN: el servidor confía en el límite que envía el cliente.
    $limit  = (float) ($_POST['max_limit'] ?? BL04_OFFICIAL_LIMIT);

    if ($amount <= 0) {
        $err = 'El monto debe ser mayor que cero.';
    } elseif ($amount > $balance) {
        $err = 'Saldo insuficiente.';
    } elseif ($withdrawn + $amount > $limit) {
        $err = 'Operación rechazada: supera tu límite diario de ' . money($limit)
             . ' (ya retiraste ' . money($withdrawn) . ' hoy).';
    } else {
        $balance   -= $amount;
        $withdrawn += $amount;
        state_set($sid, $L, 'balance', $balance);
        state_set($sid, $L, 'withdrawn_today', $withdrawn);
        $receipt = ['code' => strtoupper(bin2hex(random_bytes(4))), 'amount' => $amount];
    }
}
$remaining = max(0, BL04_OFFICIAL_LIMIT - $withdrawn);

page_header($lab['title']);
lab_intro($lab);
app_open('🏧 Banco Austral · Cajero Web', 'Retiro sin tarjeta');
?>
<div class="grid-2">
    <div class="tile">
        <div class="muted small">Saldo disponible</div>
        <div class="balance"><?= money($balance) ?></div>
        <p class="small">Límite diario de retiro: <strong><?= money(BL04_OFFICIAL_LIMIT) ?></strong> ·
            Retirado hoy: <strong><?= money($withdrawn) ?></strong></p>

        <?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif; ?>

        <form method="post" class="stack" onsubmit="return validarRetiro(this);">
            <input type="hidden" name="max_limit" value="<?= h(number_format(BL04_OFFICIAL_LIMIT, 2, '.', '')) ?>">
            <label>Monto a retirar (máximo <?= money($remaining) ?> hoy)
                <input type="number" name="amount" step="1" min="1" max="<?= h($remaining) ?>" required>
            </label>
            <button class="btn" type="submit" <?= $remaining <= 0 ? 'disabled' : '' ?>>Generar código de retiro</button>
            <p class="err" id="client-error" hidden></p>
        </form>
    </div>
    <div class="tile">
        <h3>🧾 Comprobante</h3>
        <?php if ($receipt): ?>
            <p>Código de retiro: <code class="mono"><?= h($receipt['code']) ?></code><br>
               Monto: <strong><?= money($receipt['amount']) ?></strong></p>
            <?php if ($withdrawn > BL04_OFFICIAL_LIMIT): ?>
                <div class="restricted">
                    🚨 <strong>Alerta de riesgo:</strong> los retiros de hoy (<?= money($withdrawn) ?>) superan el
                    límite oficial de <?= money(BL04_OFFICIAL_LIMIT) ?>.
                    <?= flag_box($L) ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="muted">Aquí aparecerá tu código de retiro.</p>
        <?php endif; ?>
    </div>
</div>
<script>
    function validarRetiro(f) {
        const limite = parseFloat(f.max_limit.value);
        const retirado = <?= json_encode((float) $withdrawn) ?>;
        const monto = parseFloat(f.amount.value);
        const box = document.getElementById('client-error');
        let e = '';
        if (!(monto > 0)) e = 'Monto inválido.';
        else if (retirado + monto > limite) e = 'Superas tu límite diario.';
        box.hidden = !e; box.textContent = e;
        return !e;
    }
</script>
<?php
app_close();
lab_panel($lab, $student);
page_footer();
