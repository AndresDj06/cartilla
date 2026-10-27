<?php
namespace App\Services;

/**
 * Prepara cada ejercicio para el navegador (sin la solución) y corrige en el servidor.
 */
class Evaluador
{
    /** Datos públicos del ejercicio. $conBanco decide si "completar" muestra el banco de palabras. */
    public static function preparar(array $e, int $semilla, bool $conBanco = true): array
    {
        mt_srand($semilla + $e['id']);
        $pub = ['id' => $e['id'], 'tipo' => $e['tipo'], 'enunciado' => $e['enunciado']];
        $d = $e['datos'];
        $s = $e['solucion'];

        switch ($e['tipo']) {
            case 'test':
                $opciones = [];
                foreach ($d['opciones'] as $i => $texto) {
                    $opciones[] = ['valor' => (string) $i, 'texto' => $texto];
                }
                $pub['opciones'] = self::barajar($opciones);
                break;
            case 'si_no':
                $pub['opciones'] = [['valor' => 'si', 'texto' => 'Sí'], ['valor' => 'no', 'texto' => 'No']];
                break;
            case 'completar':
                $pub['banco'] = ($conBanco && !empty($d['banco'])) ? self::barajar($d['banco']) : [];
                break;
            case 'ordenar_palabras':
                $palabras = preg_split('/\s+/u', trim($s['frase']));
                $pub['fichas'] = self::barajarDistinto($palabras);
                break;
            case 'ordenar_letras':
                $letras = preg_split('//u', mb_strtoupper($s['palabra']), -1, PREG_SPLIT_NO_EMPTY);
                $letras = array_values(array_filter($letras, fn ($l) => $l !== ' '));
                $pub['fichas'] = self::barajarDistinto($letras);
                $pub['pista'] = $d['pista'] ?? '';
                break;
            case 'relacionar':
                $pub['izquierda'] = array_column($s['pares'], 0);
                $pub['derecha'] = self::barajarDistinto(array_column($s['pares'], 1));
                break;
        }
        mt_srand();
        return $pub;
    }

    /** @return array{correcta: bool, solucion: string} */
    public static function evaluar(array $e, $respuesta): array
    {
        $s = $e['solucion'];
        $correcta = false;
        switch ($e['tipo']) {
            case 'test':
                $correcta = (string) $respuesta === (string) $s['indice'];
                break;
            case 'si_no':
                $correcta = $respuesta === $s['valor'];
                break;
            case 'completar':
                $dada = normalizar((string) $respuesta);
                foreach ($s['respuestas'] as $alternativa) {
                    if ($dada !== '' && $dada === normalizar($alternativa)) {
                        $correcta = true;
                    }
                }
                break;
            case 'ordenar_palabras':
                $correcta = normalizar(is_array($respuesta) ? implode(' ', $respuesta) : (string) $respuesta) === normalizar($s['frase']);
                break;
            case 'ordenar_letras':
                $correcta = normalizar(is_array($respuesta) ? implode('', $respuesta) : (string) $respuesta) === normalizar(str_replace(' ', '', $s['palabra']));
                break;
            case 'relacionar':
                $esperado = [];
                foreach ($s['pares'] as [$a, $b]) {
                    $esperado[normalizar($a)] = normalizar($b);
                }
                $dado = [];
                foreach ((array) $respuesta as $par) {
                    if (is_array($par) && count($par) === 2) {
                        $dado[normalizar((string) $par[0])] = normalizar((string) $par[1]);
                    }
                }
                ksort($esperado);
                ksort($dado);
                $correcta = $dado === $esperado;
                break;
        }
        return ['correcta' => $correcta, 'solucion' => self::solucionLegible($e)];
    }

    public static function solucionLegible(array $e): string
    {
        $s = $e['solucion'];
        switch ($e['tipo']) {
            case 'test':
                return $e['datos']['opciones'][$s['indice']] ?? '';
            case 'si_no':
                return $s['valor'] === 'si' ? 'Sí' : 'No';
            case 'completar':
                return $s['respuestas'][0];
            case 'ordenar_palabras':
                return $s['frase'];
            case 'ordenar_letras':
                return mb_strtoupper($s['palabra']);
            case 'relacionar':
                return implode(' · ', array_map(fn ($p) => $p[0] . ' → ' . $p[1], $s['pares']));
        }
        return '';
    }

    /** Texto guardable de la respuesta dada (para el panel docente). */
    public static function respuestaTexto(array $e, $respuesta): string
    {
        if ($e['tipo'] === 'test') {
            return (string) ($e['datos']['opciones'][(int) $respuesta] ?? $respuesta);
        }
        if ($e['tipo'] === 'si_no') {
            return $respuesta === 'si' ? 'Sí' : 'No';
        }
        if ($e['tipo'] === 'relacionar') {
            return implode(' · ', array_map(fn ($p) => implode(' → ', (array) $p), (array) $respuesta));
        }
        if ($e['tipo'] === 'ordenar_letras' && is_array($respuesta)) {
            return implode('', $respuesta);
        }
        return is_array($respuesta) ? implode(' ', $respuesta) : mb_substr((string) $respuesta, 0, 300);
    }

    private static function barajar(array $a): array
    {
        for ($i = count($a) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$a[$i], $a[$j]] = [$a[$j], $a[$i]];
        }
        return $a;
    }

    /** Baraja asegurando que el orden no coincida con la solución (si hay más de un elemento distinto). */
    private static function barajarDistinto(array $a): array
    {
        if (count(array_unique($a)) < 2) {
            return $a;
        }
        for ($k = 0; $k < 8; $k++) {
            $b = self::barajar($a);
            if ($b !== $a) {
                return $b;
            }
        }
        return array_reverse($a);
    }
}
