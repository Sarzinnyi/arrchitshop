<?php
declare(strict_types=1);
function respond(array $body, int $status = 200): never
{
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode($body, JSON_THROW_ON_ERROR);
    exit();
}
function fail(string $message, int $status = 422): never
{
    throw new App\Core\HttpException($message, $status);
}
function text(mixed $value, int $max, string $field): string
{
    if (
        !is_string($value) ||
        trim($value) === "" ||
        mb_strlen(trim($value)) > $max
    ) {
        fail("Invalid $field.");
    }
    return trim($value);
}
