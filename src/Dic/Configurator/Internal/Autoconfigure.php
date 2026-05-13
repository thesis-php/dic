<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

/**
 * @internal
 *
 * @require-extends Ref<*>
 */
trait Autoconfigure
{
    private bool $autoconfigure = true;

    final public function autoconfigure(bool $autoconfigure = true): static
    {
        $this->ensureConfigurable();

        $this->autoconfigure = $autoconfigure;

        return $this;
    }

    final public function doNotAutoconfigure(bool $doNotAutoconfigure = true): static
    {
        $this->ensureConfigurable();

        $this->autoconfigure = !$doNotAutoconfigure;

        return $this;
    }
}
