<?php
namespace App\Models;

use App\Core\Model;

class Usuario extends Model
{
    public static function buscar(int $id): ?array
    {
        return self::uno('SELECT * FROM usuarios WHERE id = ?', [$id]);
    }

    public static function porTokenInvitado(string $token): ?array
    {
        return self::uno('SELECT * FROM usuarios WHERE token_invitado = ?', [$token]);
    }

    /** Busca por correo o por nombre de usuario (un solo campo de ingreso). */
    public static function porIdentificador(string $identificador): ?array
    {
        $identificador = mb_strtolower(trim($identificador));
        return self::uno(
            "SELECT * FROM usuarios WHERE rol <> 'invitado' AND (LOWER(correo) = ? OR LOWER(usuario) = ?) LIMIT 1",
            [$identificador, $identificador]
        );
    }

    public static function existeCorreo(string $correo, int $excepto = 0): bool
    {
        return (bool) self::valor('SELECT 1 FROM usuarios WHERE LOWER(correo) = ? AND id <> ?', [mb_strtolower($correo), $excepto]);
    }

    public static function existeUsuario(string $usuario, int $excepto = 0): bool
    {
        return (bool) self::valor('SELECT 1 FROM usuarios WHERE LOWER(usuario) = ? AND id <> ?', [mb_strtolower($usuario), $excepto]);
    }

    public static function crearInvitado(string $token): int
    {
        return self::insertar("INSERT INTO usuarios (rol, token_invitado) VALUES ('invitado', ?)", [$token]);
    }

    /** Convierte la fila invitada en cuenta: el avance ya está ligado a este id. */
    public static function registrar(int $id, array $d): void
    {
        self::ejecutar(
            'UPDATE usuarios SET nombre = ?, usuario = ?, correo = ?, clave_hash = ?, rol = ?, token_invitado = NULL WHERE id = ?',
            [$d['nombre'], $d['usuario'], mb_strtolower($d['correo']), password_hash($d['clave'], PASSWORD_DEFAULT), $d['rol'], $id]
        );
    }

    public static function crear(array $d): int
    {
        return self::insertar(
            'INSERT INTO usuarios (nombre, usuario, correo, clave_hash, rol, es_demo) VALUES (?, ?, ?, ?, ?, ?)',
            [$d['nombre'], $d['usuario'], mb_strtolower($d['correo']), password_hash($d['clave'], PASSWORD_DEFAULT), $d['rol'], $d['es_demo'] ?? 0]
        );
    }

    public static function actualizarDatos(int $id, string $nombre, string $usuario, string $correo, string $rol): void
    {
        self::ejecutar('UPDATE usuarios SET nombre = ?, usuario = ?, correo = ?, rol = ? WHERE id = ?', [$nombre, $usuario, mb_strtolower($correo), $rol, $id]);
    }

    public static function cambiarClave(int $id, string $clave): void
    {
        self::ejecutar('UPDATE usuarios SET clave_hash = ? WHERE id = ?', [password_hash($clave, PASSWORD_DEFAULT), $id]);
    }

    public static function marcarIngreso(int $id): void
    {
        self::ejecutar('UPDATE usuarios SET ultimo_ingreso = NOW() WHERE id = ?', [$id]);
    }

    public static function contarPorRol(): array
    {
        $filas = self::todos('SELECT rol, COUNT(*) n FROM usuarios GROUP BY rol');
        return array_column($filas, 'n', 'rol');
    }

    public static function listar(int $limite = 100): array
    {
        return self::todos(
            "SELECT u.id, u.nombre, u.usuario, u.correo, u.rol, u.es_demo, u.creado_en, u.ultimo_ingreso,
                    COALESCE((SELECT SUM(xp) FROM actividad a WHERE a.usuario_id = u.id), 0) xp
             FROM usuarios u WHERE u.rol <> 'invitado' ORDER BY u.creado_en DESC LIMIT " . (int) $limite
        );
    }
}
