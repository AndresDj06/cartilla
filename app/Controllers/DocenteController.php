<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Curso;
use App\Models\Ejercicio;
use App\Models\Progreso;
use App\Services\GeneradorJuegos;
use App\Services\Instalador;
use App\Services\Personalizador;
use App\Services\TextoEjemplo;

class DocenteController extends Controller
{
    public function index(): void
    {
        $u = $this->exigirCuenta('docente');
        $clases = Curso::delAutor((int) $u['id']);
        foreach ($clases as &$c) {
            $est = Progreso::estudiantesDeCurso((int) $c['id']);
            $c['n_estudiantes'] = count($est);
            $precisiones = array_filter(array_column($est, 'precision_media'), fn ($p) => $p !== null);
            $c['acierto'] = $precisiones ? (int) round(array_sum($precisiones) / count($precisiones)) : null;
            $c['n_ejercicios'] = count(Ejercicio::deCurso((int) $c['id']));
        }
        unset($c);
        $this->vista('docente/index', ['titulo' => 'Panel docente · Cartilla', 'nav' => 'docente', 'clases' => $clases]);
    }

    public function formCrear(): void
    {
        $this->exigirCuenta('docente');
        $GLOBALS['_errores'] = Session::flash('errores') ?? [];
        $GLOBALS['_old'] = Session::flash('old') ?? [];
        $this->vista('docente/crear', [
            'titulo' => 'Crear juegos · Cartilla', 'nav' => 'docente',
            'textoEjemplo' => TextoEjemplo::TEXTO, 'tituloEjemplo' => TextoEjemplo::TITULO,
        ]);
    }

    public function crear(): void
    {
        $this->verificarCsrf();
        $u = $this->exigirCuenta('docente');
        $titulo = mb_substr(trim((string) $this->entrada('titulo')), 0, 120);
        $texto = trim((string) $this->entrada('texto'));
        $grado = $this->entrada('grado');
        $errores = [];
        if (!isset(Personalizador::GRADOS[$grado])) {
            $errores['grado'] = 'Elige el grado de tus estudiantes.';
        }
        try {
            $generado = GeneradorJuegos::generar($texto, $titulo);
        } catch (\InvalidArgumentException $e) {
            $errores['texto'] = $e->getMessage();
        }
        if ($errores) {
            Session::flash('errores', $errores);
            Session::flash('old', ['titulo' => $titulo, 'texto' => $texto, 'grado' => (string) $grado]);
            $this->redirigir('/docente/crear');
        }
        $id = Instalador::guardarGenerado($generado, (int) $u['id'], $grado, $texto);
        Session::flash('exito', 'Armamos ' . count(Ejercicio::deCurso($id)) . ' ejercicios. Revísalos antes de publicar.');
        $this->redirigir('/docente/clase/' . $id . '/revisar');
    }

    public function revisar(string $id): void
    {
        $u = Auth::usuario();
        $curso = $this->cursoPropio($u, (int) $id);
        $GLOBALS['_errores'] = Session::flash('errores') ?? [];
        $old = Session::flash('old_revision');
        $this->vista('docente/revisar', [
            'titulo' => 'Revisar juegos · Cartilla',
            'nav' => 'docente',
            'curso' => $curso,
            'estructura' => Curso::estructura((int) $curso['id']),
            'ejercicios' => Ejercicio::deCurso((int) $curso['id']),
            'old' => is_array($old) ? $old : null,
            'registrado' => Auth::esRegistrado($u),
            'primera' => Curso::primeraLeccion((int) $curso['id']),
        ]);
    }

    public function guardarRevision(string $id): void
    {
        $this->verificarCsrf();
        $u = Auth::usuario();
        $curso = $this->cursoPropio($u, (int) $id);
        $entrada = (array) ($_POST['e'] ?? []);
        $titulo = trim((string) $this->entrada('titulo_curso'));
        $errores = [];
        $cambios = [];
        $quitar = [];

        if (mb_strlen($titulo) < 3) {
            $errores['titulo'] = 'El título de la clase necesita al menos 3 caracteres.';
        }
        foreach (Ejercicio::deCurso((int) $curso['id']) as $ej) {
            $d = (array) ($entrada[$ej['id']] ?? []);
            if (empty($d['incluir'])) {
                $quitar[] = $ej['id'];
                continue;
            }
            $enunciado = trim((string) ($d['enunciado'] ?? $ej['enunciado']));
            $sol = $ej['solucion'];
            $datos = $ej['datos'];
            $err = null;
            switch ($ej['tipo']) {
                case 'completar':
                    $alts = array_values(array_filter(array_map('trim', explode('/', (string) ($d['respuesta'] ?? '')))));
                    if (!str_contains($enunciado, '___')) {
                        $err = 'La frase debe tener ___ (tres guiones bajos) donde va la palabra que falta.';
                    } elseif (!$alts) {
                        $err = 'Escribe la palabra que falta.';
                    } else {
                        $sol = ['respuestas' => $alts];
                        $banco = array_values(array_filter($datos['banco'] ?? [], fn ($p) => normalizar($p) !== normalizar($ej['solucion']['respuestas'][0])));
                        $datos['banco'] = array_merge([$alts[0]], array_slice($banco, 0, 3));
                    }
                    break;
                case 'ordenar_palabras':
                    $frase = trim(preg_replace('/\s+/u', ' ', (string) ($d['frase'] ?? '')));
                    if (count(explode(' ', $frase)) < 3) {
                        $err = 'La frase para ordenar necesita al menos 3 palabras.';
                    }
                    $sol = ['frase' => $frase];
                    break;
                case 'ordenar_letras':
                    $palabra = trim((string) ($d['palabra'] ?? ''));
                    if (!preg_match('/^\p{L}{3,14}$/u', $palabra)) {
                        $err = 'Escribe una sola palabra de 3 a 14 letras, sin espacios.';
                    }
                    $sol = ['palabra' => $palabra];
                    $datos['pista'] = trim((string) ($d['pista'] ?? ''));
                    break;
                case 'test':
                    $ops = array_values(array_map('trim', (array) ($d['opciones'] ?? [])));
                    $correcta = (int) ($d['correcta'] ?? 0);
                    if (count(array_filter($ops)) < count($ops) || count(array_unique(array_map('normalizar', $ops))) < count($ops)) {
                        $err = 'Las tres opciones deben estar escritas y ser distintas.';
                    }
                    $datos['opciones'] = $ops;
                    $sol = ['indice' => max(0, min(count($ops) - 1, $correcta))];
                    break;
                case 'si_no':
                    $sol = ['valor' => ($d['valor'] ?? 'si') === 'no' ? 'no' : 'si'];
                    break;
                case 'relacionar':
                    $pares = [];
                    foreach ((array) ($d['pares'] ?? []) as $p) {
                        $a = trim((string) ($p[0] ?? ''));
                        $b = trim((string) ($p[1] ?? ''));
                        if ($a !== '' && $b !== '') {
                            $pares[] = [$a, $b];
                        }
                    }
                    if (count($pares) < 2) {
                        $err = 'Deja al menos 2 parejas completas.';
                    }
                    $sol = ['pares' => $pares];
                    break;
            }
            if ($enunciado === '') {
                $err = 'El enunciado no puede quedar vacío.';
            }
            if ($err) {
                $errores['e' . $ej['id']] = $err;
            }
            $cambios[$ej['id']] = [$enunciado, $sol, $datos];
        }
        if (!$cambios && !$errores) {
            $errores['general'] = 'Deja al menos un ejercicio incluido.';
        }
        if ($errores) {
            Session::flash('errores', $errores);
            Session::flash('old_revision', $_POST);
            $this->redirigir('/docente/clase/' . $curso['id'] . '/revisar');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        Curso::actualizarTitulo((int) $curso['id'], mb_substr($titulo, 0, 140));
        foreach ($cambios as $eid => [$en, $sol, $datos]) {
            Ejercicio::actualizar($eid, $en, $sol, $datos);
        }
        foreach ($quitar as $eid) {
            Ejercicio::eliminar($eid);
        }
        $pdo->commit();

        if ($this->entrada('accion') === 'publicar') {
            $this->publicar((string) $curso['id'], false);
            return;
        }
        Session::flash('exito', 'Cambios guardados' . ($quitar ? ' y ' . plural(count($quitar), 'ejercicio quitado', 'ejercicios quitados') : '') . '.');
        $this->redirigir('/docente/clase/' . $curso['id'] . '/revisar');
    }

    public function publicar(string $id, bool $verificar = true): void
    {
        if ($verificar) {
            $this->verificarCsrf();
        }
        $u = Auth::usuario();
        $curso = $this->cursoPropio($u, (int) $id);
        if (!Auth::esRegistrado($u)) {
            // Al crear la cuenta se publica sola: no hay que volver a pulsar el botón.
            Session::set('publicar_pendiente', (int) $curso['id']);
            Session::flash('aviso', 'Para publicar crea tu cuenta: así la clase queda a tu nombre y puedes ver los resultados desde cualquier dispositivo. Tus juegos ya están guardados y la clase se publica al terminar.');
            $this->redirigir('/registro');
        }
        if ($curso['estado'] !== 'publicado') {
            Curso::publicar((int) $curso['id'], Curso::codigoNuevo());
        }
        Session::flash('exito', 'Clase publicada. Comparte el código con tus estudiantes.');
        $this->redirigir('/docente/clase/' . $curso['id']);
    }

    public function clase(string $id): void
    {
        $u = $this->exigirCuenta('docente');
        $curso = $this->cursoPropio($u, (int) $id);
        if ($curso['estado'] !== 'publicado') {
            $this->redirigir('/docente/clase/' . $curso['id'] . '/revisar');
        }
        $preguntas = Progreso::aciertoPorEjercicio((int) $curso['id']);
        foreach ($preguntas as &$p) {
            $p['frecuentes'] = (int) $p['respuestas'] > 0 && (int) $p['porcentaje'] < 100 ? Progreso::respuestasFrecuentes((int) $p['id']) : [];
        }
        unset($p);
        $estudiantes = Progreso::estudiantesDeCurso((int) $curso['id']);
        $conFallos = array_filter($preguntas, fn ($p) => (int) $p['respuestas'] > 0 && (int) $p['porcentaje'] < 100);
        $totalRespuestas = array_sum(array_column($preguntas, 'respuestas'));
        $totalAciertos = array_sum(array_column($preguntas, 'aciertos'));
        $nLecciones = array_sum(array_map(fn ($un) => count($un['lecciones']), Curso::estructura((int) $curso['id'])));

        $this->vista('docente/clase', [
            'titulo' => $curso['titulo'] . ' · Panel docente',
            'nav' => 'docente',
            'curso' => $curso,
            'preguntas' => $preguntas,
            'estudiantes' => $estudiantes,
            'kpi' => [
                'estudiantes' => count($estudiantes),
                'activos' => count(array_filter($estudiantes, fn ($e) => $e['ultima_vez'] && strtotime($e['ultima_vez']) > strtotime('-7 day'))),
                'acierto' => $totalRespuestas ? (int) round(100 * $totalAciertos / $totalRespuestas) : null,
                'respuestas' => $totalRespuestas,
                'lecciones' => $nLecciones,
                'mas_fallada' => $conFallos ? reset($conFallos) : null,
            ],
            'enlace' => (isset($_SERVER['HTTP_HOST']) ? ((!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']) : '') . url('/unirse?codigo=' . $curso['codigo']),
        ]);
    }

    public function csv(string $id): void
    {
        $u = $this->exigirCuenta('docente');
        $curso = $this->cursoPropio($u, (int) $id);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cartilla-' . $curso['codigo'] . '-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel lea las tildes.
        fputcsv($out, ['Estudiante', 'Lecciones hechas', 'Acierto a la primera (%)', 'Última práctica'], ';');
        foreach (Progreso::estudiantesDeCurso((int) $curso['id']) as $e) {
            fputcsv($out, [$e['nombre'], $e['lecciones_hechas'], $e['precision_media'] ?? '', $e['ultima_vez'] ?? ''], ';');
        }
        fputcsv($out, [], ';');
        fputcsv($out, ['Pregunta', 'Tipo', 'Lección', 'Respuestas', 'Aciertos', 'Acierto (%)'], ';');
        foreach (Progreso::aciertoPorEjercicio((int) $curso['id']) as $p) {
            fputcsv($out, [$p['enunciado'], Ejercicio::TIPOS[$p['tipo']] ?? $p['tipo'], $p['leccion'], $p['respuestas'], $p['aciertos'] ?? 0, $p['porcentaje'] ?? ''], ';');
        }
        fclose($out);
    }

    /** El curso debe ser del usuario actual (o el usuario es super administrador). */
    private function cursoPropio(?array $u, int $id): array
    {
        $curso = Curso::buscar($id);
        if (!$u || !$curso || ((int) $curso['autor_id'] !== (int) $u['id'] && $u['rol'] !== 'superadmin')) {
            http_response_code($curso ? 403 : 404);
            View::render($curso ? 'errores/403' : 'errores/404', ['titulo' => 'Sin acceso'], 'sitio');
            exit;
        }
        return $curso;
    }
}
