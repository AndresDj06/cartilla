<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Curso;
use App\Models\Ejercicio;
use App\Models\Intento;
use App\Models\Perfil;
use App\Models\Progreso;
use App\Services\Evaluador;
use App\Services\Personalizador;
use App\Services\Practica;

class LeccionController extends Controller
{
    public function jugar(string $id): void
    {
        $u = $this->usuarioOInvitado();
        $leccion = Curso::leccion((int) $id);
        if (!$leccion) {
            Session::flash('aviso', 'Esa lección no existe. Te llevamos a tu ruta.');
            $this->redirigir('/ruta');
        }
        $curso = Curso::buscar((int) $leccion['curso_id']);
        $esAutor = (int) $curso['autor_id'] === (int) $u['id'] || $u['rol'] === 'superadmin';

        if (!$esAutor && !Curso::estaInscrito((int) $u['id'], (int) $curso['id'])) {
            if ($curso['catalogo'] || $curso['estado'] === 'publicado' && $curso['codigo'] && ($_GET['codigo'] ?? '') === $curso['codigo']) {
                Curso::inscribir((int) $u['id'], (int) $curso['id']);
            } else {
                Session::flash('aviso', 'Para jugar esta lección necesitas el código de la clase.');
                $this->redirigir('/unirse');
            }
        }

        $plan = Practica::plan($u, $curso);
        if (!$esAutor && !Practica::leccionDisponible(Practica::ruta($u, $curso, $plan), (int) $leccion['id'])) {
            Session::flash('aviso', 'Esa lección se abre cuando termines la anterior.');
            $this->redirigir('/ruta?curso=' . $curso['id']);
        }

        try {
            $intento = Practica::iniciarLeccion($u, $leccion, $plan);
        } catch (\RuntimeException $e) {
            Session::flash('aviso', $e->getMessage());
            $this->redirigir($esAutor ? '/docente/clase/' . $curso['id'] . '/revisar' : '/ruta?curso=' . $curso['id']);
        }

        $this->vista('leccion/jugar', [
            'titulo' => $leccion['titulo'] . ' · Cartilla',
            'leccion' => $leccion,
            'curso' => $curso,
            'paquete' => Practica::paquete($intento, $plan),
            'salir' => $esAutor && $curso['autor_id'] == $u['id'] ? '/docente/clase/' . $curso['id'] . '/revisar' : '/ruta?curso=' . $curso['id'],
        ], 'enfoque');
    }

    public function repaso(): void
    {
        $u = Auth::usuario();
        if (!$u) {
            $this->redirigir('/empezar');
        }
        $curso = Curso::buscar((int) ($_GET['curso'] ?? 0));
        if (!$curso || !Curso::estaInscrito((int) $u['id'], (int) $curso['id'])) {
            $this->redirigir('/ruta');
        }
        $intento = Practica::iniciarRepaso($u, $curso);
        if (!$intento) {
            $proximo = Progreso::proximoRepaso((int) $u['id']);
            Session::flash('exito', 'No tienes repasos pendientes hoy.' . ($proximo ? ' El próximo es el ' . fecha_corta($proximo) . '.' : ''));
            $this->redirigir('/ruta?curso=' . $curso['id']);
        }
        $plan = Practica::plan($u, $curso);
        $this->vista('leccion/jugar', [
            'titulo' => 'Repaso · Cartilla',
            'leccion' => ['titulo' => 'Repaso de errores', 'unidad_titulo' => $curso['titulo']],
            'curso' => $curso,
            'paquete' => Practica::paquete($intento, $plan),
            'salir' => '/ruta?curso=' . $curso['id'],
        ], 'enfoque');
    }

    public function resultado(string $id): void
    {
        $u = Auth::usuario();
        $intento = Intento::buscar((int) $id);
        if (!$u || !$intento || (int) $intento['usuario_id'] !== (int) $u['id']) {
            Session::flash('aviso', 'No encontramos ese resultado en tu cuenta.');
            $this->redirigir('/ruta');
        }
        if ($intento['estado'] === 'en_curso') {
            $this->redirigir($intento['leccion_id'] ? '/leccion/' . $intento['leccion_id'] : '/repaso?curso=' . $intento['curso_id']);
        }
        $curso = Curso::buscar((int) $intento['curso_id']);
        $ejercicios = Ejercicio::variosPorId($intento['ejercicio_ids']);
        $errores = [];
        foreach (Intento::respuestas((int) $intento['id']) as $r) {
            if (!$r['correcta'] && $r['primera_vez'] && isset($ejercicios[(int) $r['ejercicio_id']])) {
                $e = $ejercicios[(int) $r['ejercicio_id']];
                $errores[] = ['enunciado' => $e['enunciado'], 'tipo' => $e['tipo'], 'dada' => $r['respuesta'], 'correcta' => Evaluador::solucionLegible($e), 'explicacion' => $e['explicacion']];
            }
        }
        $plan = Practica::plan($u, $curso);
        $ruta = Practica::ruta($u, $curso, $plan);
        $esAutor = (int) $curso['autor_id'] === (int) $u['id'];

        $this->vista('leccion/resultado', [
            'titulo' => 'Resultado · Cartilla',
            'nav' => 'ruta',
            'intento' => $intento,
            'leccion' => $intento['leccion_id'] ? Curso::leccion((int) $intento['leccion_id']) : null,
            'curso' => $curso,
            'errores' => $errores,
            'total' => count($intento['ejercicio_ids']),
            'racha' => Progreso::racha((int) $u['id']),
            'xpHoy' => Progreso::xpHoy((int) $u['id']),
            'plan' => $plan,
            'siguiente' => Practica::siguienteLeccion($ruta),
            'esAutor' => $esAutor,
            'perfil' => Perfil::de((int) $u['id']),
            'registrado' => Auth::esRegistrado($u),
        ]);
    }
}
