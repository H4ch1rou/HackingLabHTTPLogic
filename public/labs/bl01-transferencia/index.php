<?php
/**
 * BL-01 · Transferencia con monto manipulado (Banca, Básico)
 *
 * VULNERABILIDAD INTENCIONAL: el servidor confía en `amount` tal como llega.
 * Solo el navegador (JavaScript + atributos min/max) valida que sea positivo y
 * menor o igual al saldo. Un monto negativo invierte el flujo: acredita.
 */
require_once 'bootstrap.php';

[$lab, $student] = lab_begin('BL-01');
$sid = (int) $student['id'];
$L   = $lab['id'];

const BL01_INITIAL   = 1500.00;
const BL01_THRESHOLD = 100000.00; // saldo «Platinum», imposible por la vía legítima

$beneficiaries = [
    '7720-3341' => 'Inmobiliaria Los Robles SpA — Arriendo',
    '5512-0098' => 'Constanza Pérez (Banco Sur)',
    '9031-7764' => 'Colegio San Andrés — Mensualidad',
];

// Estado inicial.
$balance = state_get($sid, $L, 'balance');
if ($balance === null) {
    $balance = BL01_INITIAL;
    state_set($sid, $L, 'balance', $balance);
    state_set($sid, $L, 'movements', [
        ['date' => date('Y-m-d', strtotime('-3 days')), 'desc' => 'Abono remuneración', 'amount' => 1500.00],
    ]);
}
$movements = state_get($sid, $L, 'movements', []);

$msg = $err = null;
if (is_post() && ($_POST['action'] ?? '') === 'transfer') {
    $to     = (string) ($_POST['to_account'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);

    if (!isset($beneficiaries[$to])) {
        $err = 'Cuenta de destino no válida.';
    } elseif ($amount == 0) {
        $err = 'Ingresa un monto.';
    } else {
        // ⚠️ VULN: no se verifica que amount > 0 ni que amount <= saldo.
        $balance -= $amount;
        array_unshift($movements, [
            'date'   => date('Y-m-d H:i'),
            'desc'   => 'Transferencia a ' . $beneficiaries[$to],
            'amount' => -$amount,
        ]);
        state_set($sid, $L, 'balance', $balance);
        state_set($sid, $L, 'movements', array_slice($movements, 0, 15));
        $msg = 'Transferencia de ' . money($amount) . ' realizada con éxito.';
    }
}

page_header($lab['title']);
lab_intro($lab);
app_open('🏦 Banco Austral · Banca en Línea', 'Transferencias a terceros');
?>
<div class="grid-2">
    <div class="tile">
        <div class="muted small">Cuenta Corriente N° 0012-4455-01 · Titular: <?= h($student['name']) ?></div>
        <div class="balance <?= $balance < 0 ? 'neg' : '' ?>"><?= money($balance) ?></div>
        <div class="muted small">Saldo disponible</div>

        <?php if ($msg): ?><p class="notice"><?= h($msg) ?></p><?php endif; ?>
        <?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif; ?>

        <h3 style="margin-top:1rem">Nueva transferencia</h3>
        <form method="post" id="transfer-form" class="stack" onsubmit="return validarTransferencia();">
            <input type="hidden" name="action" value="transfer">
            <label>Cuenta de destino
                <select name="to_account">
                    <?php foreach ($beneficiaries as $acc => $name): ?>
                        <option value="<?= h($acc) ?>"><?= h($acc . ' · ' . $name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Monto
                <input type="number" name="amount" id="amount" step="0.01" min="0.01"
                       max="<?= h(max(0, $balance)) ?>" required>
            </label>
            <button class="btn" type="submit">Transferir</button>
            <p class="err" id="client-error" hidden></p>
        </form>
    </div>

    <div class="tile">
        <h3>📄 Estado de cuenta</h3>
        <?php if ($balance >= BL01_THRESHOLD): ?>
            <div class="restricted">
                💎 <strong>¡Felicitaciones! Eres Cliente Platinum.</strong> Tu saldo supera <?= money(BL01_THRESHOLD) ?>.
                <?= flag_box($L, 'Código de beneficio Platinum:') ?>
            </div>
        <?php endif; ?>
        <table class="table">
            <thead><tr><th>Fecha</th><th>Descripción</th><th class="num">Monto</th></tr></thead>
            <tbody>
            <?php foreach ($movements as $m): ?>
                <tr>
                    <td class="nowrap small"><?= h($m['date']) ?></td>
                    <td><?= h($m['desc']) ?></td>
                    <td class="num"><?= money((float) $m['amount']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Validación del lado del cliente (¡la única que existe!).
    const SALDO = <?= json_encode((float) $balance) ?>;
    function validarTransferencia() {
        const monto = parseFloat(document.getElementById('amount').value);
        const box = document.getElementById('client-error');
        let error = '';
        if (isNaN(monto) || monto <= 0) error = 'El monto debe ser mayor que cero.';
        else if (monto > SALDO) error = 'Saldo insuficiente.';
        box.hidden = !error;
        box.textContent = error;
        return !error;
    }
</script>
<?php
app_close();
lab_panel($lab, $student);
page_footer();
