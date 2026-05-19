<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

use Thesis\Dic\Exception\UnsupportedType;
use Typhoon\Type;

/**
 * @internal
 *
 * @template T
 * @require-extends Ref<T>
 */
trait Bind
{
    /**
     * @param Type<contravariant T> $type
     * @throws UnsupportedType
     */
    final public function bind(Type $type, string|\Stringable|\UnitEnum $qualifier = ''): static
    {
        $this->ensureConfigurable();

        $this->autowiring->bind($this, $type, $qualifier);

        return $this;
    }
}
