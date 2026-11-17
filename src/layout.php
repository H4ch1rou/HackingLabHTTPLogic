<?php
/**
 * Plantillas compartidas: cabecera/pie del portal, cabecera de cada lab y
 * panel de pistas + verificación de flag.
 */

declare(strict_types=1);

const DIFFICULTY_CLASS = ['Básico' => 'diff-basic', 'Intermedio' => 'diff-mid', 'Avanzado' => 'diff-adv'];

function page_header(string $title, array $opts = []): void
{
    $student = current_student();
    $extraCss = $opts['css'] ?? '';
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · HTTP Lab</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if ($extraCss): ?><style><?= $extraCss ?></style><?php endif; ?>
</head>
<body>
<div class="warn-banner" role="alert">
    ⚠️ Entorno de entrenamiento <strong>intencionalmente vulnerable</strong> — no exponer a Internet.
</div>
<header class="topbar">
    <a class="brand" href="/">&gt;_ HTTP&nbsp;Lab</a>
    <nav>
        <a href="/">Portal</a>
        <a href="/help-burp.php">¿Cómo configuro Burp?</a>
        <?php if ($student): ?>
            <span class="who" title="Tu token: <?= h($student['token']) ?>">👤 <?= h($student['name']) ?></span>
            <a href="/logout.php">Salir</a>
        <?php else: ?>
            <a href="/login.php">Identificarme</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
    <?php
}

function page_footer(array $opts = []): void
{
    ?>
</main>
<footer class="footer">
    Laboratorio académico de seguridad web · Uso exclusivo en entornos controlados y con autorización.
</footer>
<script src="/assets/js/lab.js"></script>
<?php foreach ($opts['scripts'] ?? [] as $src): ?>
<script src="<?= h($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
    <?php
}

function difficulty_badge(string $d): string
{
    return '<span class="badge ' . (DIFFICULTY_CLASS[$d] ?? '') . '">' . h($d) . '</span>';
}

/** Cabecera de un laboratorio: nombre, categoría, sector, objetivo y consigna. */
function lab_intro(array $lab, bool $full = true): void
{
    $cat = CATEGORIES[$lab['category']];
    ?>
    <section class="lab-head">
        <div class="lab-meta">
            <span class="lab-id"><?= h($lab['id']) ?></span>
            <span class="badge cat"><?= $cat['icon'] ?> <?= h($cat['name']) ?></span>
            <span class="badge sector"><?= h($lab['sector']) ?></span>
            <?= difficulty_badge($lab['difficulty']) ?>
        </div>
        <h1><a href="<?= h($lab['url']) ?>"><?= h($lab['title']) ?></a></h1>
        <?php if ($full): ?>
            <p class="objective"><strong>🎯 Objetivo didáctico:</strong> <?= h($lab['objective']) ?></p>
            <p class="scenario"><strong>📋 Escenario:</strong> <?= h($lab['scenario']) ?></p>
        <?php endif; ?>
    </section>
    <?php
}

/** Marco visual de la «aplicación vulnerable» dentro de la página del lab. */
function app_open(string $appName, string $tagline = ''): void
{
    ?>
    <section class="app-frame">
        <div class="app-bar">
            <span class="dots"><i></i><i></i><i></i></span>
            <span class="app-name"><?= h($appName) ?></span>
            <?php if ($tagline): ?><span class="app-tag"><?= h($tagline) ?></span><?php endif; ?>
        </div>
        <div class="app-body">
    <?php
}

function app_close(): void
{
    echo "</div></section>\n";
}

/** Caja que muestra la flag tras un exploit exitoso. */
function flag_box(string $labId, string $text = '¡Lo lograste! Esta es tu flag:'): string
{
    return '<div class="flag-box"><div>🏁 ' . h($text) . '</div><code class="flag">'
        . h(current_flag($labId)) . '</code><div class="muted small">Cópiala en el campo «Verificar flag» del laboratorio.</div></div>';
}

/** Panel inferior: pistas escalonadas, verificación de flag y reinicio. */
function lab_panel(array $lab, array $student): void
{
    $sid      = (int) $student['id'];
    $revealed = revealed_hints($sid, $lab['id']);
    $total    = count($lab['hints']);
    $progress = student_progress($sid)[$lab['id']];
    $done     = $progress['completed_at'] !== null;
    ?>
    <section class="lab-panel" data-lab="<?= h($lab['id']) ?>">
        <div class="panel-col">
            <h2>💡 Pistas <small class="muted">(<span class="hint-count"><?= count($revealed) ?></span>/<?= $total ?> usadas)</small></h2>
            <ol class="hints">
                <?php foreach ($revealed as $i => $text): ?>
                    <li><strong>Pista <?= $i ?>:</strong> <?= h($text) ?></li>
                <?php endforeach; ?>
            </ol>
            <button class="btn btn-ghost js-hint" <?= count($revealed) >= $total ? 'disabled' : '' ?>>
                💡 Mostrar pista
            </button>
            <p class="muted small">Las pistas van de lo conceptual a lo concreto. El profesor puede ver cuántas usaste.</p>
        </div>
        <div class="panel-col">
            <h2>🏁 Verificar flag</h2>
            <?php if ($done): ?>
                <p class="ok">✅ Laboratorio completado el <?= h($progress['completed_at']) ?> UTC.</p>
            <?php endif; ?>
            <form class="js-verify verify-form" autocomplete="off">
                <input type="text" name="flag" placeholder="FLAG{................}" class="mono" required>
                <button class="btn" type="submit">Verificar</button>
            </form>
            <div class="verify-result" aria-live="polite"></div>
            <hr>
            <button class="btn btn-ghost btn-sm js-reset">↺ Reiniciar estado del laboratorio</button>
            <a class="btn btn-ghost btn-sm" href="/help-burp.php">🛠️ ¿Cómo configuro Burp?</a>
        </div>
    </section>
    <?php
}
