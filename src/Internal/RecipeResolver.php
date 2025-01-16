<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Id;
use Thesis\DI\Module;
use Thesis\DI\ModuleId;
use function Thesis\DI\moduleId;
use const Thesis\DI\defaultArgument;

/**
 * @internal
 */
final readonly class RecipeResolver
{
    /**
     * @param class-string<Module> $module
     */
    public static function resolve(
        Autowiring $autowiring,
        Exports $exports,
        ModuleValues $values,
        string $module,
        mixed $recipe,
    ): mixed {
        return (new self(
            autowiring: $autowiring,
            exports: $exports,
            values: $values,
            module: $module,
        ))->doResolve($recipe);
    }

    /**
     * @param class-string<Module> $module
     */
    private function __construct(
        private Autowiring $autowiring,
        private Exports $exports,
        private ModuleValues $values,
        private string $module,
    ) {}

    private function doResolve(mixed $recipe): mixed
    {
        if ($recipe === defaultArgument) {
            return defaultArgument;
        }

        if ($recipe instanceof Id) {
            return $this->resolveId($recipe);
        }

        if ($recipe instanceof ModuleId) {
            /** @phpstan-ignore argument.templateType */
            return $this->resolveModuleId($recipe);
        }

        if ($recipe instanceof Value) {
            return $recipe->value;
        }

        if ($recipe instanceof Construct) {
            return $this->resolveConstructor($recipe);
        }

        if ($recipe instanceof Call) {
            return $this->resolveFactory($recipe);
        }

        if (\is_array($recipe)) {
            return array_map($this->doResolve(...), $recipe);
        }

        return $recipe;
    }

    /**
     * @template TValue
     * @param Id<TValue> $id
     * @return ModuleId<*, TValue>
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
     * @template TValue of object
     * @param Construct<TValue> $constructor
     * @return LazyValue<TValue>
     */
    private function resolveConstructor(Construct $constructor): LazyValue
    {
        $class = $constructor->class;
        $location = Location::current();

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
            if ($constructor->args !== []) {
                throw InvalidConfig::classDoesNotHaveConstructor($class, $location);
            }

            return new LazyValue(static fn(): object => new $class());
        }

        return new LazyValue(
            static fn(mixed ...$args): object => new $class(...$args),
            $this->resolveArguments(
                function: $constructorReflection,
                rawArguments: $constructor->args,
                autowire: $constructor->autowire,
                location: $location,
            ),
        );
    }

    /**
     * @template TValue
     * @param Call<TValue> $factory
     * @return LazyValue<TValue>
     */
    private function resolveFactory(Call $factory): LazyValue
    {
        return new LazyValue(
            /** @phpstan-ignore argument.type */
            $factory->function,
            $this->resolveArguments(
                function: new \ReflectionFunction($factory->function),
                rawArguments: $factory->args,
                autowire: $factory->autowire,
                location: Location::current(),
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

            return $this->doResolve($rawArguments[$index]);
        }

        if (\array_key_exists($name, $rawArguments)) {
            return $this->doResolve($rawArguments[$name]);
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
