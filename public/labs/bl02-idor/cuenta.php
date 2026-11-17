<?php
/**
 * BL-02 · IDOR en estado de cuenta (Banca, Básico)
 *
 * VULNERABILIDAD INTENCIONAL: la cuenta se obtiene solo por `account_id`,
 * sin comprobar que pertenezca al cliente de la sesión. La cuenta del alumno
 * es la 1024; la de Gerencia General (marcada en la BD) contiene la flag.
 */
require_once 'bootstrap.php';

[$lab, $student] = lab_begin('BL-02');

$accountId = (int) ($_GET['account_id'] ?? 1024);

// ⚠️ VULN: falta "AND owner_id = <alumno de la sesión>".
$stmt = db()->prepare('SELECT * FROM bl02_accounts WHERE id = ?');
$stmt->execute([$accountId]);
$account = $stmt->fetch();

if (!$account) {
    http_response_code(404);
}

// Movimientos ficticios deterministas para que cada cuenta «se vea real».
function bl02_movements(int $id, float $balance): array
{
    $descs = ['Pago tarjeta de crédito', 'Compra supermercado', 'Transferencia recibida', 'Pago servicios básicos',
              'Giro cajero automático', 'Abono remuneración', 'Pago autopista', 'Compra farmacia'];
    $out = [];
    mt_srand($id);
    for ($i = 0; $i < 5; $i++) {
        $amt = (float) (mt_rand(5000, 450000) * (mt_rand(0, 2) ? -1 : 1));
        $out[] = ['date' => date('Y-m-d', strtotime('-' . ($i * 3 + mt_rand(0, 2)) . ' days')),
                  'desc' => $descs[mt_rand(0, count($descs) - 1)], 'amount' => $amt];
    }
    mt_srand();
    return $out;
}

page_header($lab['title']);
lab_intro($lab, false);
app_open('🏦 Banco Austral · Home Banking', 'Estado de cuenta');
?>
<p><a href="./">← Mis productos</a></p>

<?php if (!$account): ?>
    <p class="err">Cuenta N° <?= h($accountId) ?> no encontrada.</p>
<?php else:
    $holder = (int) $account['id'] === 1024 ? $student['name'] : $account['holder']; ?>
    <?php if ($account['is_target']): ?>
        <div class="restricted">
            ⭐ <strong>CUENTA RESTRINGIDA — Acceso exclusivo de Gerencia.</strong><br>
            Nota interna de auditoría: el código de control de esta cuenta es el siguiente.
            <?= flag_box($lab['id'], 'Código de auditoría interna:') ?>
        </div>
    <?php endif; ?>
    <div class="grid-2" style="margin-top:1rem">
        <div class="tile">
            <div class="muted small">N° de cuenta</div>
            <div class="mono" style="font-size:1.3rem"><?= h($account['id']) ?></div>
            <div class="muted small" style="margin-top:.6rem">Titular</div>
            <div><?= h($holder) ?></div>
            <div class="muted small" style="margin-top:.6rem">Producto · Sucursal</div>
            <div><?= h($account['type']) ?> · <?= h($account['branch']) ?></div>
        </div>
        <div class="tile">
            <div class="muted small">Saldo contable</div>
            <div class="balance"><?= money((float) $account['balance']) ?></div>
        </div>
    </div>
    <h3 style="margin-top:1.2rem">Últimos movimientos</h3>
    <table class="table">
        <thead><tr><th>Fecha</th><th>Descripción</th><th class="num">Monto</th></tr></thead>
        <tbody>
        <?php foreach (bl02_movements((int) $account['id'], (float) $account['balance']) as $m): ?>
            <tr><td class="small"><?= h($m['date']) ?></td><td><?= h($m['desc']) ?></td>
                <td class="num"><?= money($m['amount']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php
app_close();
lab_panel($lab, $student);
page_footer();
