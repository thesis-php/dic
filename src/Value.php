<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Location;

/**
 * @api
 * @template-covariant T
 */
final readonly class Value
{
    public Location $location;

    /**
     * @param T $value
     */
    public function __construct(
        public mixed $value,
        ?Location $location = null,
    ) {
        $this->location = $location ?? Location::caller();
    }
}

/**
 * @api
 * @template T
 * @param T $value
 * @return Value<T>
 */
function value(mixed $value): Value
{
    return new Value($value, location: Location::caller());
}
