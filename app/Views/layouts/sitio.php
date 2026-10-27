<?php use App\Core\Session; $aviso = Session::flash('aviso'); $exito = Session::flash('exito'); ?>
<!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ?? 'Cartilla') ?></title>
<meta name="description" content="Cartilla convierte el tema del docente en juegos cortos y los organiza en una ruta diaria con racha y repaso espaciado.">
<meta name="csrf" content="<?= e(Session::csrf()) ?>">
<meta name="base" content="<?= e(base_path()) ?>">
<link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('css/sitio.css') ?>">
<script>document.documentElement.classList.add('js');</script>
</head>
<body class="sitio <?= e($claseCuerpo ?? '') ?>">
<a class="saltar" href="#principal">Saltar al contenido</a>
<?php if ($aviso || $exito): ?>
  <div class="banda-aviso <?= $exito ? 'banda-aviso--exito' : '' ?>" role="status"><?= e($exito ?: $aviso) ?></div>
<?php endif; ?>
<?= $contenido ?>
<script src="<?= asset('js/sitio.js') ?>" defer></script>
</body>
</html>
