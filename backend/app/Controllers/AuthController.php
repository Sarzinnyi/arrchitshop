<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\Admin;
use App\Models\LoginLimit;
use App\Services\RateLimiter;
use App\Services\SessionService;
final class AuthController
{
    public function session(): never
    {
        $admin = isset($_SESSION["admin_id"])
            ? Admin::find((int) $_SESSION["admin_id"])
            : null;
        respond(["admin" => $admin, "csrf" => $_SESSION["csrf"]]);
    }
    public function login(): never
    {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $email = strtolower(
            trim(is_string($data["email"] ?? null) ? $data["email"] : ""),
        );
        $pass = $data["password"] ?? "";
        if (
            strlen($email) > 190 ||
            !is_string($pass) ||
            (strlen($pass) > 72 || str_contains($pass, "\0"))
        ) {
            fail("Invalid email or password.", 401);
        }
        RateLimiter::checkLogin($email, $_SERVER["REMOTE_ADDR"] ?? "local");
        $a = Admin::byEmail($email);
        // Always perform password verification, including for unknown accounts.
        $hash = $a
            ? $a["password_hash"]
            : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (!password_verify($pass, $hash) || !$a) {
            fail("Invalid email or password.", 401);
        }
        LoginLimit::clearAccount($email, time());
        session_regenerate_id(true);
        $_SESSION["admin_id"] = $a["id"];
        $_SESSION["last_active"] = time();
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
        respond([
            "admin" => [
                "id" => $a["id"],
                "name" => $a["name"],
                "email" => $a["email"],
            ],
            "csrf" => $_SESSION["csrf"],
        ]);
    }
    public function logout(): never
    {
        SessionService::logout();
        respond(["ok" => true]);
    }
}
