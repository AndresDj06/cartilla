<?php
namespace App\Core;

use App\Models\Usuario;

/**
 * Identidad actual. Las personas sin cuenta reciben un usuario "invitado" con una
 * cookie propia, así su avance se conserva y pasa intacto a la cuenta cuando se registran.
 */
class Auth
{
    private const COOKIE = 'cartilla_invitado';
    private static ?array $cache = null;

    public static function usuario(): ?array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $id = Session::get('usuario_id');
        if ($id) {
            $u = Usuario::buscar((int) $id);
            // La firma evita que una sesión vieja apunte a otra persona si la base se reinstala.
            if ($u && hash_equals(self::firma($u), (string) Session::get('usuario_firma'))) {
                return self::$cache = $u;
            }
            Session::quitar('usuario_id');
            Session::quitar('usuario_firma');
        }
        $token = $_COOKIE[self::COOKIE] ?? null;
        if (is_string($token) && preg_match('/^[a-f0-9]{48}$/', $token)) {
            $u = Usuario::porTokenInvitado($token);
            if ($u && $u['rol'] === 'invitado') {
                self::fijar($u);
                return self::$cache = $u;
            }
        }
        return null;
    }

    public static function crearInvitado(): array
    {
        $token = bin2hex(random_bytes(24));
        $id = Usuario::crearInvitado($token);
        setcookie(self::COOKIE, $token, ['expires' => time() + 60 * 60 * 24 * 90, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => Session::https()]);
        $u = Usuario::buscar($id);
        self::fijar($u);
        return self::$cache = $u;
    }

    public static function iniciarSesion(array $usuario): void
    {
        Session::regenerar();
        self::fijar($usuario);
        self::olvidarInvitado();
        self::$cache = $usuario;
        Usuario::marcarIngreso((int) $usuario['id']);
    }

    private static function firma(array $u): string
    {
        return hash('sha256', $u['id'] . '|' . $u['creado_en']);
    }

    private static function fijar(array $u): void
    {
        Session::set('usuario_id', (int) $u['id']);
        Session::set('usuario_firma', self::firma($u));
    }

    public static function cerrarSesion(): void
    {
        self::olvidarInvitado();
        $_SESSION = [];
        session_destroy();
        self::$cache = null;
    }

    public static function olvidarInvitado(): void
    {
        setcookie(self::COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
    }

    public static function refrescar(): void
    {
        self::$cache = null;
    }

    /** El super administrador tiene todos los permisos, presentes y futuros. */
    public static function puede(array $u, string $rol): bool
    {
        if ($u['rol'] === 'superadmin') {
            return true;
        }
        return $u['rol'] === $rol;
    }

    public static function esRegistrado(?array $u): bool
    {
        return $u !== null && $u['rol'] !== 'invitado';
    }
}
