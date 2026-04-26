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
final readonly class Closure implements Factory
{
    /**
     * @param \Closure(Container): T $factory
     */
    public function __construct(
        private \Closure $factory,
    ) {}

    public function create(Container $container): mixed
    {
        return ($this->factory)($container);
    }
}
