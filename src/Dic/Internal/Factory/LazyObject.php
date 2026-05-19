<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\ClassReflection;
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
     * @param ClassReflection<T> $reflection
     * @param Factory<T> $factory
     */
    public function __construct(
        private ClassReflection $reflection,
        private Factory $factory,
    ) {}

    public function dependencies(): iterable
    {
        return $this->factory->dependencies();
    }

    public function create(Container $container): mixed
    {
        return $this->reflection->newLazyProxy(fn() => $this->factory->create($container));
    }
}
