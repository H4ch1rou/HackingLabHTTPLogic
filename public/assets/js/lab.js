/*
 * lab.js — comportamiento común del panel de cada laboratorio:
 *   - desbloquear pistas (POST /api/hint.php)
 *   - verificar flags   (POST /api/verify.php)  → la comparación es SIEMPRE en el servidor
 *   - reiniciar el estado del laboratorio (POST /api/reset.php)
 */
(function () {
    'use strict';

    const panel = document.querySelector('.lab-panel');
    if (!panel) return;
    const labId = panel.dataset.lab;

    async function post(url, data) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data),
            credentials: 'same-origin',
        });
        return res.json();
    }

    // Pistas
    const hintBtn = panel.querySelector('.js-hint');
    const hintList = panel.querySelector('.hints');
    const hintCount = panel.querySelector('.hint-count');
    hintBtn && hintBtn.addEventListener('click', async () => {
        hintBtn.disabled = true;
        try {
            const r = await post('/api/hint.php', { lab_id: labId });
            if (r.ok) {
                const li = document.createElement('li');
                const strong = document.createElement('strong');
                strong.textContent = 'Pista ' + r.index + ': ';
                li.append(strong, document.createTextNode(r.text));
                hintList.appendChild(li);
                hintCount.textContent = r.used;
            }
            hintBtn.disabled = r.used >= r.total;
        } catch (e) {
            hintBtn.disabled = false;
        }
    });

    // Verificación de flag
    const form = panel.querySelector('.js-verify');
    const out = panel.querySelector('.verify-result');
    form && form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        out.className = 'verify-result';
        out.textContent = 'Verificando…';
        try {
            const r = await post('/api/verify.php', { lab_id: labId, flag: form.flag.value });
            out.textContent = r.message;
            out.classList.add(r.ok ? 'ok' : 'bad');
        } catch (e) {
            out.textContent = 'Error de comunicación con el servidor.';
            out.classList.add('bad');
        }
    });

    // Reinicio del estado del laboratorio
    const resetBtn = panel.querySelector('.js-reset');
    resetBtn && resetBtn.addEventListener('click', async () => {
        if (!confirm('¿Reiniciar el estado de este laboratorio? (no borra tu progreso ni pistas)')) return;
        await post('/api/reset.php', { lab_id: labId });
        location.href = location.pathname.replace(/[^/]*$/, '');
    });
})();
