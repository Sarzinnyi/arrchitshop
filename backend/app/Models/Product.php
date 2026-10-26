<?php
declare(strict_types=1);
namespace App\Models;

final class Product
{
    public static function find(int $id): array
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
    public static function all(): array
    {
        return array_map(
            fn($row) => self::find((int) $row["id"]),
            query("SELECT id FROM products ORDER BY id DESC")->fetchAll(),
        );
    }
    public static function byCode(string $code): ?array
    {
        return query("SELECT id FROM products WHERE p_id=?", [
            $code,
        ])->fetch() ?:
            null;
    }
    public static function save(
        int $id,
        string $name,
        string $code,
        string $price,
        string $description,
    ): int {
        if ($id) {
            query(
                "UPDATE products SET name=?,p_id=?,price=?,description=? WHERE id=?",
                [$name, $code, $price, $description, $id],
            );
        } else {
            query(
                "INSERT INTO products(name,p_id,price,description) VALUES (?,?,?,?)",
                [$name, $code, $price, $description],
            );
            $id = (int) db()->lastInsertId();
        }
        return $id;
    }
    public static function replaceVariants(int $id, array $variants): void
    {
        query("DELETE FROM variants WHERE product_id=?", [$id]);
        foreach ($variants as $v) {
            query(
                "INSERT INTO variants(product_id,color_id,size_id,quantity) VALUES (?,?,?,?)",
                [$id, $v["color_id"], $v["size_id"], $v["quantity"]],
            );
        }
    }
    public static function replacePhotos(int $id, array $photos): void
    {
        query("DELETE FROM photos WHERE product_id=?", [$id]);
        foreach ($photos as $position => $p) {
            query(
                "INSERT INTO photos(product_id,color_id,path,position) VALUES (?,?,?,?)",
                [$id, $p["color_id"], $p["path"], $position],
            );
        }
    }
    public static function delete(int $id): void
    {
        query("DELETE FROM products WHERE id=?", [$id]);
    }
}
