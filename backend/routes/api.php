<?php
declare(strict_types=1);
use App\Controllers\AuthController;
use App\Controllers\OptionController;
use App\Controllers\ProductController;
// Method, path pattern, controller, action, requires authentication.
return [
    ["GET", '~^/api/session$~', AuthController::class, "session", false],
    ["POST", '~^/api/login$~', AuthController::class, "login", false],
    ["POST", '~^/api/logout$~', AuthController::class, "logout", true],
    ["GET", '~^/api/options$~', OptionController::class, "index", true],
    ["POST", '~^/api/sizes$~', OptionController::class, "storeSize", true],
    ["POST", '~^/api/colors$~', OptionController::class, "storeColor", true],
    ["GET", '~^/api/products$~', ProductController::class, "index", true],
    ["POST", '~^/api/products$~', ProductController::class, "store", true],
    [
        "DELETE",
        '~^/api/products/(\d+)$~',
        ProductController::class,
        "destroy",
        true,
    ],
];
