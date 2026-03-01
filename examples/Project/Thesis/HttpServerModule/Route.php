<?php

declare(strict_types=1);

namespace Project\Thesis\HttpServerModule;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Thesis\DIC\Tag;

/**
 * @implements Tag<callable(Request): Response>
 */
#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class Route implements Tag
{
    /**
     * @var non-empty-list<non-empty-string>
     */
    public array $methods;

    /**
     * @param non-empty-string|non-empty-list<non-empty-string> $methods
     * @param non-empty-string $path
     */
    public function __construct(
        string|array $methods,
        public string $path,
    ) {
        $this->methods = \is_array($methods) ? $methods : [$methods];
    }
}
