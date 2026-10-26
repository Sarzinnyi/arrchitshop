<?php
declare(strict_types=1);
namespace App\Models;

final class Admin
{
    public static function find(int $id): ?array
    {
        return query("SELECT id,name,email FROM admins WHERE id=?", [
            $id,
        ])->fetch() ?:
            null;
    }
    public static function byEmail(string $email): ?array
    {
        return query("SELECT * FROM admins WHERE email=?", [$email])->fetch() ?:
            null;
    }
    public static function create(
        string $email,
        string $name,
        string $hash,
    ): void {
        query("INSERT INTO admins(email,name,password_hash) VALUES (?,?,?)", [
            $email,
            $name,
            $hash,
        ]);
    }
}
