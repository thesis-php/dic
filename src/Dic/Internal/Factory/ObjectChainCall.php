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
final readonly class ObjectChainCall implements Factory
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

    public function create(Container $container): mixed
    {
        /** @phpstan-ignore method.dynamicName, return.type */
        return $this->factory->create($container)
            ->{$this->method}(...$this->arguments->create($container));
    }
}
