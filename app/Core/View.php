<?php
namespace App\Core;

/**
 * Renderiza vistas PHP dentro de un layout.
 */
class View
{
    public static function render(string $vista, array $datos = [], ?string $layout = 'app'): void
    {
        $archivo = dirname(__DIR__) . '/Views/' . $vista . '.php';
        extract($datos, EXTR_SKIP);
        ob_start();
        require $archivo;
        $contenido = ob_get_clean();
        if ($layout === null) {
            echo $contenido;
            return;
        }
        require dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';
    }

    public static function parte(string $vista, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        require dirname(__DIR__) . '/Views/partes/' . $vista . '.php';
    }
}
