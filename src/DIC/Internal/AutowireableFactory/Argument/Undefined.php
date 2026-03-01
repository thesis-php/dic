<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory\Argument;

use Thesis\DIC\Internal\AutowireableFactory\Argument;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;

/**
 * @internal
 */
final readonly class Undefined extends Argument
{
    public function autowire(Autowiring $autowiring): static
    {
        return $this;
    }

    public function ensureResolvable(): void
    {
        if (!$this->parameter->hasDefaultValue) {
            throw new \LogicException(\sprintf(
                'Parameter `%s` is not autowired and does not have a default value',
                $this->parameter->formattedName,
            ));
        }
    }

    public function resolve(Container $container): mixed
    {
        $this->ensureResolvable();

        return $this->parameter->defaultValue;
    }
}
