<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Location;

/**
 * @api
 * @template T
 * @implements Definition<T>
 */
final readonly class Value implements Definition
{
    public Location $location;

    /**
     * @param T $value
     * @param list<Tag<contravariant T>> $tags
     */
    public function __construct(
        public mixed $value,
        public array $tags = [],
        ?Location $location = null,
    ) {
        $this->location = $location ?? Location::caller();
    }
}

/**
 * @api
 * @template T
 * @param T $value
 * @param list<Tag<contravariant T>> $tags
 * @return Value<T>
 */
function value(mixed $value, array $tags = []): Value
{
    return new Value($value, $tags, Location::caller());
}
