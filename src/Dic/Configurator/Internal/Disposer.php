<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

/**
 * @internal
 *
 * @template T
 * @require-extends Ref<T>
 */
trait Disposer
{
    /**
     * @param callable(T, ?\Throwable): void $disposer
     */
    final public function disposer(callable $disposer): static
    {
        $this->ensureConfigurable();

        $this->containerBuilder->addDisposer($this, $disposer);

        return $this;
    }
}
