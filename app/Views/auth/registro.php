<?php $errores = $GLOBALS['_errores'] ?? []; $rolOld = ($GLOBALS['_old']['rol'] ?? '') ?: $rolSugerido; ?>
<main id="principal" class="flujo-grid">
  <aside class="etiqueta" aria-label="Qué pasa al crear la cuenta">
    <div class="etiqueta__papel">
      <p class="etiqueta__marca">Tu cuenta</p>
      <dl>
        <div><dt>Avance</dt><dd><?= $xp ? e($xp . ' XP · ' . $racha . ($racha === 1 ? ' día' : ' días') . ' de racha') : 'Empieza en cero' ?></dd></div>
        <div><dt>Pasa</dt><dd>Todo lo que hiciste como invitado</dd></div>
        <div><dt>Correo</dt><dd>Solo para ingresar</dd></div>
        <div><dt>Control</dt><dd>Descargas o borras tus datos cuando quieras</dd></div>
      </dl>
    </div>
  </aside>
  <section class="pregunta-papel" aria-labelledby="t-registro">
    <div class="pregunta-papel__cuerpo">
      <header class="avance"><a class="avance__atras" href="<?= url('/ruta') ?>">← Seguir sin cuenta</a></header>
      <form method="post" action="<?= url('/registro') ?>" class="pregunta" novalidate data-cargando="Creando tu cuenta">
        <?= csrf_field() ?>
        <h1 id="t-registro">Guarda tu avance</h1>
        <p class="por-que">Con una cuenta conservas tu racha y tus repasos en cualquier dispositivo. Si eres docente, también puedes publicar tu clase con un código.</p>
        <?php if ($errores): ?>
          <div class="resumen-errores" role="alert" tabindex="-1" data-enfocar>
            <h2>Revisa <?= count($errores) === 1 ? 'este campo' : 'estos ' . count($errores) . ' campos' ?></h2>
            <ul><?php foreach ($errores as $c => $m): ?><li><a href="#<?= e($c) ?>"><?= e($m) ?></a></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>

        <fieldset class="elecciones elecciones--juegos" aria-label="Soy">
          <label class="eleccion eleccion--simple"><input type="radio" name="rol" id="rol" value="estudiante" <?= $rolOld !== 'docente' ? 'checked' : '' ?>><span class="eleccion__titulo">Estudiante</span></label>
          <label class="eleccion eleccion--simple"><input type="radio" name="rol" value="docente" <?= $rolOld === 'docente' ? 'checked' : '' ?>><span class="eleccion__titulo">Docente</span></label>
        </fieldset>

        <div class="campo <?= isset($errores['nombre']) ? 'campo--error' : '' ?>">
          <label for="nombre">Nombre completo</label>
          <input id="nombre" name="nombre" autocomplete="name" required value="<?= old('nombre') ?>" <?= isset($errores['nombre']) ? 'aria-invalid="true" aria-describedby="e-nombre"' : '' ?>>
          <?php if (isset($errores['nombre'])): ?><p class="error" id="e-nombre"><?= e($errores['nombre']) ?></p><?php endif; ?>
        </div>
        <div class="campo <?= isset($errores['usuario']) ? 'campo--error' : '' ?>">
          <label for="usuario">Nombre de usuario</label>
          <p class="ayuda" id="a-usuario">Con él también puedes ingresar. Letras sin tildes, números, punto o guion bajo.</p>
          <input id="usuario" name="usuario" autocomplete="username" autocapitalize="none" spellcheck="false" required value="<?= old('usuario') ?>" aria-describedby="a-usuario<?= isset($errores['usuario']) ? ' e-usuario' : '' ?>" <?= isset($errores['usuario']) ? 'aria-invalid="true"' : '' ?>>
          <?php if (isset($errores['usuario'])): ?><p class="error" id="e-usuario"><?= e($errores['usuario']) ?></p><?php endif; ?>
        </div>
        <div class="campo <?= isset($errores['correo']) ? 'campo--error' : '' ?>">
          <label for="correo">Correo</label>
          <input id="correo" name="correo" type="email" autocomplete="email" required value="<?= old('correo') ?>" <?= isset($errores['correo']) ? 'aria-invalid="true" aria-describedby="e-correo"' : '' ?>>
          <?php if (isset($errores['correo'])): ?><p class="error" id="e-correo"><?= e($errores['correo']) ?><?php if (str_contains($errores['correo'], 'ingresar')): ?> <a href="<?= url('/ingresar') ?>">Ingresar</a><?php endif; ?></p><?php endif; ?>
        </div>
        <div class="campo <?= isset($errores['clave']) ? 'campo--error' : '' ?>">
          <label for="clave">Contraseña</label>
          <p class="ayuda" id="a-clave">Mínimo 8 caracteres, con letras y números.</p>
          <div class="clave-envoltura">
            <input id="clave" name="clave" type="password" autocomplete="new-password" required minlength="8" aria-describedby="a-clave<?= isset($errores['clave']) ? ' e-clave' : '' ?>" <?= isset($errores['clave']) ? 'aria-invalid="true"' : '' ?>>
            <button type="button" data-ver-clave aria-controls="clave" aria-pressed="false">Mostrar</button>
          </div>
          <?php if (isset($errores['clave'])): ?><p class="error" id="e-clave"><?= e($errores['clave']) ?></p><?php endif; ?>
        </div>
        <div class="pregunta__acciones"><button type="submit" class="boton">Crear mi cuenta</button></div>
        <p class="nota-paso">¿Ya tienes cuenta? <a href="<?= url('/ingresar') ?>">Ingresa</a>. Al crearla aceptas que guardemos tus respuestas para tu práctica. <a href="<?= url('/privacidad') ?>">Qué datos guardamos</a>.</p>
      </form>
    </div>
  </section>
  <?php App\Core\View::parte('lamina', ['fotoClave' => 'rol']); ?>
</main>
