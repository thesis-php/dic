<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

/**
 * @internal
 *
 * @require-extends Ref<*>
 */
trait Lazy
{
    private bool $lazy = false;

    final public function lazy(bool $lazy = true): static
    {
        $this->ensureConfigurable();

        $this->lazy = $lazy;

        return $this;
    }

    final public function eager(bool $eager = true): static
    {
        $this->ensureConfigurable();

        $this->lazy = !$eager;

        return $this;
    }
}
