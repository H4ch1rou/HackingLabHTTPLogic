<?php
/** Portal principal: laboratorios por categoría + progreso del alumno. */
require_once 'bootstrap.php';

$student  = require_student();
$progress = student_progress((int) $student['id']);
$done     = completed_count($progress);
$total    = count(labs());
$pct      = $total ? round($done * 100 / $total) : 0;

page_header('Portal');
?>
<section class="hero">
    <h1>Laboratorio de Seguridad Web</h1>
    <p>Practica el protocolo HTTP y la seguridad de aplicaciones con <strong>Burp Suite</strong>: intercepta, analiza y
       modifica peticiones para descubrir qué permite <em>realmente</em> cada aplicación. Las categorías son
       independientes: empieza por donde quieras.</p>
    <div class="progress-wrap">
        <div class="progress-label"><strong><?= $done ?> / <?= $total ?></strong> laboratorios completados</div>
        <div class="progress"><div class="progress-bar" style="width: <?= $pct ?>%"></div></div>
    </div>
    <p class="muted small">Tu token de alumno es <code class="mono"><?= h($student['token']) ?></code> — anótalo para
        retomar tu progreso desde otro navegador.</p>
</section>

<?php foreach (labs_by_category() as $catKey => $catLabs): $cat = CATEGORIES[$catKey]; ?>
    <section class="category">
        <h2><?= $cat['icon'] ?> <?= h($cat['name']) ?> <small class="muted">(<?= count($catLabs) ?> labs)</small></h2>
        <p class="muted"><?= h($cat['desc']) ?></p>
        <div class="cards">
            <?php foreach ($catLabs as $id => $lab): $p = $progress[$id]; $ok = $p['completed_at'] !== null; ?>
                <article class="card <?= $ok ? 'done' : '' ?>">
                    <div class="card-top">
                        <span class="lab-id"><?= h($id) ?></span>
                        <span class="status"><?= $ok ? '✅ Completado' : '⬜ Pendiente' ?></span>
                    </div>
                    <h3><?= h($lab['title']) ?></h3>
                    <div class="card-badges">
                        <span class="badge sector"><?= h($lab['sector']) ?></span>
                        <?= difficulty_badge($lab['difficulty']) ?>
                    </div>
                    <p class="muted small"><?= h($lab['objective']) ?></p>
                    <div class="card-foot">
                        <span class="muted small">💡 Pistas usadas: <?= $p['hints_used'] ?>/<?= count($lab['hints']) ?></span>
                        <a class="btn btn-sm" href="<?= h($lab['url']) ?>">Entrar →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<section class="danger-zone">
    <form method="post" action="/api/reset.php" onsubmit="return confirm('¿Borrar TODO tu progreso, pistas y estado de los labs?');">
        <input type="hidden" name="scope" value="all">
        <button class="btn btn-ghost btn-sm" type="submit">↺ Reiniciar todo mi progreso</button>
    </form>
</section>
<?php
page_footer();
