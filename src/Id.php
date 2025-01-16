<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\InvalidConfig;
use Thesis\DI\Internal\Location;

/**
 * @api
 * @template T
 * @implements Recipe<T>
 */
final readonly class Id implements Recipe
{
    public Location $location;

    /**
     * @param non-empty-string $id
     */
    public function __construct(
        private string $id,
        ?Location $location = null,
    ) {
        $this->location = $location ?? Location::caller();

        if (!preg_match('/^[a-zA-Z0-9\x80-\xff.\-\\\_]*$/', $id)) {
            throw InvalidConfig::invalidId($id, $this->location);
        }
    }

    public function equals(mixed $value): bool
    {
        return $value instanceof self
            && $this->id === $value->id;
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->id;
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->id;
    }
}
