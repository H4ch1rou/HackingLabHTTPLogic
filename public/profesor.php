<?php
/**
 * Panel del profesor: alumnos, labs completados, tiempos y pistas usadas.
 * Protegido con TEACHER_PASSWORD (variable de entorno).
 * Este panel NO forma parte de los retos: no es intencionalmente vulnerable.
 */
require_once 'bootstrap.php';

$error = null;

if (is_post() && isset($_POST['password'])) {
    if (hash_equals(TEACHER_PASSWORD, (string) $_POST['password'])) {
        session_regenerate_id(true);
        $_SESSION['teacher'] = true;
        redirect('/profesor.php');
    }
    $error = 'Contraseña incorrecta.';
}
if (isset($_GET['salir'])) {
    unset($_SESSION['teacher']);
    redirect('/profesor.php');
}

if (!is_teacher()) {
    page_header('Panel del profesor');
    ?>
    <section class="box narrow">
        <h1>🎓 Panel del profesor</h1>
        <?php if ($error): ?><p class="err"><?= h($error) ?></p><?php endif; ?>
        <form method="post" class="stack">
            <label>Contraseña (variable <code>TEACHER_PASSWORD</code>)
                <input type="password" name="password" required autofocus>
            </label>
            <button class="btn" type="submit">Entrar</button>
        </form>
    </section>
    <?php
    page_footer();
    exit;
}

// Acciones del profesor sobre un alumno.
if (is_post() && isset($_POST['action'], $_POST['student_id'])) {
    $sid = (int) $_POST['student_id'];
    if ($_POST['action'] === 'reset') {
        reset_student($sid);
    } elseif ($_POST['action'] === 'delete') {
        reset_student($sid);
        db()->prepare('DELETE FROM students WHERE id = ?')->execute([$sid]);
    }
    redirect('/profesor.php');
}

$labs     = labs();
$students = db()->query('SELECT * FROM students ORDER BY name COLLATE NOCASE')->fetchAll();
$rows     = [];
foreach ($students as $s) {
    $rows[] = ['student' => $s, 'progress' => student_progress((int) $s['id'])];
}

/** Minutos entre inicio y completado. */
function elapsed(?string $from, ?string $to): string
{
    if (!$from || !$to) {
        return '';
    }
    $m = (int) round((strtotime($to) - strtotime($from)) / 60);
    return $m < 60 ? "{$m} min" : sprintf('%dh %02dm', intdiv($m, 60), $m % 60);
}

// Exportación CSV.
if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="progreso-laboratorio.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
    fputcsv($out, ['alumno', 'token', 'lab', 'iniciado_utc', 'completado_utc', 'minutos', 'pistas_usadas', 'pistas_al_completar']);
    foreach ($rows as $r) {
        foreach ($labs as $id => $_) {
            $p = $r['progress'][$id];
            $hc = db()->prepare('SELECT hints_at_completion FROM progress WHERE student_id = ? AND lab_id = ?');
            $hc->execute([$r['student']['id'], $id]);
            $hintsAtCompletion = $hc->fetchColumn();
            fputcsv($out, [
                $r['student']['name'], $r['student']['token'], $id,
                $p['started_at'], $p['completed_at'],
                ($p['started_at'] && $p['completed_at']) ? round((strtotime($p['completed_at']) - strtotime($p['started_at'])) / 60) : '',
                $p['hints_used'], $hintsAtCompletion === false || $hintsAtCompletion === null ? '' : (int) $hintsAtCompletion,
            ]);
        }
    }
    exit;
}

page_header('Panel del profesor');
?>
<section class="hero">
    <h1>🎓 Panel del profesor</h1>
    <p class="muted"><?= count($students) ?> alumnos registrados ·
        <a href="?csv=1">⬇ Exportar CSV</a> · <a href="?salir=1">Cerrar panel</a></p>
    <p class="muted small">Celda: ✅ completado (tiempo desde la primera visita) · 💡 pistas usadas ·
        ⏳ iniciado sin completar · · no visitado. Horas en UTC.</p>
</section>

<div class="table-wrap">
<table class="matrix">
    <thead>
    <tr>
        <th>Alumno</th>
        <?php foreach ($labs as $id => $lab): ?><th title="<?= h($lab['title']) ?>"><?= h($id) ?></th><?php endforeach; ?>
        <th>Total</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="<?= count($labs) + 3 ?>" class="muted">Aún no hay alumnos.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): $s = $r['student']; ?>
        <tr>
            <td><strong><?= h($s['name']) ?></strong><br><code class="small"><?= h($s['token']) ?></code></td>
            <?php foreach ($labs as $id => $_): $p = $r['progress'][$id]; ?>
                <td class="<?= $p['completed_at'] ? 'cell-ok' : '' ?>"
                    title="<?= h('Inicio: ' . ($p['started_at'] ?? '—') . ' | Fin: ' . ($p['completed_at'] ?? '—')) ?>">
                    <?php if ($p['completed_at']): ?>
                        ✅ <span class="small"><?= h(elapsed($p['started_at'], $p['completed_at'])) ?></span>
                    <?php elseif ($p['started_at']): ?>
                        ⏳
                    <?php else: ?>
                        <span class="muted">·</span>
                    <?php endif; ?>
                    <?php if ($p['hints_used']): ?><br><span class="small">💡<?= (int) $p['hints_used'] ?></span><?php endif; ?>
                </td>
            <?php endforeach; ?>
            <td><strong><?= completed_count($r['progress']) ?>/<?= count($labs) ?></strong></td>
            <td class="nowrap">
                <form method="post" style="display:inline" onsubmit="return confirm('¿Reiniciar el progreso de este alumno?')">
                    <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
                    <button class="btn btn-ghost btn-sm" name="action" value="reset">↺</button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar a este alumno?')">
                    <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
                    <button class="btn btn-ghost btn-sm" name="action" value="delete">🗑</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php
page_footer();
