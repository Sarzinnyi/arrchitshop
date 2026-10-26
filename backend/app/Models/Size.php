<?php
declare(strict_types=1);
namespace App\Models;

final class Size
{
    public static function all(): array
    {
        return query("SELECT * FROM sizes ORDER BY id")->fetchAll();
    }
    public static function exists(int $id): bool
    {
        return (bool) query("SELECT id FROM sizes WHERE id=?", [$id])->fetch();
    }
    public static function byLabel(string $label): ?array
    {
        return query("SELECT id FROM sizes WHERE label=?", [$label])->fetch() ?:
            null;
    }
    public static function create(string $label): array
    {
        query("INSERT INTO sizes(label) VALUES (?)", [$label]);
        return ["id" => (int) db()->lastInsertId(), "label" => $label];
    }
}
