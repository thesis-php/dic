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
final readonly class LazyObjectFactory implements Factory
{
    /**
     * @param \ReflectionClass<T> $class
     * @param Factory<T> $factory
     */
    public function __construct(
        private \ReflectionClass $class,
        private Factory $factory,
    ) {}

    public function dependencies(): iterable
    {
        return $this->factory->dependencies();
    }

    public function create(Container $container): mixed
    {
        return $this->class->newLazyProxy(fn() => $this->factory->create($container));
    }
}
