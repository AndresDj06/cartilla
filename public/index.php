<?php
// Controlador frontal de Cartilla.
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\ClaseController;
use App\Controllers\CuentaController;
use App\Controllers\DocenteController;
use App\Controllers\InicioController;
use App\Controllers\LeccionController;
use App\Controllers\OnboardingController;
use App\Controllers\RutaController;
use App\Core\Env;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

// Servidor embebido de PHP: entrega los archivos estáticos tal cual.
if (PHP_SAPI === 'cli-server') {
    $archivo = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($archivo)) {
        return false;
    }
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: SAMEORIGIN');
Session::iniciar();

$r = new Router();
$r->get('/', [InicioController::class, 'index']);
$r->get('/privacidad', [InicioController::class, 'privacidad']);

$r->get('/empezar', [OnboardingController::class, 'inicio']);
$r->post('/empezar/reiniciar', [OnboardingController::class, 'reiniciar']);
$r->post('/empezar/confirmar', [OnboardingController::class, 'confirmar']);
$r->get('/empezar/{paso}', [OnboardingController::class, 'mostrar']);
$r->post('/empezar/{paso}', [OnboardingController::class, 'guardar']);

$r->get('/ruta', [RutaController::class, 'index']);
$r->get('/leccion/{id}', [LeccionController::class, 'jugar']);
$r->get('/repaso', [LeccionController::class, 'repaso']);
$r->get('/resultado/{id}', [LeccionController::class, 'resultado']);

$r->post('/api/intentos/{id}/responder', [ApiController::class, 'responder']);
$r->post('/api/intentos/{id}/terminar', [ApiController::class, 'terminar']);
$r->post('/api/muestra', [ApiController::class, 'muestra']);

$r->get('/registro', [AuthController::class, 'formRegistro']);
$r->post('/registro', [AuthController::class, 'registro']);
$r->get('/ingresar', [AuthController::class, 'formIngreso']);
$r->post('/ingresar', [AuthController::class, 'ingreso']);
$r->post('/salir', [AuthController::class, 'salir']);

$r->get('/unirse', [ClaseController::class, 'form']);
$r->post('/unirse', [ClaseController::class, 'unirse']);

$r->get('/cuenta', [CuentaController::class, 'index']);
$r->get('/cuenta/datos', [CuentaController::class, 'descargar']);
$r->post('/cuenta/borrar', [CuentaController::class, 'borrar']);

$r->get('/docente', [DocenteController::class, 'index']);
$r->get('/docente/crear', [DocenteController::class, 'formCrear']);
$r->post('/docente/crear', [DocenteController::class, 'crear']);
$r->get('/docente/clase/{id}', [DocenteController::class, 'clase']);
$r->get('/docente/clase/{id}/revisar', [DocenteController::class, 'revisar']);
$r->post('/docente/clase/{id}/revisar', [DocenteController::class, 'guardarRevision']);
$r->post('/docente/clase/{id}/publicar', [DocenteController::class, 'publicar']);
$r->get('/docente/clase/{id}/csv', [DocenteController::class, 'csv']);

$r->get('/admin', [AdminController::class, 'index']);

$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = base_path();
if ($base !== '' && str_starts_with($ruta, $base)) {
    $ruta = substr($ruta, strlen($base)) ?: '/';
}

try {
    $r->despachar($_SERVER['REQUEST_METHOD'] ?? 'GET', rawurldecode($ruta));
} catch (PDOException $e) {
    error_log('[cartilla] ' . $e->getMessage());
    http_response_code(503);
    View::render('errores/500', [
        'titulo' => 'Sin conexión con la base de datos',
        'mensaje' => 'No pudimos conectar con la base de datos. Si eres quien instala Cartilla, revisa que MySQL esté encendido y ejecuta «php bin/cartilla instalar».',
        'detalle' => Env::get('APP_DEBUG') === '1' ? $e->getMessage() : null,
    ], 'sitio');
} catch (Throwable $e) {
    error_log('[cartilla] ' . $e);
    http_response_code(500);
    View::render('errores/500', [
        'titulo' => 'Algo salió mal',
        'mensaje' => 'Tuvimos un error inesperado. Tu avance anterior sigue guardado. Vuelve a intentarlo o regresa al inicio.',
        'detalle' => Env::get('APP_DEBUG') === '1' ? $e->getMessage() . ' · ' . $e->getFile() . ':' . $e->getLine() : null,
    ], 'sitio');
}
