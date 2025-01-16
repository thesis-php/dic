<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\FunctionRecipe;

/**
 * @internal
 * @template-covariant TValue of object
 * @implements FunctionRecipe<TValue>
 */
final readonly class Construct implements FunctionRecipe
{
    /**
     * @param class-string<TValue> $class
     * @param array<mixed> $args
     */
    public function __construct(
        public string $class,
        public array $args = [],
        public bool $autowire = true,
    ) {}

    public function doNotAutowire(): static
    {
        return new self(
            class: $this->class,
            args: $this->args,
            autowire: false,
        );
    }

    public function args(mixed ...$args): static
    {
        return new self(
            class: $this->class,
            args: $args,
            autowire: $this->autowire,
        );
    }
}
