<?php
/** BL-02 · IDOR en estado de cuenta — página de inicio del home banking. */
require_once 'bootstrap.php';

[$lab, $student] = lab_begin('BL-02');

page_header($lab['title']);
lab_intro($lab);
app_open('🏦 Banco Austral · Home Banking', 'Mis productos');
?>
<p>Hola, <strong><?= h($student['name']) ?></strong>. Estos son tus productos:</p>
<table class="table">
    <thead><tr><th>Producto</th><th>N° de cuenta</th><th></th></tr></thead>
    <tbody>
    <tr>
        <td>Cuenta Corriente</td>
        <td class="mono">1024</td>
        <td><a class="btn btn-sm" href="cuenta.php?account_id=1024">Ver estado de cuenta</a></td>
    </tr>
    </tbody>
</table>
<?php
app_close();
lab_panel($lab, $student);
page_footer();
