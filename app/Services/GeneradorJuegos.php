<?php
namespace App\Services;

/**
 * Convierte un texto del docente en ejercicios de seis tipos con reglas explicables.
 * No usa servicios externos: el texto nunca sale del servidor. Cada ejercicio guarda
 * la oración de la que salió, para que el docente pueda verificarlo.
 */
class GeneradorJuegos
{
    public const MIN_CARACTERES = 400;
    public const MAX_CARACTERES = 12000;

    private const VACIAS = ['a', 'al', 'algo', 'algunas', 'algunos', 'ante', 'antes', 'aquel', 'aquella', 'aquellas', 'aquellos', 'aqui', 'asi',
        'aun', 'aunque', 'bajo', 'bien', 'cada', 'casi', 'como', 'con', 'contra', 'cual', 'cuales', 'cuando', 'cuanto', 'de', 'del', 'desde',
        'donde', 'dos', 'durante', 'el', 'ella', 'ellas', 'ellos', 'en', 'entre', 'era', 'eran', 'eres', 'es', 'esa', 'esas', 'ese', 'eso', 'esos',
        'esta', 'estaba', 'estado', 'estan', 'estar', 'estas', 'este', 'esto', 'estos', 'fue', 'fueron', 'gran', 'grandes', 'ha', 'habia',
        'han', 'hasta', 'hay', 'hace', 'hacen', 'hacer', 'hacia', 'la', 'las', 'le', 'les', 'lo', 'los', 'mas', 'mayor', 'mediante', 'menos',
        'mientras', 'mismo', 'misma', 'mismos', 'mucho', 'muchos', 'muy', 'nada', 'ni', 'no', 'nos', 'nosotros', 'nuestra', 'nuestro', 'o',
        'otra', 'otras', 'otro', 'otros', 'para', 'parte', 'pero', 'poco', 'por', 'porque', 'pueden', 'puede', 'que', 'quien', 'se', 'sea',
        'segun', 'ser', 'si', 'sido', 'siempre', 'sin', 'sobre', 'son', 'su', 'sus', 'tal', 'tambien', 'tan', 'tanto', 'tiene', 'tienen',
        'todo', 'todos', 'toda', 'todas', 'tras', 'un', 'una', 'unas', 'uno', 'unos', 'y', 'ya', 'cuyo', 'cuya', 'luego', 'despues', 'entonces',
        'tiempo', 'forma', 'manera', 'veces', 'llamado', 'llamada', 'llaman', 'ademas', 'embargo', 'pues', 'dentro', 'fuera', 'mediante',
        'otras', 'cuales', 'primer', 'primero', 'primera', 'segundo', 'segunda', 'cierto', 'cierta', 'puede', 'podemos', 'debemos', 'deben',
        'nuestras', 'nuestros', 'estamos', 'tenemos', 'hacemos', 'vamos', 'quiere', 'decir', 'existe', 'existen',
        'lleva', 'llevan', 'llega', 'llegan', 'corre', 'corren', 'sube', 'suben', 'caen', 'forman', 'ayuda', 'ayudan', 'tienen', 'pueden',
        'hacia', 'desde', 'donde', 'gracias', 'demasiado', 'pequenas', 'pequenos', 'grande', 'muchas', 'varios', 'varias', 'llamada'];

    private const DEFINICION = '/^(?:el|la|los|las|un|una)?\s*([\p{L} ]{3,40}?)\s+(?:es|son|significa|se llama|consiste en|se conoce como)\s+(.{10,90}?)(?:[.,;]|$)/iu';

    /**
     * @return array{titulo: string, lecciones: array<int, array{titulo: string, ejercicios: array}>, claves: array}
     * @throws \InvalidArgumentException con un mensaje que se puede mostrar a la persona.
     */
    public static function generar(string $texto, string $titulo = ''): array
    {
        $texto = trim(preg_replace("/[ \t]+/u", ' ', str_replace("\r", '', $texto)));
        $largo = mb_strlen($texto);
        if ($largo < self::MIN_CARACTERES) {
            throw new \InvalidArgumentException('El texto tiene ' . $largo . ' caracteres. Necesitamos al menos ' . self::MIN_CARACTERES . ' (un par de párrafos) para armar juegos con sentido.');
        }
        if ($largo > self::MAX_CARACTERES) {
            $texto = mb_substr($texto, 0, self::MAX_CARACTERES);
        }

        $oraciones = self::oraciones($texto);
        if (count($oraciones) < 4) {
            throw new \InvalidArgumentException('Encontramos ' . count($oraciones) . ' oraciones completas. Escribe al menos 4 oraciones terminadas en punto, de 6 palabras o más.');
        }
        $claves = self::palabrasClave($oraciones);
        if (count($claves) < 4) {
            throw new \InvalidArgumentException('El texto repite pocas palabras importantes. Agrega más detalle del tema (nombres, conceptos, lugares) e inténtalo de nuevo.');
        }

        $usadas = [];
        $completar = self::completar($oraciones, $claves, $usadas, 4);
        $siNo = self::siNo($oraciones, $claves, $usadas, 2);
        $test = self::test($oraciones, $claves, $usadas, 2);
        $ordenar = self::ordenarPalabras($oraciones, $usadas, 2);
        $respondidas = array_map(fn ($e) => normalizar($e['solucion']['respuestas'][0]), $completar);
        $letras = self::ordenarLetras($oraciones, array_values(array_filter($claves, fn ($c) => !in_array(normalizar($c), $respondidas, true))), 2);
        $relacionar = self::relacionar($oraciones, $claves);

        $leccion1 = array_merge(array_slice($completar, 0, 2), $siNo, $test);
        $leccion2 = array_merge($ordenar, $letras, $relacionar ? [$relacionar] : [], array_slice($completar, 2));
        if (count($leccion1) + count($leccion2) < 5) {
            throw new \InvalidArgumentException('Solo pudimos armar ' . (count($leccion1) + count($leccion2)) . ' ejercicios. Usa oraciones más completas (sujeto, verbo y dato) para obtener al menos 5.');
        }

        if ($titulo === '') {
            $primera = trim(strtok($texto, "\n"));
            $titulo = mb_strlen($primera) <= 60 && !preg_match('/[.!?]$/u', $primera) ? $primera : 'Tema: ' . mb_convert_case($claves[0], MB_CASE_TITLE);
        }

        return [
            'titulo' => mb_substr($titulo, 0, 120),
            'claves' => array_slice($claves, 0, 10),
            'lecciones' => array_values(array_filter([
                ['titulo' => 'Lo esencial', 'ejercicios' => $leccion1],
                ['titulo' => 'Ponlo en orden', 'ejercicios' => $leccion2],
            ], fn ($l) => count($l['ejercicios']) > 0)),
        ];
    }

    private static function oraciones(string $texto): array
    {
        $partes = preg_split('/(?<=[.!?])\s+|\n+/u', $texto);
        $oraciones = [];
        foreach ($partes as $p) {
            $p = trim(preg_replace('/^[-•*\d.)\s]+/u', '', $p));
            $n = count(preg_split('/\s+/u', $p));
            if ($n >= 6 && $n <= 40 && preg_match('/[.!?]$/u', $p)) {
                $oraciones[] = $p;
            }
        }
        return array_values(array_unique($oraciones));
    }

    /** Palabras de 5+ letras, sin vacías, puntuadas por frecuencia, longitud y mayúscula interna. */
    private static function palabrasClave(array $oraciones): array
    {
        $puntos = [];
        $forma = [];
        foreach ($oraciones as $o) {
            preg_match_all('/\p{L}+/u', $o, $m, PREG_OFFSET_CAPTURE);
            foreach ($m[0] as $k => [$palabra, $pos]) {
                $n = normalizar($palabra);
                // Se descartan infinitivos en minúscula (cuidar, proteger): no sirven como distractores.
                if (mb_strlen($n) < 5 || in_array($n, self::VACIAS, true) || ($k === 0 || !preg_match('/^\p{Lu}/u', $palabra)) && preg_match('/(ar|er|ir)$/', $n)) {
                    continue;
                }
                $nombrePropio = $k > 0 && preg_match('/^\p{Lu}/u', $palabra);
                $puntos[$n] = ($puntos[$n] ?? 0) + 1 + ($nombrePropio ? 1.5 : 0) + min(mb_strlen($n), 12) / 12;
                // La forma de inicio de oración no cuenta como mayúscula propia.
                if ($k > 0) {
                    $forma[$n] = $palabra;
                } else {
                    $forma[$n] = $forma[$n] ?? mb_strtolower($palabra);
                }
            }
        }
        arsort($puntos);
        $claves = [];
        foreach (array_keys($puntos) as $n) {
            // Evita singular/plural repetidos (río / ríos).
            foreach ($claves as $c) {
                if (str_starts_with(normalizar($c), $n) || str_starts_with($n, normalizar($c))) {
                    continue 2;
                }
            }
            $claves[] = $forma[$n];
            if (count($claves) >= 14) {
                break;
            }
        }
        return $claves;
    }

    private static function contiene(string $oracion, string $clave): bool
    {
        return (bool) preg_match('/(?<!\p{L})' . preg_quote($clave, '/') . '(?!\p{L})/iu', $oracion);
    }

    private static function reemplazar(string $oracion, string $clave, string $por): string
    {
        return preg_replace('/(?<!\p{L})' . preg_quote($clave, '/') . '(?!\p{L})/iu', $por, $oracion, 1);
    }

    private static function esPropio(string $palabra): bool
    {
        return (bool) preg_match('/^\p{Lu}/u', $palabra);
    }

    /** Otras palabras clave, primero las de la misma clase (nombre propio o común) para que el distractor sea creíble. */
    private static function otrasClaves(array $claves, string $excepto, int $n, int $semilla, bool $soloMismaClase = false): array
    {
        $otras = array_values(array_filter($claves, fn ($c) => normalizar($c) !== normalizar($excepto)));
        mt_srand($semilla);
        shuffle($otras);
        mt_srand();
        $propio = self::esPropio($excepto);
        $plural = !$propio && str_ends_with(normalizar($excepto), 's');
        $misma = array_values(array_filter($otras, fn ($c) => self::esPropio($c) === $propio
            && ($propio || !$soloMismaClase || str_ends_with(normalizar($c), 's') === $plural)));
        $resto = $soloMismaClase ? [] : array_values(array_filter($otras, fn ($c) => self::esPropio($c) !== $propio));
        return array_slice(array_merge($misma, $resto), 0, $n);
    }

    private static function completar(array $oraciones, array $claves, array &$usadas, int $max): array
    {
        $res = [];
        foreach ($claves as $i => $clave) {
            foreach ($oraciones as $k => $o) {
                if (isset($usadas[$k]) || !self::contiene($o, $clave)) {
                    continue;
                }
                $usadas[$k] = true;
                $res[] = [
                    'tipo' => 'completar',
                    'enunciado' => self::reemplazar($o, $clave, '___'),
                    'datos' => ['banco' => array_merge([$clave], self::otrasClaves($claves, $clave, 3, $i))],
                    'solucion' => ['respuestas' => [$clave]],
                    'explicacion' => 'La palabra que falta es «' . $clave . '».',
                    'fuente' => $o,
                ];
                break;
            }
            if (count($res) >= $max) {
                break;
            }
        }
        return $res;
    }

    private static function alterar(string $o, array $claves, int $semilla): ?array
    {
        foreach ($claves as $clave) {
            if (self::contiene($o, $clave)) {
                foreach (self::otrasClaves($claves, $clave, 4, $semilla, true) as $otra) {
                    if (!self::contiene($o, $otra)) {
                        return [self::reemplazar($o, $clave, $otra), $clave, $otra];
                    }
                }
            }
        }
        return null;
    }

    private static function siNo(array $oraciones, array $claves, array &$usadas, int $max): array
    {
        $res = [];
        foreach ($oraciones as $k => $o) {
            if (isset($usadas[$k]) || count($res) >= $max) {
                continue;
            }
            $verdadera = count($res) % 2 === 1;
            $alterada = $verdadera ? null : self::alterar($o, $claves, $k);
            if (!$verdadera && !$alterada) {
                continue;
            }
            $usadas[$k] = true;
            $res[] = [
                'tipo' => 'si_no',
                'enunciado' => '¿Es correcto según el texto? «' . ($verdadera ? $o : $alterada[0]) . '»',
                'datos' => [],
                'solucion' => ['valor' => $verdadera ? 'si' : 'no'],
                'explicacion' => $verdadera ? 'Así aparece en el texto.' : 'El texto dice «' . $alterada[1] . '», no «' . $alterada[2] . '».',
                'fuente' => $o,
            ];
        }
        return $res;
    }

    private static function test(array $oraciones, array $claves, array &$usadas, int $max): array
    {
        $res = [];
        foreach ($oraciones as $k => $o) {
            if (isset($usadas[$k]) || count(preg_split('/\s+/u', $o)) > 24) {
                continue;
            }
            $falsa1 = self::alterar($o, $claves, $k + 7);
            $falsa2 = self::alterar($o, array_reverse($claves), $k + 13);
            if (!$falsa1 || !$falsa2 || $falsa1[0] === $falsa2[0]) {
                continue;
            }
            $usadas[$k] = true;
            $res[] = [
                'tipo' => 'test',
                'enunciado' => '¿Cuál de estas afirmaciones dice el texto?',
                'datos' => ['opciones' => [$o, $falsa1[0], $falsa2[0]]],
                'solucion' => ['indice' => 0],
                'explicacion' => 'Las otras dos cambian una palabra clave del texto.',
                'fuente' => $o,
            ];
            if (count($res) >= $max) {
                break;
            }
        }
        return $res;
    }

    private static function ordenarPalabras(array $oraciones, array &$usadas, int $max): array
    {
        $candidatas = [];
        foreach ($oraciones as $k => $o) {
            foreach (preg_split('/[,;:]\s*/u', rtrim($o, '.!?')) as $trozo) {
                $n = count(preg_split('/\s+/u', trim($trozo)));
                if ($n >= 5 && $n <= 10) {
                    $candidatas[] = [$k, trim($trozo), $o];
                }
            }
        }
        $res = [];
        foreach ($candidatas as [$k, $trozo, $o]) {
            if (isset($usadas[$k])) {
                continue;
            }
            $usadas[$k] = true;
            $res[] = [
                'tipo' => 'ordenar_palabras',
                'enunciado' => 'Ordena las palabras para formar la idea del texto.',
                'datos' => [],
                'solucion' => ['frase' => $trozo],
                'explicacion' => 'Así aparece en el texto.',
                'fuente' => $o,
            ];
            if (count($res) >= $max) {
                break;
            }
        }
        return $res;
    }

    private static function ordenarLetras(array $oraciones, array $claves, int $max): array
    {
        $res = [];
        foreach ($claves as $clave) {
            $largo = mb_strlen($clave);
            if ($largo < 5 || $largo > 11 || str_contains($clave, ' ')) {
                continue;
            }
            foreach ($oraciones as $o) {
                if (self::contiene($o, $clave)) {
                    $pista = self::reemplazar($o, $clave, '___');
                    $res[] = [
                        'tipo' => 'ordenar_letras',
                        'enunciado' => 'Ordena las letras de la palabra que falta.',
                        'datos' => ['pista' => mb_strlen($pista) > 160 ? mb_substr($pista, 0, 157) . '…' : $pista],
                        'solucion' => ['palabra' => $clave],
                        'explicacion' => 'La palabra es «' . $clave . '».',
                        'fuente' => $o,
                    ];
                    break;
                }
            }
            if (count($res) >= $max) {
                break;
            }
        }
        return $res;
    }

    private static function relacionar(array $oraciones, array $claves): ?array
    {
        $pares = [];
        $fuentes = [];
        foreach ($oraciones as $o) {
            if (preg_match(self::DEFINICION, $o, $m)) {
                $termino = trim($m[1]);
                if (count(preg_split('/\s+/u', $termino)) <= 4 && !isset($pares[normalizar($termino)])) {
                    $pares[normalizar($termino)] = [mb_strtoupper(mb_substr($termino, 0, 1)) . mb_substr($termino, 1), rtrim(trim($m[2]), ',;')];
                    $fuentes[] = $o;
                }
            }
            if (count($pares) >= 4) {
                break;
            }
        }
        // Si el texto no trae definiciones explícitas, relaciona cada palabra clave con su oración.
        if (count($pares) < 3) {
            $pares = [];
            $fuentes = [];
            foreach (array_slice($claves, 0, 8) as $clave) {
                foreach ($oraciones as $o) {
                    if (self::contiene($o, $clave)) {
                        $contexto = self::reemplazar($o, $clave, '___');
                        if (mb_strlen($contexto) > 90) {
                            $pos = mb_strpos($contexto, '___');
                            $inicio = max(0, $pos - 40);
                            $contexto = ($inicio > 0 ? '…' : '') . mb_substr($contexto, $inicio, 85) . '…';
                        }
                        $pares[normalizar($clave)] = [$clave, $contexto];
                        $fuentes[] = $o;
                        break;
                    }
                }
                if (count($pares) >= 4) {
                    break;
                }
            }
        }
        if (count($pares) < 3) {
            return null;
        }
        return [
            'tipo' => 'relacionar',
            'enunciado' => 'Relaciona cada término con lo que dice el texto.',
            'datos' => [],
            'solucion' => ['pares' => array_values($pares)],
            'explicacion' => 'Cada pareja sale de una oración del texto.',
            'fuente' => implode(' ', array_unique($fuentes)),
        ];
    }
}
