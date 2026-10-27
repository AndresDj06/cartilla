<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Curso;
use App\Models\Perfil;
use App\Models\Progreso;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function formRegistro(): void
    {
        $u = Auth::usuario();
        if (Auth::esRegistrado($u)) {
            $this->redirigir('/ruta');
        }
        $perfil = $u ? Perfil::de((int) $u['id']) : null;
        $GLOBALS['_errores'] = Session::flash('errores') ?? [];
        $GLOBALS['_old'] = Session::flash('old') ?? [];
        $this->vista('auth/registro', [
            'titulo' => 'Crear cuenta · Cartilla',
            'claseCuerpo' => 'flujo paso-registro',
            'rolSugerido' => $perfil['rol_elegido'] ?? 'estudiante',
            'xp' => $u ? Progreso::xpTotal((int) $u['id']) : 0,
            'racha' => $u ? Progreso::racha((int) $u['id']) : 0,
        ], 'sitio');
    }

    public function registro(): void
    {
        $this->verificarCsrf();
        $d = [
            'nombre' => trim((string) $this->entrada('nombre')),
            'usuario' => mb_strtolower(trim((string) $this->entrada('usuario'))),
            'correo' => mb_strtolower(trim((string) $this->entrada('correo'))),
            'clave' => (string) ($_POST['clave'] ?? ''),
            'rol' => $this->entrada('rol'),
        ];
        $actual = Auth::usuario();
        $excepto = $actual ? (int) $actual['id'] : 0;
        $errores = [];
        if (mb_strlen($d['nombre']) < 2) {
            $errores['nombre'] = 'Escribe tu nombre (al menos 2 letras).';
        }
        if (!preg_match('/^[a-z0-9._]{3,30}$/', $d['usuario'])) {
            $errores['usuario'] = 'Usa de 3 a 30 caracteres: letras sin tildes, números, punto o guion bajo.';
        } elseif (Usuario::existeUsuario($d['usuario'], $excepto)) {
            $errores['usuario'] = 'Ese usuario ya está en uso. Prueba con otro, por ejemplo agregando un número.';
        }
        if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Escribe un correo válido, como nombre@ejemplo.com.';
        } elseif (Usuario::existeCorreo($d['correo'], $excepto)) {
            $errores['correo'] = 'Ya hay una cuenta con ese correo. ¿Quieres ingresar?';
        }
        if (strlen($d['clave']) < 8 || !preg_match('/\pL/u', $d['clave']) || !preg_match('/\d/', $d['clave'])) {
            $errores['clave'] = 'La contraseña necesita al menos 8 caracteres, con letras y números.';
        }
        if (!in_array($d['rol'], ['estudiante', 'docente'], true)) {
            $errores['rol'] = 'Elige si eres estudiante o docente.';
        }
        if ($errores) {
            Session::flash('errores', $errores);
            Session::flash('old', array_diff_key($d, ['clave' => 1]));
            $this->redirigir('/registro');
        }

        // Quien publica una clase al registrarse es docente, aunque haya marcado otra opción.
        if (Session::get('publicar_pendiente')) {
            $d['rol'] = 'docente';
        }
        if ($actual && $actual['rol'] === 'invitado') {
            // La fila invitada se convierte en cuenta: XP, racha, repasos y clases quedan intactos.
            Usuario::registrar((int) $actual['id'], $d);
            $id = (int) $actual['id'];
        } else {
            $id = Usuario::crear($d);
        }
        Auth::refrescar();
        $nuevo = Usuario::buscar($id);
        Auth::iniciarSesion($nuevo);
        $xp = Progreso::xpTotal($id);
        Session::flash('exito', 'Cuenta creada, ' . explode(' ', $nuevo['nombre'])[0] . '.' . ($xp ? " Tu avance de invitado quedó guardado: $xp XP." : ''));
        $this->redirigir($this->destinoTrasIngreso($nuevo));
    }

    public function formIngreso(): void
    {
        if (Auth::esRegistrado(Auth::usuario())) {
            $this->redirigir('/ruta');
        }
        $GLOBALS['_errores'] = Session::flash('errores') ?? [];
        $GLOBALS['_old'] = Session::flash('old') ?? [];
        $cfg = require dirname(__DIR__, 2) . '/config/app.php';
        $this->vista('auth/ingreso', ['titulo' => 'Ingresar · Cartilla', 'claseCuerpo' => 'flujo paso-ingreso', 'demo' => $cfg['demo']], 'sitio');
    }

    public function ingreso(): void
    {
        $this->verificarCsrf();
        $identificador = trim((string) $this->entrada('identificador'));
        $clave = (string) ($_POST['clave'] ?? '');

        // Freno simple ante intentos repetidos desde la misma sesión.
        $fallos = (int) Session::get('fallos_ingreso', 0);
        $espera = (int) Session::get('espera_ingreso', 0);
        if ($espera > time()) {
            Session::flash('errores', ['general' => 'Demasiados intentos. Espera ' . ($espera - time()) . ' segundos y vuelve a intentarlo.']);
            Session::flash('old', ['identificador' => $identificador]);
            $this->redirigir('/ingresar');
        }

        $errores = [];
        if ($identificador === '') {
            $errores['identificador'] = 'Escribe tu correo o tu nombre de usuario.';
        }
        if ($clave === '') {
            $errores['clave'] = 'Escribe tu contraseña.';
        }
        $u = $errores ? null : Usuario::porIdentificador($identificador);
        if (!$errores && (!$u || !$u['clave_hash'] || !password_verify($clave, $u['clave_hash']))) {
            $fallos++;
            Session::set('fallos_ingreso', $fallos);
            if ($fallos >= 5) {
                Session::set('espera_ingreso', time() + 30);
                Session::set('fallos_ingreso', 0);
            }
            $errores['general'] = 'El correo, el usuario o la contraseña no coinciden. Revisa e inténtalo de nuevo.';
        }
        if ($errores) {
            Session::flash('errores', $errores);
            Session::flash('old', ['identificador' => $identificador]);
            $this->redirigir('/ingresar');
        }
        Session::quitar('fallos_ingreso');
        if (password_needs_rehash($u['clave_hash'], PASSWORD_DEFAULT)) {
            Usuario::cambiarClave((int) $u['id'], $clave);
        }
        Auth::iniciarSesion($u);
        Session::flash('exito', 'Hola de nuevo, ' . explode(' ', (string) $u['nombre'])[0] . '.');
        $this->redirigir($this->destinoTrasIngreso($u));
    }

    public function salir(): void
    {
        $this->verificarCsrf();
        Auth::cerrarSesion();
        Session::iniciar();
        Session::flash('exito', 'Cerraste sesión. Tu avance queda guardado en tu cuenta.');
        $this->redirigir('/');
    }

    private function destinoTrasIngreso(array $u): string
    {
        $pendiente = (int) Session::get('publicar_pendiente', 0);
        Session::quitar('publicar_pendiente');
        if ($pendiente) {
            $curso = Curso::buscar($pendiente);
            if ($curso && (int) $curso['autor_id'] === (int) $u['id']) {
                if ($curso['estado'] !== 'publicado') {
                    Curso::publicar($pendiente, Curso::codigoNuevo());
                }
                Session::flash('exito', 'Cuenta creada y clase publicada. Comparte el código con tus estudiantes.');
                return '/docente/clase/' . $pendiente;
            }
        }
        $volver = Session::get('volver_a');
        Session::quitar('volver_a');
        if ($volver) {
            $base = base_path();
            return $base !== '' && str_starts_with($volver, $base) ? substr($volver, strlen($base)) : $volver;
        }
        if ($u['rol'] === 'superadmin') {
            return '/admin';
        }
        return $u['rol'] === 'docente' ? '/docente' : '/ruta';
    }
}
