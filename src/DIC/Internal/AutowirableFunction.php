<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Internal\AutowirableFunction\Argument;
use Thesis\DIC\Internal\AutowirableFunction\Argument\Defined;
use Thesis\DIC\Internal\AutowirableFunction\Parameter;

/**
 * @internal
 *
 * @template-covariant T
 */
final readonly class AutowirableFunction
{
    /**
     * @template R
     * @param callable(mixed...): R $function
     * @return self<R>
     */
    public static function callable(callable $function): self
    {
        return self::proxy(
            function: $function,
            reflection: new \ReflectionFunction($function(...)),
        );
    }

    /**
     * @template O of object
     * @param class-string<O> $class
     * @return self<O>
     */
    public static function constructor(string $class): self
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new \LogicException();
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return new self(static fn() => new $class());
        }

        return self::proxy(
            function: static fn(mixed ...$args) => new $class(...$args),
            reflection: $constructor,
        );
    }

    /**
     * @template R
     * @param callable(mixed...): R $function
     * @return self<R>
     */
    public static function proxy(callable $function, \ReflectionFunctionAbstract $reflection): self
    {
        $function = $function(...);

        $parameters = $reflection->getParameters();

        // todo support variadic
        if ($reflection->isVariadic()) {
            array_pop($parameters);
        }

        return new self(
            function: $function,
            arguments: array_map(
                static fn(\ReflectionParameter $reflection) => Argument::undefined(new Parameter($reflection)),
                $parameters,
            ),
        );
    }

    /**
     * @template R
     * @param callable(Container, mixed...): R $function
     * @return self<R>
     */
    public static function containerAwareProxy(callable $function, \ReflectionFunctionAbstract $reflection): self
    {
        $function = $function(...);

        $parameters = $reflection->getParameters();

        // todo support variadic
        if ($reflection->isVariadic()) {
            array_pop($parameters);
        }

        return new self(
            /** @phpstan-ignore argument.type */
            function: $function,
            arguments: array_map(
                static fn(\ReflectionParameter $reflection) => Argument::undefined(new Parameter($reflection)),
                $parameters,
            ),
            containerAware: true,
        );
    }

    /**
     * @return self<never>
     */
    public static function never(): self
    {
        return new self(static fn() => throw new \LogicException());
    }

    /**
     * @param \Closure(): T $function
     * @param array<non-negative-int, Argument> $arguments
     */
    private function __construct(
        private \Closure $function,
        private array $arguments = [],
        private bool $containerAware = false,
    ) {}

    /**
     * @param non-negative-int|non-empty-string $param
     */
    public function withArgument(int|string $param, mixed $value): static
    {
        $values = [$param => $value];

        $arguments = array_map(
            static function (Argument $argument) use (&$values) {
                return $argument->assign($values);
            },
            $this->arguments,
        );

        if ($values !== []) {
            throw new \LogicException();
        }

        return $this->cloneWithArguments($arguments);
    }

    /**
     * @param array<non-negative-int|non-empty-string, mixed> $values
     */
    public function withArguments(array $values): static
    {
        $arguments = array_map(
            static function (Argument $argument) use (&$values) {
                return $argument->assign($values, replace: true);
            },
            $this->arguments,
        );

        if ($values !== []) {
            throw new \LogicException();
        }

        return $this->cloneWithArguments($arguments);
    }

    public function check(): void
    {
        foreach ($this->arguments as $argument) {
            $argument->check();
        }
    }

    public function autowire(Autowiring $autowiring): static
    {
        return $this->cloneWithArguments(
            array_map(
                static fn(Argument $argument) => $argument->autowire($autowiring),
                $this->arguments,
            ),
        );
    }

    /**
     * @return \Closure(mixed...): T
     */
    public function apply(Container $container): \Closure
    {
        return function (mixed ...$args) use ($container): mixed {
            /** @var array<non-negative-int, mixed> */
            static $cache = [];

            $resolvedArgs = [];

            if ($this->containerAware) {
                $resolvedArgs = [$container];
            }

            $position = 0;

            foreach ($this->arguments as $i => $argument) {
                if ($argument instanceof Defined) {
                    $resolvedArgs[] = \array_key_exists($i, $cache) ? $cache[$i] : $cache[$i] = $argument->resolve($container);

                    // we intentionally do not increment $position here,
                    // because defined arguments are not a part of the applied signature

                    continue;
                }

                $resolvedArgs[] = match (true) {
                    \array_key_exists($position, $args) => $args[$position],
                    \array_key_exists($argument->name, $args) => $args[$argument->name],
                    default => \array_key_exists($i, $cache) ? $cache[$i] : $cache[$i] = $argument->resolve($container),
                };

                ++$position;
            }

            return ($this->function)(...$resolvedArgs);
        };
    }

    /**
     * @return T
     */
    public function __invoke(Container $container): mixed
    {
        $resolvedArgs = array_map(
            static fn(Argument $argument) => $argument->resolve($container),
            $this->arguments,
        );

        if ($this->containerAware) {
            return ($this->function)($container, ...$resolvedArgs);
        }

        return ($this->function)(...$resolvedArgs);
    }

    /**
     * @param array<non-negative-int, Argument> $arguments
     */
    private function cloneWithArguments(array $arguments): static
    {
        return new self(
            function: $this->function,
            arguments: $arguments,
            containerAware: $this->containerAware,
        );
    }
}
