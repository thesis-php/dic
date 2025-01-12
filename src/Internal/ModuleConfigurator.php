<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Constructor;
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
    public static function create(string $module, Exports $exports, Autowiring $autowiring): self
    {
        return new self(
            module: $module,
            exports: $exports,
            autowiring: $autowiring,
            values: ModuleValues::create(),
        );
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

    public function importAs(ModuleId $id, Id $as): static
    {
        /** @var self<TReqs, TModule> */
        return new self(
            module: $this->module,
            exports: $this->exports,
            autowiring: $this->autowiring,
            values: $this->values->with($as, $this->resolveModuleId($id)),
        );
    }

    public function define(Value|Factory|Constructor $value, ?Id &$inferredId = null): static
    {
        $inferredId = $this->autowiring->identify($value);

        return $this->defineAs($value, $inferredId);
    }

    public function defineAs(Value|Id|Factory|Constructor $value, Id $as): static
    {
        /** @var self<TReqs, TModule> */
        return new self(
            module: $this->module,
            exports: $this->exports,
            autowiring: $this->autowiring,
            values: $this->values->with($as, $this->resolveValue($value)),
        );
    }

    public function export(Value|Factory|Constructor $value, ?Id &$inferredId = null): static
    {
        $inferredId = $this->autowiring->identify($value);

        return $this->exportAs($value, $inferredId);
    }

    public function exportAs(Value|Id|Factory|Constructor $value, Id $as): static
    {
        /** @var self<TReqs, TModule> */
        return new self(
            module: $this->module,
            exports: $this->exports->with(moduleId($this->module, $as)),
            autowiring: $this->autowiring,
            values: $this->values->with($as, $this->resolveValue($value)),
        );
    }

    private function resolveValue(mixed $value): mixed
    {
        if ($value === defaultArgument) {
            return defaultArgument;
        }

        if ($value instanceof Id) {
            return $this->resolveId($value);
        }

        if ($value instanceof ModuleId) {
            /** @phpstan-ignore argument.templateType */
            return $this->resolveModuleId($value);
        }

        if ($value instanceof Value) {
            return $value->value;
        }

        if ($value instanceof Constructor) {
            return $this->resolveConstructor($value);
        }

        if ($value instanceof Factory) {
            return $this->resolveFactory($value);
        }

        if (\is_array($value)) {
            return array_map($this->resolveValue(...), $value);
        }

        return $value;
    }

    /**
     * @template T
     * @param Id<T> $id
     * @return ModuleId<TModule, T>
     */
    private function resolveId(Id $id): ModuleId
    {
        if ($this->values->has($id)) {
            return moduleId($this->module, $id);
        }

        throw InvalidConfig::idNotRegistered($this->module, $id, $id->location);
    }

    /**
     * @template TModuleId of ModuleId<*, *>
     * @param TModuleId $id
     * @return TModuleId
     */
    private function resolveModuleId(ModuleId $id): ModuleId
    {
        if ($id->module === $this->module) {
            if ($this->values->has($id->id)) {
                return $id;
            }

            throw InvalidConfig::idNotRegistered($id->module, $id->id, $id->location);
        }

        if ($this->exports->has($id)) {
            return $id;
        }

        throw InvalidConfig::idNotRegistered($this->module, $id->id, $id->location);
    }

    /**
     * @template T of object
     * @param Constructor<T> $constructor
     * @return LazyValue<T>
     */
    private function resolveConstructor(Constructor $constructor): LazyValue
    {
        $class = $constructor->class;
        $location = $constructor->location;

        try {
            $classReflection = new \ReflectionClass($class);
        } catch (/** @phpstan-ignore catch.neverThrown */ \ReflectionException $exception) {
            throw InvalidConfig::classDoesNotExist($class, $location, $exception);
        }

        if (!$classReflection->isInstantiable()) {
            throw InvalidConfig::classNotInstantiable($class, $location);
        }

        $constructorReflection = $classReflection->getConstructor();

        if ($constructorReflection === null) {
            if ($constructor->arguments !== []) {
                throw InvalidConfig::classDoesNotHaveConstructor($class, $location);
            }

            return new LazyValue(static fn(): object => new $class());
        }

        return new LazyValue(
            static fn(mixed ...$arguments): object => new $class(...$arguments),
            $this->resolveArguments(
                function: $constructorReflection,
                rawArguments: $constructor->arguments,
                autowire: $constructor->autowire,
                location: $location,
            ),
        );
    }

    /**
     * @template T
     * @param Factory<T> $factory
     * @return LazyValue<T>
     */
    private function resolveFactory(Factory $factory): LazyValue
    {
        return new LazyValue(
            /** @phpstan-ignore argument.type */
            $factory->factory,
            $this->resolveArguments(
                function: new \ReflectionFunction($factory->factory),
                rawArguments: $factory->arguments,
                autowire: $factory->autowire,
                location: $factory->location,
            ),
        );
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
        $arguments = [];

        foreach ($function->getParameters() as $parameter) {
            $argument = $this->resolveArgument($function, $parameter, $rawArguments, $autowire, $location);
            unset($rawArguments[$parameter->getPosition()], $rawArguments[$parameter->name]);

            if ($argument !== defaultArgument) {
                /** @var non-empty-string */
                $name = $parameter->name;
                $arguments[$name] = $argument;
            }
        }

        if ($rawArguments !== []) {
            throw InvalidConfig::functionDoesNotHaveParameters($function, array_keys($rawArguments), $location);
        }

        return $arguments;
    }

    /**
     * @param array<mixed> $rawArguments
     */
    private function resolveArgument(
        \ReflectionFunctionAbstract $function,
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

            return $this->resolveValue($rawArguments[$index]);
        }

        if (\array_key_exists($name, $rawArguments)) {
            return $this->resolveValue($rawArguments[$name]);
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
                type: $parameter->getType(),
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
