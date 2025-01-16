<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\FunctionRecipe;

/**
 * @internal
 * @template-covariant TValue
 * @implements FunctionRecipe<TValue>
 */
final readonly class Call implements FunctionRecipe
{
    /**
     * @param \Closure(never, never, never, never, never): TValue $function
     * @param array<mixed> $args
     */
    public function __construct(
        public \Closure $function,
        public array $args = [],
        public bool $autowire = true,
    ) {}

    public function doNotAutowire(): static
    {
        return new self(
            function: $this->function,
            args: $this->args,
            autowire: false,
        );
    }

    public function args(mixed ...$args): static
    {
        return new self(
            function: $this->function,
            args: $args,
            autowire: $this->autowire,
        );
    }
}
