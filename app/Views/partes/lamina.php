<?php
// Una fotografía real por paso, a la derecha del papel en pantallas anchas.
$fotosPaso = [
    'rol' => ['asset-collection-trip-to-colombia.jpg', 'Estudiantes de un colegio en Colombia levantan la mano para participar'],
    'objetivo' => ['juegos-creativos.jpg', 'Dos estudiantes de uniforme presentan su trabajo'],
    'tema' => ['docentes-wayuu.jpg', 'Docentes wayuu de escuelas rurales trabajan juntas en un aula'],
    'nivel' => ['free-student-texting-class-image.webp', 'Una estudiante usa su celular en clase'],
    'grado' => ['nivin-maestra-mostrando-caracteristicas-de-una-tableta.jpg', 'Una maestra muestra una tableta a sus estudiantes'],
    'tiempo' => ['free-student-texting-class-image.webp', 'Una estudiante usa su celular en clase'],
    'juegos' => ['salonconestudiantes.jpg', 'Estudiantes de un colegio colombiano en clase'],
    'resumen' => ['unicef-ecu-2020-pintado.jpg', 'Una niña sonríe con los ojos cerrados'],
];
[$fotoArchivo, $fotoAlt] = $fotosPaso[$fotoClave ?? 'rol'] ?? $fotosPaso['rol'];
?>
<figure class="lamina" aria-hidden="false">
  <img src="<?= url('assets/img/' . $fotoArchivo) ?>" alt="<?= e($fotoAlt) ?>" width="1200" height="1600">
</figure>
