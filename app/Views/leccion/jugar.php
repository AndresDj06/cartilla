<?php
use App\Models\Ejercicio;
$esRepaso = $paquete['modo'] === 'repaso';
?>
<div class="jugador" data-paquete='<?= e(json_encode($paquete, JSON_UNESCAPED_UNICODE)) ?>' data-salir="<?= e(url($salir)) ?>" data-tipos='<?= e(json_encode(Ejercicio::TIPOS, JSON_UNESCAPED_UNICODE)) ?>'>
  <header class="jugador__barra">
    <a class="jugador__salir" href="<?= url($salir) ?>" data-salir-enlace aria-label="Salir de la lección">
      <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.5"/></svg>
      <span>Salir</span>
    </a>
    <div class="jugador__avance" role="progressbar" aria-label="Avance de la lección" aria-valuemin="0" aria-valuemax="<?= count($paquete['ejercicios']) ?>" aria-valuenow="0"><span></span></div>
    <p class="jugador__vidas" aria-live="polite" <?= $esRepaso ? 'hidden' : '' ?>></p>
  </header>

  <main id="principal" class="jugador__escena">
    <p class="jugador__contexto"><?= e($leccion['unidad_titulo'] ?? '') ?> · <?= e($leccion['titulo']) ?></p>
    <div class="ejercicio" aria-live="off">
      <p class="ejercicio__tipo"></p>
      <h1 class="ejercicio__enunciado" tabindex="-1">Cargando la lección…</h1>
      <div class="ejercicio__zona"></div>
    </div>
    <noscript><p class="aviso aviso--error">Las lecciones necesitan JavaScript activado para mostrar los juegos. Actívalo y recarga la página.</p></noscript>
  </main>

  <footer class="jugador__pie" data-estado="respondiendo">
    <div class="jugador__pie-interior">
      <div class="retro" role="status" aria-live="assertive"></div>
      <button type="button" class="boton boton--grande jugador__boton" disabled>Comprobar</button>
    </div>
  </footer>
</div>
<script src="<?= asset('js/leccion.js') ?>"></script>
