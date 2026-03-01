<?php

declare(strict_types=1);

namespace Project\Thesis\HttpServerModule;

use Amp\Http\Server\RequestHandler;

final readonly class Endpoint
{
    public function __construct(
        public Route $route,
        public RequestHandler $handler,
    ) {}
}
