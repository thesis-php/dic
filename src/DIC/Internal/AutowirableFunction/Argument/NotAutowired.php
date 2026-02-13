<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowirableFunction\Argument;

use Thesis\DIC\Internal\AutowirableFunction\Argument;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;

/**
 * @internal
 */
final readonly class NotAutowired extends Argument
{
    public function autowire(Autowiring $autowiring): static
    {
        return $this;
    }

    public function check(): void
    {
        if (!$this->parameter->hasDefault) {
            throw new \LogicException(\sprintf(
                'Parameter `%s` is not autowired and does not have a default value',
                $this->parameter->formattedName,
            ));
        }
    }

    public function resolve(Container $container): mixed
    {
        $this->check();

        return $this->parameter->default;
    }
}
