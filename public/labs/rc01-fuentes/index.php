<?php
/**
 * RC-01 · Fugas en el código fuente (Corporativo, Básico)
 *
 * VULNERABILIDAD INTENCIONAL: un comentario HTML contiene credenciales de QA y
 * js/app.js contiene la ruta de un panel heredado (/admin-legacy/). Con ambas
 * piezas se accede al panel, donde está la flag.
 */
require_once 'bootstrap.php';

[$lab, $student] = lab_begin('RC-01');

page_header($lab['title']);
lab_intro($lab);
app_open('🛡️ Andes Seguros', 'Protegemos lo que más quieres');
?>
<!-- ============================================================
     Sitio institucional v3.2 — build 2024.03.18
     TODO (Seba): quitar antes de pasar a producción!!
     Cuenta de QA para el panel antiguo: qa_tester / Andes#QA2024
     ============================================================ -->
<div class="grid-2">
    <div>
        <h2>Seguros para cada etapa de tu vida</h2>
        <p>Con más de 40 años de experiencia, Andes Seguros acompaña a más de 300.000 familias con seguros de hogar,
           auto, vida y salud. Cotiza en línea en menos de 5 minutos.</p>
        <p><button class="btn" id="cotizar">Cotizar ahora</button></p>
        <p id="cotizar-msg" class="muted small"></p>
    </div>
    <div class="tile">
        <h3>Nuestros productos</h3>
        <ul>
            <li>🏠 Seguro de Hogar Integral</li>
            <li>🚗 Seguro Automotriz Full Cobertura</li>
            <li>❤️ Seguro de Vida Familiar</li>
            <li>🩺 Seguro Complementario de Salud</li>
        </ul>
        <!-- <li><a href="/intranet/">Intranet colaboradores</a></li>  (deshabilitado: migración a SSO) -->
    </div>
</div>
<p class="muted small" style="margin-top:1.5rem">© 2024 Andes Seguros S.A. · Todos los derechos reservados.</p>
<script src="js/app.js"></script>
<?php
app_close();
lab_panel($lab, $student);
page_footer();
