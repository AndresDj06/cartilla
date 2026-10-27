<?php $errores = $GLOBALS['_errores'] ?? []; ?>
<main id="principal" class="flujo-grid">
  <aside class="etiqueta" aria-label="Cuentas de demostración">
    <div class="etiqueta__papel">
      <p class="etiqueta__marca">Para el jurado</p>
      <dl>
        <div><dt>Docente</dt><dd><?= e($demo['docente']['usuario']) ?></dd></div>
        <div><dt>Estudiante</dt><dd><?= e($demo['estudiante']['usuario']) ?></dd></div>
        <div><dt>Clave</dt><dd><?= e($demo['clave']) ?></dd></div>
        <div><dt>Clase</dt><dd>Código <?= e($demo['codigo_clase']) ?></dd></div>
      </dl>
    </div>
  </aside>
  <section class="pregunta-papel" aria-labelledby="t-ingreso">
    <div class="pregunta-papel__cuerpo">
      <header class="avance"><a class="avance__atras" href="<?= url('/') ?>">← Inicio</a></header>
      <form method="post" action="<?= url('/ingresar') ?>" class="pregunta" novalidate data-cargando="Ingresando">
        <?= csrf_field() ?>
        <h1 id="t-ingreso">Ingresar</h1>
        <p class="por-que">Con tu correo o tu nombre de usuario, en el mismo campo.</p>
        <?php if (isset($errores['general'])): ?>
          <div class="resumen-errores" role="alert" tabindex="-1" data-enfocar><h2>No pudimos ingresar</h2><p><?= e($errores['general']) ?></p></div>
        <?php endif; ?>
        <div class="campo <?= isset($errores['identificador']) ? 'campo--error' : '' ?>">
          <label for="identificador">Correo o usuario</label>
          <input id="identificador" name="identificador" autocomplete="username" autocapitalize="none" spellcheck="false" required value="<?= old('identificador') ?>" <?= isset($errores['identificador']) ? 'aria-invalid="true" aria-describedby="e-identificador"' : '' ?>>
          <?php if (isset($errores['identificador'])): ?><p class="error" id="e-identificador"><?= e($errores['identificador']) ?></p><?php endif; ?>
        </div>
        <div class="campo <?= isset($errores['clave']) ? 'campo--error' : '' ?>">
          <label for="clave">Contraseña</label>
          <div class="clave-envoltura">
            <input id="clave" name="clave" type="password" autocomplete="current-password" required <?= isset($errores['clave']) ? 'aria-invalid="true" aria-describedby="e-clave"' : '' ?>>
            <button type="button" data-ver-clave aria-controls="clave" aria-pressed="false">Mostrar</button>
          </div>
          <?php if (isset($errores['clave'])): ?><p class="error" id="e-clave"><?= e($errores['clave']) ?></p><?php endif; ?>
        </div>
        <div class="pregunta__acciones"><button type="submit" class="boton">Ingresar</button></div>
        <p class="nota-paso">¿No tienes cuenta? <a href="<?= url('/empezar') ?>">Empieza sin registrarte</a> y créala cuando quieras guardar tu avance.</p>
      </form>
    </div>
  </section>
  <?php App\Core\View::parte('lamina', ['fotoClave' => 'objetivo']); ?>
</main>
