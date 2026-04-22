<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Lifetime;

/**
 * @api
 */
trait HasLifetime
{
    private Lifetime $lifetime = Lifetime::Singleton;

    final public function singleton(): static
    {
        $this->lifetime = Lifetime::Singleton;

        return $this;
    }

    final public function scoped(): static
    {
        $this->lifetime = Lifetime::Scoped;

        return $this;
    }

    final public function transient(): static
    {
        $this->lifetime = Lifetime::Transient;

        return $this;
    }
}
