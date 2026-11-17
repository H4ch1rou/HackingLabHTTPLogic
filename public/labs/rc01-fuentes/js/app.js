/*!
 * Andes Seguros — app.js v3.2.0
 * (c) 2024 Andes Seguros S.A.
 */
(function () {
    'use strict';

    var CONFIG = {
        apiBase: '/api/v2/',
        cotizadorUrl: '/cotizador/',
        analyticsId: 'AS-000-FICTICIO',
        // Panel antiguo de gestión de pólizas. Mantener hasta terminar la migración.
        // legacyAdmin: '/labs/rc01-fuentes/admin-legacy/',
        locale: 'es-CL'
    };

    function initCotizador() {
        var btn = document.getElementById('cotizar');
        if (!btn) return;
        btn.addEventListener('click', function () {
            document.getElementById('cotizar-msg').textContent =
                'El cotizador en línea estará disponible próximamente.';
        });
    }

    document.addEventListener('DOMContentLoaded', initCotizador);
    if (document.readyState !== 'loading') initCotizador();
    window.ANDES = { config: CONFIG, version: '3.2.0' };
})();
