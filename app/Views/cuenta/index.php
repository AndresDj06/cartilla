<?php $invitado = $u['rol'] === 'invitado'; ?>
<div class="pagina">
  <header class="titulo-fila"><div><h1><?= $invitado ? 'Tu avance como invitado' : 'Mi cuenta' ?></h1><p class="titulo-fila__sub num"><?= $xp ?> XP · <?= $racha ?> <?= $racha === 1 ? 'día' : 'días' ?> de racha · <?= $enRepaso ?> en repaso</p></div></header>
  <div class="rejilla-dos">
    <div>
      <?php if ($invitado): ?>
        <section class="bloque guardar" aria-labelledby="t-guardar" style="position:static">
          <h2 id="t-guardar">Guarda tu avance</h2>
          <p>Ahora tu avance vive solo en este navegador, con una cookie que dura 90 días. Si creas una cuenta, todo pasa a ella.</p>
          <a class="boton" href="<?= url('/registro') ?>">Crear mi cuenta</a>
          <p class="guardar__nota">¿Ya tienes una? <a href="<?= url('/ingresar') ?>">Ingresa</a>. Lo hecho como invitado no se mezcla con una cuenta existente.</p>
        </section>
      <?php else: ?>
        <section class="bloque" aria-labelledby="t-datos">
          <h2 id="t-datos" class="seccion-titulo">Tus datos</h2>
          <div class="tabla-envoltura">
            <table class="datos-tabla">
              <tbody>
                <tr><th scope="row">Nombre</th><td><?= e($u['nombre']) ?></td></tr>
                <tr><th scope="row">Usuario</th><td><?= e($u['usuario']) ?></td></tr>
                <tr><th scope="row">Correo</th><td><?= e($u['correo']) ?></td></tr>
                <tr><th scope="row">Rol</th><td><?= e(ucfirst($u['rol'])) ?></td></tr>
                <tr><th scope="row">Cuenta creada</th><td><?= e(fecha_corta($u['creado_en'])) ?></td></tr>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <section class="bloque" aria-labelledby="t-pref">
        <h2 id="t-pref" class="seccion-titulo">Tus respuestas iniciales</h2>
        <?php if ($decisiones): ?>
          <ul class="decisiones">
            <?php foreach ($decisiones as $d): ?><li><div><p class="decisiones__elegiste"><?= e($d['elegiste']) ?></p><p class="decisiones__efecto"><?= e($d['efecto']) ?></p></div></li><?php endforeach; ?>
          </ul>
          <a class="boton boton--suave" href="<?= url('/empezar/resumen') ?>">Cambiar mis respuestas</a>
        <?php else: ?>
          <div class="panel panel--vacio"><p>Aún no respondes las preguntas iniciales.</p><a href="<?= url('/empezar') ?>">Responderlas ahora</a></div>
        <?php endif; ?>
      </section>
    </div>

    <aside aria-labelledby="t-control">
      <h2 id="t-control" class="seccion-titulo">Tu control</h2>
      <?php if (!$invitado): ?>
        <form method="post" action="<?= url('/salir') ?>" class="panel" style="margin-bottom:16px"><?= csrf_field() ?><button type="submit" class="boton boton--suave boton--ancho">Cerrar sesión</button></form>
      <?php endif; ?>
      <div class="panel">
        <p>Descarga en un archivo todo lo que guardamos de ti: perfil, respuestas, XP y repasos.</p>
        <a class="boton boton--suave boton--ancho" href="<?= url('/cuenta/datos') ?>" download>Descargar mis datos (JSON)</a>
      </div>
      <div class="panel" style="margin-top:16px">
        <p><b>Borrar <?= $invitado ? 'mi avance' : 'mi cuenta' ?>.</b> Elimina tu historial, tus respuestas y las clases que creaste. No se puede deshacer.</p>
        <?php if ($u['rol'] === 'superadmin'): ?>
          <p class="secundario">La cuenta de super administrador no se borra desde aquí.</p>
        <?php else: ?>
          <form method="post" action="<?= url('/cuenta/borrar') ?>" data-confirmar="Esto borra todo tu historial y no se puede deshacer. ¿Continuar?">
            <?= csrf_field() ?>
            <?php if (!$invitado): ?>
              <div class="campo"><label for="clave-borrar">Tu contraseña, para confirmar</label><input id="clave-borrar" name="clave" type="password" autocomplete="current-password" required></div>
            <?php endif; ?>
            <button type="submit" class="boton boton--peligro boton--ancho">Borrar todo</button>
          </form>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</div>
