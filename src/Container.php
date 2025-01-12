<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Exports;
use Thesis\DI\Internal\LazyValue;
use Thesis\DI\Internal\Location;
use Thesis\DI\Internal\Values;

/**
 * @api
 * @template TReqs of Module
 */
final class Container
{
    /**
     * @var array<non-empty-string, mixed>
     */
    private array $resolvedValues = [];

    /**
     * @internal Thesis\DI
     */
    public function __construct(
        private readonly Exports $exports,
        private readonly Values $values,
    ) {}

    /**
     * @param ModuleId<*, *> $id
     */
    public function has(ModuleId $id): bool
    {
        return $this->exports->has($id);
    }

    /**
     * @template T
     * @param ModuleId<covariant TReqs, T> $id
     * @return T
     * @throws ValueIsNotAvailable
     */
    public function get(ModuleId $id): mixed
    {
        if ($this->exports->has($id)) {
            return $this->doGet($id);
        }

        $location = Location::caller();

        if (!$this->values->hasModule($id->module)) {
            throw ValueIsNotAvailable::moduleIsNotRequired($id->module, $location);
        }

        if ($this->values->has($id)) {
            throw ValueIsNotAvailable::idIsNotExported($id, $location);
        }

        throw ValueIsNotAvailable::idIsNotDefined($id, $location);
    }

    /**
     * @template T
     * @param ModuleId<*, T> $id
     * @return T
     */
    private function doGet(ModuleId $id): mixed
    {
        $key = $id->toString();

        if (\array_key_exists($key, $this->resolvedValues)) {
            /** @var T */
            return $this->resolvedValues[$key];
        }

        /** @var T */
        $value = $this->resolve($this->values->get($id));
        $this->resolvedValues[$key] = $value;
        $this->values->remove($id);

        return $value;
    }

    private function resolve(mixed $value): mixed
    {
        if ($value instanceof LazyValue) {
            return ($value->function)(...array_map($this->resolve(...), $value->arguments));
        }

        if ($value instanceof ModuleId) {
            return $this->doGet($value);
        }

        \assert(!$value instanceof Definition, \sprintf(
            'At this point container value must not be an instance of %s',
            get_debug_type($value),
        ));

        if (\is_array($value)) {
            return array_map($this->resolve(...), $value);
        }

        return $value;
    }
}
