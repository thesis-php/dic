<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowirableFunction\Argument;

use Thesis\DIC\Internal\AutowirableFunction\Argument;
use Thesis\DIC\Internal\AutowirableFunction\Parameter;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Mapping\DoNotAutowire;
use Thesis\DIC\Ref;
use Thesis\DIC\Value;

/**
 * @internal
 */
final readonly class Defined extends Argument
{
    private static function processValue(mixed $value): mixed
    {
        if ($value instanceof DoNotAutowire) {
            throw new \LogicException('`doNotAutowire` is only allowed at root level');
        }

        if ($value instanceof Value) {
            return $value->value;
        }

        if (\is_array($value)) {
            return array_map(self::processValue(...), $value);
        }

        return $value;
    }

    private static function hasRefs(mixed $value): bool
    {
        if ($value instanceof Ref) {
            return true;
        }

        if (\is_array($value)) {
            return array_any($value, self::hasRefs(...));
        }

        return false;
    }

    private mixed $value;

    private bool $hasRefs;

    protected function __construct(
        Parameter $parameter,
        mixed $value,
    ) {
        $this->value = self::processValue($value);
        $this->hasRefs = self::hasRefs($this->value);

        parent::__construct($parameter);
    }

    public function autowire(Autowiring $autowiring): static
    {
        return $this;
    }

    public function check(): void {}

    public function resolve(Container $container): mixed
    {
        if ($this->hasRefs) {
            return $container->resolve($this->value);
        }

        return $this->value;
    }
}
