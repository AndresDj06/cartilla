<?php
use App\Models\Ejercicio;
$errores = $GLOBALS['_errores'] ?? [];
$porLeccion = [];
foreach ($ejercicios as $ej) {
    $porLeccion[$ej['leccion_titulo']][] = $ej;
}
// Valor previo enviado (si hubo error) o el guardado.
$v = function (int $id, string $clave, $porDefecto) use ($old) {
    if ($old !== null) {
        return $old['e'][$id][$clave] ?? ($clave === 'incluir' ? null : $porDefecto);
    }
    return $porDefecto;
};
$publicado = $curso['estado'] === 'publicado';
?>
<div class="pagina">
  <header class="titulo-fila">
    <div>
      <h1>Revisa tus juegos</h1>
      <p class="titulo-fila__sub num"><?= plural(count($ejercicios), 'ejercicio', 'ejercicios') ?> en <?= plural(count($porLeccion), 'lección', 'lecciones') ?> · <?= $publicado ? 'Publicada con el código ' . e($curso['codigo']) : 'Borrador: nadie lo ve todavía' ?></p>
    </div>
    <?php if ($primera): ?><a class="boton boton--suave" href="<?= url('/leccion/' . $primera['id']) ?>">Probar como estudiante</a><?php endif; ?>
  </header>

  <form method="post" action="<?= url('/docente/clase/' . $curso['id'] . '/revisar') ?>" novalidate data-cargando="Guardando">
    <?= csrf_field() ?>
    <?php if ($errores): ?>
      <div class="resumen-errores" role="alert" tabindex="-1" data-enfocar>
        <h2>Hay <?= plural(count($errores), 'ejercicio por corregir', 'ejercicios por corregir') ?></h2>
        <ul><?php foreach ($errores as $c => $m): ?><li><a href="#<?= e($c) ?>"><?= e($m) ?></a></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <div class="campo <?= isset($errores['titulo']) ? 'campo--error' : '' ?>" style="max-width:640px">
      <label for="titulo">Título de la clase</label>
      <input id="titulo" name="titulo_curso" maxlength="140" value="<?= e($old['titulo_curso'] ?? $curso['titulo']) ?>">
    </div>
    <p class="secundario" style="margin-bottom:24px">Cada ejercicio muestra la oración de tu texto de la que salió. Cambia lo que quieras o desmarca «Incluir» para quitarlo.</p>

    <?php foreach ($porLeccion as $leccionTitulo => $lista): ?>
      <section class="bloque" aria-labelledby="l-<?= md5($leccionTitulo) ?>">
        <h2 class="seccion-titulo" id="l-<?= md5($leccionTitulo) ?>">Lección: <?= e($leccionTitulo) ?></h2>
        <ol class="revision">
          <?php foreach ($lista as $ej): $id = $ej['id']; $err = $errores['e' . $id] ?? null; $incluido = $old === null || !empty($old['e'][$id]['incluir']); ?>
            <li id="e<?= $id ?>" class="<?= $incluido ? '' : 'quitado' ?>">
              <div class="revision__cabeza">
                <span class="revision__tipo"><?= e(Ejercicio::TIPOS[$ej['tipo']]) ?></span>
                <label class="casilla"><input type="checkbox" name="e[<?= $id ?>][incluir]" value="1" <?= $incluido ? 'checked' : '' ?> onchange="this.closest('li').classList.toggle('quitado', !this.checked)"> Incluir</label>
              </div>
              <?php if ($err): ?><p class="error-campo" role="alert"><?= e($err) ?></p><?php endif; ?>
              <div class="campo">
                <label for="en-<?= $id ?>"><?= $ej['tipo'] === 'completar' ? 'Frase (usa ___ donde va la palabra)' : 'Enunciado' ?></label>
                <textarea id="en-<?= $id ?>" name="e[<?= $id ?>][enunciado]" rows="2"><?= e($v($id, 'enunciado', $ej['enunciado'])) ?></textarea>
              </div>
              <?php switch ($ej['tipo']):
                case 'completar': ?>
                  <div class="campo"><label for="r-<?= $id ?>">Palabra que falta</label><p class="ayuda">Si aceptas varias, sepáralas con «/».</p><input id="r-<?= $id ?>" name="e[<?= $id ?>][respuesta]" value="<?= e($v($id, 'respuesta', implode(' / ', $ej['solucion']['respuestas']))) ?>"></div>
                  <?php break;
                case 'ordenar_palabras': ?>
                  <div class="campo"><label for="f-<?= $id ?>">Frase en el orden correcto</label><input id="f-<?= $id ?>" name="e[<?= $id ?>][frase]" value="<?= e($v($id, 'frase', $ej['solucion']['frase'])) ?>"></div>
                  <?php break;
                case 'ordenar_letras': ?>
                  <div class="campo"><label for="p-<?= $id ?>">Palabra</label><input id="p-<?= $id ?>" name="e[<?= $id ?>][palabra]" value="<?= e($v($id, 'palabra', $ej['solucion']['palabra'])) ?>"></div>
                  <div class="campo"><label for="pi-<?= $id ?>">Pista</label><input id="pi-<?= $id ?>" name="e[<?= $id ?>][pista]" value="<?= e($v($id, 'pista', $ej['datos']['pista'] ?? '')) ?>"></div>
                  <?php break;
                case 'test': $ops = $v($id, 'opciones', $ej['datos']['opciones']); $corr = (int) $v($id, 'correcta', $ej['solucion']['indice']); ?>
                  <fieldset class="campo" style="border:0;padding:0;margin:0">
                    <legend class="etiqueta">Opciones (marca la correcta)</legend>
                    <?php foreach ($ops as $k => $op): ?>
                      <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
                        <input type="radio" name="e[<?= $id ?>][correcta]" value="<?= $k ?>" <?= $corr === $k ? 'checked' : '' ?> aria-label="Opción <?= $k + 1 ?> es la correcta" style="width:20px;height:20px;accent-color:var(--accent)">
                        <input name="e[<?= $id ?>][opciones][<?= $k ?>]" value="<?= e($op) ?>" aria-label="Texto de la opción <?= $k + 1 ?>">
                      </div>
                    <?php endforeach; ?>
                  </fieldset>
                  <?php break;
                case 'si_no': $val = $v($id, 'valor', $ej['solucion']['valor']); ?>
                  <fieldset class="campo" style="border:0;padding:0;margin:0">
                    <legend class="etiqueta">Respuesta correcta</legend>
                    <label class="casilla"><input type="radio" name="e[<?= $id ?>][valor]" value="si" <?= $val === 'si' ? 'checked' : '' ?>> Sí</label>
                    <label class="casilla" style="margin-left:20px"><input type="radio" name="e[<?= $id ?>][valor]" value="no" <?= $val === 'no' ? 'checked' : '' ?>> No</label>
                  </fieldset>
                  <?php break;
                case 'relacionar': $pares = $v($id, 'pares', $ej['solucion']['pares']); ?>
                  <fieldset class="campo" style="border:0;padding:0;margin:0">
                    <legend class="etiqueta">Parejas</legend>
                    <?php foreach ($pares as $k => $p): ?>
                      <div style="display:grid;grid-template-columns:1fr 2fr;gap:8px;margin-bottom:8px">
                        <input name="e[<?= $id ?>][pares][<?= $k ?>][0]" value="<?= e($p[0]) ?>" aria-label="Término <?= $k + 1 ?>">
                        <input name="e[<?= $id ?>][pares][<?= $k ?>][1]" value="<?= e($p[1]) ?>" aria-label="Pareja del término <?= $k + 1 ?>">
                      </div>
                    <?php endforeach; ?>
                  </fieldset>
                  <?php break;
              endswitch; ?>
              <?php if ($ej['fuente']): ?><p class="revision__fuente">Sale de tu texto: «<?= e(mb_strimwidth($ej['fuente'], 0, 320, '…')) ?>»</p><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>
    <?php endforeach; ?>

    <div class="barra-guardar">
      <button type="submit" name="accion" value="guardar" class="boton boton--suave">Guardar cambios</button>
      <button type="submit" name="accion" value="publicar" class="boton"><?= $publicado ? 'Guardar y ver resultados' : 'Guardar y publicar con código' ?></button>
      <?php if (!$registrado): ?><span class="secundario">Para publicar te pediremos crear una cuenta; tus juegos no se pierden.</span><?php endif; ?>
    </div>
  </form>
</div>
