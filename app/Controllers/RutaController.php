<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Curso;
use App\Models\Intento;
use App\Models\Perfil;
use App\Models\Progreso;
use App\Services\Personalizador;
use App\Services\Practica;

class RutaController extends Controller
{
    public function index(): void
    {
        $u = Auth::usuario();
        if (!$u) {
            $this->redirigir('/empezar');
        }
        $id = (int) $u['id'];
        $perfil = Perfil::de($id);
        $cursos = Curso::inscritos($id);

        if (!$cursos) {
            if (in_array($u['rol'], ['docente', 'superadmin'], true) || $perfil['rol_elegido'] === 'docente') {
                $this->redirigir('/docente');
            }
            $this->redirigir('/empezar');
        }

        $pedido = (int) ($_GET['curso'] ?? 0);
        $curso = null;
        foreach ($cursos as $c) {
            if ((int) $c['id'] === $pedido || (!$pedido && (int) $c['id'] === (int) $perfil['curso_id'])) {
                $curso = $c;
            }
        }
        $curso = $curso ?? $cursos[0];

        $plan = Practica::plan($u, $curso);
        $ruta = Practica::ruta($u, $curso, $plan);
        $totalLecciones = array_sum(array_map(fn ($un) => count($un['lecciones']), $ruta));
        $hechas = array_sum(array_column($ruta, 'hechas'));

        $this->vista('ruta/index', [
            'titulo' => 'Mi ruta · ' . $curso['titulo'],
            'nav' => 'ruta',
            'curso' => $curso,
            'cursos' => $cursos,
            'ruta' => $ruta,
            'plan' => $plan,
            'siguiente' => Practica::siguienteLeccion($ruta),
            'pendientes' => count(Progreso::pendientesRepaso($id, (int) $curso['id'], 50)),
            'proximoRepaso' => Progreso::proximoRepaso($id),
            'racha' => Progreso::racha($id),
            'xpHoy' => Progreso::xpHoy($id),
            'xpTotal' => Progreso::xpTotal($id),
            'semana' => Progreso::semana($id),
            'recientes' => Intento::recientes($id, 4),
            'hechas' => $hechas,
            'totalLecciones' => $totalLecciones,
            'perfil' => $perfil,
            'decisiones' => $perfil['completado'] ? Personalizador::decisiones($perfil, Personalizador::plan($perfil), Curso::buscar((int) $perfil['curso_id'])) : [],
            'priorizaRepaso' => in_array($perfil['objetivo'], ['evaluacion', 'examen'], true),
        ]);
    }
}
