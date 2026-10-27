<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Curso;
use App\Models\Usuario;

class AdminController extends Controller
{
    public function index(): void
    {
        $this->exigirCuenta('superadmin');
        $pdo = Database::pdo();
        $this->vista('admin/index', [
            'titulo' => 'Administración · Cartilla',
            'nav' => 'admin',
            'roles' => Usuario::contarPorRol(),
            'usuarios' => Usuario::listar(),
            'cursos' => Curso::todosParaAdmin(),
            'respuestasHoy' => (int) $pdo->query('SELECT COUNT(*) FROM respuestas WHERE DATE(creado_en) = CURDATE()')->fetchColumn(),
            'respuestas' => (int) $pdo->query('SELECT COUNT(*) FROM respuestas')->fetchColumn(),
        ]);
    }
}
