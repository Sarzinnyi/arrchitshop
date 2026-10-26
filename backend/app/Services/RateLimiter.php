<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\LoginLimit;
final class RateLimiter
{
    public static function checkLogin(string $email, string $ip): void
    {
        $now = time();
        foreach (
            [["ip:" . $ip, 30], ["account:" . $email, 5]]
            as [$key, $max]
        ) {
            $key = hash("sha256", $key);
            $expiry = $now + 900;
            $limit = LoginLimit::hit($key, $now, $expiry);
            if ((int) $limit["attempts"] > $max) {
                header("Retry-After: " . max(1, $limit["expires_at"] - $now));
                fail(
                    "Too many sign-in attempts. Try again in 15 minutes.",
                    429,
                );
            }
        }
    }
}
