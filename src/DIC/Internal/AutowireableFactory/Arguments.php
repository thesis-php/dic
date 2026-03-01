<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;

/**
 * @internal
 */
final class Arguments
{
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @phpstan-ignore missingType.callable
     */
    public static function fromFunction(callable $function): self
    {
        return self::fromParameters(new \ReflectionFunction($function(...))->getParameters());
    }

    /**
     * @param list<\ReflectionParameter> $parameters
     */
    public static function fromParameters(array $parameters): self
    {
        return new self(array_map(
            static fn(\ReflectionParameter $parameter) => Argument::undefined(new Parameter($parameter)),
            $parameters,
        ));
    }

    public bool $isEmpty {
        get => $this->arguments === [];
    }

    /**
     * @param list<Argument> $arguments
     */
    private function __construct(
        public readonly array $arguments = [],
    ) {}

    /**
     * @param array<non-negative-int|non-empty-string, mixed> $values
     */
    public function merge(array $values): self
    {
        $arguments = array_map(
            static function (Argument $argument) use (&$values) {
                return $argument->merge($values);
            },
            $this->arguments,
        );

        if ($values !== []) {
            throw new \LogicException();
        }

        return new self($arguments);
    }

    /**
     * @param array<non-negative-int|non-empty-string, mixed> $values
     */
    public function replace(array $values): self
    {
        $arguments = array_map(
            static function (Argument $argument) use (&$values) {
                return $argument->replace($values);
            },
            $this->arguments,
        );

        if ($values !== []) {
            throw new \LogicException();
        }

        return new self($arguments);
    }

    public function autowire(Autowiring $autowiring): static
    {
        return new self(array_map(
            static fn(Argument $argument) => $argument->autowire($autowiring),
            $this->arguments,
        ));
    }

    public function ensureResolvable(): void
    {
        foreach ($this->arguments as $argument) {
            $argument->ensureResolvable();
        }
    }

    /**
     * @return list<mixed>
     */
    public function resolve(Container $container): array
    {
        return array_map(
            static fn(Argument $argument) => $argument->resolve($container),
            $this->arguments,
        );
    }
}
