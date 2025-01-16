<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Location;

/**
 * @api
 * @template-covariant TModule of Module
 * @template T
 * @implements Recipe<T>
 */
final readonly class ModuleId implements Recipe
{
    public Location $location;

    /**
     * @param class-string<TModule> $module
     * @param Id<T> $id
     */
    public function __construct(
        public string $module,
        public Id $id,
        ?Location $location = null,
    ) {
        $this->location = $location ?? Location::caller();
    }

    public function equals(mixed $value): bool
    {
        return $value instanceof self
            && $this->module === $value->module
            && $this->id->equals($value->id);
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->module . '@' . $this->id->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
