<?php

use App\Core\Session;

/** Escapa texto para HTML. */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Ruta base de la aplicación (soporta subcarpeta de XAMPP y servidor embebido). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $script = preg_replace('#/public$#', '', $script);
        $base = rtrim($script, '/');
    }
    return $base;
}

function url(string $ruta = '/'): string
{
    return base_path() . '/' . ltrim($ruta, '/');
}

function asset(string $ruta): string
{
    $archivo = dirname(__DIR__) . '/public/assets/' . $ruta;
    $v = is_file($archivo) ? filemtime($archivo) : 1;
    return url('assets/' . $ruta) . '?v=' . $v;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Session::csrf()) . '">';
}

/** Valor anterior de un campo tras un error de validación. */
function old(string $campo, string $porDefecto = ''): string
{
    $viejos = $GLOBALS['_old'] ?? [];
    return e($viejos[$campo] ?? $porDefecto);
}

function error_de(string $campo): ?string
{
    return ($GLOBALS['_errores'] ?? [])[$campo] ?? null;
}

/** Normaliza texto para comparar respuestas: sin tildes, minúsculas, espacios simples. */
function normalizar(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    $texto = preg_replace('/[^\p{L}\p{N}\/ ]+/u', ' ', $texto);
    return trim(preg_replace('/\s+/', ' ', $texto));
}

function plural(int $n, string $uno, string $varios): string
{
    return $n . ' ' . ($n === 1 ? $uno : $varios);
}

function fecha_corta(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($fecha);
    return date('j', $t) . ' ' . $meses[(int) date('n', $t) - 1];
}
