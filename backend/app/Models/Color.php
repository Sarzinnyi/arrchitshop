<?php
declare(strict_types=1);
namespace App\Models;

final class Color
{
    public static function all(): array
    {
        return query("SELECT * FROM colors ORDER BY id")->fetchAll();
    }
    public static function exists(int $id): bool
    {
        return (bool) query("SELECT id FROM colors WHERE id=?", [$id])->fetch();
    }
    public static function byName(string $name): ?array
    {
        return query("SELECT id FROM colors WHERE name=?", [$name])->fetch() ?:
            null;
    }
    public static function create(string $name, string $hex): array
    {
        query("INSERT INTO colors(name,hex) VALUES (?,?)", [$name, $hex]);
        return [
            "id" => (int) db()->lastInsertId(),
            "name" => $name,
            "hex" => $hex,
        ];
    }
}
