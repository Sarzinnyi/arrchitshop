<?php
declare(strict_types=1);
namespace App\Core;
use PDO;
final class Database
{
    public static function connection(): \PDO
    {
        static $pdo;
        if (!$pdo) {
            $config = require dirname(__DIR__, 2) . "/config/database.php";
            $pdo = new PDO(
                $config["dsn"],
                $config["user"],
                $config["password"],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            );
            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === "sqlite") {
                $pdo->exec("PRAGMA foreign_keys=ON");
                $pdo->exec("PRAGMA busy_timeout=5000");
            }
        }
        return $pdo;
    }
    public static function query(string $sql, array $args = []): \PDOStatement
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($args);
        return $statement;
    }
}
