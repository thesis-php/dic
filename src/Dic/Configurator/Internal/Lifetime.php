<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

use Thesis\Dic;

/**
 * @internal
 *
 * @require-extends Ref<*>
 */
trait Lifetime
{
    public private(set) Dic\Lifetime $lifetime = Dic\Lifetime::Singleton;

    final public function scoped(bool $scoped = true): static
    {
        $this->ensureConfigurable();

        $this->lifetime = $scoped ? Dic\Lifetime::Scoped : Dic\Lifetime::Singleton;

        return $this;
    }

    final public function singleton(bool $singleton = true): static
    {
        $this->ensureConfigurable();

        $this->lifetime = $singleton ? Dic\Lifetime::Singleton : Dic\Lifetime::Scoped;

        return $this;
    }
}
