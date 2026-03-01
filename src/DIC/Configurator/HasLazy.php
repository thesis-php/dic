<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

/**
 * @api
 */
trait HasLazy
{
    private bool $lazy = false;

    final public function lazy(): static
    {
        $this->lazy = true;

        return $this;
    }
}
