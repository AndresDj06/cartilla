<?php
use App\Models\Ejercicio;
use App\Services\Personalizador;

$nombresPasos = ['rol' => 'Quién eres', 'objetivo' => 'Para qué', 'tema' => 'Tema', 'nivel' => 'Nivel', 'grado' => 'Grado', 'tiempo' => 'Tiempo', 'juegos' => 'Juegos', 'resumen' => 'Resumen'];
$totalPreguntas = count($pasos) - 1;
$rolActual = $perfil['rol_elegido'] ?: ($rolSugerido === 'docente' ? 'docente' : null);
$tiposPerfil = array_filter(explode(',', (string) $perfil['tipos']));
$q = $volver ? '?volver=resumen' : '';
$errores = $GLOBALS['_errores'] ?? [];
$minutos = (int) ($perfil['minutos'] ?: 0);
$etiquetaRol = $perfil['rol_elegido'] ? ($esDocente ? 'Docente' : 'Estudiante') : null;
?>
<main id="principal" class="flujo-grid">

  <aside class="etiqueta" aria-label="Tu cartilla hasta ahora">
    <div class="etiqueta__papel">
      <p class="etiqueta__marca">Cartilla</p>
      <dl>
        <div><dt>Para</dt><dd><?= e($etiquetaRol ?? '—') ?></dd></div>
        <div><dt>Busca</dt><dd><?= e($perfil['objetivo'] ? (Personalizador::OBJETIVOS[$perfil['rol_elegido']][$perfil['objetivo']] ?? '—') : '—') ?></dd></div>
        <div><dt>Tema</dt><dd><?= e($curso['titulo'] ?? ($perfil['titulo_tema'] ?: ($perfil['texto_fuente'] ? 'Texto propio' : '—'))) ?></dd></div>
        <?php if ($esDocente): ?>
          <div><dt>Grado</dt><dd><?= e(Personalizador::GRADOS[$perfil['grado']] ?? '—') ?></dd></div>
        <?php else: ?>
          <div><dt>Nivel</dt><dd><?= e(Personalizador::NIVELES[$perfil['nivel']]['texto'] ?? '—') ?></dd></div>
        <?php endif; ?>
        <div><dt>Al día</dt><dd><?= $minutos ? e($minutos . ' min · ' . $plan['ejercicios'] . ' ejercicios · meta ' . $plan['meta'] . ' XP') : '—' ?></dd></div>
        <div><dt>Juegos</dt><dd><?= $tiposPerfil ? e(count($tiposPerfil) === 6 ? 'Los seis' : implode(', ', array_map(fn ($t) => Ejercicio::TIPOS[$t], $tiposPerfil))) : '—' ?></dd></div>
      </dl>
    </div>
    <nav class="pasos" aria-label="Pasos">
      <ol>
        <?php foreach ($pasos as $i => $p):
          $hecho = $i < $indice || ($p !== 'resumen' && $p !== $paso && $i > $indice && false);
          $esActual = $p === $paso; ?>
          <li class="<?= $esActual ? 'es-actual' : ($i < $indice ? 'es-hecho' : '') ?>">
            <?php if ($i < $indice): ?>
              <a href="<?= url('/empezar/' . $p) ?>"><?= e($nombresPasos[$p]) ?><span class="sr"> (respondido, cambiar)</span></a>
            <?php else: ?>
              <span <?= $esActual ? 'aria-current="step"' : '' ?>><?= e($nombresPasos[$p]) ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    </nav>
  </aside>

  <section class="pregunta-papel" aria-labelledby="t-paso">
    <div class="pregunta-papel__cuerpo">
      <header class="avance">
        <?php if ($indice > 0): ?>
          <a class="avance__atras" href="<?= url('/empezar/' . ($volver ? 'resumen' : $pasos[$indice - 1])) ?>">← <?= $volver ? 'Volver al resumen' : 'Atrás' ?></a>
        <?php else: ?>
          <a class="avance__atras" href="<?= url('/') ?>">← Inicio</a>
        <?php endif; ?>
        <p class="avance__texto"><?= $paso === 'resumen' ? 'Listo: revisa y empieza' : 'Pregunta ' . ($indice + 1) . ' de ' . ($perfil['rol_elegido'] ? $totalPreguntas : 6) ?></p>
        <div class="avance__barra" role="progressbar" aria-label="Avance del inicio" aria-valuemin="0" aria-valuemax="<?= count($pasos) ?>" aria-valuenow="<?= $indice + 1 ?>">
          <span style="width: <?= round(100 * ($indice + 1) / max(count($pasos), 7)) ?>%"></span>
        </div>
      </header>

<?php if ($paso !== 'resumen'): ?>
      <form method="post" action="<?= url('/empezar/' . $paso) ?>" class="pregunta" novalidate data-cargando="Guardando">
        <?= csrf_field() ?>
        <?php if ($volver): ?><input type="hidden" name="volver" value="resumen"><?php endif; ?>
        <?php if ($errores): ?>
          <div class="resumen-errores" role="alert" tabindex="-1" data-enfocar>
            <h2>Falta corregir algo</h2>
            <ul><?php foreach ($errores as $campo => $msg): ?><li><a href="#campo-<?= e($campo) ?>"><?= e($msg) ?></a></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>

<?php switch ($paso):
case 'rol': ?>
        <h1 id="t-paso">¿Con qué vienes a Cartilla?</h1>
        <p class="por-que">Con esto decidimos si te mostramos cursos para practicar o el creador de juegos para tu clase.</p>
        <fieldset class="elecciones" id="campo-rol" aria-labelledby="t-paso" <?= isset($errores['rol']) ? 'aria-describedby="e-rol"' : '' ?>>
          <label class="eleccion"><input type="radio" name="rol" value="estudiante" <?= $rolActual === 'estudiante' ? 'checked' : '' ?> required><span class="eleccion__titulo">Soy estudiante</span><span class="eleccion__desc">Quiero practicar un tema un poco cada día y no olvidarlo.</span></label>
          <label class="eleccion"><input type="radio" name="rol" value="docente" <?= $rolActual === 'docente' ? 'checked' : '' ?>><span class="eleccion__titulo">Soy docente</span><span class="eleccion__desc">Quiero que mis estudiantes practiquen lo que vimos en clase y ver qué les cuesta.</span></label>
        </fieldset>
        <?php if (isset($errores['rol'])): ?><p class="error-campo" id="e-rol"><?= e($errores['rol']) ?></p><?php endif; ?>
        <p class="nota-paso">¿Tu docente te dio un código? <a href="<?= url('/unirse') ?>">Úsalo aquí</a> y entras directo a su clase.</p>
<?php break; case 'objetivo': ?>
        <h1 id="t-paso"><?= $esDocente ? '¿Qué quieres lograr con tus estudiantes?' : '¿Qué quieres lograr?' ?></h1>
        <p class="por-que">Según tu objetivo te sugerimos cuánto tiempo practicar al día; tú decides al final.</p>
        <fieldset class="elecciones" id="campo-objetivo" aria-labelledby="t-paso">
          <?php foreach (Personalizador::OBJETIVOS[$perfil['rol_elegido']] as $valor => $texto): ?>
            <label class="eleccion eleccion--simple"><input type="radio" name="objetivo" value="<?= e($valor) ?>" <?= $perfil['objetivo'] === $valor ? 'checked' : '' ?>><span class="eleccion__titulo"><?= e($texto) ?></span></label>
          <?php endforeach; ?>
        </fieldset>
        <?php if (isset($errores['objetivo'])): ?><p class="error-campo"><?= e($errores['objetivo']) ?></p><?php endif; ?>
<?php break; case 'tema': ?>
  <?php if ($esDocente): ?>
        <h1 id="t-paso">Pega el texto de tu tema</h1>
        <p class="por-que">Con tu texto armamos juegos de seis tipos. Se procesa en nuestro servidor y no se envía a ningún servicio externo. Antes de compartir podrás revisar y corregir cada ejercicio.</p>
        <div class="campo">
          <label for="titulo">Título del tema <span class="opcional">(opcional)</span></label>
          <input id="titulo" name="titulo" maxlength="120" value="<?= old('titulo', (string) $perfil['titulo_tema']) ?>" autocomplete="off">
        </div>
        <div class="campo <?= isset($errores['texto']) ? 'campo--error' : '' ?>" id="campo-texto">
          <label for="texto">Texto del tema</label>
          <p class="ayuda" id="ayuda-texto">Mínimo 400 caracteres: un par de párrafos con oraciones completas. Sirven mejor los textos informativos (definiciones, fechas, lugares, procesos).</p>
          <textarea id="texto" name="texto" rows="12" aria-describedby="ayuda-texto contador-texto<?= isset($errores['texto']) ? ' e-texto' : '' ?>" data-contador="contador-texto" data-minimo="400"><?= old('texto', (string) $perfil['texto_fuente']) ?></textarea>
          <p class="ayuda num" id="contador-texto" aria-live="polite"></p>
          <?php if (isset($errores['texto'])): ?><p class="error" id="e-texto"><?= e($errores['texto']) ?></p><?php endif; ?>
        </div>
        <p class="nota-paso"><button type="button" class="enlace-boton" data-rellenar="texto" data-fuente="texto-ejemplo" data-titulo="titulo" data-titulo-valor="<?= e($tituloEjemplo) ?>">Usar un texto de ejemplo</button> sobre el ciclo del agua en el Chocó.</p>
        <template id="texto-ejemplo"><?= e($textoEjemplo) ?></template>
  <?php else: ?>
        <h1 id="t-paso">¿Qué quieres practicar?</h1>
        <p class="por-que">Tu ruta se arma con las unidades y lecciones de este curso. Puedes sumar otros después.</p>
        <fieldset class="elecciones elecciones--cursos" id="campo-curso" aria-labelledby="t-paso">
          <?php foreach ($catalogo as $c): ?>
            <label class="eleccion"><input type="radio" name="curso" value="<?= (int) $c['id'] ?>" <?= (int) $perfil['curso_id'] === (int) $c['id'] ? 'checked' : '' ?>><span class="eleccion__titulo"><?= e($c['titulo']) ?></span><span class="eleccion__desc"><?= e($c['materia']) ?> · <?= (int) $c['lecciones'] ?> lecciones. <?= e($c['descripcion']) ?></span></label>
          <?php endforeach; ?>
        </fieldset>
        <?php if (isset($errores['curso'])): ?><p class="error-campo"><?= e($errores['curso']) ?></p><?php endif; ?>
        <p class="nota-paso">¿Tu docente creó una clase? <a href="<?= url('/unirse') ?>">Entra con su código</a>.</p>
  <?php endif; ?>
<?php break; case 'nivel': ?>
        <h1 id="t-paso">¿Cómo te sientes con «<?= e($curso['titulo'] ?? 'este tema') ?>»?</h1>
        <p class="por-que">Decide desde qué unidad empiezas, si tienes banco de palabras al completar frases y cuántas vidas tienes por lección.</p>
        <fieldset class="elecciones" id="campo-nivel" aria-labelledby="t-paso">
          <?php $descNivel = ['cero' => 'Empiezas en la unidad 1, con banco de palabras y 3 vidas.', 'basico' => 'Empiezas en la unidad 2; la 1 queda abierta para repasar.', 'reto' => 'Unidad 2, sin banco de palabras y con 2 vidas por lección.'];
          foreach (Personalizador::NIVELES as $valor => $n): ?>
            <label class="eleccion"><input type="radio" name="nivel" value="<?= e($valor) ?>" <?= $perfil['nivel'] === $valor ? 'checked' : '' ?>><span class="eleccion__titulo"><?= e($n['texto']) ?></span><span class="eleccion__desc"><?= e($descNivel[$valor]) ?></span></label>
          <?php endforeach; ?>
        </fieldset>
        <?php if (isset($errores['nivel'])): ?><p class="error-campo"><?= e($errores['nivel']) ?></p><?php endif; ?>
<?php break; case 'grado': ?>
        <h1 id="t-paso">¿En qué grado están tus estudiantes?</h1>
        <p class="por-que">Decide si las frases para completar muestran banco de palabras y cuántas vidas tiene cada estudiante por lección.</p>
        <fieldset class="elecciones" id="campo-grado" aria-labelledby="t-paso">
          <?php $descGrado = ['primaria' => 'Con banco de palabras y 3 vidas.', 'secundaria' => 'Con banco de palabras y 3 vidas.', 'media' => 'Escriben la respuesta, con 3 vidas.', 'universidad' => 'Escriben la respuesta, con 2 vidas.'];
          foreach (Personalizador::GRADOS as $valor => $texto): ?>
            <label class="eleccion"><input type="radio" name="grado" value="<?= e($valor) ?>" <?= $perfil['grado'] === $valor ? 'checked' : '' ?>><span class="eleccion__titulo"><?= e($texto) ?></span><span class="eleccion__desc"><?= e($descGrado[$valor]) ?></span></label>
          <?php endforeach; ?>
        </fieldset>
        <?php if (isset($errores['grado'])): ?><p class="error-campo"><?= e($errores['grado']) ?></p><?php endif; ?>
<?php break; case 'tiempo': ?>
        <h1 id="t-paso"><?= $esDocente ? '¿Cuánto tiempo al día practicarán?' : '¿Cuánto tiempo al día tienes?' ?></h1>
        <p class="por-que">Define cuántos ejercicios trae cada lección y la meta diaria de XP. Cada acierto a la primera da 2 XP y terminar una lección, 5 más.</p>
        <fieldset class="elecciones elecciones--tres" id="campo-minutos" aria-labelledby="t-paso">
          <?php foreach (Personalizador::MINUTOS as $min => $m): $marcado = $minutos ? $minutos === $min : $sugerido === $min; ?>
            <label class="eleccion eleccion--numero"><input type="radio" name="minutos" value="<?= $min ?>" <?= $marcado ? 'checked' : '' ?>><span class="eleccion__cifra num"><?= $min ?> min</span><span class="eleccion__desc"><?= $m['ejercicios'] ?> ejercicios por lección · meta de <?= $m['meta'] ?> XP</span><?php if ($sugerido === $min): ?><span class="sugerido">Sugerido por tu objetivo</span><?php endif; ?></label>
          <?php endforeach; ?>
        </fieldset>
        <?php if (isset($errores['minutos'])): ?><p class="error-campo"><?= e($errores['minutos']) ?></p><?php endif; ?>
<?php break; case 'juegos': ?>
        <h1 id="t-paso"><?= $esDocente ? '¿Qué juegos quieres que salgan primero?' : '¿Cómo prefieres practicar?' ?></h1>
        <p class="por-que">Cada lección escoge primero estos juegos. Si marcas pocos, completamos con los demás para llegar a <?= (int) $plan['ejercicios'] ?> ejercicios.</p>
        <fieldset class="elecciones elecciones--juegos" id="campo-tipos" aria-labelledby="t-paso">
          <?php $ejemplos = ['test' => 'Eliges una de tres opciones.', 'si_no' => 'Dices si una afirmación es correcta.', 'completar' => 'Escribes o eliges la palabra que falta.', 'ordenar_palabras' => 'Tocas las palabras en el orden correcto.', 'ordenar_letras' => 'Armas una palabra letra por letra.', 'relacionar' => 'Unes cada término con su pareja.'];
          foreach (Ejercicio::TIPOS as $t => $nombre): $marcado = $tiposPerfil ? in_array($t, $tiposPerfil, true) : true; ?>
            <label class="eleccion eleccion--casilla"><input type="checkbox" name="tipos[]" value="<?= e($t) ?>" <?= $marcado ? 'checked' : '' ?>><span class="eleccion__titulo"><?= e($nombre) ?></span><span class="eleccion__desc"><?= e($ejemplos[$t]) ?></span></label>
          <?php endforeach; ?>
        </fieldset>
        <?php if (isset($errores['tipos'])): ?><p class="error-campo"><?= e($errores['tipos']) ?></p><?php endif; ?>
<?php break; endswitch; ?>

        <div class="pregunta__acciones">
          <button type="submit" class="boton"><?= $volver ? 'Guardar y volver al resumen' : 'Continuar' ?></button>
        </div>
      </form>

<?php else: /* ------------------ Resumen ------------------ */ ?>
      <div class="resumen">
        <h1 id="t-paso"><?= $esDocente ? 'Tus juegos están listos' : 'Tu ruta está lista' ?></h1>
        <p class="por-que"><?= $esDocente
            ? 'Armamos ' . plural((int) $nEjercicios, 'ejercicio', 'ejercicios') . ' a partir de tu texto, en ' . plural(count($estructura[0]['lecciones'] ?? []), 'lección', 'lecciones') . '. Así cambió Cartilla según lo que respondiste:'
            : 'Así cambió Cartilla según lo que respondiste. Puedes corregir cualquier decisión antes de empezar.' ?></p>

        <ul class="decisiones">
          <?php foreach ($decisiones as $d): ?>
            <li>
              <div>
                <p class="decisiones__elegiste"><?= e($d['elegiste']) ?></p>
                <p class="decisiones__efecto"><?= e($d['efecto']) ?></p>
              </div>
              <a href="<?= url('/empezar/' . $d['paso'] . '?volver=resumen') ?>">Cambiar<span class="sr"> <?= e($nombresPasos[$d['paso']]) ?></span></a>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="vista-ruta" aria-label="Vista previa de tu ruta">
          <?php foreach ($estructura as $un): ?>
            <p class="vista-ruta__unidad">Unidad <?= (int) $un['orden'] ?> · <?= e($un['titulo']) ?><?php if (!$esDocente && (int) $un['orden'] < $plan['unidad_inicial']): ?> <span class="etiqueta-suave">Opcional</span><?php endif; ?></p>
            <ul>
              <?php foreach ($un['lecciones'] as $l): ?>
                <li><span><?= e($l['titulo']) ?></span><span class="num"><?= min((int) $l['n_ejercicios'], $plan['ejercicios']) ?> ejercicios</span></li>
              <?php endforeach; ?>
            </ul>
          <?php endforeach; ?>
        </div>

        <form method="post" action="<?= url('/empezar/confirmar') ?>" class="resumen__acciones" data-cargando="Abriendo">
          <?= csrf_field() ?>
          <?php if ($esDocente): ?>
            <button type="submit" name="destino" value="revisar" class="boton">Revisar y corregir mis juegos</button>
            <button type="submit" name="destino" value="probar" class="boton boton--suave">Probar como estudiante</button>
          <?php else: ?>
            <button type="submit" class="boton">Empezar mi primera lección</button>
          <?php endif; ?>
        </form>
        <p class="nota-paso"><?= $registrado
            ? 'Tu avance se guarda en tu cuenta.'
            : 'No necesitas cuenta para probar. Tu avance se guarda en este navegador; al final te ofrecemos crear una cuenta para no perderlo' . ($esDocente ? ' y para compartir tu clase con un código.' : '.') ?></p>
        <form method="post" action="<?= url('/empezar/reiniciar') ?>" class="reiniciar" data-confirmar="¿Borrar tus respuestas y empezar de nuevo? Tu avance en lecciones se conserva.">
          <?= csrf_field() ?>
          <button type="submit" class="enlace-boton">Empezar de nuevo desde la primera pregunta</button>
        </form>
      </div>
<?php endif; ?>
    </div>
  </section>
  <?php App\Core\View::parte('lamina', ['fotoClave' => $paso]); ?>
</main>
