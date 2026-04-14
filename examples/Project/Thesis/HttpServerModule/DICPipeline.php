<?php

declare(strict_types=1);

namespace Project\Thesis\HttpServerModule;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Thesis\DIC\Scope;
use Typhoon\Type;

final class DICPipeline implements Pipeline
{
    /**
     * @var non-negative-int
     */
    private int $offset = 0;

    /**
     * @param Scope<callable(Request): Response> $scopedHandler
     * @param list<Middleware> $middleware
     */
    public function __construct(
        private Scope $scopedHandler,
        private readonly array $middleware = [],
    ) {}

    public function handleRequest(Request $request): Response
    {
        $middleware = $this->middleware[$this->offset] ?? null;

        if ($middleware === null) {
            return ($this->scopedHandler->with($request)->obtain())($request);
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
