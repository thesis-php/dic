<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowirableFunction;

use Thesis\DIC\Internal\AutowirableFunction\Argument\Autowired;
use Thesis\DIC\Internal\AutowirableFunction\Argument\Defined;
use Thesis\DIC\Internal\AutowirableFunction\Argument\NotAutowired;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Mapping\DoNotAutowire;

abstract readonly class Argument
{
    final public static function defined(Parameter $parameter, mixed $value): self
    {
        if ($value instanceof DoNotAutowire) {
            return new NotAutowired($parameter);
        }

        return new Defined($parameter, $value);
    }

    final public static function undefined(Parameter $parameter): self
    {
        if ($parameter->isAutowirable) {
            return new Autowired($parameter);
        }

        return new NotAutowired($parameter);
    }

    /**
     * @var non-empty-string
     */
    public string $name;

    protected function __construct(
        protected Parameter $parameter,
    ) {
        $this->name = $parameter->name;
    }

    /**
     * @param array<non-negative-int|non-empty-string, mixed> $values
     */
    final public function assign(array &$values, bool $replace = false): self
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

            $argument = self::defined($this->parameter, $values[$position]);
            unset($values[$position]);

            return $argument;
        }

        if (\array_key_exists($name, $values)) {
            $argument = self::defined($this->parameter, $values[$name]);
            unset($values[$name]);

            return $argument;
        }

        if ($replace) {
            return self::undefined($this->parameter);
        }

        return $this;
    }

    abstract public function check(): void;

    abstract public function autowire(Autowiring $autowiring): self;

    abstract public function resolve(Container $container): mixed;
}
