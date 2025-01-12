<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Location;

/**
 * @api
 * @template T of object
 * @implements Definition<T>
 */
final readonly class Constructor implements Definition
{
    public Location $location;

    /**
     * @param class-string<T> $class
     * @param array<mixed> $arguments
     * @param list<Tag<contravariant T>> $tags
     */
    public function __construct(
        public string $class,
        public array $arguments = [],
        public array $tags = [],
        public bool $autowire = true,
        ?Location $location = null,
    ) {
        $this->location = $location ?? Location::caller();
    }
}

/**
 * @api
 * @template T of object
 * @param class-string<T> $class
 * @param array<mixed> $arguments
 * @param list<Tag<contravariant T>> $tags
 * @return Constructor<T>
 */
function constructor(string $class, array $arguments = [], array $tags = [], bool $autowire = true): Constructor
{
    return new Constructor(
        class: $class,
        arguments: $arguments,
        tags: $tags,
        autowire: $autowire,
        location: Location::caller(),
    );
}
