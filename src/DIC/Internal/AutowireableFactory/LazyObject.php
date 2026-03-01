<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

use Thesis\DIC\Internal\AutowireableFactory;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;

/**
 * @internal
 *
 * @template-covariant T of object
 * @implements AutowireableFactory<T>
 */
final readonly class LazyObject implements AutowireableFactory
{
    /**
     * @param \ReflectionClass<T> $reflection
     * @param AutowireableFactory<T> $factory
     */
    public function __construct(
        private \ReflectionClass $reflection,
        private AutowireableFactory $factory,
    ) {}

    public function autowire(Autowiring $autowiring): AutowireableFactory
    {
        return new self(
            reflection: $this->reflection,
            factory: $this->factory->autowire($autowiring),
        );
    }

    public function __invoke(Container $container): mixed
    {
        return $this->reflection->newLazyProxy(fn() => ($this->factory)($container));
    }
}
