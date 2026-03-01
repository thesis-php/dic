<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Lifetime;
use const Thesis\DIC\scoped;
use const Thesis\DIC\singleton;
use const Thesis\DIC\transient;

/**
 * @api
 */
trait HasLifetime
{
    private Lifetime $lifetime = singleton;

    final public function singleton(): static
    {
        $this->lifetime = singleton;

        return $this;
    }

    final public function scoped(): static
    {
        $this->lifetime = scoped;

        return $this;
    }

    final public function transient(): static
    {
        $this->lifetime = transient;

        return $this;
    }
}
