<?php
$meta = (int) $plan['meta'];
$pctMeta = $meta ? min(100, (int) round(100 * $xpHoy / $meta)) : 0;
$maxSemana = max(array_merge([$meta], array_column($semana, 'xp')));
$tarjetaRepaso = $pendientes > 0;
?>
<div class="pagina pagina--ruta">
  <div class="columna-principal">
    <header class="titulo-fila">
      <div>
        <h1><?= e($curso['titulo']) ?></h1>
        <p class="titulo-fila__sub num"><?= $hechas ?> de <?= $totalLecciones ?> lecciones hechas · <?= e($curso['materia']) ?></p>
      </div>
      <?php if (count($cursos) > 1): ?>
        <form method="get" action="<?= url('/ruta') ?>" class="selector-curso">
          <label for="curso-sel" class="sr">Cambiar de curso</label>
          <select id="curso-sel" name="curso" onchange="this.form.submit()">
            <?php foreach ($cursos as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $curso['id'] ? 'selected' : '' ?>><?= e($c['titulo']) ?></option><?php endforeach; ?>
          </select>
          <noscript><button type="submit" class="boton boton--suave boton--chico">Ver</button></noscript>
        </form>
      <?php endif; ?>
    </header>

    <?php if ($tarjetaRepaso && ($priorizaRepaso || !$siguiente)): ?>
      <section class="tarjeta-repaso" aria-labelledby="t-repaso">
        <div>
          <h2 id="t-repaso">Repaso de hoy</h2>
          <p><?= plural($pendientes, 'pregunta que fallaste vuelve', 'preguntas que fallaste vuelven') ?> hoy. Repasar justo antes de olvidar es lo que fija la memoria.</p>
        </div>
        <a class="boton" href="<?= url('/repaso?curso=' . $curso['id']) ?>">Repasar ahora</a>
      </section>
    <?php endif; ?>

    <?php if ($siguiente): ?>
      <section class="siguiente" aria-labelledby="t-siguiente">
        <p class="siguiente__antes">Sigue en tu ruta</p>
        <h2 id="t-siguiente"><?= e($siguiente['titulo']) ?></h2>
        <p class="num"><?= min((int) $siguiente['n_ejercicios'], $plan['ejercicios']) ?> ejercicios · unos <?= $plan['minutos'] ?> minutos · <?= $plan['vidas'] ?> vidas</p>
        <a class="boton boton--grande" href="<?= url('/leccion/' . $siguiente['id']) ?>">Empezar lección</a>
      </section>
    <?php elseif ($hechas === $totalLecciones): ?>
      <section class="siguiente siguiente--fin" aria-labelledby="t-siguiente">
        <h2 id="t-siguiente">Terminaste este curso</h2>
        <p>Repite cualquier lección para subir tu precisión, o suma otro curso a tu ruta.</p>
        <a class="boton" href="<?= url('/unirse') ?>">Sumar otro curso</a>
      </section>
    <?php endif; ?>

    <div class="cuaderno" aria-label="Ruta del curso">
      <?php foreach ($ruta as $un): ?>
        <section class="unidad" aria-labelledby="u-<?= (int) $un['id'] ?>">
          <header class="unidad__cabeza">
            <h2 id="u-<?= (int) $un['id'] ?>">Unidad <?= (int) $un['orden'] ?> · <?= e($un['titulo']) ?></h2>
            <p class="num"><?= (int) $un['hechas'] ?>/<?= count($un['lecciones']) ?><?= $un['opcional'] ? ' · opcional, según tu nivel' : '' ?></p>
          </header>
          <ol class="lecciones">
            <?php foreach ($un['lecciones'] as $l): $est = $l['estado']; ?>
              <li class="leccion-fila leccion-fila--<?= $est ?>">
                <span class="leccion-fila__marca" aria-hidden="true"><?php if ($est === 'hecha'): ?><svg viewBox="0 0 24 24" width="18" height="18"><path d="M5 12.5l4.5 4.5L19 7" fill="none" stroke="currentColor" stroke-width="3"/></svg><?php endif; ?></span>
                <div class="leccion-fila__texto">
                  <p class="leccion-fila__titulo"><?= e($l['titulo']) ?></p>
                  <p class="leccion-fila__meta num">
                    <?php if ($est === 'hecha'): ?>Hecha · mejor precisión <?= (int) $l['precision'] ?> %<?php elseif ($est === 'bloqueada'): ?>Se abre al terminar la anterior<?php else: ?><?= min((int) $l['n_ejercicios'], $plan['ejercicios']) ?> ejercicios<?php endif; ?>
                  </p>
                </div>
                <?php if ($est === 'bloqueada'): ?>
                  <span class="leccion-fila__accion leccion-fila__accion--cerrada">Cerrada</span>
                <?php else: ?>
                  <a class="leccion-fila__accion" href="<?= url('/leccion/' . $l['id']) ?>"><?= $est === 'hecha' ? 'Repetir' : 'Empezar' ?><span class="sr"> «<?= e($l['titulo']) ?>»</span></a>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ol>
        </section>
      <?php endforeach; ?>
    </div>
  </div>

  <aside class="riel" aria-label="Tu progreso">
    <h2 class="riel__titulo">Hoy</h2>
    <section class="panel">
      <div class="dato-grande">
        <p class="dato-grande__cifra num"><?= $racha ?></p>
        <p><?= $racha === 1 ? 'día seguido practicando' : 'días seguidos practicando' ?></p>
      </div>
      <div class="meta">
        <p class="meta__texto num"><span>Meta del día</span><span><?= $xpHoy ?> / <?= $meta ?> XP</span></p>
        <div class="barra" role="progressbar" aria-label="Meta diaria" aria-valuemin="0" aria-valuemax="<?= $meta ?>" aria-valuenow="<?= min($xpHoy, $meta) ?>"><span style="width: <?= $pctMeta ?>%"></span></div>
        <p class="meta__nota"><?= $xpHoy >= $meta ? 'Cumpliste la meta de hoy.' : 'Te faltan ' . ($meta - $xpHoy) . ' XP: más o menos una lección.' ?></p>
      </div>
    </section>

    <h2 class="riel__titulo">Últimos 7 días</h2>
    <section class="panel">
      <div class="semana-barras" role="img" aria-label="XP por día en los últimos siete días: <?= e(implode(', ', array_map(fn ($d) => $d['dia'] . ' ' . $d['xp'], $semana))) ?>">
        <?php foreach ($semana as $d): ?>
          <div class="semana-barras__col <?= $d['hoy'] ? 'es-hoy' : '' ?>">
            <span class="semana-barras__valor num"><?= $d['xp'] ?: '' ?></span>
            <span class="semana-barras__barra" style="height: <?= $maxSemana ? round(100 * $d['xp'] / $maxSemana) : 0 ?>%"></span>
            <span class="semana-barras__dia"><?= e($d['dia']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="panel__pie num"><?= $xpTotal ?> XP en total</p>
    </section>

    <h2 class="riel__titulo">Repaso</h2>
    <section class="panel">
      <?php if ($pendientes): ?>
        <p><?= plural($pendientes, 'pregunta pendiente', 'preguntas pendientes') ?> para hoy.</p>
        <a class="boton boton--suave boton--ancho" href="<?= url('/repaso?curso=' . $curso['id']) ?>">Repasar</a>
      <?php else: ?>
        <p>Nada pendiente hoy.<?= $proximoRepaso ? ' Próximo repaso: ' . e(fecha_corta($proximoRepaso)) . '.' : ' Lo que falles aparecerá aquí al día siguiente.' ?></p>
      <?php endif; ?>
    </section>

    <?php if ($decisiones): ?>
      <h2 class="riel__titulo">Por qué ves esta ruta</h2>
      <section class="panel panel--plan">
        <ul>
          <?php foreach ($decisiones as $d): ?><li><?= e($d['efecto']) ?></li><?php endforeach; ?>
        </ul>
        <a href="<?= url('/empezar/resumen') ?>">Cambiar mis respuestas</a>
      </section>
    <?php endif; ?>
  </aside>
</div>
