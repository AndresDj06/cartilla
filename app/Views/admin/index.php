<div class="pagina">
  <header class="titulo-fila"><div><h1>Administración</h1><p class="titulo-fila__sub">Vista general del piloto</p></div></header>
  <dl class="kpis">
    <div><dt>Estudiantes</dt><dd class="num"><?= (int) ($roles['estudiante'] ?? 0) ?><small><?= (int) ($roles['invitado'] ?? 0) ?> invitados sin cuenta</small></dd></div>
    <div><dt>Docentes</dt><dd class="num"><?= (int) ($roles['docente'] ?? 0) ?></dd></div>
    <div><dt>Cursos y clases</dt><dd class="num"><?= count($cursos) ?></dd></div>
    <div><dt>Respuestas</dt><dd class="num"><?= $respuestas ?><small><?= $respuestasHoy ?> hoy</small></dd></div>
  </dl>
  <section class="bloque" aria-labelledby="t-cursos">
    <h2 id="t-cursos" class="seccion-titulo">Cursos y clases</h2>
    <div class="tabla-envoltura">
      <table class="datos-tabla">
        <thead><tr><th scope="col">Título</th><th scope="col">Autor</th><th scope="col">Estado</th><th scope="col" class="num">Inscritos</th><th scope="col">Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($cursos as $c): ?>
            <tr>
              <td><?= e($c['titulo']) ?><span class="secundario" style="display:block"><?= e($c['materia']) ?><?= $c['catalogo'] ? ' · catálogo' : '' ?></span></td>
              <td><?= e($c['autor'] ?? '—') ?></td>
              <td><span class="etiqueta-estado etiqueta-estado--<?= e($c['estado']) ?>"><?= e($c['estado'] === 'publicado' ? 'Publicado' . ($c['codigo'] ? ' · ' . $c['codigo'] : '') : 'Borrador') ?></span></td>
              <td class="num"><?= (int) $c['inscritos'] ?></td>
              <td><?php if (!$c['catalogo']): ?><a href="<?= url('/docente/clase/' . $c['id'] . ($c['estado'] === 'publicado' ? '' : '/revisar')) ?>">Abrir</a><?php else: ?>—<?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <section class="bloque" aria-labelledby="t-usuarios">
    <h2 id="t-usuarios" class="seccion-titulo">Cuentas</h2>
    <div class="tabla-envoltura">
      <table class="datos-tabla">
        <thead><tr><th scope="col">Nombre</th><th scope="col">Usuario</th><th scope="col">Correo</th><th scope="col">Rol</th><th scope="col" class="num">XP</th><th scope="col" class="num">Último ingreso</th></tr></thead>
        <tbody>
          <?php foreach ($usuarios as $x): ?>
            <tr>
              <td><?= e($x['nombre']) ?><?= $x['es_demo'] ? ' <span class="secundario">(demo)</span>' : '' ?></td>
              <td><?= e($x['usuario']) ?></td>
              <td><?= e($x['correo']) ?></td>
              <td><?= e($x['rol']) ?></td>
              <td class="num"><?= (int) $x['xp'] ?></td>
              <td class="num"><?= e(fecha_corta($x['ultimo_ingreso'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
