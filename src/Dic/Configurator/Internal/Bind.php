<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

use Thesis\Dic\Exception\BindingTypeNotSupported;
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
     * @throws BindingTypeNotSupported
     */
    final public function bind(Type $type, string|\Stringable|\UnitEnum $qualifier = ''): static
    {
        $this->ensureConfigurable();

        $this->autowiring->bind($this, $type, $qualifier);

        return $this;
    }
}
