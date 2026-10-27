<?php
namespace App\Models;

use App\Core\Model;

class Ejercicio extends Model
{
    public const TIPOS = [
        'test' => 'Test',
        'si_no' => 'Sí o no',
        'completar' => 'Completar frases',
        'ordenar_palabras' => 'Ordenar palabras',
        'ordenar_letras' => 'Ordenar letras',
        'relacionar' => 'Relacionar columnas',
    ];

    public static function buscar(int $id): ?array
    {
        $e = self::uno('SELECT * FROM ejercicios WHERE id = ?', [$id]);
        return $e ? self::decodificar($e) : null;
    }

    public static function deLeccion(int $leccionId): array
    {
        return array_map([self::class, 'decodificar'], self::todos('SELECT * FROM ejercicios WHERE leccion_id = ? ORDER BY orden', [$leccionId]));
    }

    public static function deCurso(int $cursoId): array
    {
        return array_map([self::class, 'decodificar'], self::todos(
            'SELECT e.*, l.titulo leccion_titulo FROM ejercicios e JOIN lecciones l ON l.id = e.leccion_id
             JOIN unidades u ON u.id = l.unidad_id WHERE u.curso_id = ? ORDER BY u.orden, l.orden, e.orden',
            [$cursoId]
        ));
    }

    public static function variosPorId(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $filas = self::todos('SELECT * FROM ejercicios WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', array_values($ids));
        $porId = [];
        foreach ($filas as $f) {
            $porId[(int) $f['id']] = self::decodificar($f);
        }
        return $porId;
    }

    public static function crear(int $leccionId, int $orden, array $e): int
    {
        return self::insertar(
            'INSERT INTO ejercicios (leccion_id, orden, tipo, enunciado, datos, solucion, explicacion, fuente) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$leccionId, $orden, $e['tipo'], $e['enunciado'], json_encode($e['datos'], JSON_UNESCAPED_UNICODE),
                json_encode($e['solucion'], JSON_UNESCAPED_UNICODE), $e['explicacion'] ?? null, $e['fuente'] ?? null]
        );
    }

    public static function actualizar(int $id, string $enunciado, array $solucion, array $datos): void
    {
        self::ejecutar('UPDATE ejercicios SET enunciado = ?, solucion = ?, datos = ? WHERE id = ?', [
            $enunciado, json_encode($solucion, JSON_UNESCAPED_UNICODE), json_encode($datos, JSON_UNESCAPED_UNICODE), $id,
        ]);
    }

    public static function eliminar(int $id): void
    {
        self::ejecutar('DELETE FROM ejercicios WHERE id = ?', [$id]);
    }

    private static function decodificar(array $e): array
    {
        $e['datos'] = json_decode($e['datos'], true) ?: [];
        $e['solucion'] = json_decode($e['solucion'], true) ?: [];
        $e['id'] = (int) $e['id'];
        return $e;
    }
}
