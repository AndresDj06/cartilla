<?php
namespace App\Core;

/**
 * Lector mínimo de archivos .env (CLAVE=valor). No sobrescribe variables ya definidas.
 */
class Env
{
    private static array $valores = [];

    public static function cargar(string $ruta): void
    {
        if (!is_file($ruta)) {
            return;
        }
        foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
            $valor = trim($valor, "\"'");
            self::$valores[$clave] = $valor;
        }
    }

    public static function get(string $clave, ?string $porDefecto = null): ?string
    {
        $entorno = getenv($clave);
        if ($entorno !== false && $entorno !== '') {
            return $entorno;
        }
        return self::$valores[$clave] ?? $porDefecto;
    }
}
