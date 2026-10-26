<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\Admin;
final class SessionService
{
    public static function start(): void
    {
        @mkdir(dirname(__DIR__, 2) . "/var/sessions", 0700, true);
        session_save_path(dirname(__DIR__, 2) . "/var/sessions");
        session_name("arrchit_admin");
        session_set_cookie_params([
            "lifetime" => 0,
            "path" => "/",
            "secure" => getenv("APP_ENV") === "production",
            "httponly" => true,
            "samesite" => "Strict",
        ]);
        ini_set("session.use_strict_mode", "1");
        session_start();
        if (
            isset($_SESSION["admin_id"]) &&
            time() - ($_SESSION["last_active"] ?? 0) > 28800
        ) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        if (isset($_SESSION["admin_id"])) {
            $_SESSION["last_active"] = time();
        }
        if (!isset($_SESSION["csrf"])) {
            $_SESSION["csrf"] = bin2hex(random_bytes(32));
        }
    }
    public static function requireAdmin(): array
    {
        if (!isset($_SESSION["admin_id"])) {
            fail("Please sign in.", 401);
        }
        $a = Admin::find((int) $_SESSION["admin_id"]);
        if (!$a) {
            fail("Please sign in.", 401);
        }
        return $a;
    }
    public static function verifyCsrf(): void
    {
        if (
            !hash_equals($_SESSION["csrf"], $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "")
        ) {
            fail("Session expired. Refresh and try again.", 403);
        }
    }
    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), "", [
            "expires" => time() - 3600,
            "path" => "/",
            "secure" => getenv("APP_ENV") === "production",
            "httponly" => true,
            "samesite" => "Strict",
        ]);
    }
}
