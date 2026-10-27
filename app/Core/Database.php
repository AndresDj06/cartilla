<?php
namespace App\Core;

use PDO;

/**
 * Conexión PDO única a MySQL/MariaDB con sanitización automática de parámetros.
 */
class Database
{
    private static ?PDO $pdo = null;

    /**
     * Resuelve y limpia el host y puerto de conexión.
     * Corrige automáticamente errores comunes (ej: ingresar 'https://...', puertos pegados, o rutas web).
     */
    public static function resolverHostYPuerto(): array
    {
        $hostOriginal = (string) Env::get('DB_HOST', '127.0.0.1');
        $host = trim($hostOriginal);
        $puerto = trim((string) Env::get('DB_PORT', '3306'));

        // Si el usuario puso por error una URL completa con esquema (ej: https://localhost, https://tudominio.com)
        if (preg_match('#^https?://#i', $host)) {
            $parsed = parse_url($host);
            $host = $parsed['host'] ?? $host;
            if (!empty($parsed['port'])) {
                $puerto = (string) $parsed['port'];
            }
        }

        // Si viene con formato host:puerto (ej: localhost:3306 o 127.0.0.1:3306)
        if (str_contains($host, ':')) {
            $partes = explode(':', $host, 2);
            $host = $partes[0];
            if (!empty($partes[1]) && is_numeric($partes[1])) {
                $puerto = $partes[1];
            }
        }

        // Limpiar barras o espacios
        $host = trim($host, "/ \t\n\r\0\x0B");

        // Si quedó vacío o contiene solo 'https' / 'http'
        if ($host === '' || strcasecmp($host, 'https') === 0 || strcasecmp($host, 'http') === 0) {
            $host = 'localhost';
        }

        return [$host, $puerto !== '' ? $puerto : '3306'];
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            [$host, $puerto] = self::resolverHostYPuerto();
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $host,
                $puerto,
                Env::get('DB_NAME', 'cartilla')
            );
            self::$pdo = new PDO($dsn, Env::get('DB_USER', 'root'), Env::get('DB_PASS', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$pdo;
    }

    /** Conexión sin base seleccionada, usada por el instalador. */
    public static function servidor(): PDO
    {
        [$host, $puerto] = self::resolverHostYPuerto();
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $puerto);
        return new PDO($dsn, Env::get('DB_USER', 'root'), Env::get('DB_PASS', ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
}
