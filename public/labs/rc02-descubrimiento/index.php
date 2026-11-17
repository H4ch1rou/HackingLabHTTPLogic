<?php
/**
 * RC-02 · Descubrimiento de contenido y archivos expuestos (Corporativo, Intermedio)
 *
 * VULNERABILIDADES INTENCIONALES (restos de despliegue):
 *   - /robots.txt revela /staging/ y /respaldo/.
 *   - /staging/ tiene directory listing (Options +Indexes) con un log de despliegue.
 *   - /respaldo/config.php.bak (y ~, .old, .orig, .save, .backup) devuelve el
 *     código fuente de config.php en texto plano, con la flag.
 *   - Cabeceras de respuesta que revelan versiones (ver .htaccess).
 */
require_once 'bootstrap.php';

[$lab, $student] = lab_begin('RC-02');

page_header($lab['title']);
lab_intro($lab);
app_open('🚚 Andes Logística · Portal de Proveedores', 'Acceso para proveedores registrados');
?>
<div class="grid-2">
    <div>
        <h2>Bienvenido al Portal de Proveedores</h2>
        <p>Consulta órdenes de compra, sube facturas y revisa el estado de tus pagos. Si aún no eres proveedor
           registrado, contacta a <span class="mono">proveedores@andeslogistica.example</span>.</p>
        <p class="muted small">Estamos finalizando la migración a nuestros nuevos servidores. Algunas funciones podrían
           no estar disponibles.</p>
    </div>
    <div class="tile">
        <h3>Ingreso de proveedores</h3>
        <form class="stack" onsubmit="event.preventDefault(); document.getElementById('m').textContent='Servicio en mantención por migración.';">
            <label>RUT empresa <input name="rut"></label>
            <label>Clave <input type="password" name="clave"></label>
            <button class="btn" type="submit">Ingresar</button>
            <p id="m" class="muted small"></p>
        </form>
    </div>
</div>
<?php
app_close();
lab_panel($lab, $student);
page_footer();
