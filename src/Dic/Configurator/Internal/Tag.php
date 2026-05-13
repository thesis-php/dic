<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

use Thesis\Dic;

/**
 * @internal
 *
 * @template T
 * @require-extends Ref<T>
 */
trait Tag
{
    /**
     * @param Dic\Tag<T> $tag
     */
    final public function tag(Dic\Tag $tag): static
    {
        $this->ensureConfigurable();

        $this->containerBuilder->tag($this, $tag);

        return $this;
    }
}
