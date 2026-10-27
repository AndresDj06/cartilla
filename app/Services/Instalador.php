<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Env;
use App\Models\Curso;
use App\Models\Ejercicio;
use App\Models\Usuario;

/**
 * Crea la base, el esquema, el catálogo y los datos de demostración.
 */
class Instalador
{
    public static function crearBase(): void
    {
        try {
            $nombre = preg_replace('/[^a-zA-Z0-9_]/', '', Env::get('DB_NAME', 'cartilla'));
            Database::servidor()->exec("CREATE DATABASE IF NOT EXISTS `$nombre` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            // En hosting compartido (Hostinger/cPanel), la base de datos se crea desde el panel
            // de control y el usuario MySQL no tiene privilegios de 'CREATE DATABASE'.
        }
    }

    public static function esquema(): void
    {
        $sql = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
        $sql = preg_replace('/^--.*$/m', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $sentencia) {
            Database::pdo()->exec($sentencia);
        }
    }

    public static function catalogo(): int
    {
        $cursos = require dirname(__DIR__, 2) . '/database/contenido.php';
        $n = 0;
        foreach ($cursos as $c) {
            $cursoId = Curso::crear($c + ['catalogo' => 1]);
            foreach ($c['unidades'] as $iu => $u) {
                $unidadId = Curso::crearUnidad($cursoId, $iu + 1, $u['titulo'], $u['descripcion']);
                foreach ($u['lecciones'] as $il => $l) {
                    $leccionId = Curso::crearLeccion($unidadId, $il + 1, $l['titulo']);
                    foreach ($l['ejercicios'] as $ie => $fila) {
                        Ejercicio::crear($leccionId, $ie + 1, self::expandir($fila));
                        $n++;
                    }
                }
            }
        }
        return $n;
    }

    /** Convierte el formato compacto de contenido.php al formato de la tabla ejercicios. */
    public static function expandir(array $f): array
    {
        switch ($f[0]) {
            case 'test':
                return ['tipo' => 'test', 'enunciado' => $f[1], 'datos' => ['opciones' => $f[2]], 'solucion' => ['indice' => $f[3]], 'explicacion' => $f[4]];
            case 'si_no':
                return ['tipo' => 'si_no', 'enunciado' => $f[1], 'datos' => [], 'solucion' => ['valor' => $f[2]], 'explicacion' => $f[3]];
            case 'completar':
                $respuestas = explode('|', $f[2]);
                return ['tipo' => 'completar', 'enunciado' => $f[1], 'datos' => ['banco' => array_merge([$respuestas[0]], $f[3])],
                    'solucion' => ['respuestas' => $respuestas], 'explicacion' => $f[4]];
            case 'ordenar':
                return ['tipo' => 'ordenar_palabras', 'enunciado' => $f[2], 'datos' => [], 'solucion' => ['frase' => $f[1]], 'explicacion' => '«' . $f[1] . '».'];
            case 'letras':
                return ['tipo' => 'ordenar_letras', 'enunciado' => 'Ordena las letras para formar la palabra.', 'datos' => ['pista' => $f[2]],
                    'solucion' => ['palabra' => $f[1]], 'explicacion' => 'La palabra es «' . $f[1] . '».'];
            case 'relacionar':
                return ['tipo' => 'relacionar', 'enunciado' => $f[1], 'datos' => [], 'solucion' => ['pares' => $f[2]], 'explicacion' => null];
        }
        throw new \InvalidArgumentException('Tipo desconocido: ' . $f[0]);
    }

    /** Guarda en la base un curso generado desde texto. Devuelve el id del curso. */
    public static function guardarGenerado(array $generado, ?int $autorId, string $grado, string $texto, string $estado = 'borrador', ?string $codigo = null): int
    {
        $cursoId = Curso::crear([
            'slug' => 'clase-' . bin2hex(random_bytes(5)),
            'titulo' => $generado['titulo'],
            'materia' => 'Clase propia',
            'descripcion' => 'Creado a partir del texto del docente.',
            'autor_id' => $autorId,
            'codigo' => $codigo,
            'grado' => $grado,
            'texto_fuente' => $texto,
            'estado' => $estado,
        ]);
        $unidadId = Curso::crearUnidad($cursoId, 1, $generado['titulo'], 'Juegos generados desde tu texto.');
        foreach ($generado['lecciones'] as $il => $l) {
            $leccionId = Curso::crearLeccion($unidadId, $il + 1, $l['titulo']);
            foreach ($l['ejercicios'] as $ie => $e) {
                Ejercicio::crear($leccionId, $ie + 1, $e);
            }
        }
        return $cursoId;
    }

    /** Cuentas y clase de demostración con historial para que el panel docente tenga datos reales. */
    public static function demo(): string
    {
        $cfg = (require dirname(__DIR__, 2) . '/config/app.php')['demo'];
        $pdo = Database::pdo();
        $docenteId = Usuario::crear($cfg['docente'] + ['clave' => $cfg['clave'], 'rol' => 'docente', 'es_demo' => 1]);
        $estudianteId = Usuario::crear($cfg['estudiante'] + ['clave' => $cfg['clave'], 'rol' => 'estudiante', 'es_demo' => 1]);

        $generado = GeneradorJuegos::generar(TextoEjemplo::TEXTO, TextoEjemplo::TITULO);
        $claseId = self::guardarGenerado($generado, $docenteId, 'secundaria', TextoEjemplo::TEXTO, 'publicado', $cfg['codigo_clase']);
        $pdo->prepare('INSERT INTO perfiles (usuario_id, rol_elegido, objetivo, minutos, grado, tipos, completado, texto_fuente) VALUES (?, ?, ?, ?, ?, ?, 1, ?)')
            ->execute([$docenteId, 'docente', 'entre_clases', 10, 'secundaria', implode(',', array_keys(Ejercicio::TIPOS)), TextoEjemplo::TEXTO]);

        $agua = (int) $pdo->query("SELECT id FROM cursos WHERE slug = 'agua-en-la-tierra'")->fetchColumn();
        $pdo->prepare('INSERT INTO perfiles (usuario_id, rol_elegido, objetivo, curso_id, nivel, minutos, tipos, completado) VALUES (?, ?, ?, ?, ?, ?, ?, 1)')
            ->execute([$estudianteId, 'estudiante', 'reforzar', $agua, 'cero', 10, implode(',', array_keys(Ejercicio::TIPOS))]);

        // Estudiantes ficticios de la clase demo, con distinta habilidad.
        $alumnos = [['Yeison Mosquera', 0.9], ['Dayana Palacios', 0.8], ['Luis Ángel Rentería', 0.65], ['Mariana Asprilla', 0.75], ['Kevin Valencia', 0.5], ['Sofía Murillo', 0.85]];
        mt_srand(2026);
        foreach ($alumnos as $i => [$nombre, $habilidad]) {
            $slug = 'demo.' . strtolower(strtr(explode(' ', $nombre)[0], ['á' => 'a', 'Á' => 'A']));
            $id = Usuario::crear(['nombre' => $nombre, 'usuario' => $slug, 'correo' => $slug . '@cartilla.test', 'clave' => $cfg['clave'], 'rol' => 'estudiante', 'es_demo' => 1]);
            Curso::inscribir($id, $claseId);
            $lecciones = $i === 4 ? 1 : 2;
            self::simularCurso($id, $claseId, $habilidad, $lecciones, 5 - $i % 3);
        }
        Curso::inscribir($estudianteId, $claseId);
        Curso::inscribir($estudianteId, $agua);
        self::simularCurso($estudianteId, $agua, 0.8, 2, 4);
        self::simularCurso($estudianteId, $claseId, 0.7, 1, 1);
        mt_srand();
        return "Docente demo, estudiante demo y clase {$cfg['codigo_clase']} con 6 estudiantes creados.";
    }

    /** Simula intentos pasados respondiendo ejercicios con la probabilidad de acierto dada. */
    private static function simularCurso(int $usuarioId, int $cursoId, float $habilidad, int $nLecciones, int $diasAtras): void
    {
        $pdo = Database::pdo();
        $lecciones = array_merge(...array_map(fn ($u) => $u['lecciones'], Curso::estructura($cursoId)));
        foreach (array_slice($lecciones, 0, $nLecciones) as $k => $l) {
            $dia = date('Y-m-d', strtotime('-' . max(0, $diasAtras - $k * 2) . ' day'));
            $ejercicios = array_slice(Ejercicio::deLeccion((int) $l['id']), 0, 8);
            $pdo->prepare("INSERT INTO intentos (usuario_id, leccion_id, curso_id, modo, ejercicios, vidas, total, estado, iniciado_en, terminado_en)
                           VALUES (?, ?, ?, 'leccion', ?, 3, ?, 'completado', ?, ?)")
                ->execute([$usuarioId, $l['id'], $cursoId, implode(',', array_column($ejercicios, 'id')), count($ejercicios), "$dia 16:10:00", "$dia 16:16:00"]);
            $intentoId = (int) $pdo->lastInsertId();
            $aciertos = 0;
            foreach ($ejercicios as $e) {
                // Relacionar y ordenar letras son más difíciles: bajan la probabilidad.
                $p = $habilidad - (in_array($e['tipo'], ['relacionar', 'ordenar_letras'], true) ? 0.2 : 0);
                $bien = mt_rand(0, 100) / 100 < $p;
                $ins = $pdo->prepare('INSERT INTO respuestas (intento_id, ejercicio_id, respuesta, correcta, primera_vez, creado_en) VALUES (?, ?, ?, ?, ?, ?)');
                if ($bien) {
                    $aciertos++;
                    $ins->execute([$intentoId, $e['id'], Evaluador::solucionLegible($e), 1, 1, "$dia 16:12:00"]);
                } else {
                    $ins->execute([$intentoId, $e['id'], self::respuestaErrada($e), 0, 1, "$dia 16:12:00"]);
                    $ins->execute([$intentoId, $e['id'], Evaluador::solucionLegible($e), 1, 0, "$dia 16:15:00"]);
                    $pdo->prepare('INSERT IGNORE INTO repasos (usuario_id, ejercicio_id, caja, proxima) VALUES (?, ?, 1, ?)')
                        ->execute([$usuarioId, $e['id'], date('Y-m-d', strtotime("$dia +1 day"))]);
                }
            }
            $xp = $aciertos * Practica::XP_ACIERTO + Practica::XP_COMPLETAR;
            $precision = (int) round(100 * $aciertos / max(1, count($ejercicios)));
            $pdo->prepare('UPDATE intentos SET aciertos = ?, xp = ? WHERE id = ?')->execute([$aciertos, $xp, $intentoId]);
            $pdo->prepare('INSERT INTO progreso (usuario_id, leccion_id, veces, mejor_precision, completada_en) VALUES (?, ?, 1, ?, ?)')
                ->execute([$usuarioId, $l['id'], $precision, "$dia 16:16:00"]);
            $pdo->prepare('INSERT INTO actividad (usuario_id, fecha, xp) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE xp = xp + VALUES(xp)')
                ->execute([$usuarioId, $dia, $xp]);
        }
        // Días de práctica intermedios para que la racha sea creíble.
        for ($d = $diasAtras; $d >= 1; $d--) {
            $pdo->prepare('INSERT IGNORE INTO actividad (usuario_id, fecha, xp) VALUES (?, ?, ?)')
                ->execute([$usuarioId, date('Y-m-d', strtotime("-$d day")), 6 + mt_rand(0, 8)]);
        }
    }

    private static function respuestaErrada(array $e): string
    {
        switch ($e['tipo']) {
            case 'test':
                $otras = $e['datos']['opciones'];
                unset($otras[$e['solucion']['indice']]);
                return (string) array_values($otras)[mt_rand(0, count($otras) - 1)];
            case 'si_no':
                return $e['solucion']['valor'] === 'si' ? 'No' : 'Sí';
            case 'completar':
                $banco = array_slice($e['datos']['banco'] ?? [], 1);
                return $banco ? $banco[mt_rand(0, count($banco) - 1)] : '—';
            case 'ordenar_palabras':
                $p = explode(' ', $e['solucion']['frase']);
                return implode(' ', array_reverse($p));
            case 'ordenar_letras':
                return mb_strtoupper(implode('', array_reverse(preg_split('//u', $e['solucion']['palabra'], -1, PREG_SPLIT_NO_EMPTY))));
            case 'relacionar':
                $pares = $e['solucion']['pares'];
                [$pares[0][1], $pares[1][1]] = [$pares[1][1], $pares[0][1]];
                return implode(' · ', array_map(fn ($p) => $p[0] . ' → ' . $p[1], $pares));
        }
        return '—';
    }
}
