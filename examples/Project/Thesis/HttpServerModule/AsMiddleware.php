<?php

declare(strict_types=1);

namespace Project\Thesis\HttpServerModule;

use Thesis\DIC\Tag;

/**
 * @implements Tag<Middleware>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsMiddleware implements Tag
{
    public function __construct(
        public float $priority = 0,
    ) {}
}
