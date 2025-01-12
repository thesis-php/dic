<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Location;

/**
 * @api
 * @template T
 * @implements Definition<T>
 */
final readonly class Factory implements Definition
{
    /**
     * @var \Closure(never, never, never, never, never): T
     */
    public \Closure $factory;

    public Location $location;

    /**
     * @param callable(never, never, never, never, never): T $factory
     * @param array<mixed> $arguments
     * @param list<Tag<contravariant T>> $tags
     */
    public function __construct(
        callable $factory,
        public array $arguments = [],
        public array $tags = [],
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
 * @param list<Tag<contravariant T>> $tags
 * @return Factory<T>
 */
function factory(callable $factory, array $arguments = [], array $tags = [], bool $autowire = true): Factory
{
    return new Factory(
        factory: $factory,
        arguments: $arguments,
        tags: $tags,
        autowire: $autowire,
        location: Location::caller(),
    );
}
