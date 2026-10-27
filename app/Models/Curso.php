<?php
namespace App\Models;

use App\Core\Model;

class Curso extends Model
{
    public static function buscar(int $id): ?array
    {
        return self::uno('SELECT * FROM cursos WHERE id = ?', [$id]);
    }

    public static function porCodigo(string $codigo): ?array
    {
        return self::uno("SELECT * FROM cursos WHERE codigo = ? AND estado = 'publicado'", [strtoupper(trim($codigo))]);
    }

    public static function catalogo(): array
    {
        return self::todos(
            "SELECT c.*, (SELECT COUNT(*) FROM unidades u JOIN lecciones l ON l.unidad_id = u.id WHERE u.curso_id = c.id) lecciones
             FROM cursos c WHERE c.catalogo = 1 AND c.estado = 'publicado' ORDER BY c.id"
        );
    }

    public static function delAutor(int $autorId): array
    {
        return self::todos('SELECT * FROM cursos WHERE autor_id = ? ORDER BY creado_en DESC', [$autorId]);
    }

    public static function todosParaAdmin(): array
    {
        return self::todos(
            "SELECT c.id, c.titulo, c.materia, c.codigo, c.estado, c.catalogo, c.creado_en, u.nombre autor,
                    (SELECT COUNT(*) FROM inscripciones i WHERE i.curso_id = c.id) inscritos
             FROM cursos c LEFT JOIN usuarios u ON u.id = c.autor_id ORDER BY c.creado_en DESC"
        );
    }

    /** Unidades con sus lecciones, en orden. */
    public static function estructura(int $cursoId): array
    {
        $unidades = self::todos('SELECT * FROM unidades WHERE curso_id = ? ORDER BY orden', [$cursoId]);
        $lecciones = self::todos(
            'SELECT l.*, (SELECT COUNT(*) FROM ejercicios e WHERE e.leccion_id = l.id) n_ejercicios
             FROM lecciones l JOIN unidades u ON u.id = l.unidad_id WHERE u.curso_id = ? ORDER BY u.orden, l.orden',
            [$cursoId]
        );
        foreach ($unidades as &$u) {
            $u['lecciones'] = array_values(array_filter($lecciones, fn ($l) => (int) $l['unidad_id'] === (int) $u['id']));
        }
        return $unidades;
    }

    public static function crear(array $d): int
    {
        return self::insertar(
            'INSERT INTO cursos (slug, titulo, materia, descripcion, autor_id, codigo, grado, texto_fuente, estado, catalogo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d['slug'], $d['titulo'], $d['materia'], $d['descripcion'] ?? '', $d['autor_id'] ?? null, $d['codigo'] ?? null,
                $d['grado'] ?? null, $d['texto_fuente'] ?? null, $d['estado'] ?? 'publicado', $d['catalogo'] ?? 0]
        );
    }

    public static function crearUnidad(int $cursoId, int $orden, string $titulo, string $descripcion = ''): int
    {
        return self::insertar('INSERT INTO unidades (curso_id, orden, titulo, descripcion) VALUES (?, ?, ?, ?)', [$cursoId, $orden, $titulo, $descripcion]);
    }

    public static function crearLeccion(int $unidadId, int $orden, string $titulo): int
    {
        return self::insertar('INSERT INTO lecciones (unidad_id, orden, titulo) VALUES (?, ?, ?)', [$unidadId, $orden, $titulo]);
    }

    public static function leccion(int $id): ?array
    {
        return self::uno(
            'SELECT l.*, u.titulo unidad_titulo, u.orden unidad_orden, u.curso_id, c.titulo curso_titulo
             FROM lecciones l JOIN unidades u ON u.id = l.unidad_id JOIN cursos c ON c.id = u.curso_id WHERE l.id = ?',
            [$id]
        );
    }

    public static function asignarAutor(int $cursoId, int $autorId): void
    {
        self::ejecutar('UPDATE cursos SET autor_id = ? WHERE id = ?', [$autorId, $cursoId]);
    }

    public static function publicar(int $cursoId, string $codigo): void
    {
        self::ejecutar("UPDATE cursos SET estado = 'publicado', codigo = ? WHERE id = ?", [$codigo, $cursoId]);
    }

    public static function actualizarTitulo(int $cursoId, string $titulo): void
    {
        self::ejecutar('UPDATE cursos SET titulo = ? WHERE id = ?', [$titulo, $cursoId]);
    }

    public static function codigoNuevo(): string
    {
        // Sin 0/O ni 1/I para que se pueda dictar en voz alta en el salón.
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $codigo = '';
            for ($i = 0; $i < 6; $i++) {
                $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
        } while (self::valor('SELECT 1 FROM cursos WHERE codigo = ?', [$codigo]));
        return $codigo;
    }

    public static function inscribir(int $usuarioId, int $cursoId): void
    {
        self::ejecutar('INSERT IGNORE INTO inscripciones (usuario_id, curso_id) VALUES (?, ?)', [$usuarioId, $cursoId]);
    }

    public static function inscritos(int $usuarioId): array
    {
        return self::todos(
            'SELECT c.* FROM inscripciones i JOIN cursos c ON c.id = i.curso_id WHERE i.usuario_id = ? ORDER BY i.creado_en',
            [$usuarioId]
        );
    }

    public static function estaInscrito(int $usuarioId, int $cursoId): bool
    {
        return (bool) self::valor('SELECT 1 FROM inscripciones WHERE usuario_id = ? AND curso_id = ?', [$usuarioId, $cursoId]);
    }

    /** Borra un curso solo si sigue en borrador y nadie se ha inscrito. */
    public static function eliminarBorrador(int $cursoId): void
    {
        self::ejecutar(
            "DELETE FROM cursos WHERE id = ? AND estado = 'borrador' AND NOT EXISTS (SELECT 1 FROM inscripciones i WHERE i.curso_id = cursos.id)",
            [$cursoId]
        );
    }

    public static function actualizarGrado(int $cursoId, string $grado): void
    {
        self::ejecutar('UPDATE cursos SET grado = ? WHERE id = ?', [$grado, $cursoId]);
    }

    public static function primeraLeccion(int $cursoId): ?array
    {
        return self::uno(
            'SELECT l.* FROM lecciones l JOIN unidades u ON u.id = l.unidad_id WHERE u.curso_id = ? ORDER BY u.orden, l.orden LIMIT 1',
            [$cursoId]
        );
    }
}
