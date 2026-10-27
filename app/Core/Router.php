<?php
namespace App\Core;

/**
 * Enrutador simple: GET/POST con parámetros {nombre}.
 */
class Router
{
    private array $rutas = [];

    public function get(string $patron, array $destino): void
    {
        $this->rutas['GET'][] = [$patron, $destino];
    }

    public function post(string $patron, array $destino): void
    {
        $this->rutas['POST'][] = [$patron, $destino];
    }

    public function despachar(string $metodo, string $ruta): void
    {
        $ruta = '/' . trim($ruta, '/');
        foreach ($this->rutas[$metodo] ?? [] as [$patron, $destino]) {
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $patron) . '$#';
            if (preg_match($regex, $ruta, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                [$clase, $accion] = $destino;
                (new $clase())->$accion(...array_values($params));
                return;
            }
        }
        http_response_code(404);
        View::render('errores/404', ['titulo' => 'Página no encontrada'], 'sitio');
    }
}
