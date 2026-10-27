<?php
namespace App\Models;

use App\Core\Model;

/**
 * Avance por lección, XP diaria (racha) y cola de repaso espaciado.
 */
class Progreso extends Model
{
    /** Días de espera por caja de Leitner. */
    public const ESPERA = [1 => 1, 2 => 2, 3 => 4, 4 => 7, 5 => 15];

    public static function deCurso(int $usuarioId, int $cursoId): array
    {
        $filas = self::todos(
            'SELECT p.* FROM progreso p JOIN lecciones l ON l.id = p.leccion_id JOIN unidades u ON u.id = l.unidad_id
             WHERE p.usuario_id = ? AND u.curso_id = ?',
            [$usuarioId, $cursoId]
        );
        $porLeccion = [];
        foreach ($filas as $f) {
            $porLeccion[(int) $f['leccion_id']] = $f;
        }
        return $porLeccion;
    }

    public static function registrarLeccion(int $usuarioId, int $leccionId, int $precision, bool $completada): void
    {
        self::ejecutar(
            'INSERT INTO progreso (usuario_id, leccion_id, veces, mejor_precision, completada_en) VALUES (?, ?, 1, ?, ?)
             ON DUPLICATE KEY UPDATE veces = veces + 1, mejor_precision = GREATEST(mejor_precision, VALUES(mejor_precision)),
             completada_en = COALESCE(completada_en, VALUES(completada_en))',
            [$usuarioId, $leccionId, $precision, $completada ? date('Y-m-d H:i:s') : null]
        );
    }

    public static function sumarXp(int $usuarioId, int $xp, ?string $fecha = null): void
    {
        self::ejecutar(
            'INSERT INTO actividad (usuario_id, fecha, xp) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE xp = xp + VALUES(xp)',
            [$usuarioId, $fecha ?? date('Y-m-d'), $xp]
        );
    }

    public static function xpHoy(int $usuarioId): int
    {
        return (int) self::valor('SELECT xp FROM actividad WHERE usuario_id = ? AND fecha = CURDATE()', [$usuarioId]);
    }

    public static function xpTotal(int $usuarioId): int
    {
        return (int) self::valor('SELECT COALESCE(SUM(xp), 0) FROM actividad WHERE usuario_id = ?', [$usuarioId]);
    }

    /** Días seguidos con práctica, contando hoy o, si hoy aún no practica, desde ayer. */
    public static function racha(int $usuarioId): int
    {
        $fechas = array_column(self::todos(
            'SELECT fecha FROM actividad WHERE usuario_id = ? AND xp > 0 AND fecha >= CURDATE() - INTERVAL 400 DAY ORDER BY fecha DESC',
            [$usuarioId]
        ), 'fecha');
        if (!$fechas) {
            return 0;
        }
        $dia = new \DateTimeImmutable('today');
        if ($fechas[0] !== $dia->format('Y-m-d')) {
            $dia = $dia->modify('-1 day');
        }
        $racha = 0;
        foreach ($fechas as $f) {
            if ($f !== $dia->format('Y-m-d')) {
                break;
            }
            $racha++;
            $dia = $dia->modify('-1 day');
        }
        return $racha;
    }

    /** XP de los últimos siete días, de lunes a hoy en orden cronológico. */
    public static function semana(int $usuarioId): array
    {
        $filas = self::todos('SELECT fecha, xp FROM actividad WHERE usuario_id = ? AND fecha > CURDATE() - INTERVAL 7 DAY', [$usuarioId]);
        $porFecha = array_column($filas, 'xp', 'fecha');
        $dias = ['D', 'L', 'M', 'X', 'J', 'V', 'S'];
        $semana = [];
        for ($i = 6; $i >= 0; $i--) {
            $t = strtotime("-$i day");
            $f = date('Y-m-d', $t);
            $semana[] = ['fecha' => $f, 'dia' => $dias[(int) date('w', $t)], 'xp' => (int) ($porFecha[$f] ?? 0), 'hoy' => $i === 0];
        }
        return $semana;
    }

    public static function programarRepaso(int $usuarioId, int $ejercicioId, bool $correcta, bool $enRepaso): void
    {
        $actual = self::uno('SELECT caja FROM repasos WHERE usuario_id = ? AND ejercicio_id = ?', [$usuarioId, $ejercicioId]);
        if (!$correcta) {
            $caja = 1;
        } elseif ($actual) {
            $caja = $enRepaso ? min(5, (int) $actual['caja'] + 1) : (int) $actual['caja'];
        } else {
            return; // Acertado a la primera y nunca fallado: no entra a la cola.
        }
        $proxima = date('Y-m-d', strtotime('+' . self::ESPERA[$caja] . ' day'));
        self::ejecutar(
            'INSERT INTO repasos (usuario_id, ejercicio_id, caja, proxima) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE caja = VALUES(caja), proxima = VALUES(proxima)',
            [$usuarioId, $ejercicioId, $caja, $proxima]
        );
    }

    /** Ejercicios cuyo repaso vence hoy o antes, del curso indicado. */
    public static function pendientesRepaso(int $usuarioId, ?int $cursoId = null, int $limite = 10): array
    {
        $sql = 'SELECT r.ejercicio_id, r.caja, r.proxima FROM repasos r';
        $params = [$usuarioId];
        if ($cursoId) {
            $sql .= ' JOIN ejercicios e ON e.id = r.ejercicio_id JOIN lecciones l ON l.id = e.leccion_id JOIN unidades u ON u.id = l.unidad_id';
        }
        $sql .= ' WHERE r.usuario_id = ? AND r.proxima <= CURDATE() AND r.caja < 5';
        if ($cursoId) {
            $sql .= ' AND u.curso_id = ?';
            $params[] = $cursoId;
        }
        return self::todos($sql . ' ORDER BY r.proxima, r.caja LIMIT ' . (int) $limite, $params);
    }

    public static function proximoRepaso(int $usuarioId): ?string
    {
        return self::valor('SELECT MIN(proxima) FROM repasos WHERE usuario_id = ? AND proxima > CURDATE() AND caja < 5', [$usuarioId]);
    }

    public static function enRepasoTotal(int $usuarioId): int
    {
        return (int) self::valor('SELECT COUNT(*) FROM repasos WHERE usuario_id = ? AND caja < 5', [$usuarioId]);
    }

    // ---- Panel docente ----

    public static function estudiantesDeCurso(int $cursoId): array
    {
        return self::todos(
            "SELECT u.id, COALESCE(u.nombre, 'Invitado sin registrar') nombre, u.rol, i.creado_en inscrito_en,
                    (SELECT COUNT(DISTINCT p.leccion_id) FROM progreso p JOIN lecciones l ON l.id = p.leccion_id JOIN unidades un ON un.id = l.unidad_id
                       WHERE p.usuario_id = u.id AND un.curso_id = ? AND p.completada_en IS NOT NULL) lecciones_hechas,
                    (SELECT ROUND(100 * SUM(r.correcta) / NULLIF(COUNT(*), 0)) FROM respuestas r JOIN intentos it ON it.id = r.intento_id
                       WHERE it.usuario_id = u.id AND it.curso_id = ? AND r.primera_vez = 1) precision_media,
                    (SELECT MAX(it.terminado_en) FROM intentos it WHERE it.usuario_id = u.id AND it.curso_id = ?) ultima_vez
             FROM inscripciones i JOIN usuarios u ON u.id = i.usuario_id WHERE i.curso_id = ? ORDER BY ultima_vez DESC",
            [$cursoId, $cursoId, $cursoId, $cursoId]
        );
    }

    /** Acierto por pregunta (primer intento), de la más fallada a la menos. */
    public static function aciertoPorEjercicio(int $cursoId): array
    {
        $filas = self::todos(
            'SELECT e.id, e.tipo, e.enunciado, e.datos, e.solucion, l.titulo leccion, COUNT(r.id) respuestas, SUM(r.correcta) aciertos,
                    ROUND(100 * SUM(r.correcta) / NULLIF(COUNT(r.id), 0)) porcentaje
             FROM ejercicios e JOIN lecciones l ON l.id = e.leccion_id JOIN unidades u ON u.id = l.unidad_id
             LEFT JOIN respuestas r ON r.ejercicio_id = e.id AND r.primera_vez = 1
             WHERE u.curso_id = ? GROUP BY e.id ORDER BY (COUNT(r.id) = 0), porcentaje ASC, e.id',
            [$cursoId]
        );
        // Los juegos de enunciado genérico se describen con su respuesta para que el docente sepa cuál es.
        foreach ($filas as &$f) {
            $e = ['tipo' => $f['tipo'], 'datos' => json_decode($f['datos'], true), 'solucion' => json_decode($f['solucion'], true)];
            if (in_array($f['tipo'], ['ordenar_letras', 'ordenar_palabras', 'relacionar'], true)) {
                $f['enunciado'] = rtrim($f['enunciado'], '.') . ': «' . \App\Services\Evaluador::solucionLegible($e) . '»';
            }
            unset($f['datos'], $f['solucion']);
        }
        return $filas;
    }

    public static function respuestasFrecuentes(int $ejercicioId, int $limite = 3): array
    {
        return self::todos(
            'SELECT respuesta, COUNT(*) n FROM respuestas WHERE ejercicio_id = ? AND correcta = 0 AND primera_vez = 1
             GROUP BY respuesta ORDER BY n DESC LIMIT ' . (int) $limite,
            [$ejercicioId]
        );
    }
}
