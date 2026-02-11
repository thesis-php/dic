<?php

declare(strict_types=1);

namespace Project\HttpServer;

final readonly class Response
{
    public function __construct(
        public int $status = 200,
        public string $body = '',
    ) {}
}
