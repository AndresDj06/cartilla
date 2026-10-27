<?php use App\Models\Ejercicio; ?>
<div class="pagina">
  <header class="titulo-fila">
    <div><h1><?= e($curso['titulo']) ?></h1><p class="titulo-fila__sub">Resultados de la clase · acierto a la primera, sin contar reintentos</p></div>
    <div class="acciones-fila">
      <a class="boton boton--suave" href="<?= url('/docente/clase/' . $curso['id'] . '/revisar') ?>">Editar juegos</a>
      <a class="boton boton--suave" href="<?= url('/docente/clase/' . $curso['id'] . '/csv') ?>" download>Descargar CSV</a>
    </div>
  </header>

  <dl class="kpis">
    <div><dt>Estudiantes</dt><dd class="num"><?= $kpi['estudiantes'] ?><small><?= $kpi['activos'] ?> practicaron esta semana</small></dd></div>
    <div><dt>Acierto medio</dt><dd class="num"><?= $kpi['acierto'] !== null ? $kpi['acierto'] . ' %' : '—' ?><small>sobre <?= $kpi['respuestas'] ?> respuestas</small></dd></div>
    <div><dt>Lecciones</dt><dd class="num"><?= $kpi['lecciones'] ?><small><?= plural(count($preguntas), 'pregunta', 'preguntas') ?></small></dd></div>
    <div><dt>Más fallada</dt><dd class="num"><?= $kpi['mas_fallada'] ? (int) $kpi['mas_fallada']['porcentaje'] . ' %' : '—' ?><small><?= $kpi['mas_fallada'] ? e(Ejercicio::TIPOS[$kpi['mas_fallada']['tipo']]) : ($kpi['respuestas'] ? 'ninguna fallada todavía' : 'sin respuestas aún') ?></small></dd></div>
  </dl>

  <div class="rejilla-dos">
    <div>
      <section class="bloque" aria-labelledby="t-preguntas">
        <h2 id="t-preguntas" class="seccion-titulo">Preguntas, de la más fallada a la menos</h2>
        <?php if (!$kpi['respuestas']): ?>
          <div class="panel panel--vacio"><p>Cuando tus estudiantes respondan, aquí verás qué pregunta les cuesta más y qué respondieron mal. Comparte el código para empezar.</p></div>
        <?php endif; ?>
        <div class="tabla-envoltura">
          <table class="datos-tabla">
            <caption class="sr">Acierto por pregunta</caption>
            <thead><tr><th scope="col">Pregunta</th><th scope="col" class="num">Respuestas</th><th scope="col" class="num">Acierto</th></tr></thead>
            <tbody>
              <?php foreach ($preguntas as $p): $pct = $p['porcentaje'] === null ? null : (int) $p['porcentaje']; ?>
                <tr>
                  <td>
                    <?= e(mb_strimwidth($p['enunciado'], 0, 140, '…')) ?>
                    <span class="secundario" style="display:block"><?= e(Ejercicio::TIPOS[$p['tipo']]) ?> · <?= e($p['leccion']) ?></span>
                    <?php if ($p['frecuentes']): ?>
                      <span class="secundario" style="display:block">Respuestas equivocadas: <?= e(implode(' · ', array_map(fn ($f) => '«' . mb_strimwidth($f['respuesta'], 0, 60, '…') . '» (' . $f['n'] . ')', $p['frecuentes']))) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="num"><?= (int) $p['respuestas'] ?></td>
                  <td class="num <?= $pct !== null && $pct < 60 ? 'bajo' : '' ?>"><?= $pct === null ? '—' : $pct . ' %' ?><?php if ($pct !== null): ?><span class="barra-acierto" aria-hidden="true"><span style="width: <?= $pct ?>%"></span></span><?php endif; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="bloque" aria-labelledby="t-estudiantes">
        <h2 id="t-estudiantes" class="seccion-titulo">Estudiantes</h2>
        <?php if (!$estudiantes): ?>
          <div class="panel panel--vacio"><p>Nadie ha entrado todavía. Dicta el código <b><?= e($curso['codigo']) ?></b> o comparte el enlace.</p></div>
        <?php else: ?>
          <div class="tabla-envoltura">
            <table class="datos-tabla">
              <caption class="sr">Avance por estudiante</caption>
              <thead><tr><th scope="col">Nombre</th><th scope="col" class="num">Lecciones</th><th scope="col" class="num">Acierto</th><th scope="col" class="num">Última vez</th></tr></thead>
              <tbody>
                <?php foreach ($estudiantes as $e): ?>
                  <tr>
                    <td><?= e($e['nombre']) ?><?= $e['rol'] === 'invitado' ? ' <span class="secundario">(sin cuenta)</span>' : '' ?></td>
                    <td class="num"><?= (int) $e['lecciones_hechas'] ?>/<?= $kpi['lecciones'] ?></td>
                    <td class="num <?= $e['precision_media'] !== null && (int) $e['precision_media'] < 60 ? 'bajo' : '' ?>"><?= $e['precision_media'] === null ? '—' : (int) $e['precision_media'] . ' %' ?></td>
                    <td class="num"><?= e(fecha_corta($e['ultima_vez'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </div>

    <aside aria-labelledby="t-codigo">
      <h2 id="t-codigo" class="seccion-titulo">Código de la clase</h2>
      <div class="codigo-clase">
        <p class="codigo-clase__cifra" aria-label="Código <?= e(implode(' ', str_split($curso['codigo']))) ?>"><?= e($curso['codigo']) ?></p>
        <p>Díctalo en clase: sin O ni I para que no se confundan con 0 y 1.</p>
        <label for="enlace" class="sr">Enlace para compartir</label>
        <input id="enlace" value="<?= e($enlace) ?>" readonly onclick="this.select()">
      </div>
    </aside>
  </div>
</div>
