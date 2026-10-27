<?php
// Carga automática PSR-4 simple para el espacio de nombres App\.
spl_autoload_register(function (string $clase) {
    if (str_starts_with($clase, 'App\\')) {
        $archivo = __DIR__ . '/' . str_replace('\\', '/', substr($clase, 4)) . '.php';
        if (is_file($archivo)) {
            require $archivo;
        }
    }
});

require __DIR__ . '/helpers.php';

App\Core\Env::cargar(dirname(__DIR__) . '/.env');
date_default_timezone_set(App\Core\Env::get('APP_TZ', 'America/Bogota'));
mb_internal_encoding('UTF-8');
