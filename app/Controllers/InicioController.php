<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Curso;
use App\Models\Ejercicio;
use App\Services\Evaluador;

class InicioController extends Controller
{
    /** Ejercicio real del catálogo que se responde en la portada (río Atrato, sujeto de derechos). */
    private const EJERCICIO_PORTADA = 'En 2016 la Corte Constitucional reconoció al río ___ como sujeto de derechos.';

    public function index(): void
    {
        $catalogo = Curso::catalogo();
        $pdo = Database::pdo();

        $st = $pdo->prepare('SELECT id FROM ejercicios WHERE enunciado = ? LIMIT 1');
        $st->execute([self::EJERCICIO_PORTADA]);
        $ej = Ejercicio::buscar((int) $st->fetchColumn());

        // Cifras reales del producto para el tablero de hechos.
        $cifras = [
            'cursos' => count($catalogo),
            'lecciones' => (int) $pdo->query('SELECT COUNT(*) FROM lecciones l JOIN unidades u ON u.id = l.unidad_id JOIN cursos c ON c.id = u.curso_id WHERE c.catalogo = 1')->fetchColumn(),
            'ejercicios' => (int) $pdo->query('SELECT COUNT(*) FROM ejercicios e JOIN lecciones l ON l.id = e.leccion_id JOIN unidades u ON u.id = l.unidad_id JOIN cursos c ON c.id = u.curso_id WHERE c.catalogo = 1')->fetchColumn(),
            'tipos' => count(Ejercicio::TIPOS),
        ];

        $this->vista('inicio/index', [
            'titulo' => 'Cartilla — que no se te olvide lo que viste en clase',
            'ejercicio' => $ej ? Evaluador::preparar($ej, 3) : null,
            'fuente' => $ej ? str_replace('___', $ej['solucion']['respuestas'][0], $ej['enunciado']) : '',
            'catalogo' => $catalogo,
            'cifras' => $cifras,
            'registrado' => Auth::esRegistrado(Auth::usuario()),
        ], 'portada');
    }

    public function privacidad(): void
    {
        $this->vista('inicio/privacidad', ['titulo' => 'Tus datos en Cartilla'], 'sitio');
    }
}
