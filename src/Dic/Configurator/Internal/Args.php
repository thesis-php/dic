<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

use function Thesis\Dic\autowire;
use const Thesis\Dic\autowire;
use const Thesis\Dic\doNotAutowire;

/**
 * @internal
 *
 * @require-extends Ref<*>
 */
trait Args
{
    final public function autowire(false|string|\Stringable|\UnitEnum $qualifier = ''): static
    {
        $this->ensureConfigurable();

        $this->arguments->fill(match ($qualifier) {
            false => doNotAutowire,
            '' => autowire,
            default => autowire($qualifier),
        });

        return $this;
    }

    final public function doNotAutowire(bool $doNotAutowire = true): static
    {
        $this->ensureConfigurable();

        $this->arguments->fill($doNotAutowire ? doNotAutowire : autowire);

        return $this;
    }

    final public function arg(int|string $param, mixed $arg): static
    {
        $this->ensureConfigurable();

        $this->arguments->set($param, $arg);

        return $this;
    }

    /**
     * @param array<mixed> $args
     */
    final public function args(array $args): static
    {
        $this->ensureConfigurable();

        foreach ($args as $param => $arg) {
            $this->arguments->set($param, $arg);
        }

        return $this;
    }
}
