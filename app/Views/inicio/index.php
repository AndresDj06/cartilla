<?php
use App\Models\Ejercicio;
$banco = $ejercicio['banco'] ?? [];
$partes = explode('___', $ejercicio['enunciado'] ?? '___');
?>
<header class="cabecera">
  <a class="cabecera__marca" href="<?= url('/') ?>" aria-label="Cartilla, inicio">Cartilla</a>
  <nav class="cabecera__nav" aria-label="Cuenta">
    <a class="cabecera__enlace" href="<?= url($registrado ? '/ruta' : '/ingresar') ?>"><?= $registrado ? 'Mi ruta' : 'Ingresar' ?></a>
    <a class="boton" href="<?= url('/empezar') ?>">Empezar gratis</a>
  </nav>
</header>

<main id="principal">

<!-- La línea del agua: lo que no se repasa se hunde. Al hacer scroll la ola sube detrás del titular. -->
<section class="ola" aria-labelledby="t-portada" data-ola>
  <div class="ola__escena">
    <div class="ola__agua" aria-hidden="true">
      <svg class="ola__cresta" viewBox="0 0 2880 120" preserveAspectRatio="none">
        <path class="ola__onda ola__onda--fondo" d="M0 60 C 240 20 480 20 720 60 S 1200 100 1440 60 S 1920 20 2160 60 S 2640 100 2880 60 V 120 H 0 Z"/>
        <path class="ola__onda ola__onda--frente" d="M0 72 C 180 40 540 40 720 72 S 1260 104 1440 72 S 1980 40 2160 72 S 2700 104 2880 72 V 120 H 0 Z"/>
      </svg>
    </div>
    <div class="ola__capa">
      <h1 id="t-portada" class="titular" data-titular><span class="linea"><span>Que no se</span></span><span class="linea"><span>te olvide</span></span></h1>
    </div>
    <p class="ola__apoyo">Cartilla convierte el tema de clase en juegos de cinco minutos y te los repite justo antes de que se te olviden.</p>
    <p class="ola__hundido" aria-hidden="true">Sin repaso, a la semana queda cerca de una cuarta parte.</p>
  </div>
</section>

<section class="campos" aria-labelledby="t-campos">
  <div class="campo-color campo-color--amarillo">
    <h2 id="t-campos" class="campos__titulo">Así se queda</h2>
    <div class="ficha" data-ui>
      <p class="ficha__rotulo">Texto de la profe · Ciencias naturales</p>
      <p class="ficha__texto"><?= e($fuente) ?></p>
    </div>
    <p class="campo-color__frase">Primero, la profe pega su tema.</p>
  </div>
  <div class="campo-color campo-color--blanco">
    <div class="ficha" data-ui>
      <p class="ficha__rotulo">Completar frases · Lección 2</p>
      <p class="ficha__texto"><?= e($partes[0]) ?><span class="ficha__hueco"></span><?= e($partes[1] ?? '') ?></p>
      <ul class="ficha__banco" aria-label="Banco de palabras"><?php foreach ($banco as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
    </div>
    <p class="campo-color__frase">Luego, Cartilla lo vuelve juego.</p>
  </div>
  <div class="campo-color campo-color--rosa">
    <form class="ficha ficha--viva" data-ui data-ficha-viva data-id="<?= (int) ($ejercicio['id'] ?? 0) ?>">
      <p class="ficha__rotulo">Pregunta real · Ciencias naturales</p>
      <p class="ficha__texto"><?= e($partes[0]) ?><span class="ficha__hueco" data-hueco aria-live="polite"></span><?= e($partes[1] ?? '') ?></p>
      <fieldset class="ficha__opciones">
        <legend class="sr">Elige la palabra que falta</legend>
        <?php foreach ($banco as $p): ?>
          <label><input type="radio" name="respuesta" value="<?= e($p) ?>"><span><?= e($p) ?></span></label>
        <?php endforeach; ?>
      </fieldset>
      <button type="submit" class="ficha__boton">Comprobar</button>
      <noscript><p>Para responder aquí activa JavaScript, o <a href="<?= url('/empezar') ?>">haz una lección completa</a>.</p></noscript>
    </form>
    <p class="campo-color__frase">Ahora te toca: responde aquí.</p>
  </div>
  <div class="campo-color campo-color--cian">
    <div class="ficha ficha--repaso" data-ui data-ficha-repaso aria-live="polite">
      <p class="ficha__rotulo">Tu repaso</p>
      <p class="ficha__estado" data-estado>Responde la tarjeta de al lado y mira qué pasa con ella.</p>
      <ol class="cajas" aria-label="Días de espera entre repasos">
        <li data-caja="1"><span class="num">1</span> día</li><li data-caja="2"><span class="num">2</span></li><li data-caja="3"><span class="num">4</span></li><li data-caja="4"><span class="num">7</span></li><li data-caja="5"><span class="num">15</span></li>
      </ol>
    </div>
    <p class="campo-color__frase">Y vuelve cuando lo ibas a olvidar.</p>
  </div>
</section>

<section class="interruptor-sec panel" data-modo="sin" aria-labelledby="t-interruptor">
  <div class="interruptor-sec__texto">
    <h2 id="t-interruptor">Sin repaso, <span data-palabra>se hunde</span></h2>
    <p>Lo que recuerdas de un tema durante dos semanas.</p>
    <p class="interruptor-sec__cifra"><span class="num" data-cifra>23 %</span><span>queda a los 14 días</span></p>
    <p class="interruptor-sec__nota">Modelo ilustrativo. Sin repaso: fórmula de Ebbinghaus (1885). Con repaso: repasos a los 1, 2, 4 y 7 días.</p>
  </div>
  <div class="control-gigante" data-ui>
    <svg class="curva" viewBox="0 0 300 120" aria-hidden="true" preserveAspectRatio="none"><path data-curva d="" fill="none" stroke="currentColor" stroke-width="4" vector-effect="non-scaling-stroke"/></svg>
    <div class="segmentos" role="radiogroup" aria-label="Repaso">
      <button type="button" role="radio" aria-checked="true" data-valor="sin">Sin repaso</button>
      <button type="button" role="radio" aria-checked="false" data-valor="con">Con repaso</button>
    </div>
  </div>
</section>

<section class="rasgos panel" aria-labelledby="t-rasgos">
  <h2 id="t-rasgos" class="rasgos__titulo">Seis juegos, una ruta</h2>
  <div class="rasgos__lista">
    <article class="rasgo">
      <h3>Los juegos de siempre</h3>
      <div class="rasgo__fila">
        <div class="rasgo__marco"><img src="<?= url('assets/img/juegos-creativos.jpg') ?>" width="1024" height="768" alt="Dos estudiantes de uniforme presentan su trabajo frente a un mural de figuras geométricas" loading="lazy"></div>
        <ul><?php foreach (Ejercicio::TIPOS as $nombre): ?><li><?= e($nombre) ?></li><?php endforeach; ?></ul>
      </div>
    </article>
    <article class="rasgo rasgo--invertido">
      <h3>Una ruta de cinco minutos</h3>
      <div class="rasgo__fila">
        <div class="rasgo__marco"><img src="<?= url('assets/img/free-student-texting-class-image.webp') ?>" width="1024" height="683" alt="Una estudiante usa su celular en clase mientras el profesor escribe en el tablero" loading="lazy"></div>
        <ul><li>Lecciones que se abren en orden</li><li>Meta diaria de XP</li><li>Racha de días seguidos</li><li>Tres vidas por lección</li><li>Empiezas según tu nivel</li></ul>
      </div>
    </article>
    <article class="rasgo">
      <h3>Repaso a tiempo</h3>
      <div class="rasgo__fila">
        <div class="rasgo__marco"><img src="<?= url('assets/img/unicef-ecu-2020-pintado.jpg') ?>" width="1024" height="683" alt="Una niña sonríe con los ojos cerrados, al aire libre" loading="lazy"></div>
        <ul><li>Lo fallado vuelve al día siguiente</li><li>Luego a los 2, 4, 7 y 15 días</li><li>Primero, si preparas evaluación</li><li>Practicar recordando fija más (Roediger y Karpicke, 2006)</li></ul>
      </div>
    </article>
    <article class="rasgo rasgo--invertido">
      <h3>Panel para la profe</h3>
      <div class="rasgo__fila">
        <div class="rasgo__marco"><img src="<?= url('assets/img/nivin-maestra-mostrando-caracteristicas-de-una-tableta.jpg') ?>" width="1024" height="576" alt="Una maestra muestra una tableta a sus estudiantes en el salón" loading="lazy"></div>
        <ul><li>Acierto por pregunta</li><li>Las respuestas equivocadas más comunes</li><li>Avance de cada estudiante</li><li>Exporta a CSV</li><li>Código de seis letras para entrar</li></ul>
      </div>
    </article>
    <article class="rasgo">
      <h3>Tu texto, tus juegos</h3>
      <div class="rasgo__fila">
        <div class="rasgo__marco"><img src="<?= url('assets/img/docentes-wayuu.jpg') ?>" width="1920" height="864" alt="Docentes wayuu de escuelas rurales trabajan juntas en un aula" loading="lazy"></div>
        <ul><li>Pegas dos párrafos de tu tema</li><li>Revisas y corriges cada ejercicio</li><li>Ves la oración de la que salió</li><li>Tu texto no sale del servidor</li></ul>
      </div>
    </article>
  </div>
</section>

<section class="hechos panel" aria-labelledby="t-hechos">
  <div class="hechos__tablero">
    <div class="hecho hecho--titulo"><h2 id="t-hechos">Lo que ya trae</h2></div>
    <div class="hecho"><p class="hecho__dato"><span class="hecho__cifra num"><?= (int) $cifras['cursos'] ?></span> cursos listos para practicar</p></div>
    <div class="hecho"><p class="hecho__dato"><span class="hecho__cifra num"><?= (int) $cifras['lecciones'] ?></span> lecciones de cinco a diez minutos</p></div>
    <div class="hecho"><p class="hecho__dato"><span class="hecho__cifra num"><?= (int) $cifras['ejercicios'] ?></span> ejercicios con su explicación</p></div>
    <div class="hecho"><p class="hecho__dato"><span class="hecho__cifra num"><?= (int) $cifras['tipos'] ?></span> tipos de juego</p></div>
    <div class="hecho"><p class="hecho__dato"><span class="hecho__cifra num">$0</span> durante el piloto</p></div>
    <div class="hecho"><p class="hecho__dato"><span class="hecho__cifra num">AGUA26</span> clase de demostración</p></div>
    <?php foreach (array_slice($catalogo, 0, 4) as $c): ?>
      <div class="hecho hecho--curso"><p class="hecho__nombre"><?= e($c['titulo']) ?></p><p><?= e($c['materia']) ?></p></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="boleto-sec panel" aria-labelledby="t-boleto">
  <div class="boleto">
    <div class="boleto__cuerpo">
      <h2 id="t-boleto">Gratis en el piloto</h2>
      <ul>
        <li>Sin tarjeta y sin correo para probar</li>
        <li>Para docentes y estudiantes</li>
        <li>Descargas o borras tus datos cuando quieras</li>
        <li>Lo que creas sigue siendo tuyo</li>
      </ul>
    </div>
    <div class="boleto__talon">
      <p class="boleto__precio num">$0</p>
      <a class="boleto__barra" href="<?= url('/empezar') ?>">Empezar</a>
    </div>
  </div>
</section>

<section class="dudas panel" aria-labelledby="t-dudas">
  <h2 id="t-dudas">Lo que preguntan</h2>
  <div class="dudas__lista">
    <details name="dudas" open><summary>¿Tengo que registrarme para probar?</summary><p>No. Respondes seis preguntas, juegas una lección completa y ves tu resultado. Te pedimos correo solo si quieres guardar el avance para entrar desde otro dispositivo, y todo lo que hiciste pasa a tu cuenta.</p></details>
    <details name="dudas"><summary>¿Qué cambia según lo que respondo al empezar?</summary><p>El curso, la unidad donde empiezas, cuántos ejercicios trae cada lección, tu meta diaria, si hay banco de palabras, cuántas vidas tienes y qué juegos salen primero. El resumen te dice cada decisión y la puedes cambiar.</p></details>
    <details name="dudas"><summary>¿Cómo arma los juegos a partir de mi texto?</summary><p>Con reglas sobre tus oraciones: busca palabras clave, frases cortas y definiciones del tipo «X es…». No usa servicios externos y cada ejercicio muestra la oración de la que salió, para que lo revises antes de publicar.</p></details>
    <details name="dudas"><summary>¿Cómo entran mis estudiantes?</summary><p>Con el código de seis letras de tu clase, sin O ni I para que no se confundan al dictarlo. Pueden empezar sin cuenta y crearla después.</p></details>
    <details name="dudas"><summary>¿Qué datos guardan?</summary><p>Nombre, usuario, correo y tus respuestas, solo para tu práctica y para el panel de tu docente. Sin publicidad. Desde «Mi cuenta» descargas o borras todo. <a href="<?= url('/privacidad') ?>">Detalle de datos</a>.</p></details>
    <details name="dudas"><summary>¿Funciona en el celular?</summary><p>Sí, en el navegador, con botones grandes y también con teclado. Necesita conexión: cada respuesta se guarda al momento, así que si se corta, sigues donde ibas.</p></details>
    <details name="dudas"><summary>¿Qué no hace todavía?</summary><p>No envía recordatorios, no es una app instalable, no funciona sin internet y el generador rinde mejor con textos informativos que con poemas o cuentos.</p></details>
  </div>
</section>

<section class="eleccion panel" aria-labelledby="t-eleccion">
  <h2 id="t-eleccion" class="eleccion__pregunta"><span class="eleccion__l1">¿Estudias</span> <span class="eleccion__l2">o enseñas?</span></h2>
  <div class="eleccion__centro">
    <div class="eleccion__botones">
      <a class="boton boton--grande" href="<?= url('/empezar/rol?rol=estudiante') ?>">Estudio</a>
      <span class="eleccion__o">o</span>
      <a class="boton boton--grande" href="<?= url('/empezar/rol?rol=docente') ?>">Enseño</a>
    </div>
    <p>En los dos casos pruebas sin registrarte. ¿Tienes un código de clase? <a href="<?= url('/unirse') ?>">Entra aquí</a>.</p>
  </div>
</section>

</main>

<footer class="pie" aria-labelledby="t-pie">
  <figure class="pie__foto">
    <img src="<?= url('assets/img/asset-collection-trip-to-colombia.jpg') ?>" width="1300" height="865" alt="Estudiantes de un colegio en Colombia levantan la mano para participar" loading="lazy">
  </figure>
  <div class="pie__menu">
    <p class="pie__marca">Cartilla</p>
    <h2 id="t-pie" class="sr">Menú</h2>
    <ul class="pie__enlaces">
      <li><a href="<?= url('/empezar') ?>">Empezar gratis</a></li>
      <li><a href="<?= url('/empezar/rol?rol=docente') ?>">Crear juegos</a></li>
      <li><a href="<?= url('/unirse') ?>">Código de clase</a></li>
      <li><a href="<?= url('/ingresar') ?>">Ingresar</a></li>
      <li><a href="<?= url('/privacidad') ?>">Tus datos</a></li>
    </ul>
    <div class="pie__bloques">
      <p>Hecho en Colombia para una hackatón: los juegos que crean los docentes y el hábito diario de una app de idiomas, en un solo lugar.</p>
      <p>Funciona de verdad y guarda tu avance. Prueba la clase de demostración con el código <b>AGUA26</b>.</p>
    </div>
    <p class="pie__legal">Fotos: <a href="https://www.rawpixel.com/image/3382437/free-photo-image-student-raising-hands-raised-hand-2013">«Asset collection trip to Colombia»</a>, Peace Corps, CC0; <a href="https://www.flickr.com/photos/18618970@N00/1737784363">«juegos creativos»</a>, medea_material, <a href="https://creativecommons.org/licenses/by/2.0/">CC BY 2.0</a>; <a href="https://www.rawpixel.com/image/5921219/photo-image-public-domain-student-woman">«Free student texting class image»</a>, CC0; <a href="https://www.flickr.com/photos/37021522@N02/50071954543">©UNICEF/ECU/2020/Pintado</a>, <a href="https://creativecommons.org/licenses/by/2.0/">CC BY 2.0</a>; <a href="https://commons.wikimedia.org/wiki/File:Niv%C3%ADn._Maestra_mostrando_caracter%C3%ADsticas_de_una_tableta_a_estudiante.jpg">«Nivín. Maestra mostrando características de una tableta»</a>, Escuela de Nivín, <a href="https://creativecommons.org/licenses/by-sa/4.0">CC BY-SA 4.0</a>; <a href="https://commons.wikimedia.org/wiki/File:Docentes_Wayuu.jpg">«Docentes Wayuu»</a>, Neima Paz, CC0. © 2026 Cartilla.</p>
  </div>
</footer>
