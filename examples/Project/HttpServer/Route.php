<?php

declare(strict_types=1);

namespace Project\HttpServer;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Thesis\DIC\Tag;

/**
 * @implements Tag<callable(Request): Response>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class Route implements Tag
{
    /**
     * @param non-empty-string $method
     * @param non-empty-string $path
     */
    public function __construct(
        public string $method,
        public string $path,
    ) {}
}
