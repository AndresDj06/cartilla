<?php
namespace App\Core;

use PDO;

/**
 * Modelo base: atajos sobre PDO con consultas preparadas.
 */
abstract class Model
{
    protected static function db(): PDO
    {
        return Database::pdo();
    }

    protected static function uno(string $sql, array $params = []): ?array
    {
        $st = self::db()->prepare($sql);
        $st->execute($params);
        $fila = $st->fetch();
        return $fila === false ? null : $fila;
    }

    protected static function todos(string $sql, array $params = []): array
    {
        $st = self::db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    protected static function valor(string $sql, array $params = [])
    {
        $st = self::db()->prepare($sql);
        $st->execute($params);
        $v = $st->fetchColumn();
        return $v === false ? null : $v;
    }

    protected static function ejecutar(string $sql, array $params = []): int
    {
        $st = self::db()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    protected static function insertar(string $sql, array $params = []): int
    {
        self::ejecutar($sql, $params);
        return (int) self::db()->lastInsertId();
    }
}
