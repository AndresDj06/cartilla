<main id="principal" class="hoja-simple">
  <div class="hoja-simple__papel">
    <h1><?= e($titulo) ?></h1>
    <p><?= e($mensaje) ?></p>
    <?php if (!empty($detalle)): ?><p class="hoja-simple__detalle">Detalle técnico: <?= e($detalle) ?></p><?php endif; ?>
    <p class="hoja-simple__acciones"><a class="boton" href="javascript:location.reload()">Volver a intentar</a> <a href="<?= url('/') ?>">Ir al inicio</a></p>
  </div>
</main>
