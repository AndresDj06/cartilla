<?php
namespace App\Core;

/**
 * Sesión, mensajes flash, datos antiguos de formularios y token CSRF.
 */
class Session
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('cartilla_sesion');
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/', 'secure' => self::https()]);
            session_start();
        }
    }

    /** En producción con HTTPS las cookies solo viajan cifradas. */
    public static function https(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public static function get(string $clave, $porDefecto = null)
    {
        return $_SESSION[$clave] ?? $porDefecto;
    }

    public static function set(string $clave, $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function quitar(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    public static function flash(string $clave, $valor = null)
    {
        if ($valor !== null) {
            $_SESSION['_flash'][$clave] = $valor;
            return null;
        }
        $v = $_SESSION['_flash'][$clave] ?? null;
        unset($_SESSION['_flash'][$clave]);
        return $v;
    }

    public static function csrf(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function csrfValido(?string $token): bool
    {
        return is_string($token) && hash_equals(self::csrf(), $token);
    }

    public static function regenerar(): void
    {
        session_regenerate_id(true);
    }
}
