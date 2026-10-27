<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\Curso;
use App\Models\Perfil;
use App\Models\Progreso;
use App\Services\Personalizador;

/**
 * Control de la persona sobre sus datos: ver, descargar y borrar.
 */
class CuentaController extends Controller
{
    public function index(): void
    {
        $u = Auth::usuario();
        if (!$u) {
            $this->redirigir('/empezar');
        }
        $perfil = Perfil::de((int) $u['id']);
        $this->vista('cuenta/index', [
            'titulo' => 'Mi cuenta · Cartilla',
            'nav' => 'cuenta',
            'u' => $u,
            'perfil' => $perfil,
            'decisiones' => $perfil['completado'] ? Personalizador::decisiones($perfil, Personalizador::plan($perfil), Curso::buscar((int) $perfil['curso_id'])) : [],
            'xp' => Progreso::xpTotal((int) $u['id']),
            'racha' => Progreso::racha((int) $u['id']),
            'enRepaso' => Progreso::enRepasoTotal((int) $u['id']),
        ]);
    }

    public function descargar(): void
    {
        $u = Auth::usuario();
        if (!$u) {
            $this->redirigir('/');
        }
        $id = (int) $u['id'];
        $pdo = Database::pdo();
        $tabla = function (string $sql) use ($pdo, $id) {
            $st = $pdo->prepare($sql);
            $st->execute([$id]);
            return $st->fetchAll();
        };
        $datos = [
            'exportado_en' => date('c'),
            'usuario' => array_diff_key($u, ['clave_hash' => 1, 'token_invitado' => 1]),
            'perfil' => Perfil::de($id),
            'inscripciones' => $tabla('SELECT c.titulo, c.codigo, i.creado_en FROM inscripciones i JOIN cursos c ON c.id = i.curso_id WHERE i.usuario_id = ?'),
            'cursos_creados' => $tabla('SELECT id, titulo, codigo, estado, creado_en FROM cursos WHERE autor_id = ?'),
            'intentos' => $tabla('SELECT id, leccion_id, curso_id, modo, aciertos, total, xp, estado, iniciado_en, terminado_en FROM intentos WHERE usuario_id = ?'),
            'respuestas' => $tabla('SELECT r.intento_id, r.ejercicio_id, r.respuesta, r.correcta, r.primera_vez, r.creado_en FROM respuestas r JOIN intentos i ON i.id = r.intento_id WHERE i.usuario_id = ?'),
            'actividad' => $tabla('SELECT fecha, xp FROM actividad WHERE usuario_id = ? ORDER BY fecha'),
            'repasos' => $tabla('SELECT ejercicio_id, caja, proxima FROM repasos WHERE usuario_id = ?'),
        ];
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="cartilla-mis-datos-' . date('Y-m-d') . '.json"');
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function borrar(): void
    {
        $this->verificarCsrf();
        $u = Auth::usuario();
        if (!$u) {
            $this->redirigir('/');
        }
        if ($u['rol'] === 'superadmin') {
            Session::flash('aviso', 'La cuenta de super administrador no se puede borrar desde aquí.');
            $this->redirigir('/cuenta');
        }
        if ($u['rol'] !== 'invitado' && !password_verify((string) ($_POST['clave'] ?? ''), (string) $u['clave_hash'])) {
            Session::flash('aviso', 'La contraseña no coincide. No borramos nada.');
            $this->redirigir('/cuenta');
        }
        Database::pdo()->prepare('DELETE FROM usuarios WHERE id = ?')->execute([(int) $u['id']]);
        Auth::cerrarSesion();
        Session::iniciar();
        Session::flash('exito', 'Borramos tu cuenta y todo su historial.');
        $this->redirigir('/');
    }
}
