<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

/**
 * @api
 *
 * @template-covariant T
 * @extends Autoconfig<T>
 *
 * @phpstan-sealed FactoryConfig
 */
interface LifetimeConfig extends Autoconfig
{
    public function singleton(): static;

    public function canBeScoped(): static;

    public function scoped(): static;
}
