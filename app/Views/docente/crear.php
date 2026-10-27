<?php use App\Services\Personalizador; $errores = $GLOBALS['_errores'] ?? []; $gradoOld = $GLOBALS['_old']['grado'] ?? 'secundaria'; ?>
<div class="pagina">
  <header class="titulo-fila"><div><h1>Crear juegos</h1><p class="titulo-fila__sub">Pega el texto de tu tema. Te mostramos cada ejercicio para que lo revises antes de publicar.</p></div></header>
  <form method="post" action="<?= url('/docente/crear') ?>" class="tarjeta-form" style="max-width:820px" novalidate data-cargando="Armando los juegos">
    <?= csrf_field() ?>
    <?php if ($errores): ?>
      <div class="resumen-errores" role="alert" tabindex="-1" data-enfocar><h2>Falta corregir algo</h2><ul><?php foreach ($errores as $c => $m): ?><li><a href="#<?= e($c) ?>"><?= e($m) ?></a></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <div class="campo">
      <label for="titulo">Título del tema <span class="opcional">(opcional)</span></label>
      <input id="titulo" name="titulo" maxlength="120" value="<?= old('titulo') ?>">
    </div>
    <div class="campo <?= isset($errores['grado']) ? 'campo--error' : '' ?>">
      <label for="grado">Grado de tus estudiantes</label>
      <p class="ayuda" id="a-grado">Primaria y secundaria tienen banco de palabras; en universidad hay 2 vidas por lección.</p>
      <select id="grado" name="grado" aria-describedby="a-grado">
        <?php foreach (Personalizador::GRADOS as $v => $t): ?><option value="<?= e($v) ?>" <?= $gradoOld === $v ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="campo <?= isset($errores['texto']) ? 'campo--error' : '' ?>">
      <label for="texto">Texto del tema</label>
      <p class="ayuda" id="a-texto">Mínimo 400 caracteres con oraciones completas. Funciona mejor con textos informativos: definiciones, fechas, lugares y procesos.</p>
      <textarea id="texto" name="texto" rows="14" data-contador="contador" data-minimo="400" aria-describedby="a-texto contador<?= isset($errores['texto']) ? ' e-texto' : '' ?>"><?= old('texto') ?></textarea>
      <p class="ayuda num" id="contador" aria-live="polite"></p>
      <?php if (isset($errores['texto'])): ?><p class="error" id="e-texto"><?= e($errores['texto']) ?></p><?php endif; ?>
    </div>
    <p class="nota-paso" style="margin:0 0 20px"><button type="button" class="enlace-boton" data-rellenar="texto" data-fuente="texto-ejemplo" data-titulo="titulo" data-titulo-valor="<?= e($tituloEjemplo) ?>">Usar un texto de ejemplo</button></p>
    <template id="texto-ejemplo"><?= e($textoEjemplo) ?></template>
    <button type="submit" class="boton">Armar los juegos</button>
  </form>
</div>
