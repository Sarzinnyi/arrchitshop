<?php
declare(strict_types=1);
spl_autoload_register(static function (string $class): void {
    $prefix = "App\\";
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file =
        __DIR__ .
        "/app/" .
        str_replace("\\", "/", substr($class, strlen($prefix))) .
        ".php";
    if (is_file($file)) {
        require $file;
    }
});
require_once __DIR__ . "/app/Core/helpers.php";
// Compatibility helpers for database setup and the integration test runner.
function db(): PDO
{
    return App\Core\Database::connection();
}
function query(string $sql, array $args = []): PDOStatement
{
    return App\Core\Database::query($sql, $args);
}
