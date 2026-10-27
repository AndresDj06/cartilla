<?php
// Configuración no secreta. Las credenciales van en .env (no versionado).
return [
    'nombre' => 'Cartilla',
    'superadmin' => [
        'nombre' => 'JHONATAN CORDOBA',
        'usuario' => 'jrodriguez',
        'correo' => 'jhonatanlaur@gmail.com',
    ],
    // Cuentas de demostración para el jurado (valores de prueba, no reales).
    'demo' => [
        'clave' => 'Demo2026!',
        'docente' => ['nombre' => 'Profe Demo', 'usuario' => 'profe.demo', 'correo' => 'docente@cartilla.test'],
        'estudiante' => ['nombre' => 'Estudiante Demo', 'usuario' => 'estudiante.demo', 'correo' => 'estudiante@cartilla.test'],
        'codigo_clase' => 'AGUA26',
    ],
];
