<?php
/**
 * BL-03 · Manipulación de precio y cupones en checkout (Retail, Intermedio)
 *
 * VULNERABILIDADES INTENCIONALES:
 *   1) Al agregar al carrito, el servidor guarda el `price` que envía el cliente
 *      en vez de usar el precio del catálogo.
 *   2) Al aplicar un cupón, el servidor usa el `discount` enviado por el cliente
 *      y no impide aplicar el mismo cupón varias veces (solo el JS lo bloquea).
 * La flag se revela al confirmar una compra con total <= 0.
 */
require_once 'bootstrap.php';

[$lab, $student] = lab_begin('BL-03');
$sid = (int) $student['id'];
$L   = $lab['id'];

$catalog = [
    101 => ['name' => 'Notebook Ultra 14"',          'price' => 1299.00, 'icon' => '💻'],
    102 => ['name' => 'Audífonos Bluetooth NC',       'price' => 189.90,  'icon' => '🎧'],
    103 => ['name' => 'Smartwatch Fit 3',             'price' => 249.50,  'icon' => '⌚'],
    104 => ['name' => 'Cable USB-C trenzado 2 m',     'price' => 19.90,   'icon' => '🔌'],
];
$validCoupons = ['BIENVENIDA10']; // el % "oficial" es 10, pero el servidor no lo usa…

$cart    = state_get($sid, $L, 'cart', []);
$coupons = state_get($sid, $L, 'coupons', []);
$msg = $err = null;
$order = null;

$action = $_POST['action'] ?? '';
if (is_post()) {
    switch ($action) {
        case 'add':
            $pid = (int) ($_POST['product_id'] ?? 0);
            $qty = (int) ($_POST['qty'] ?? 1);
            if (!isset($catalog[$pid])) {
                $err = 'Producto inexistente.';
            } elseif ($qty < 1 || $qty > 10) {
                $err = 'Cantidad inválida (1 a 10).';
            } else {
                // ⚠️ VULN 1: precio tomado de la petición.
                $price = (float) ($_POST['price'] ?? $catalog[$pid]['price']);
                $cart[] = ['pid' => $pid, 'name' => $catalog[$pid]['name'], 'price' => $price, 'qty' => $qty];
                $msg = 'Producto agregado al carrito.';
            }
            break;

        case 'coupon':
            $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
            if (!in_array($code, $validCoupons, true)) {
                $err = 'Cupón no válido.';
            } else {
                // ⚠️ VULN 2: % de descuento enviado por el cliente y cupón apilable.
                $discount = (float) ($_POST['discount'] ?? 10);
                $coupons[] = ['code' => $code, 'discount' => $discount];
                $msg = "Cupón $code aplicado.";
            }
            break;

        case 'clear':
            $cart = $coupons = [];
            break;

        case 'checkout':
            if (!$cart) {
                $err = 'Tu carrito está vacío.';
                break;
            }
            $order = ['id' => 'TM-' . random_int(100000, 999999), 'total' => null];
            break;
    }
}

// Totales calculados en el servidor… a partir de datos que vinieron del cliente.
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
$discPct  = array_sum(array_column($coupons, 'discount'));
$discount = round($subtotal * $discPct / 100, 2);
$total    = round($subtotal - $discount, 2);

if ($order) {
    $order['total'] = $total;
    $cart = $coupons = [];
}
state_set($sid, $L, 'cart', $cart);
state_set($sid, $L, 'coupons', $coupons);

page_header($lab['title']);
lab_intro($lab);
app_open('🛒 TecnoMarket', 'Tecnología al mejor precio');
?>
<?php if ($msg): ?><p class="notice"><?= h($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif; ?>

<?php if ($order): ?>
    <div class="tile">
        <h3>🧾 Pedido <?= h($order['id']) ?> confirmado</h3>
        <p>Total cobrado a tu tarjeta (simulado): <strong class="mono"><?= money($order['total']) ?></strong></p>
        <?php if ($order['total'] <= 0): ?>
            <p>🎓 ¡Cerraste una compra con total <?= money($order['total']) ?>! Aquí tienes tu certificado de comprador experto.</p>
            <?= flag_box($L, 'Certificado de comprador experto:') ?>
        <?php else: ?>
            <p class="muted">Gracias por tu compra. El premio es solo para pedidos con total $0 o menos… 😉</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<h3>Catálogo</h3>
<div class="products">
    <?php foreach ($catalog as $pid => $p): ?>
        <form method="post" class="tile product">
            <div style="font-size:2rem"><?= $p['icon'] ?></div>
            <strong><?= h($p['name']) ?></strong>
            <span class="price"><?= money($p['price']) ?></span>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= $pid ?>">
            <input type="hidden" name="price" value="<?= h(number_format($p['price'], 2, '.', '')) ?>">
            <label>Cantidad <input type="number" name="qty" value="1" min="1" max="10"></label>
            <button class="btn btn-sm" type="submit">Agregar al carrito</button>
        </form>
    <?php endforeach; ?>
</div>

<h3 style="margin-top:1.5rem">🛒 Tu carrito</h3>
<div class="grid-2">
    <div class="tile">
        <?php if (!$cart): ?>
            <p class="muted">El carrito está vacío.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Producto</th><th class="num">Cant.</th><th class="num">Precio</th><th class="num">Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($cart as $i): ?>
                    <tr><td><?= h($i['name']) ?></td><td class="num"><?= (int) $i['qty'] ?></td>
                        <td class="num"><?= money($i['price']) ?></td><td class="num"><?= money($i['price'] * $i['qty']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <form method="post" style="margin-top:.6rem">
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-ghost btn-sm">Vaciar carrito</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="tile">
        <form method="post" class="stack" id="coupon-form">
            <input type="hidden" name="action" value="coupon">
            <!-- El porcentaje se envía para mostrarlo en el resumen del pedido -->
            <input type="hidden" name="discount" value="10">
            <label>Cupón de descuento
                <input type="text" name="code" placeholder="BIENVENIDA10">
            </label>
            <button class="btn btn-ghost btn-sm" id="coupon-btn" type="submit"
                    <?= $coupons ? 'disabled title="Ya aplicaste un cupón"' : '' ?>>Aplicar cupón</button>
        </form>
        <table class="table" style="margin-top:1rem">
            <tr><td>Subtotal</td><td class="num"><?= money($subtotal) ?></td></tr>
            <?php foreach ($coupons as $c): ?>
                <tr><td>Cupón <?= h($c['code']) ?> (<?= h($c['discount']) ?>%)</td>
                    <td class="num">-<?= money($subtotal * $c['discount'] / 100) ?></td></tr>
            <?php endforeach; ?>
            <tr><th>Total</th><th class="num"><?= money($total) ?></th></tr>
        </table>
        <form method="post" style="margin-top:.8rem">
            <input type="hidden" name="action" value="checkout">
            <button class="btn" type="submit" <?= $cart ? '' : 'disabled' ?>>Confirmar compra</button>
        </form>
    </div>
</div>
<script>
    // "Protección" del lado del cliente: un solo cupón por pedido.
    document.getElementById('coupon-form').addEventListener('submit', function () {
        document.getElementById('coupon-btn').disabled = true;
    });
</script>
<?php
app_close();
lab_panel($lab, $student);
page_footer();
