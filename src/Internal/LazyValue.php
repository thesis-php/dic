<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

/**
 * @internal
 * @template-covariant T
 */
final readonly class LazyValue
{
    /**
     * @param \Closure(mixed...): T $function
     * @param array<non-empty-string, mixed> $arguments
     */
    public function __construct(
        public \Closure $function,
        public array $arguments = [],
    ) {}
}
