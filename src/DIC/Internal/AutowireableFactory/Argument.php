<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

use Thesis\DIC\Internal\AutowireableFactory\Argument\Undefined;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Mapping\DoNotAutowire;
use Thesis\DIC\Ref;

/**
 * @internal
 */
abstract readonly class Argument
{
    final public static function undefined(Parameter $parameter): self
    {
        if ($parameter->isAutowirable) {
            return new Argument\UndefinedAutowired($parameter);
        }

        return new Undefined($parameter);
    }

    final public static function value(Parameter $parameter, mixed $value): self
    {
        if ($value instanceof DoNotAutowire) {
            return new Undefined($parameter);
        }

        if (self::hasRefs($value)) {
            return new Argument\ValueWithRefs($parameter, $value);
        }

        return new Argument\Value($parameter, $value);
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

    protected function __construct(
        public Parameter $parameter,
    ) {}

    /**
     * @param array<non-negative-int|non-empty-string, mixed> $values
     */
    final public function merge(array &$values): self
    {
        return $this->assign($values, $this);
    }

    /**
     * @param array<non-negative-int|non-empty-string, mixed> $values
     */
    final public function replace(array &$values): self
    {
        return $this->assign($values, self::undefined($this->parameter));
    }

    /**
     * @param array<non-negative-int|non-empty-string, mixed> $values
     */
    private function assign(array &$values, self $default): self
    {
        $position = $this->parameter->position;
        $name = $this->parameter->name;

        if (\array_key_exists($position, $values)) {
            if (\array_key_exists($name, $values)) {
                throw new \LogicException(\sprintf(
                    'Parameter `%s` is assigned multiple arguments: by position (%d) and by name (%s)',
                    $this->parameter->formattedName,
                    $position,
                    $name,
                ));
            }

            $argument = self::value($this->parameter, $values[$position]);
            unset($values[$position]);

            return $argument;
        }

        if (\array_key_exists($name, $values)) {
            $argument = self::value($this->parameter, $values[$name]);
            unset($values[$name]);

            return $argument;
        }

        return $default;
    }

    abstract public function autowire(Autowiring $autowiring): self;

    abstract public function ensureResolvable(): void;

    abstract public function resolve(Container $container): mixed;
}
