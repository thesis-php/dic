<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Arg;
use Thesis\DIC\Internal\Args\DefaultValue;

/**
 * @internal
 *
 * @phpstan-type Param = non-negative-int|non-empty-string
 */
final class Args
{
    /**
     * @var array<Param, mixed>
     */
    private array $values = [];

    private bool $autowire = true;

    /**
     * @param list<\ReflectionParameter> $parameters
     */
    public function __construct(
        private readonly array $parameters,
    ) {}

    /**
     * @param Param $param
     */
    public function setOne(int|string $param, mixed $value): void
    {
        $this->values[$param] = $value;
    }

    /**
     * @param array<Param, mixed> $values
     */
    public function set(array $values): void
    {
        $this->values = $values;
    }

    public function doNotAutowire(): void
    {
        $this->autowire = false;
    }

    /**
     * @return iterable<int, mixed>
     */
    public function resolve(Autowiring $autowiring): iterable
    {
        $resolved = [];
        $fallback = $this->autowire ? Arg::Autowire : Arg::Default;

        foreach ($this->parameters as $index => $parameter) {
            $name = $parameter->name;

            if ($parameter->isVariadic()) {
                continue; // todo
            }

            if (\array_key_exists($index, $this->values)) {
                if (\array_key_exists($name, $this->values)) {
                    throw new \LogicException('Ambiguous.');
                }

                $resolved[] = $this->resolveOne($autowiring, $parameter, $this->values[$index]);

                continue;
            }

            if (\array_key_exists($name, $this->values)) {
                $resolved[] = $this->resolveOne($autowiring, $parameter, $this->values[$name]);

                continue;
            }

            $resolved[] = $this->resolveOne($autowiring, $parameter, $fallback);
        }

        if (array_all($resolved, static fn(mixed $arg) => $arg instanceof DefaultValue)) {
            return [];
        }

        return new Args\Resolved($resolved);
    }

    private function resolveOne(Autowiring $autowiring, \ReflectionParameter $parameter, mixed $value): mixed
    {
        if ($value === Arg::Default) {
            if (!$parameter->isDefaultValueAvailable()) {
                throw new \LogicException('No default value');
            }

            return new DefaultValue($parameter);
        }

        if ($value === Arg::Autowire) {
            $value = $autowiring->autowire($parameter);

            if ($value !== null) {
                return $value;
            }

            if (!$parameter->isDefaultValueAvailable()) {
                throw new \LogicException('Failed to autowire');
            }

            return new DefaultValue($parameter);
        }

        return $value;
    }
}
