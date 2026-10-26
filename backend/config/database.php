<?php
declare(strict_types=1);
return [
    "dsn" =>
        getenv("DB_DSN") ?: "sqlite:" . dirname(__DIR__) . "/var/shop.sqlite",
    "user" => getenv("DB_USER") ?: null,
    "password" => getenv("DB_PASSWORD") ?: null,
];
