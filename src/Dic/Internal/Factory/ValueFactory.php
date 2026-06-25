<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Container\Scope;
use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Ref;

/**
 * @internal
 *
 * @template-covariant T = mixed
 * @implements Factory<T>
 */
final readonly class ValueFactory implements Factory
{
    public static function from(mixed $value): self
    {
        return new self(
            value: $value,
            hasDependencies: self::findDependencies($value)->valid(),
        );
    }

    /**
     * @param T $value
     */
    private function __construct(
        public mixed $value,
        private bool $hasDependencies,
    ) {}

    public function dependencies(): iterable
    {
        if ($this->hasDependencies) {
            yield from self::findDependencies($this->value);
        }
    }

    /**
     * @return \Generator<Dependency>
     */
    private static function findDependencies(mixed $value): \Generator
    {
        if ($value instanceof Ref) {
            yield Dependency::of($value);

            return;
        }

        if (\is_array($value)) {
            foreach ($value as $key => $item) {
                foreach (self::findDependencies($item) as $dependency) {
                    yield $dependency->key($key);
                }
            }
        }
    }

    public function create(Container $container): mixed
    {
        if ($this->hasDependencies) {
            return self::createRecursively($this->value, $container);
        }

        return $this->value;
    }

    private static function createRecursively(mixed $value, Container|Scope $container): mixed
    {
        if ($value instanceof Ref) {
            return $container->get($value);
        }

        if (\is_array($value)) {
            return array_map(
                static fn(mixed $item) => self::createRecursively($item, $container),
                $value,
            );
        }

        return $value;
    }
}
