<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Curso;
use App\Models\Ejercicio;
use App\Models\Perfil;
use App\Services\GeneradorJuegos;
use App\Services\Instalador;
use App\Services\Personalizador;
use App\Services\Practica;
use App\Services\TextoEjemplo;

/**
 * Onboarding: de la intención de la persona a su primera lección (o a sus primeros juegos).
 * Cada respuesta se guarda al momento, así se puede volver atrás o cambiarla desde el resumen.
 */
class OnboardingController extends Controller
{
    private const PASOS = [
        'estudiante' => ['rol', 'objetivo', 'tema', 'nivel', 'tiempo', 'juegos', 'resumen'],
        'docente' => ['rol', 'objetivo', 'tema', 'grado', 'tiempo', 'juegos', 'resumen'],
    ];

    public function inicio(): void
    {
        $u = $this->usuarioOInvitado();
        $perfil = Perfil::de((int) $u['id']);
        $this->redirigir('/empezar/' . $this->primerPendiente($perfil));
    }

    public function mostrar(string $paso): void
    {
        $u = $this->usuarioOInvitado();
        $perfil = Perfil::de((int) $u['id']);
        $pasos = $this->pasos($perfil);
        if (!in_array($paso, $pasos, true)) {
            $this->redirigir('/empezar/' . $this->primerPendiente($perfil));
        }
        // No se puede saltar a un paso cuyos anteriores no estén respondidos.
        $pendiente = $this->primerPendiente($perfil);
        if (array_search($paso, $pasos, true) > array_search($pendiente, $pasos, true)) {
            $this->redirigir('/empezar/' . $pendiente);
        }

        $datos = $this->datosComunes($u, $perfil, $paso);
        if ($paso === 'resumen') {
            $datos += $this->prepararResumen($u, $perfil);
            if (isset($datos['errorGeneracion'])) {
                Session::flash('aviso', $datos['errorGeneracion']);
                $this->redirigir('/empezar/tema');
            }
        }
        $GLOBALS['_errores'] = Session::flash('errores') ?? [];
        $GLOBALS['_old'] = Session::flash('old') ?? [];
        $this->vista('onboarding/paso', $datos, 'sitio');
    }

    public function guardar(string $paso): void
    {
        $this->verificarCsrf();
        $u = $this->usuarioOInvitado();
        $id = (int) $u['id'];
        $perfil = Perfil::de($id);
        $volverResumen = $this->entrada('volver') === 'resumen';
        $errores = [];
        $cambios = [];

        switch ($paso) {
            case 'rol':
                $rol = $this->entrada('rol');
                if (!in_array($rol, ['estudiante', 'docente'], true)) {
                    $errores['rol'] = 'Elige si vienes como estudiante o como docente.';
                } elseif ($rol !== $perfil['rol_elegido']) {
                    // Cambiar de rol reinicia las respuestas que dependen de él.
                    if ($perfil['rol_elegido'] === 'docente' && $perfil['curso_id']) {
                        Curso::eliminarBorrador((int) $perfil['curso_id']);
                    }
                    $cambios = ['rol_elegido' => $rol, 'objetivo' => null, 'curso_id' => null, 'nivel' => null, 'grado' => null, 'completado' => 0];
                    $volverResumen = false;
                }
                break;
            case 'objetivo':
                $objetivo = $this->entrada('objetivo');
                if (!isset(Personalizador::OBJETIVOS[$perfil['rol_elegido']][$objetivo])) {
                    $errores['objetivo'] = 'Elige la opción que más se parece a lo que buscas.';
                } else {
                    $cambios['objetivo'] = $objetivo;
                }
                break;
            case 'tema':
                if ($perfil['rol_elegido'] === 'docente') {
                    $titulo = mb_substr(trim((string) $this->entrada('titulo')), 0, 120);
                    $texto = trim((string) $this->entrada('texto'));
                    try {
                        GeneradorJuegos::generar($texto, $titulo);
                        if ($texto !== $perfil['texto_fuente'] || $titulo !== $perfil['titulo_tema']) {
                            if ($perfil['curso_id']) {
                                Curso::eliminarBorrador((int) $perfil['curso_id']);
                            }
                            $cambios = ['titulo_tema' => $titulo ?: null, 'texto_fuente' => $texto, 'curso_id' => null];
                        }
                    } catch (\InvalidArgumentException $e) {
                        $errores['texto'] = $e->getMessage();
                    }
                } else {
                    $curso = Curso::buscar((int) $this->entrada('curso'));
                    if (!$curso || !$curso['catalogo']) {
                        $errores['curso'] = 'Elige uno de los cursos de la lista.';
                    } else {
                        $cambios['curso_id'] = (int) $curso['id'];
                    }
                }
                break;
            case 'nivel':
                $nivel = $this->entrada('nivel');
                if (!isset(Personalizador::NIVELES[$nivel])) {
                    $errores['nivel'] = 'Elige cómo te sientes con este tema.';
                } else {
                    $cambios['nivel'] = $nivel;
                }
                break;
            case 'grado':
                $grado = $this->entrada('grado');
                if (!isset(Personalizador::GRADOS[$grado])) {
                    $errores['grado'] = 'Elige el grado de tus estudiantes.';
                } else {
                    $cambios['grado'] = $grado;
                }
                break;
            case 'tiempo':
                $minutos = (int) $this->entrada('minutos');
                if (!isset(Personalizador::MINUTOS[$minutos])) {
                    $errores['minutos'] = 'Elige 5, 10 o 15 minutos.';
                } else {
                    $cambios['minutos'] = $minutos;
                }
                break;
            case 'juegos':
                $tipos = array_values(array_intersect((array) ($_POST['tipos'] ?? []), array_keys(Ejercicio::TIPOS)));
                if (!$tipos) {
                    $errores['tipos'] = 'Marca al menos un tipo de juego.';
                } else {
                    $cambios['tipos'] = implode(',', $tipos);
                }
                break;
            default:
                $this->redirigir('/empezar');
        }

        if ($errores) {
            Session::flash('errores', $errores);
            Session::flash('old', array_map(fn ($v) => is_string($v) ? $v : '', $_POST));
            $this->redirigir('/empezar/' . $paso . ($volverResumen ? '?volver=resumen' : ''));
        }
        if ($cambios) {
            Perfil::guardar($id, $cambios);
        }
        $perfil = Perfil::de($id);
        $siguiente = $volverResumen ? 'resumen' : $this->siguiente($perfil, $paso);
        $this->redirigir('/empezar/' . ($volverResumen ? 'resumen' : $this->primerPendienteDesde($perfil, $siguiente)));
    }

    /** Desde el resumen: marca el onboarding como hecho y lleva al primer resultado útil. */
    public function confirmar(): void
    {
        $this->verificarCsrf();
        $u = $this->usuarioOInvitado();
        $perfil = Perfil::de((int) $u['id']);
        if ($this->primerPendiente($perfil) !== 'resumen' || !$perfil['curso_id']) {
            $this->redirigir('/empezar');
        }
        Perfil::guardar((int) $u['id'], ['completado' => 1]);
        $curso = Curso::buscar((int) $perfil['curso_id']);

        if ($perfil['rol_elegido'] === 'docente') {
            $destino = $this->entrada('destino') === 'probar' ? 'probar' : 'revisar';
            if ($destino === 'probar') {
                $l = Curso::primeraLeccion((int) $curso['id']);
                $this->redirigir('/leccion/' . $l['id']);
            }
            $this->redirigir('/docente/clase/' . $curso['id'] . '/revisar');
        }
        $plan = Practica::plan($u, $curso);
        $siguiente = Practica::siguienteLeccion(Practica::ruta($u, $curso, $plan));
        $this->redirigir($siguiente ? '/leccion/' . $siguiente['id'] : '/ruta?curso=' . $curso['id']);
    }

    public function reiniciar(): void
    {
        $this->verificarCsrf();
        $u = $this->usuarioOInvitado();
        $perfil = Perfil::de((int) $u['id']);
        if ($perfil['rol_elegido'] === 'docente' && $perfil['curso_id']) {
            Curso::eliminarBorrador((int) $perfil['curso_id']);
        }
        Perfil::guardar((int) $u['id'], ['rol_elegido' => null, 'objetivo' => null, 'curso_id' => null, 'nivel' => null, 'grado' => null,
            'minutos' => null, 'tipos' => null, 'titulo_tema' => null, 'texto_fuente' => null, 'completado' => 0]);
        Session::flash('exito', 'Empezamos de nuevo. Tu avance en lecciones anteriores sigue guardado.');
        $this->redirigir('/empezar/rol');
    }

    // ---------------------------------------------------------------

    private function pasos(array $perfil): array
    {
        return self::PASOS[$perfil['rol_elegido'] ?? ''] ?? ['rol'];
    }

    private function respondido(array $perfil, string $paso): bool
    {
        switch ($paso) {
            case 'rol': return (bool) $perfil['rol_elegido'];
            case 'objetivo': return (bool) $perfil['objetivo'];
            case 'tema': return $perfil['rol_elegido'] === 'docente' ? (bool) $perfil['texto_fuente'] : (bool) $perfil['curso_id'];
            case 'nivel': return (bool) $perfil['nivel'];
            case 'grado': return (bool) $perfil['grado'];
            case 'tiempo': return (bool) $perfil['minutos'];
            case 'juegos': return (bool) $perfil['tipos'];
        }
        return false;
    }

    private function primerPendiente(array $perfil): string
    {
        foreach ($this->pasos($perfil) as $p) {
            if ($p === 'resumen' || !$this->respondido($perfil, $p)) {
                return $p;
            }
        }
        return 'rol';
    }

    private function primerPendienteDesde(array $perfil, string $desde): string
    {
        $pasos = $this->pasos($perfil);
        foreach (array_slice($pasos, (int) array_search($desde, $pasos, true)) as $p) {
            if ($p === 'resumen' || !$this->respondido($perfil, $p)) {
                return $p;
            }
        }
        return 'resumen';
    }

    private function siguiente(array $perfil, string $paso): string
    {
        $pasos = $this->pasos($perfil);
        $i = array_search($paso, $pasos, true);
        return $pasos[$i + 1] ?? 'resumen';
    }

    private function datosComunes(array $u, array $perfil, string $paso): array
    {
        $pasos = $this->pasos($perfil);
        $esDocente = $perfil['rol_elegido'] === 'docente';
        $plan = Personalizador::plan($perfil);
        $curso = $perfil['curso_id'] ? Curso::buscar((int) $perfil['curso_id']) : null;
        return [
            'titulo' => 'Empezar · Cartilla',
            'claseCuerpo' => 'flujo paso-' . $paso,
            'paso' => $paso,
            'pasos' => $pasos,
            'indice' => (int) array_search($paso, $pasos, true),
            'perfil' => $perfil,
            'plan' => $plan,
            'curso' => $curso,
            'esDocente' => $esDocente,
            'rolSugerido' => $this->entrada('rol'),
            'volver' => $this->entrada('volver') === 'resumen',
            'catalogo' => Curso::catalogo(),
            'sugerido' => Personalizador::SUGERIDO[$perfil['objetivo'] ?? ''] ?? null,
            'textoEjemplo' => TextoEjemplo::TEXTO,
            'tituloEjemplo' => TextoEjemplo::TITULO,
            'registrado' => Auth::esRegistrado($u),
        ];
    }

    private function prepararResumen(array $u, array $perfil): array
    {
        $id = (int) $u['id'];
        if ($perfil['rol_elegido'] === 'docente') {
            if (!$perfil['curso_id'] || !Curso::buscar((int) $perfil['curso_id'])) {
                try {
                    $generado = GeneradorJuegos::generar($perfil['texto_fuente'], (string) $perfil['titulo_tema']);
                } catch (\InvalidArgumentException $e) {
                    return ['errorGeneracion' => $e->getMessage()];
                }
                $cursoId = Instalador::guardarGenerado($generado, $id, $perfil['grado'] ?: 'secundaria', $perfil['texto_fuente']);
                Perfil::guardar($id, ['curso_id' => $cursoId]);
                $perfil = Perfil::de($id);
            } else {
                Curso::actualizarGrado((int) $perfil['curso_id'], $perfil['grado'] ?: 'secundaria');
            }
        } else {
            Curso::inscribir($id, (int) $perfil['curso_id']);
        }
        $curso = Curso::buscar((int) $perfil['curso_id']);
        $plan = Personalizador::plan($perfil);
        $estructura = Curso::estructura((int) $curso['id']);
        $nEjercicios = array_sum(array_map(fn ($un) => array_sum(array_column($un['lecciones'], 'n_ejercicios')), $estructura));
        return [
            'perfil' => $perfil,
            'curso' => $curso,
            'plan' => $plan,
            'estructura' => $estructura,
            'nEjercicios' => $nEjercicios,
            'decisiones' => Personalizador::decisiones($perfil, $plan, $curso),
        ];
    }
}
