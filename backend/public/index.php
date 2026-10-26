<?php
declare(strict_types=1);
require dirname(__DIR__) . "/bootstrap.php";

use App\Core\Router;
use App\Services\PhotoService;
use App\Services\SessionService;

try {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?: "/";
    PhotoService::serve($path);
    header("X-Content-Type-Options: nosniff");
    header("Cache-Control: no-store");
    SessionService::start();
    $routes = require dirname(__DIR__) . "/routes/api.php";
    new Router()->dispatch($_SERVER["REQUEST_METHOD"], $path, $routes);
} catch (App\Core\HttpException $error) {
    respond(["error" => $error->getMessage()], $error->status);
} catch (Throwable $error) {
    if (
        $error instanceof PDOException &&
        str_starts_with((string) $error->getCode(), "23")
    ) {
        respond(["error" => "That value already exists or is in use."], 409);
    }
    error_log($error->getMessage());
    respond(
        [
            "error" =>
                "The server could not complete the request. Check the server log and database setup.",
        ],
        500,
    );
}
