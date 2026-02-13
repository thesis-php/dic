<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Container;

use Thesis\DIC\Internal\Container;

/**
 * A sentinel callable value for {@see Singletons}.
 *
 * @internal
 *
 * @template-covariant T
 */
final readonly class Factory
{
    /**
     * @param callable(Container): T $factory
     */
    public function __construct(
        private mixed $factory,
    ) {}

    /**
     * @return T
     */
    public function __invoke(Container $container): mixed
    {
        return ($this->factory)($container);
    }
}
