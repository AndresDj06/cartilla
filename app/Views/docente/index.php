<div class="pagina">
  <header class="titulo-fila">
    <div><h1>Mis clases</h1><p class="titulo-fila__sub">Crea juegos desde un texto, compártelos con un código y mira qué pregunta cuesta más.</p></div>
    <a class="boton" href="<?= url('/docente/crear') ?>">Crear juegos desde un texto</a>
  </header>

  <?php if (!$clases): ?>
    <div class="panel panel--vacio">
      <p><b>Aún no tienes clases.</b> Pega el texto de tu próximo tema y en segundos tendrás dos lecciones de juegos para revisar.</p>
      <a class="boton" href="<?= url('/docente/crear') ?>">Crear mi primera clase</a>
    </div>
  <?php else: ?>
    <ul class="lista-clases">
      <?php foreach ($clases as $c): ?>
        <li>
          <div>
            <h2><?= e($c['titulo']) ?></h2>
            <p class="secundario num">
              <span class="etiqueta-estado etiqueta-estado--<?= e($c['estado']) ?>"><?= $c['estado'] === 'publicado' ? 'Publicada · ' . e($c['codigo']) : 'Borrador' ?></span>
              <?= plural((int) $c['n_ejercicios'], 'ejercicio', 'ejercicios') ?> · <?= plural((int) $c['n_estudiantes'], 'estudiante', 'estudiantes') ?><?= $c['acierto'] !== null ? ' · acierto medio ' . $c['acierto'] . ' %' : '' ?>
            </p>
          </div>
          <div class="acciones-fila">
            <?php if ($c['estado'] === 'publicado'): ?>
              <a class="boton boton--chico" href="<?= url('/docente/clase/' . $c['id']) ?>">Ver resultados<span class="sr"> de <?= e($c['titulo']) ?></span></a>
            <?php endif; ?>
            <a class="boton boton--suave boton--chico" href="<?= url('/docente/clase/' . $c['id'] . '/revisar') ?>">Editar juegos<span class="sr"> de <?= e($c['titulo']) ?></span></a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
