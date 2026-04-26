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
final readonly class Constructor implements Factory
{
    /**
     * @param class-string<T> $class
     * @param Factory<list<mixed>> $arguments
     */
    public function __construct(
        private string $class,
        private Factory $arguments,
    ) {}

    public function create(Container $container): mixed
    {
        return new ($this->class)(...$this->arguments->create($container));
    }
}
