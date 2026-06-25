<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory;

/**
 * @internal
 *
 * @template-covariant T
 * @implements Factory<T>
 */
final readonly class CallableFactory implements Factory
{
    /**
     * @param callable(): T $callable
     * @param Factory<array<mixed>> $arguments
     */
    public function __construct(
        private mixed $callable,
        private Factory $arguments,
    ) {}

    public function dependencies(): iterable
    {
        return $this->arguments->dependencies();
    }

    public function create(Container $container): mixed
    {
        return ($this->callable)(...$this->arguments->create($container));
    }
}
