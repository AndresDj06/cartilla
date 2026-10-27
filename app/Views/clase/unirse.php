<?php $errores = $GLOBALS['_errores'] ?? []; ?>
<div class="pagina">
  <header class="titulo-fila"><div><h1>Clases y cursos</h1><p class="titulo-fila__sub">Entra a la clase de tu docente con su código o suma un curso de práctica.</p></div></header>
  <div class="rejilla-dos">
    <div>
      <section class="bloque" aria-labelledby="t-codigo">
        <h2 id="t-codigo" class="seccion-titulo">Tengo un código</h2>
        <form method="post" action="<?= url('/unirse') ?>" class="tarjeta-form" novalidate data-cargando="Buscando la clase">
          <?= csrf_field() ?>
          <div class="campo <?= isset($errores['codigo']) ? 'campo--error' : '' ?>">
            <label for="codigo">Código de la clase</label>
            <p class="ayuda" id="a-codigo">Seis caracteres que te da tu docente, por ejemplo AGUA26.</p>
            <input id="codigo" name="codigo" class="codigo" maxlength="8" autocomplete="off" autocapitalize="characters" spellcheck="false" value="<?= e($codigo) ?>" aria-describedby="a-codigo<?= isset($errores['codigo']) ? ' e-codigo' : '' ?>" <?= isset($errores['codigo']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errores['codigo'])): ?><p class="error" id="e-codigo" role="alert"><?= e($errores['codigo']) ?></p><?php endif; ?>
          </div>
          <button type="submit" class="boton boton--ancho">Entrar a la clase</button>
        </form>
      </section>

      <section class="bloque" aria-labelledby="t-catalogo">
        <h2 id="t-catalogo" class="seccion-titulo">Cursos de práctica</h2>
        <ul class="lista-clases">
          <?php foreach ($catalogo as $c): $dentro = in_array((int) $c['id'], $inscritos, true); ?>
            <li>
              <div><h3 class="seccion-titulo" style="margin:0"><?= e($c['titulo']) ?></h3><p class="secundario"><?= e($c['materia']) ?> · <?= (int) $c['lecciones'] ?> lecciones</p></div>
              <?php if ($dentro): ?>
                <a class="boton boton--suave boton--chico" href="<?= url('/ruta?curso=' . $c['id']) ?>">Ir a la ruta</a>
              <?php else: ?>
                <form method="post" action="<?= url('/unirse') ?>"><?= csrf_field() ?><input type="hidden" name="curso" value="<?= (int) $c['id'] ?>"><button type="submit" class="boton boton--chico">Sumar a mi ruta<span class="sr"> «<?= e($c['titulo']) ?>»</span></button></form>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    </div>
    <aside aria-labelledby="t-mias">
      <h2 id="t-mias" class="seccion-titulo">Donde estás</h2>
      <?php if ($misClases): ?>
        <ul class="lista-clases">
          <?php foreach ($misClases as $c): ?>
            <li><a href="<?= url('/ruta?curso=' . $c['id']) ?>"><?= e($c['titulo']) ?></a><span class="secundario"><?= $c['codigo'] ? 'Clase ' . e($c['codigo']) : e($c['materia']) ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="panel panel--vacio"><p>Todavía no estás en ninguna clase ni curso. Usa un código o suma un curso de práctica.</p></div>
      <?php endif; ?>
    </aside>
  </div>
</div>
