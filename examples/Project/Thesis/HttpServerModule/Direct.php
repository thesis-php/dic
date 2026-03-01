<?php

declare(strict_types=1);

namespace Project\Thesis\HttpServerModule;

final readonly class Direct
{
    public function __construct(
        public int $connectionLimit = 1_000,
        public int $connectionLimitPerIp = 10,
    ) {}
}
