<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\InvalidConfig;
use Thesis\DI\Internal\Location;

/**
 * @api
 * @template T
 * @implements Definition<T>
 */
final readonly class Id implements Definition
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

    public function equals(mixed $value): bool
    {
        return $value instanceof self
            && $this->id === $value->id;
    }
}

/**
 * @api
 * @template T of object
 * @param class-string<T> $class
 * @return Id<T>
 */
function objectId(string $class): Id
{
    /** @var Id<T> */
    return new Id($class, Location::caller());
}
