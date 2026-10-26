<?php
declare(strict_types=1);
namespace App\Core;
use App\Services\SessionService;
final class Router
{
    public function dispatch(string $method, string $path, array $routes): never
    {
        if (!in_array($method, ["GET", "HEAD"], true)) {
            SessionService::verifyCsrf();
        }
        foreach (
            $routes
            as [$verb, $pattern, $controller, $action, $protected]
        ) {
            if ($method !== $verb || !preg_match($pattern, $path, $matches)) {
                continue;
            }
            if ($protected) {
                SessionService::requireAdmin();
            }
            $parameters = array_map("intval", array_slice($matches, 1));
            new $controller()->$action(...$parameters);
        }
        SessionService::requireAdmin();
        fail("Endpoint not found.", 404);
    }
}
