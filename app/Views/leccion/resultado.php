<?php
use App\Models\Ejercicio;
$aciertos = (int) $intento['aciertos'];
$precision = $total ? (int) round(100 * $aciertos / $total) : 0;
$sinVidas = $intento['estado'] === 'sin_vidas';
$esRepaso = $intento['modo'] === 'repaso';
$meta = (int) $plan['meta'];
$docenteProbando = $esAutor;
?>
<div class="pagina pagina--resultado">
  <section class="resultado-cabeza" aria-labelledby="t-resultado">
    <p class="resultado-cabeza__antes"><?= e($esRepaso ? 'Repaso de ' . $curso['titulo'] : ($leccion['titulo'] ?? '')) ?></p>
    <h1 id="t-resultado"><?= $sinVidas ? 'Se acabaron las vidas' : ($precision >= 80 ? 'Lección superada' : 'Lección terminada') ?></h1>
    <p class="resultado-cabeza__frase"><?= $sinVidas
        ? 'Tu avance quedó guardado. Lo que fallaste ya está en tu repaso; repite la lección cuando quieras.'
        : ($errores ? 'Lo que fallaste vuelve mañana en tu repaso, para que no se te olvide.' : 'Sin errores a la primera. Esto ya lo tienes.') ?></p>
  </section>

  <dl class="cifras" aria-label="Resumen del resultado">
    <div><dt>XP ganada</dt><dd class="num">+<?= (int) $intento['xp'] ?></dd></div>
    <div><dt>Acierto a la primera</dt><dd class="num"><?= $aciertos ?>/<?= $total ?> <span>(<?= $precision ?> %)</span></dd></div>
    <div><dt>Racha</dt><dd class="num"><?= $racha ?> <span><?= $racha === 1 ? 'día' : 'días' ?></span></dd></div>
    <div><dt>Meta de hoy</dt><dd class="num"><?= min($xpHoy, $meta) ?>/<?= $meta ?> <span>XP</span></dd></div>
  </dl>

  <div class="resultado-cuerpo">
    <div class="resultado-principal">
      <?php if ($errores): ?>
        <section aria-labelledby="t-errores">
          <h2 id="t-errores" class="seccion-titulo">Para revisar</h2>
          <ul class="errores">
            <?php foreach ($errores as $er): ?>
              <li>
                <p class="errores__tipo"><?= e(Ejercicio::TIPOS[$er['tipo']] ?? '') ?></p>
                <p class="errores__enunciado"><?= e($er['enunciado']) ?></p>
                <p class="errores__dada">Respondiste: <s><?= e($er['dada']) ?></s></p>
                <p class="errores__correcta">Correcto: <?= e($er['correcta']) ?></p>
                <?php if ($er['explicacion']): ?><p class="errores__explicacion"><?= e($er['explicacion']) ?></p><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php else: ?>
        <section class="panel panel--vacio">
          <p>No hubo errores a la primera en esta lección, así que no agregamos nada a tu repaso.</p>
        </section>
      <?php endif; ?>

      <div class="resultado-acciones">
        <?php if ($docenteProbando): ?>
          <a class="boton boton--grande" href="<?= url('/docente/clase/' . $curso['id'] . '/revisar') ?>">Volver a revisar mis juegos</a>
        <?php elseif ($sinVidas && $intento['leccion_id']): ?>
          <a class="boton boton--grande" href="<?= url('/leccion/' . $intento['leccion_id']) ?>">Repetir la lección</a>
        <?php elseif ($siguiente): ?>
          <a class="boton boton--grande" href="<?= url('/leccion/' . $siguiente['id']) ?>">Siguiente: <?= e($siguiente['titulo']) ?></a>
        <?php endif; ?>
        <a class="boton boton--suave" href="<?= url('/ruta?curso=' . $curso['id']) ?>">Ver mi ruta</a>
      </div>
    </div>

    <?php if (!$registrado): ?>
      <aside class="guardar" aria-labelledby="t-guardar">
        <h2 id="t-guardar">Guarda este avance</h2>
        <p>Ahora vive solo en este navegador. Con una cuenta lo conservas en cualquier dispositivo<?= $docenteProbando ? ' y puedes publicar tu clase con un código' : '' ?>.</p>
        <ul>
          <li>Pedimos nombre, usuario, correo y contraseña. Nada más.</li>
          <li>El correo es solo para ingresar: sin publicidad.</li>
          <li>Todo lo que hiciste como invitado pasa a tu cuenta.</li>
          <li>Puedes descargar o borrar tus datos cuando quieras.</li>
        </ul>
        <a class="boton boton--ancho" href="<?= url('/registro') ?>">Crear mi cuenta gratis</a>
        <p class="guardar__nota">Prefieres seguir sin cuenta: también puedes. <a href="<?= url('/privacidad') ?>">Qué datos guardamos</a>.</p>
      </aside>
    <?php endif; ?>
  </div>
</div>
