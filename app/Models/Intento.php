<?php
namespace App\Models;

use App\Core\Model;

class Intento extends Model
{
    public static function crear(int $usuarioId, ?int $leccionId, int $cursoId, string $modo, array $ejercicioIds, int $vidas = 3): int
    {
        return self::insertar(
            'INSERT INTO intentos (usuario_id, leccion_id, curso_id, modo, ejercicios, vidas, total) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$usuarioId, $leccionId, $cursoId, $modo, implode(',', $ejercicioIds), $vidas, count($ejercicioIds)]
        );
    }

    public static function buscar(int $id): ?array
    {
        $i = self::uno('SELECT * FROM intentos WHERE id = ?', [$id]);
        if ($i) {
            $i['ejercicio_ids'] = array_map('intval', array_filter(explode(',', $i['ejercicios'])));
        }
        return $i;
    }

    /** Intento abierto de la misma lección, para continuar donde se quedó. */
    public static function abierto(int $usuarioId, ?int $leccionId, string $modo): ?array
    {
        $sql = "SELECT id FROM intentos WHERE usuario_id = ? AND modo = ? AND estado = 'en_curso' AND iniciado_en > NOW() - INTERVAL 1 DAY AND "
            . ($leccionId === null ? 'leccion_id IS NULL' : 'leccion_id = ?') . ' ORDER BY id DESC LIMIT 1';
        $params = $leccionId === null ? [$usuarioId, $modo] : [$usuarioId, $modo, $leccionId];
        $id = self::valor($sql, $params);
        return $id ? self::buscar((int) $id) : null;
    }

    public static function respuestas(int $intentoId): array
    {
        return self::todos('SELECT * FROM respuestas WHERE intento_id = ? ORDER BY id', [$intentoId]);
    }

    public static function registrarRespuesta(int $intentoId, int $ejercicioId, string $respuesta, bool $correcta): bool
    {
        $previa = (bool) self::valor('SELECT 1 FROM respuestas WHERE intento_id = ? AND ejercicio_id = ?', [$intentoId, $ejercicioId]);
        self::ejecutar(
            'INSERT INTO respuestas (intento_id, ejercicio_id, respuesta, correcta, primera_vez) VALUES (?, ?, ?, ?, ?)',
            [$intentoId, $ejercicioId, $respuesta, $correcta ? 1 : 0, $previa ? 0 : 1]
        );
        return !$previa;
    }

    public static function restarVida(int $intentoId): int
    {
        self::ejecutar('UPDATE intentos SET vidas = GREATEST(vidas - 1, 0) WHERE id = ?', [$intentoId]);
        return (int) self::valor('SELECT vidas FROM intentos WHERE id = ?', [$intentoId]);
    }

    public static function cerrar(int $intentoId, string $estado, int $aciertos, int $xp): void
    {
        self::ejecutar('UPDATE intentos SET estado = ?, aciertos = ?, xp = ?, terminado_en = NOW() WHERE id = ?', [$estado, $aciertos, $xp, $intentoId]);
    }

    public static function recientes(int $usuarioId, int $limite = 5): array
    {
        return self::todos(
            "SELECT i.*, l.titulo leccion_titulo FROM intentos i LEFT JOIN lecciones l ON l.id = i.leccion_id
             WHERE i.usuario_id = ? AND i.estado <> 'en_curso' ORDER BY i.terminado_en DESC LIMIT " . (int) $limite,
            [$usuarioId]
        );
    }
}
