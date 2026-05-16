<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

use Thesis\Dic\Internal;
use Thesis\Dic\Internal\Lifetime as Enum;

/**
 * @internal
 *
 * @require-extends Ref<*>
 */
trait Lifetime
{
    final public function scoped(bool $scoped = true): static
    {
        $this->ensureConfigurable();

        $this->lifetime = $scoped ? Enum::Scoped : Enum::Singleton;

        return $this;
    }

    final public function singleton(bool $singleton = true): static
    {
        $this->ensureConfigurable();

        $this->lifetime = $singleton ? Enum::Singleton : Enum::Scoped;

        return $this;
    }
}
