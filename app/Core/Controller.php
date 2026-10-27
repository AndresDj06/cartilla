<?php
namespace App\Core;

use App\Models\Usuario;

/**
 * Controlador base: vistas, JSON, redirecciones, CSRF y control de acceso.
 */
abstract class Controller
{
    protected function vista(string $vista, array $datos = [], string $layout = 'app'): void
    {
        $datos['usuario'] = $datos['usuario'] ?? Auth::usuario();
        View::render($vista, $datos, $layout);
    }

    protected function json($datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    protected function redirigir(string $ruta): void
    {
        header('Location: ' . url($ruta));
        exit;
    }

    /** Detiene la petición si el token CSRF no coincide. */
    protected function verificarCsrf(bool $comoJson = false): void
    {
        $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!Session::csrfValido($token)) {
            if ($comoJson) {
                $this->json(['error' => 'La sesión expiró. Recarga la página para seguir.'], 419);
                exit;
            }
            Session::flash('aviso', 'La página estuvo abierta mucho tiempo y la sesión expiró. Vuelve a intentarlo.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('/')));
            exit;
        }
    }

    /** Exige una cuenta registrada (no invitada). */
    protected function exigirCuenta(?string $rol = null): array
    {
        $u = Auth::usuario();
        if (!$u || $u['rol'] === 'invitado') {
            Session::set('volver_a', $_SERVER['REQUEST_URI'] ?? '/');
            Session::flash('aviso', 'Ingresa o crea tu cuenta para ver esta página.');
            $this->redirigir('/ingresar');
        }
        if ($rol !== null && !Auth::puede($u, $rol)) {
            http_response_code(403);
            View::render('errores/403', ['titulo' => 'Sin permiso', 'usuario' => $u], 'sitio');
            exit;
        }
        return $u;
    }

    /** Devuelve el usuario actual, creando uno invitado si hace falta. */
    protected function usuarioOInvitado(): array
    {
        return Auth::usuario() ?? Auth::crearInvitado();
    }

    protected function entrada(string $clave, $porDefecto = '')
    {
        $v = $_POST[$clave] ?? $_GET[$clave] ?? $porDefecto;
        return is_string($v) ? trim($v) : $v;
    }

    protected function jsonEntrada(): array
    {
        $datos = json_decode(file_get_contents('php://input') ?: '[]', true);
        return is_array($datos) ? $datos : [];
    }
}
