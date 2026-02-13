<?php

declare(strict_types=1);

namespace Project\HttpServer;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Thesis\DIC\Scoped;
use Typhoon\Type;

final class DICPipeline implements Pipeline
{
    /**
     * @var non-negative-int
     */
    private int $offset = 0;

    /**
     * @param Scoped<callable(Request): Response> $scopedHandler
     * @param list<Middleware> $middlewares
     */
    public function __construct(
        private Scoped $scopedHandler,
        private readonly array $middlewares,
    ) {}

    public function handleRequest(Request $request): Response
    {
        $middleware = $this->middlewares[$this->offset] ?? null;

        if ($middleware === null) {
            return ($this->scopedHandler->value)($request);
        }

        $pipeline = clone $this;
        ++$pipeline->offset;

        return $middleware->handleRequest($request, $pipeline);
    }

    public function with(mixed $value, ?Type $type = null, \UnitEnum|\Stringable|string $qualifier = ''): Pipeline
    {
        $pipeline = clone $this;

        $pipeline->scopedHandler = $this->scopedHandler->with($value, $type, $qualifier);

        return $pipeline;
    }
}
