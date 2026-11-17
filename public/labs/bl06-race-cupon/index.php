<?php
/** BL-06 · Race condition: doble canje de cupón — interfaz de la billetera. */
require_once 'bootstrap.php';

[$lab, $student] = lab_begin('BL-06');
$sid = (int) $student['id'];
$L   = $lab['id'];

$balance  = (float) state_get($sid, $L, 'balance', 0);
$redeemed = (bool) state_get($sid, $L, 'redeemed', false);
$history  = state_get($sid, $L, 'history', []);

page_header($lab['title']);
lab_intro($lab);
app_open('📱 BilleteraYa', 'Tu billetera digital');
?>
<div class="grid-2">
    <div class="tile">
        <div class="muted small">Saldo de la billetera</div>
        <div class="balance" id="balance"><?= money($balance) ?></div>
        <h3 style="margin-top:1rem">🎁 Canjear cupón</h3>
        <p class="small">Tienes un cupón de regalo: <code>REGALO50</code> ($50,00 · uso único).</p>
        <form id="redeem-form" class="stack">
            <label>Código <input name="code" value="REGALO50" class="mono"></label>
            <button class="btn" type="submit" id="redeem-btn" <?= $redeemed ? 'disabled' : '' ?>>Canjear</button>
        </form>
        <div id="redeem-result" class="small" style="margin-top:.6rem"></div>
    </div>
    <div class="tile">
        <h3>Movimientos</h3>
        <?php if (!$history): ?><p class="muted">Sin movimientos.</p><?php endif; ?>
        <ul class="small">
            <?php foreach ($history as $h): ?><li><?= h($h) ?></li><?php endforeach; ?>
        </ul>
        <?php if ($balance > 50): ?>
            <?= flag_box($L, 'Tu saldo supera el valor de un único cupón:') ?>
        <?php endif; ?>
    </div>
</div>
<script>
    document.getElementById('redeem-form').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const btn = document.getElementById('redeem-btn');
        const out = document.getElementById('redeem-result');
        btn.disabled = true;
        out.textContent = 'Validando cupón con el emisor…';
        const res = await fetch('canjear.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ code: ev.target.code.value }),
        });
        const r = await res.json();
        out.textContent = r.message;
        if (r.balance_fmt) document.getElementById('balance').textContent = r.balance_fmt;
        if (r.flag) setTimeout(() => location.reload(), 800);
    });
</script>
<?php
app_close();
lab_panel($lab, $student);
page_footer();
