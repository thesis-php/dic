<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

/**
 * @internal
 * @template-covariant TValue
 */
final readonly class LazyValue
{
    /**
     * @param \Closure(mixed...): TValue $function
     * @param array<non-empty-string, mixed> $arguments
     */
    public function __construct(
        public \Closure $function,
        public array $arguments = [],
    ) {}
}
