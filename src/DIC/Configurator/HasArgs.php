<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Internal\AutowireableFactory\Arguments;

/**
 * @api
 */
trait HasArgs
{
    private Arguments $arguments;

    /**
     * @param array<non-negative-int|non-empty-string, mixed> $args
     */
    final public function args(array $args): static
    {
        $this->arguments = $this->arguments->replace($args);

        return $this;
    }

    /**
     * @param non-negative-int|non-empty-string $param
     */
    final public function arg(int|string $param, mixed $arg): static
    {
        $this->arguments = $this->arguments->merge([$param => $arg]);

        return $this;
    }
}
