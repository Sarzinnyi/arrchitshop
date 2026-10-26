<?php
declare(strict_types=1);
namespace App\Core;
final class HttpException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}
