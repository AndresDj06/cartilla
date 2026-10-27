<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Curso;

/**
 * Entrar a una clase con el código de seis caracteres (como el Game Pin) o sumar un curso del catálogo.
 */
class ClaseController extends Controller
{
    public function form(): void
    {
        $u = Auth::usuario();
        $GLOBALS['_errores'] = Session::flash('errores') ?? [];
        $inscritos = $u ? array_column(Curso::inscritos((int) $u['id']), 'id') : [];
        $this->vista('clase/unirse', [
            'titulo' => 'Clases · Cartilla',
            'nav' => 'unirse',
            'codigo' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($_GET['codigo'] ?? Session::flash('codigo') ?? ''))),
            'catalogo' => Curso::catalogo(),
            'inscritos' => array_map('intval', $inscritos),
            'misClases' => $u ? Curso::inscritos((int) $u['id']) : [],
        ]);
    }

    public function unirse(): void
    {
        $this->verificarCsrf();
        $u = $this->usuarioOInvitado();

        if ($this->entrada('curso') !== '') {
            $curso = Curso::buscar((int) $this->entrada('curso'));
            if (!$curso || !$curso['catalogo']) {
                Session::flash('aviso', 'Ese curso no está disponible.');
                $this->redirigir('/unirse');
            }
        } else {
            $codigo = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->entrada('codigo')));
            if (strlen($codigo) !== 6) {
                Session::flash('errores', ['codigo' => 'El código tiene 6 caracteres (letras y números). Revisa lo que te dictó tu docente.']);
                Session::flash('codigo', $codigo);
                $this->redirigir('/unirse');
            }
            $curso = Curso::porCodigo($codigo);
            if (!$curso) {
                Session::flash('errores', ['codigo' => "No encontramos una clase con el código $codigo. Puede que aún no esté publicada o que haya una letra cambiada (no usamos O ni I)."]);
                Session::flash('codigo', $codigo);
                $this->redirigir('/unirse');
            }
        }
        $ya = Curso::estaInscrito((int) $u['id'], (int) $curso['id']);
        Curso::inscribir((int) $u['id'], (int) $curso['id']);
        Session::flash('exito', $ya ? 'Ya estabas en «' . $curso['titulo'] . '».' : 'Entraste a «' . $curso['titulo'] . '». Tu primera lección está lista.');
        $this->redirigir('/ruta?curso=' . $curso['id']);
    }
}
