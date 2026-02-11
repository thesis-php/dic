<?php

declare(strict_types=1);

namespace Project\HttpServer;

use Thesis\DIC\Tag;

/**
 * @phpstan-import-type Action from Server
 * @implements Tag<Action>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
final readonly class Route implements Tag
{
    public function __construct(
        public string $path,
    ) {}
}
