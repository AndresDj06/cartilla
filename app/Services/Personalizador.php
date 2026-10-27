<?php
namespace App\Services;

use App\Models\Ejercicio;
use App\Models\Perfil;

/**
 * Traduce las respuestas del onboarding en decisiones concretas y visibles:
 * cuántos ejercicios por lección, meta diaria, desde qué unidad empezar,
 * si se muestra banco de palabras y qué tipos de juego van primero.
 */
class Personalizador
{
    public const MINUTOS = [5 => ['ejercicios' => 5, 'meta' => 10], 10 => ['ejercicios' => 8, 'meta' => 20], 15 => ['ejercicios' => 10, 'meta' => 30]];

    public const NIVELES = [
        'cero' => ['texto' => 'Empiezo desde cero', 'unidad' => 1],
        'basico' => ['texto' => 'Ya sé lo básico', 'unidad' => 2],
        'reto' => ['texto' => 'Quiero un reto', 'unidad' => 2],
    ];

    public const GRADOS = [
        'primaria' => 'Primaria',
        'secundaria' => 'Secundaria (6.° a 9.°)',
        'media' => 'Media (10.° y 11.°)',
        'universidad' => 'Universidad',
    ];

    public const OBJETIVOS = [
        'estudiante' => [
            'evaluacion' => 'Prepararme para una evaluación',
            'reforzar' => 'Reforzar lo que vimos en clase',
            'nuevo' => 'Aprender algo nuevo por mi cuenta',
        ],
        'docente' => [
            'entre_clases' => 'Que mis estudiantes practiquen entre clases',
            'diagnostico' => 'Saber qué entendieron antes de avanzar',
            'examen' => 'Repasar antes de un examen',
        ],
    ];

    /** Minutos que sugerimos según el objetivo; se marcan como sugeridos en el paso de tiempo. */
    public const SUGERIDO = ['evaluacion' => 15, 'reforzar' => 10, 'nuevo' => 5, 'entre_clases' => 10, 'diagnostico' => 5, 'examen' => 15];

    public static function plan(array $perfil): array
    {
        $minutos = (int) ($perfil['minutos'] ?: 10);
        $base = self::MINUTOS[$minutos] ?? self::MINUTOS[10];
        $esDocente = $perfil['rol_elegido'] === 'docente';
        $nivel = $perfil['nivel'] ?: 'cero';
        $grado = $perfil['grado'] ?: 'secundaria';
        $tipos = Perfil::tipos($perfil) ?: array_keys(Ejercicio::TIPOS);

        // El banco de palabras ayuda a quien empieza y a primaria; se retira para el reto y la universidad.
        $conBanco = $esDocente ? in_array($grado, ['primaria', 'secundaria'], true) : $nivel !== 'reto';
        $vidas = ($nivel === 'reto' || $grado === 'universidad') ? 2 : 3;

        return [
            'ejercicios' => $base['ejercicios'],
            'meta' => $base['meta'],
            'minutos' => $minutos,
            'unidad_inicial' => $esDocente ? 1 : self::NIVELES[$nivel]['unidad'] ?? 1,
            'con_banco' => $conBanco,
            'vidas' => $vidas,
            'tipos' => $tipos,
        ];
    }

    /** Explicaciones de causa y efecto que se muestran en el resumen y en la ruta. */
    public static function decisiones(array $perfil, array $plan, ?array $curso): array
    {
        $esDocente = $perfil['rol_elegido'] === 'docente';
        $d = [];
        $rol = $esDocente ? 'docente' : 'estudiante';
        if (!empty($perfil['objetivo']) && isset(self::OBJETIVOS[$rol][$perfil['objetivo']])) {
            $sug = self::SUGERIDO[$perfil['objetivo']];
            $d[] = ['paso' => 'objetivo', 'elegiste' => self::OBJETIVOS[$rol][$perfil['objetivo']], 'efecto' => 'Por eso te sugerimos ' . $sug . ' minutos al día' . ($perfil['objetivo'] === 'evaluacion' || $perfil['objetivo'] === 'examen' ? ' y el repaso aparece primero en tu ruta cuando tengas errores pendientes.' : '.')];
        }
        if ($curso) {
            $d[] = ['paso' => 'tema', 'elegiste' => $curso['titulo'], 'efecto' => $esDocente
                ? 'Cartilla convirtió tu texto en una unidad con dos lecciones que puedes revisar antes de compartir.'
                : 'Tu ruta usa las unidades y lecciones de este curso.'];
        }
        $d[] = ['paso' => 'tiempo', 'elegiste' => $plan['minutos'] . ' minutos al día', 'efecto' => 'Cada lección trae ' . $plan['ejercicios'] . ' ejercicios y tu meta diaria es de ' . $plan['meta'] . ' XP.'];
        if ($esDocente) {
            $d[] = ['paso' => 'grado', 'elegiste' => self::GRADOS[$perfil['grado']] ?? 'Secundaria', 'efecto' => ($plan['con_banco']
                ? 'Las frases para completar muestran un banco de palabras.'
                : 'Las frases para completar piden escribir la respuesta, sin banco de palabras.') . ' Cada estudiante tendrá ' . $plan['vidas'] . ' vidas por lección.'];
        } else {
            $nivel = $perfil['nivel'] ?: 'cero';
            $d[] = ['paso' => 'nivel', 'elegiste' => self::NIVELES[$nivel]['texto'], 'efecto' => ($plan['unidad_inicial'] > 1
                ? 'Empiezas en la unidad ' . $plan['unidad_inicial'] . '; la unidad 1 queda abierta por si quieres repasarla.'
                : 'Empiezas por la unidad 1.') . ($plan['con_banco'] ? ' Tendrás banco de palabras al completar frases.' : ' Escribirás las respuestas sin banco de palabras.') . ' Tienes ' . $plan['vidas'] . ' vidas por lección.'];
        }
        $nombres = array_map(fn ($t) => Ejercicio::TIPOS[$t] ?? $t, $plan['tipos']);
        $d[] = ['paso' => 'juegos', 'elegiste' => implode(', ', $nombres), 'efecto' => count($plan['tipos']) === count(Ejercicio::TIPOS)
            ? 'Las lecciones mezclan los seis tipos de juego.'
            : 'Cada lección escoge primero estos juegos; los demás aparecen solo si faltan ejercicios para completarla.'];
        return $d;
    }

    /** Elige y ordena los ejercicios de una lección según el plan. */
    public static function seleccionar(array $ejercicios, array $plan): array
    {
        $preferidos = array_values(array_filter($ejercicios, fn ($e) => in_array($e['tipo'], $plan['tipos'], true)));
        $otros = array_values(array_filter($ejercicios, fn ($e) => !in_array($e['tipo'], $plan['tipos'], true)));
        $elegidos = array_slice(array_merge($preferidos, $otros), 0, $plan['ejercicios']);
        // Se respeta el orden pedagógico original dentro de lo elegido.
        usort($elegidos, fn ($a, $b) => (int) $a['orden'] <=> (int) $b['orden']);
        return $elegidos;
    }
}
