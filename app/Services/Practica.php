<?php
namespace App\Services;

use App\Models\Curso;
use App\Models\Ejercicio;
use App\Models\Intento;
use App\Models\Perfil;
use App\Models\Progreso;

/**
 * Recorrido principal: ruta de lecciones, intentos, corrección, XP, racha y repaso.
 */
class Practica
{
    public const XP_ACIERTO = 2;
    public const XP_COMPLETAR = 5;

    /** Plan de la persona; en cursos de un docente, el grado elegido por el docente manda en banco y vidas. */
    public static function plan(array $usuario, array $curso): array
    {
        $plan = Personalizador::plan(Perfil::de((int) $usuario['id']));
        if (!empty($curso['grado']) && (int) $curso['autor_id'] !== (int) $usuario['id']) {
            $plan['con_banco'] = in_array($curso['grado'], ['primaria', 'secundaria'], true);
            $plan['vidas'] = $curso['grado'] === 'universidad' ? 2 : 3;
            $plan['unidad_inicial'] = 1;
        }
        return $plan;
    }

    /** Unidades con el estado de cada lección: hecha, disponible, actual o bloqueada. */
    public static function ruta(array $usuario, array $curso, array $plan): array
    {
        $unidades = Curso::estructura((int) $curso['id']);
        $progreso = Progreso::deCurso((int) $usuario['id'], (int) $curso['id']);
        $inicial = min($plan['unidad_inicial'], max(1, count($unidades)));
        $anteriorHecha = true;
        $actualMarcada = false;
        foreach ($unidades as &$u) {
            $previa = (int) $u['orden'] < $inicial;
            $u['opcional'] = $previa;
            $hechas = 0;
            foreach ($u['lecciones'] as &$l) {
                $p = $progreso[(int) $l['id']] ?? null;
                $l['hecha'] = $p && $p['completada_en'] !== null;
                $l['precision'] = $p ? (int) $p['mejor_precision'] : null;
                if ($l['hecha']) {
                    $l['estado'] = 'hecha';
                    $hechas++;
                } elseif ($previa || $anteriorHecha) {
                    $l['estado'] = 'disponible';
                } else {
                    $l['estado'] = 'bloqueada';
                }
                if (!$previa) {
                    if ($l['estado'] === 'disponible' && !$actualMarcada) {
                        $l['estado'] = 'actual';
                        $actualMarcada = true;
                    }
                    $anteriorHecha = $l['hecha'];
                }
            }
            unset($l);
            $u['hechas'] = $hechas;
        }
        unset($u);
        return $unidades;
    }

    public static function leccionDisponible(array $ruta, int $leccionId): bool
    {
        foreach ($ruta as $u) {
            foreach ($u['lecciones'] as $l) {
                if ((int) $l['id'] === $leccionId) {
                    return $l['estado'] !== 'bloqueada';
                }
            }
        }
        return false;
    }

    public static function siguienteLeccion(array $ruta): ?array
    {
        foreach ($ruta as $u) {
            foreach ($u['lecciones'] as $l) {
                if ($l['estado'] === 'actual') {
                    return $l;
                }
            }
        }
        return null;
    }

    public static function iniciarLeccion(array $usuario, array $leccion, array $plan): array
    {
        $abierto = Intento::abierto((int) $usuario['id'], (int) $leccion['id'], 'leccion');
        if ($abierto) {
            return $abierto;
        }
        $elegidos = Personalizador::seleccionar(Ejercicio::deLeccion((int) $leccion['id']), $plan);
        if (!$elegidos) {
            throw new \RuntimeException('Esta lección todavía no tiene ejercicios.');
        }
        $id = Intento::crear((int) $usuario['id'], (int) $leccion['id'], (int) $leccion['curso_id'], 'leccion', array_column($elegidos, 'id'), $plan['vidas']);
        return Intento::buscar($id);
    }

    public static function iniciarRepaso(array $usuario, array $curso): ?array
    {
        $abierto = Intento::abierto((int) $usuario['id'], null, 'repaso');
        if ($abierto && (int) $abierto['curso_id'] === (int) $curso['id']) {
            return $abierto;
        }
        $pendientes = Progreso::pendientesRepaso((int) $usuario['id'], (int) $curso['id'], 8);
        if (!$pendientes) {
            return null;
        }
        $id = Intento::crear((int) $usuario['id'], null, (int) $curso['id'], 'repaso', array_map('intval', array_column($pendientes, 'ejercicio_id')), 99);
        return Intento::buscar($id);
    }

    /** Ejercicios del intento listos para el navegador, más el estado para poder reanudar. */
    public static function paquete(array $intento, array $plan): array
    {
        $porId = Ejercicio::variosPorId($intento['ejercicio_ids']);
        $ejercicios = [];
        foreach ($intento['ejercicio_ids'] as $id) {
            if (isset($porId[$id])) {
                $ejercicios[] = Evaluador::preparar($porId[$id], (int) $intento['id'], $plan['con_banco']);
            }
        }
        $resueltos = [];
        foreach (Intento::respuestas((int) $intento['id']) as $r) {
            if ($r['correcta']) {
                $resueltos[] = (int) $r['ejercicio_id'];
            }
        }
        return [
            'intento' => (int) $intento['id'],
            'modo' => $intento['modo'],
            'vidas' => (int) $intento['vidas'],
            'vidasMax' => $intento['modo'] === 'repaso' ? null : $plan['vidas'],
            'ejercicios' => $ejercicios,
            'resueltos' => array_values(array_unique($resueltos)),
        ];
    }

    public static function responder(array $usuario, array $intento, int $ejercicioId, $respuesta): array
    {
        if ((int) $intento['usuario_id'] !== (int) $usuario['id']) {
            throw new \DomainException('Este intento pertenece a otra persona.', 403);
        }
        if ($intento['estado'] !== 'en_curso') {
            throw new \DomainException('Esta lección ya terminó. Vuelve a tu ruta para empezar otra.', 409);
        }
        if (!in_array($ejercicioId, $intento['ejercicio_ids'], true)) {
            throw new \DomainException('Ese ejercicio no hace parte de esta lección.', 422);
        }
        if ($respuesta === null || $respuesta === '' || $respuesta === []) {
            throw new \DomainException('Elige o escribe una respuesta antes de comprobar.', 422);
        }
        $ejercicio = Ejercicio::buscar($ejercicioId);
        $resultado = Evaluador::evaluar($ejercicio, $respuesta);
        $primera = Intento::registrarRespuesta((int) $intento['id'], $ejercicioId, Evaluador::respuestaTexto($ejercicio, $respuesta), $resultado['correcta']);

        $vidas = (int) $intento['vidas'];
        if (!$resultado['correcta'] && $intento['modo'] === 'leccion') {
            $vidas = Intento::restarVida((int) $intento['id']);
        }
        if ($primera || !$resultado['correcta']) {
            Progreso::programarRepaso((int) $usuario['id'], $ejercicioId, $resultado['correcta'], $intento['modo'] === 'repaso');
        }
        return [
            'correcta' => $resultado['correcta'],
            'solucion' => $resultado['solucion'],
            'explicacion' => $ejercicio['explicacion'],
            'fuente' => $ejercicio['fuente'],
            'vidas' => $vidas,
            'sinVidas' => $intento['modo'] === 'leccion' && $vidas <= 0,
        ];
    }

    public static function terminar(array $usuario, array $intento): array
    {
        if ((int) $intento['usuario_id'] !== (int) $usuario['id']) {
            throw new \DomainException('Este intento pertenece a otra persona.', 403);
        }
        if ($intento['estado'] !== 'en_curso') {
            return $intento;
        }
        $respuestas = Intento::respuestas((int) $intento['id']);
        $aciertosPrimera = 0;
        $resueltos = [];
        foreach ($respuestas as $r) {
            if ($r['primera_vez'] && $r['correcta']) {
                $aciertosPrimera++;
            }
            if ($r['correcta']) {
                $resueltos[(int) $r['ejercicio_id']] = true;
            }
        }
        $total = count($intento['ejercicio_ids']);
        $completo = count($resueltos) >= $total;
        $sinVidas = $intento['modo'] === 'leccion' && (int) $intento['vidas'] <= 0;
        if (!$completo && !$sinVidas) {
            throw new \DomainException('Aún quedan ejercicios por resolver en esta lección.', 409);
        }
        $estado = $sinVidas ? 'sin_vidas' : 'completado';
        $xp = $aciertosPrimera * self::XP_ACIERTO + ($estado === 'completado' && $intento['modo'] === 'leccion' ? self::XP_COMPLETAR : 0);
        Intento::cerrar((int) $intento['id'], $estado, $aciertosPrimera, $xp);
        if ($xp > 0) {
            Progreso::sumarXp((int) $usuario['id'], $xp);
        }
        if ($intento['modo'] === 'leccion') {
            $precision = $total ? (int) round(100 * $aciertosPrimera / $total) : 0;
            Progreso::registrarLeccion((int) $usuario['id'], (int) $intento['leccion_id'], $precision, $estado === 'completado');
        }
        return Intento::buscar((int) $intento['id']);
    }
}
