<?php
namespace App\Models;

use App\Core\Model;

/**
 * Respuestas del onboarding de cada persona.
 */
class Perfil extends Model
{
    public const CAMPOS = ['rol_elegido', 'objetivo', 'curso_id', 'nivel', 'grado', 'minutos', 'tipos', 'titulo_tema', 'texto_fuente', 'completado'];

    public static function de(int $usuarioId): array
    {
        $p = self::uno('SELECT * FROM perfiles WHERE usuario_id = ?', [$usuarioId]);
        if (!$p) {
            return ['usuario_id' => $usuarioId, 'rol_elegido' => null, 'objetivo' => null, 'curso_id' => null, 'nivel' => null,
                'grado' => null, 'minutos' => null, 'tipos' => null, 'titulo_tema' => null, 'texto_fuente' => null, 'completado' => 0];
        }
        return $p;
    }

    public static function guardar(int $usuarioId, array $cambios): void
    {
        $cambios = array_intersect_key($cambios, array_flip(self::CAMPOS));
        if (!$cambios) {
            return;
        }
        $cols = array_keys($cambios);
        $sql = 'INSERT INTO perfiles (usuario_id, ' . implode(', ', $cols) . ') VALUES (?' . str_repeat(', ?', count($cols)) . ')
                ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(fn ($c) => "$c = VALUES($c)", $cols));
        self::ejecutar($sql, array_merge([$usuarioId], array_values($cambios)));
    }

    public static function tipos(array $perfil): array
    {
        return array_values(array_filter(explode(',', (string) $perfil['tipos'])));
    }
}
