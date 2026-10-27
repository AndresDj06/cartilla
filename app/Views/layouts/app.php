<?php
use App\Core\Auth;
use App\Core\Session;
use App\Models\Progreso;

$u = $usuario ?? Auth::usuario();
$aviso = Session::flash('aviso');
$exito = Session::flash('exito');
$nav = $nav ?? '';
$esInvitado = !$u || $u['rol'] === 'invitado';
$esDocente = $u && in_array($u['rol'], ['docente', 'superadmin'], true);
$rachaBarra = $u ? Progreso::racha((int) $u['id']) : 0;
$xpBarra = $u ? Progreso::xpTotal((int) $u['id']) : 0;
$enlaces = [
    ['ruta', '/ruta', 'Mi ruta', '<path d="M5 19c4 0 3-7 7-7s3-7 7-7" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="5" r="2"/>'],
    ['unirse', '/unirse', 'Clases', '<rect x="3" y="5" width="18" height="14" fill="none" stroke="currentColor" stroke-width="2"/><path d="M7 10h10M7 14h6" stroke="currentColor" stroke-width="2"/>'],
];
if ($esDocente) {
    $enlaces[] = ['docente', '/docente', 'Panel docente', '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2" fill="none" stroke="currentColor" stroke-width="2"/>'];
}
if ($u && $u['rol'] === 'superadmin') {
    $enlaces[] = ['admin', '/admin', 'Administración', '<circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 21c1-5 5-7 8-7s7 2 8 7" fill="none" stroke="currentColor" stroke-width="2"/>'];
}
$enlaces[] = ['cuenta', '/cuenta', $esInvitado ? 'Guardar avance' : 'Mi cuenta', '<path d="M6 3h12v18l-6-4-6 4z" fill="none" stroke="currentColor" stroke-width="2"/>'];
?>
<!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ?? 'Cartilla') ?></title>
<meta name="csrf" content="<?= e(Session::csrf()) ?>">
<meta name="base" content="<?= e(base_path()) ?>">
<link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script>document.documentElement.classList.add('js');</script>
</head>
<body class="app">
<a class="saltar" href="#principal">Saltar al contenido</a>
<div class="marco">
  <aside class="lateral" aria-label="Navegación principal">
    <a class="lateral__marca" href="<?= url('/') ?>">Cartilla</a>
    <nav>
      <ul>
        <?php foreach ($enlaces as [$clave, $ruta, $texto, $icono]): ?>
          <li><a href="<?= url($ruta) ?>" class="<?= $nav === $clave ? 'activo' : '' ?>" <?= $nav === $clave ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" fill="currentColor"><?= $icono ?></svg><span><?= e($texto) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php if ($u && !$esInvitado): ?>
      <form method="post" action="<?= url('/salir') ?>" class="lateral__salir"><?= csrf_field() ?><button type="submit" class="enlace-boton">Cerrar sesión</button></form>
    <?php endif; ?>
  </aside>

  <div class="cuerpo">
    <header class="superior">
      <p class="superior__quien"><?= $esInvitado ? 'Estás como invitado' : e($u['nombre'] ?? $u['usuario']) ?></p>
      <div class="superior__marcas">
        <p class="marca-dato" title="Días seguidos practicando"><span class="num"><?= $rachaBarra ?></span> <?= $rachaBarra === 1 ? 'día de racha' : 'días de racha' ?></p>
        <p class="marca-dato"><span class="num"><?= $xpBarra ?></span> XP</p>
        <?php if ($esInvitado): ?><a class="boton boton--chico" href="<?= url('/registro') ?>">Guardar mi avance</a><?php endif; ?>
      </div>
    </header>
    <?php if ($aviso || $exito): ?>
      <div class="aviso-app <?= $aviso ? 'aviso-app--alerta' : '' ?>" role="status"><?= e($exito ?: $aviso) ?></div>
    <?php endif; ?>
    <main id="principal" class="contenido">
      <?= $contenido ?>
    </main>
  </div>
</div>
<script src="<?= asset('js/sitio.js') ?>" defer></script>
</body>
</html>
