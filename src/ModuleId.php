<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Location;

/**
 * @api
 * @template TModule of Module
 * @template T
 * @implements Definition<T>
 */
final readonly class ModuleId implements Definition
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

    public function equals(mixed $value): bool
    {
        return $value instanceof self
            && $this->module === $value->module
            && $this->id->equals($value->id);
    }
}

/**
 * @api
 * @template TModule of Module
 * @template T
 * @param class-string<TModule> $module
 * @param Id<T> $id
 * @return ModuleId<TModule, T>
 */
function moduleId(string $module, Id $id): ModuleId
{
    return new ModuleId($module, $id);
}
