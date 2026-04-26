<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Configurator;
use Thesis\Dic\Lifetime;

/**
 * @api
 *
 * @template T
 * @extends Configurator<T>
 */
abstract class LifetimeConfigurator extends Configurator
{
    private bool $lifetimeLocked = false;

    public private(set) Lifetime $lifetime = Lifetime::Singleton {
        get {
            $this->lifetimeLocked = true;

            return $this->lifetime;
        }
        set {
            $this->ensureConfigurable();

            if ($this->lifetimeLocked) {
                throw new \LogicException("Cannot change lifetime of {$this} after the lifetime has been accessed");
            }

            $this->lifetime = $value;
        }
    }

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
}
