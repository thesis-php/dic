<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory;

/**
 * @internal
 *
 * @template-covariant T of object
 * @implements Factory<T>
 */
final readonly class LazyObject implements Factory
{
    /**
     * @param \ReflectionClass<T> $reflection
     * @param Factory<T> $factory
     */
    public function __construct(
        private \ReflectionClass $reflection,
        private Factory $factory,
    ) {}

    public function create(Container $container): mixed
    {
        return $this->reflection->newLazyProxy(fn() => $this->factory->create($container));
    }
}
