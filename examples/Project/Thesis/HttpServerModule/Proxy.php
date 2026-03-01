<?php

declare(strict_types=1);

namespace Project\Thesis\HttpServerModule;

use Amp\Http\Server\Middleware\ForwardedHeaderType;

final readonly class Proxy
{
    /**
     * @param non-empty-list<non-empty-string> $trustedProxies
     */
    public function __construct(
        public ForwardedHeaderType $headerType,
        public array $trustedProxies,
    ) {}
}
