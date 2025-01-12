<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Exports;
use Thesis\DI\Internal\LazyValue;
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

    public function __construct(
        private readonly Exports $exports,
        private readonly Values $values,
    ) {}

    /**
     * @template T
     * @param ModuleId<covariant TReqs, T> $id
     * @return T
     */
    public function get(ModuleId $id): mixed
    {
        if (!$this->exports->has($id)) {
            throw new \RuntimeException('TODO');
        }

        return $this->doGet($id);
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
            return $value->function->__invoke(...array_map($this->resolve(...), $value->arguments));
        }

        if ($value instanceof ModuleId) {
            return $this->doGet($value);
        }

        \assert(!$value instanceof Definition, 'TODO');

        if (\is_array($value)) {
            return array_map($this->resolve(...), $value);
        }

        return $value;
    }
}
