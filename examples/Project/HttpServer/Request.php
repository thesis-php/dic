<?php

declare(strict_types=1);

namespace Project\HttpServer;

final readonly class Request
{
    public function __construct(
        public string $path,
    ) {}
}
