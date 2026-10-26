<?php
declare(strict_types=1);
namespace App\Models;
use PDO;
final class LoginLimit
{
    public static function hit(string $key, int $now, int $expiry): array
    {
        if (db()->getAttribute(PDO::ATTR_DRIVER_NAME) === "sqlite") {
            query(
                "INSERT INTO login_limits(bucket,attempts,expires_at) VALUES (?,1,?) ON CONFLICT(bucket) DO UPDATE SET attempts=CASE WHEN expires_at<=? THEN 1 ELSE attempts+1 END, expires_at=CASE WHEN expires_at<=? THEN ? ELSE expires_at END",
                [$key, $expiry, $now, $now, $expiry],
            );
        } else {
            query(
                "INSERT INTO login_limits(bucket,attempts,expires_at) VALUES (?,1,?) ON DUPLICATE KEY UPDATE attempts=IF(expires_at<=?,1,attempts+1), expires_at=IF(expires_at<=?,?,expires_at)",
                [$key, $expiry, $now, $now, $expiry],
            );
        }
        $limit = query("SELECT * FROM login_limits WHERE bucket=?", [
            $key,
        ])->fetch();
        return $limit;
    }
    public static function clearAccount(string $email, int $now): void
    {
        query("DELETE FROM login_limits WHERE bucket=?", [
            hash("sha256", "account:" . $email),
        ]);
        query("DELETE FROM login_limits WHERE expires_at<?", [$now]);
    }
}
