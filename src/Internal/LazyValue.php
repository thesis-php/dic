<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

/**
 * @internal
 * @psalm-internal Thesis\DI
 * @template T
 */
final readonly class LazyValue
{
    /**
     * @param \Closure(never, never, never, never, never): T $function
     * @param array<non-empty-string, mixed> $arguments
     */
    public function __construct(
        public \Closure $function,
        public array $arguments = [],
    ) {}
}
