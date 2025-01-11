<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Location;

/**
 * @api
 * @template-covariant T of object
 * @implements Definition<T>
 */
final readonly class Constructor implements Definition
{
    public Location $location;

    /**
     * @param class-string<T> $class
     * @param array<mixed> $arguments
     */
    public function __construct(
        public string $class,
        public array $arguments = [],
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
 * @return Constructor<T>
 */
function constructor(string $class, array $arguments = [], bool $autowire = true): Constructor
{
    return new Constructor(
        class: $class,
        arguments: $arguments,
        autowire: $autowire,
        location: Location::caller(),
    );
}
