<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Constructor;
use Thesis\DI\Definition;
use Thesis\DI\Factory;
use Thesis\DI\Id;
use Thesis\DI\Module;
use Thesis\DI\ModuleConfigurator as ModuleConfiguratorI;
use Thesis\DI\ModuleId;
use Thesis\DI\Value;
use function Thesis\DI\moduleId;
use const Thesis\DI\defaultArgument;

/**
 * @internal
 * @psalm-internal Thesis\DI
 * @template TReqs of Module
 * @template TModule of Module<TReqs>
 * @implements ModuleConfiguratorI<TReqs, TModule>
 */
final readonly class ModuleConfigurator implements ModuleConfiguratorI
{
    /**
     * @template TNewReqs of Module
     * @template TNewModule of Module<TNewReqs>
     * @param class-string<TNewModule> $module
     * @return self<TNewReqs, TNewModule>
     */
    public static function create(
        string $module,
        Exports $exports,
        Autowiring $autowiring = new Autowiring(),
    ): self {
        return new self($module, $exports, $autowiring, ModuleValues::create());
    }

    /**
     * @param class-string<TModule> $module
     */
    private function __construct(
        private string $module,
        public Exports $exports,
        private Autowiring $autowiring,
        public ModuleValues $values,
    ) {}

    public function define(Id|ModuleId|Value|Constructor|Factory $value, null|Id|ModuleId $as = null, ?ModuleId &$ref = null): static
    {
        if ($as instanceof Id) {
            $as = moduleId($this->module, $as);
        }

        $resolved = $this->resolve($value);

        if ($resolved instanceof ModuleId) {
            if ($resolved->module === $this->module && ($as === null || $resolved->id->equals($as->id))) {
                /** @phpstan-ignore paramOut.type */
                $ref = $resolved;

                return $this;
            }

            $ref = $as ??= moduleId($this->module, $resolved->id);
        } else {
            $ref = $as ??= moduleId($this->module, $this->autowiring->identify($value));
        }

        /** @var self<TReqs, TModule> */
        return new self(
            module: $this->module,
            exports: $this->exports,
            autowiring: $this->autowiring,
            values: $this->values->with($as->id, $resolved),
        );
    }

    public function export(Id|ModuleId|Value|Constructor|Factory $value, null|ModuleId|Id $as = null, ?ModuleId &$ref = null): static
    {
        $builder = $this->define($value, $as, $ref);

        /** @var self<TReqs, TModule> */
        return new self(
            module: $this->module,
            exports: $this->exports->with($ref),
            autowiring: $this->autowiring,
            values: $builder->values,
        );
    }

    private function resolve(mixed $value): mixed
    {
        if ($value === defaultArgument) {
            return defaultArgument;
        }

        if ($value instanceof Id) {
            if ($this->values->has($value)) {
                return moduleId($this->module, $value);
            }

            throw InvalidConfig::idNotRegistered($this->module, $value, $value->location);
        }

        if ($value instanceof ModuleId) {
            if ($value->module === $this->module) {
                if ($this->values->has($value->id)) {
                    return $value;
                }

                throw InvalidConfig::idNotRegistered($value->module, $value->id, $value->location);
            }

            if ($this->exports->has($value)) {
                return $value;
            }

            throw InvalidConfig::idNotRegistered($this->module, $value->id, $value->location);
        }

        if ($value instanceof Value) {
            return $value->value;
        }

        if ($value instanceof Constructor) {
            $class = $value->class;
            $reflection = new \ReflectionClass($class);

            if (!$reflection->isInstantiable()) {
                throw InvalidConfig::classNotInstantiable($class, $value->location);
            }

            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                if ($value->arguments !== []) {
                    throw InvalidConfig::classDoesNotHaveConstructor($class, $value->location);
                }

                return new LazyValue(static fn(): object => new $class());
            }

            return new LazyValue(
                static fn(mixed ...$arguments): object => new $class(...$arguments),
                $this->resolveArguments(
                    function: $constructor,
                    rawArguments: $value->arguments,
                    autowire: $value->autowire,
                    location: $value->location,
                ),
            );
        }

        if ($value instanceof Factory) {
            return new LazyValue(
                $value->factory,
                $this->resolveArguments(
                    function: new \ReflectionFunction($value->factory),
                    rawArguments: $value->arguments,
                    autowire: $value->autowire,
                    location: $value->location,
                ),
            );
        }

        if ($value instanceof Definition) {
            throw new \LogicException('TODO');
        }

        return $value;
    }

    /**
     * @param array<mixed> $rawArguments
     * @return array<non-empty-string, mixed>
     */
    private function resolveArguments(
        \ReflectionFunctionAbstract $function,
        array $rawArguments,
        bool $autowire,
        Location $location,
    ): array {
        $functionName = describeReflectedSymbol($function);
        $arguments = [];

        foreach ($function->getParameters() as $parameter) {
            $argument = $this->resolveArgument($functionName, $parameter, $rawArguments, $autowire, $location);
            unset($rawArguments[$parameter->getPosition()], $rawArguments[$parameter->name]);

            if ($argument !== defaultArgument) {
                /** @var non-empty-string */
                $name = $parameter->name;
                $arguments[$name] = $argument;
            }
        }

        if ($rawArguments !== []) {
            throw InvalidConfig::functionDoesNotHaveParameters($functionName, array_keys($rawArguments), $location);
        }

        return $arguments;
    }

    /**
     * @param non-empty-string $function
     * @param array<mixed> $rawArguments
     */
    private function resolveArgument(
        string $function,
        \ReflectionParameter $parameter,
        array $rawArguments,
        bool $autowire,
        Location $location,
    ): mixed {
        $index = $parameter->getPosition();
        $name = $parameter->name;

        if (\array_key_exists($index, $rawArguments)) {
            if (\array_key_exists($parameter->name, $rawArguments)) {
                throw InvalidConfig::argumentConfiguredTwice($function, $index, $name, $location);
            }

            return $this->resolve($rawArguments[$index]);
        }

        if (\array_key_exists($name, $rawArguments)) {
            return $this->resolve($rawArguments[$name]);
        }

        if ($autowire) {
            if (!$this->values->isEmpty()) {
                $autowiredId = $this->autowiring->autowire($parameter, $this->values);

                if ($autowiredId !== null) {
                    return new ModuleId($this->module, $autowiredId);
                }
            }

            if ($parameter->isOptional()) {
                return defaultArgument;
            }

            throw InvalidConfig::cannotAutowire(
                function: $function,
                parameter: $name,
                type: describeReflectedType($parameter->getType()),
                location: $location,
            );
        }

        if ($parameter->isOptional()) {
            return defaultArgument;
        }

        throw InvalidConfig::requiredArgumentMissing(
            function: $function,
            parameter: $name,
            location: $location,
        );
    }
}
