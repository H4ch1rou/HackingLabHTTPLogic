<?php
/** Identificación del alumno: alta con nombre o retomar con token. */
require_once 'bootstrap.php';

$error = null;
$next  = $_GET['next'] ?? $_POST['next'] ?? '/';
// Solo redirecciones internas.
if (!is_string($next) || !str_starts_with($next, '/') || str_starts_with($next, '//')) {
    $next = '/';
}

if (is_post()) {
    $mode = $_POST['mode'] ?? '';
    if ($mode === 'new') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 60) {
            $error = 'Ingresa un nombre (máximo 60 caracteres).';
        } else {
            login_student(create_student($name));
            redirect($next);
        }
    } elseif ($mode === 'resume') {
        $student = find_student_by_token((string) ($_POST['token'] ?? ''));
        if (!$student) {
            $error = 'No existe un alumno con ese token.';
        } else {
            login_student($student);
            redirect($next);
        }
    }
}

page_header('Identificación');
?>
<section class="login-grid">
    <div class="box">
        <h1>👋 Bienvenido/a al laboratorio</h1>
        <p>Identifícate para registrar tu progreso. Cada alumno recibe un <strong>token</strong> único: de él se derivan
           <em>tus</em> flags, así que compartir una respuesta no le sirve a nadie más.</p>
        <?php if ($error): ?><p class="err"><?= h($error) ?></p><?php endif; ?>

        <form method="post" class="stack">
            <input type="hidden" name="mode" value="new">
            <input type="hidden" name="next" value="<?= h($next) ?>">
            <label>Nombre y apellido (o tu usuario del curso)
                <input type="text" name="name" maxlength="60" required autofocus>
            </label>
            <button class="btn" type="submit">Comenzar</button>
        </form>
    </div>
    <div class="box">
        <h2>¿Ya tienes un token?</h2>
        <p class="muted">Retoma tu progreso desde otro navegador o equipo.</p>
        <form method="post" class="stack">
            <input type="hidden" name="mode" value="resume">
            <input type="hidden" name="next" value="<?= h($next) ?>">
            <label>Token de alumno
                <input type="text" name="token" class="mono" placeholder="p. ej. 3fa9c01b7d22" required>
            </label>
            <button class="btn btn-ghost" type="submit">Retomar</button>
        </form>
        <p class="muted small" style="margin-top:2rem">¿Eres docente? <a href="/profesor.php">Panel del profesor</a></p>
    </div>
</section>
<?php
page_footer();
