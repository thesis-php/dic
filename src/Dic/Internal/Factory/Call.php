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
final readonly class Call implements Factory
{
    /**
     * @param \Closure(): T $function
     * @param Factory<list<mixed>> $arguments
     */
    public function __construct(
        private \Closure $function,
        private Factory $arguments,
    ) {}

    public function create(Container $container): mixed
    {
        return ($this->function)(...$this->arguments->create($container));
    }
}
