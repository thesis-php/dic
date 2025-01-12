<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Location;

/**
 * @api
 * @template-covariant T
 */
final readonly class Factory
{
    /**
     * @var \Closure(never, never, never, never, never): T
     */
    public \Closure $factory;

    public Location $location;

    /**
     * @param callable(never, never, never, never, never): T $factory
     * @param array<mixed> $arguments
     */
    public function __construct(
        callable $factory,
        public array $arguments = [],
        public bool $autowire = true,
        ?Location $location = null,
    ) {
        $this->factory = $factory(...);
        $this->location = $location ?? Location::caller();
    }
}

/**
 * @api
 * @template T
 * @param callable(never, never, never, never, never): T $factory
 * @param array<mixed> $arguments
 * @return Factory<T>
 */
function factory(callable $factory, array $arguments = [], bool $autowire = true): Factory
{
    return new Factory(
        factory: $factory,
        arguments: $arguments,
        autowire: $autowire,
        location: Location::caller(),
    );
}
