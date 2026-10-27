<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Env;
use App\Models\Usuario;

/**
 * Crea o actualiza la cuenta del super administrador de forma idempotente.
 * La identidad sale de config/app.php; la contraseña, solo de SUPERADMIN_CLAVE en .env.
 * Si la cuenta ya existe, la contraseña no se toca salvo que se pida restablecerla.
 */
class SuperAdministradorService
{
    /** @return string mensaje para la consola */
    public static function asegurar(bool $restablecerClave = false): string
    {
        $cfg = (require dirname(__DIR__, 2) . '/config/app.php')['superadmin'];
        $clave = (string) Env::get('SUPERADMIN_CLAVE', '');
        $pdo = Database::pdo();

        $st = $pdo->prepare('SELECT id FROM usuarios WHERE LOWER(correo) = ? OR LOWER(usuario) = ? LIMIT 1');
        $st->execute([mb_strtolower($cfg['correo']), mb_strtolower($cfg['usuario'])]);
        $id = $st->fetchColumn();

        if ($id) {
            $pdo->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, correo = ?, rol = 'superadmin', token_invitado = NULL WHERE id = ?")
                ->execute([$cfg['nombre'], $cfg['usuario'], mb_strtolower($cfg['correo']), $id]);
            if ($restablecerClave) {
                self::exigirClave($clave);
                Usuario::cambiarClave((int) $id, $clave);
                return "Super administrador actualizado y contraseña restablecida (id $id).";
            }
            return "Super administrador ya existía (id $id); datos verificados, contraseña sin cambios.";
        }

        self::exigirClave($clave);
        $nuevo = Usuario::crear(['nombre' => $cfg['nombre'], 'usuario' => $cfg['usuario'], 'correo' => $cfg['correo'], 'clave' => $clave, 'rol' => 'superadmin']);
        return "Super administrador creado (id $nuevo).";
    }

    private static function exigirClave(string $clave): void
    {
        if (strlen($clave) < 8) {
            throw new \RuntimeException('Define SUPERADMIN_CLAVE en el archivo .env (mínimo 8 caracteres) y vuelve a ejecutar el comando.');
        }
    }
}
