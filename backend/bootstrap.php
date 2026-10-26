<?php
declare(strict_types=1);
function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $dsn = getenv("DB_DSN") ?: "sqlite:" . __DIR__ . "/var/shop.sqlite";
        $pdo = new PDO(
            $dsn,
            getenv("DB_USER") ?: null,
            getenv("DB_PASSWORD") ?: null,
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
function query(string $sql, array $args = []): PDOStatement
{
    $s = db()->prepare($sql);
    $s->execute($args);
    return $s;
}
function respond(array $body, int $status = 200): never
{
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode($body, JSON_THROW_ON_ERROR);
    exit();
}
function fail(string $message, int $status = 422): never
{
    respond(["error" => $message], $status);
}
function text(mixed $value, int $max, string $field): string
{
    if (
        !is_string($value) ||
        trim($value) === "" ||
        mb_strlen(trim($value)) > $max
    ) {
        fail("Invalid $field.");
    }
    return trim($value);
}
function admin(): array
{
    if (!isset($_SESSION["admin_id"])) {
        fail("Please sign in.", 401);
    }
    $a = query("SELECT id,name,email FROM admins WHERE id=?", [
        $_SESSION["admin_id"],
    ])->fetch();
    if (!$a) {
        fail("Please sign in.", 401);
    }
    return $a;
}
function csrf(): void
{
    if (!hash_equals($_SESSION["csrf"], $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "")) {
        fail("Session expired. Refresh and try again.", 403);
    }
}
function product(int $id): array
{
    $p = query("SELECT * FROM products WHERE id=?", [$id])->fetch();
    if (!$p) {
        fail("Product not found.", 404);
    }
    $p["variants"] = query(
        "SELECT v.*,s.label AS size,c.name AS color,c.hex FROM variants v JOIN sizes s ON s.id=v.size_id JOIN colors c ON c.id=v.color_id WHERE product_id=? ORDER BY v.id",
        [$id],
    )->fetchAll();
    $p["photos"] = query(
        "SELECT * FROM photos WHERE product_id=? ORDER BY position",
        [$id],
    )->fetchAll();
    return $p;
}
