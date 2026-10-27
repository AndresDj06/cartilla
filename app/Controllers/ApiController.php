<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Ejercicio;
use App\Models\Intento;
use App\Models\Progreso;
use App\Services\Evaluador;
use App\Services\Practica;

/**
 * Endpoints JSON del reproductor de lecciones. Toda corrección ocurre en el servidor.
 */
class ApiController extends Controller
{
    public function responder(string $id): void
    {
        $this->verificarCsrf(true);
        $usuario = Auth::usuario();
        $intento = Intento::buscar((int) $id);
        if (!$usuario || !$intento) {
            $this->json(['error' => 'No encontramos esta lección. Vuelve a tu ruta y ábrela de nuevo.'], 404);
            return;
        }
        $datos = $this->jsonEntrada();
        try {
            $r = Practica::responder($usuario, $intento, (int) ($datos['ejercicio'] ?? 0), $datos['respuesta'] ?? null);
            $this->json($r);
        } catch (\DomainException $e) {
            $this->json(['error' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function terminar(string $id): void
    {
        $this->verificarCsrf(true);
        $usuario = Auth::usuario();
        $intento = Intento::buscar((int) $id);
        if (!$usuario || !$intento) {
            $this->json(['error' => 'No encontramos esta lección.'], 404);
            return;
        }
        try {
            $cerrado = Practica::terminar($usuario, $intento);
            $this->json(['ok' => true, 'destino' => url('/resultado/' . $cerrado['id']), 'xp' => (int) $cerrado['xp'], 'racha' => Progreso::racha((int) $usuario['id'])]);
        } catch (\DomainException $e) {
            $this->json(['error' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    /** Pregunta de la portada: solo ejercicios del catálogo público. */
    public function muestra(): void
    {
        $this->verificarCsrf(true);
        $datos = $this->jsonEntrada();
        $id = (int) ($datos['id'] ?? 0);
        $st = Database::pdo()->prepare(
            'SELECT 1 FROM ejercicios e JOIN lecciones l ON l.id = e.leccion_id JOIN unidades u ON u.id = l.unidad_id
             JOIN cursos c ON c.id = u.curso_id WHERE e.id = ? AND c.catalogo = 1'
        );
        $st->execute([$id]);
        $ejercicio = $st->fetchColumn() ? Ejercicio::buscar($id) : null;
        if (!$ejercicio) {
            $this->json(['error' => 'Pregunta no disponible.'], 404);
            return;
        }
        $r = Evaluador::evaluar($ejercicio, $datos['respuesta'] ?? '');
        $this->json(['correcta' => $r['correcta'], 'solucion' => $r['solucion'], 'explicacion' => $ejercicio['explicacion']]);
    }
}
