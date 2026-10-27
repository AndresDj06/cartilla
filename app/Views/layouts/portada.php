<?php use App\Core\Session; $aviso = Session::flash('aviso'); $exito = Session::flash('exito'); ?>
<!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ?? 'Cartilla') ?></title>
<meta name="description" content="Cartilla convierte el tema de clase en juegos de cinco minutos y te los repite justo antes de que se te olviden. Para docentes y estudiantes de Colombia.">
<meta name="csrf" content="<?= e(Session::csrf()) ?>">
<meta name="base" content="<?= e(base_path()) ?>">
<meta name="theme-color" content="#ffd249">
<link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Work+Sans:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/portada.css') ?>">
<script>document.documentElement.classList.add('js');</script>
</head>
<body>
<a class="saltar" href="#principal">Saltar al contenido</a>
<?php if ($aviso || $exito): ?>
  <div class="banda" role="status"><?= e($exito ?: $aviso) ?></div>
<?php endif; ?>
<?= $contenido ?>
<script src="<?= asset('js/portada.js') ?>" defer></script>
</body>
</html>
