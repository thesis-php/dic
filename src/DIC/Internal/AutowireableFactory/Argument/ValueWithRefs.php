<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory\Argument;

use Thesis\DIC\Internal\AutowireableFactory\Argument;
use Thesis\DIC\Internal\AutowireableFactory\Parameter;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;

/**
 * @internal
 */
final readonly class ValueWithRefs extends Argument
{
    protected function __construct(
        Parameter $parameter,
        private mixed $value,
    ) {
        parent::__construct($parameter);
    }

    public function autowire(Autowiring $autowiring): static
    {
        return $this;
    }

    public function ensureResolvable(): void {}

    public function resolve(Container $container): mixed
    {
        return $container->resolve($this->value);
    }
}
