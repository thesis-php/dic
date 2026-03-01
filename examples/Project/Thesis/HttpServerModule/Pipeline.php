<?php

declare(strict_types=1);

namespace Project\Thesis\HttpServerModule;

use Amp\Http\Server\RequestHandler;
use Typhoon\Type;

interface Pipeline extends RequestHandler
{
    /**
     * @template T
     * @param T $value
     * @param ?Type<contravariant T> $type
     */
    public function with(mixed $value, ?Type $type = null, string|\Stringable|\UnitEnum $qualifier = ''): self;
}
