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
final readonly class ObjectCall implements Factory
{
    /**
     * @param Factory<T> $factory
     * @param non-empty-string $method
     * @param Factory<list<mixed>> $arguments
     */
    public function __construct(
        private Factory $factory,
        private string $method,
        private Factory $arguments,
    ) {}

    public function dependencies(): iterable
    {
        yield from $this->factory->dependencies();
        yield from $this->arguments->dependencies();
    }

    public function create(Container $container): mixed
    {
        $object = $this->factory->create($container);

        /** @phpstan-ignore method.dynamicName */
        $object->{$this->method}(...$this->arguments->create($container));

        return $object;
    }
}
