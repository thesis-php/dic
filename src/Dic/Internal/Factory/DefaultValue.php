<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory;

/**
 * @internal
 */
final readonly class DefaultValue implements Factory
{
    public function __construct(
        private \ReflectionParameter $parameter,
    ) {}

    public function dependencies(): iterable
    {
        return [];
    }

    public function create(Container $container): mixed
    {
        return $this->parameter->getDefaultValue();
    }
}
