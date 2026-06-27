<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Error;
use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Arguments\ClosureArguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\ArgumentsFactory;
use Thesis\Dic\Internal\Factory\CallableFactory;
use Thesis\Dic\Internal\Factory\LazyObjectFactory;
use Thesis\Dic\Internal\Factory\NewFactory;
use Thesis\Dic\Internal\Factory\RefCallableFactory;
use Thesis\Dic\Internal\Signature;
use Thesis\Dic\Internal\Signature\ReflectionFunctionSignature;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use function Thesis\Formatter\formatReflectedClass;

/**
 * @api
 *
 * @template T of object
 * @extends Config<T>
 */
final class ObjectConfig extends Config
{
    /**
     * @internal
     *
     * @param \ReflectionClass<T> $class
     * @param null|Ref<callable(): T>|callable(): T $factory
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        public readonly \ReflectionClass $class,
        private readonly mixed $factory,
        Location $declaredAt,
    ) {
        $this->arguments = new Arguments(
            signature: $this->resolveFactorySignature($this->factory),
            autowiring: $autowiring,
            closureArguments: ClosureArguments::empty(),
        );

        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            declaredAt: $declaredAt,
        );
    }

    /**
     * @param null|Ref<callable(): T>|callable(): T $factory
     */
    private function resolveFactorySignature(null|Ref|callable $factory): Signature
    {
        if ($factory === null) {
            if (!$this->class->isInstantiable()) {
                throw Error::classNotInstantiable($this->class);
            }

            return Signature::ofConstructor($this->class);
        }

        if (!$factory instanceof Ref) {
            return Signature::ofCallable($factory);
        }

        return $factory->signature ?? throw Error::factoryNotCallable($factory);
    }

    protected function defaultLabel(): string
    {
        return formatReflectedClass($this->class);
    }

    /**
     * @var ?ReflectionFunctionSignature<\ReflectionMethod>
     */
    protected ?Signature $signature {
        get => Signature::ofClass($this->class);
    }

    public ?\ReflectionMethod $function {
        get => $this->signature?->reflection;
    }

    private readonly Arguments $arguments;

    public function doNotAutowire(): static
    {
        $this->arguments->doNotAutowire();

        return $this;
    }

    public function arg(int|string $positionOrName, mixed $value): static
    {
        $this->arguments->arg($positionOrName, $value);

        return $this;
    }

    /**
     * @param iterable<array-key, mixed>|Ref<iterable<array-key, mixed>> $variadic
     */
    public function variadic(iterable|Ref $variadic): static
    {
        $this->arguments->variadic($variadic);

        return $this;
    }

    /**
     * @param array<mixed> $values
     */
    public function args(array $values): static
    {
        $this->arguments->args($values);

        return $this;
    }

    private bool $lazy = false;

    public function lazy(): static
    {
        if (!$this->class->isInstantiable()) {
            throw Error::lazyClassNotInstantiable($this->class);
        }

        $this->lazy = true;

        return $this;
    }

    public function eager(): static
    {
        $this->lazy = false;

        return $this;
    }

    /**
     * @var list<\Closure(Factory<T>): Factory<T>>
     */
    private array $factoryDecorators = [];

    /**
     * @param array<mixed> $args
     */
    public function call(string $method, array $args = []): static
    {
        $arguments = $this->createMethodArguments($method, $args);

        /** @phpstan-ignore assign.propertyType */
        $this->factoryDecorators[] = static fn(Factory $factory) => new Factory\PostCallFactory(
            factory: $factory,
            method: $method,
            arguments: ArgumentsFactory::from($arguments),
        );

        return $this;
    }

    /**
     * @param array<mixed> $args
     */
    public function chain(string $method, array $args = []): static
    {
        $arguments = $this->createMethodArguments($method, $args);

        /** @phpstan-ignore assign.propertyType */
        $this->factoryDecorators[] = static fn(Factory $factory) => new Factory\PostChainFactory(
            factory: $factory,
            method: $method,
            arguments: ArgumentsFactory::from($arguments),
        );

        return $this;
    }

    /**
     * @param array<mixed> $values
     */
    private function createMethodArguments(string $name, array $values): Arguments
    {
        $reflection = $this->class->getMethod($name);

        if (!$reflection->isPublic()) {
            throw Error::calledMethodNotPublic($reflection);
        }

        $arguments = new Arguments(
            signature: Signature::ofMethod($reflection),
            autowiring: $this->autowiring,
            closureArguments: ClosureArguments::empty(),
        );
        $arguments->args($values);

        return $arguments;
    }

    /**
     * @return MethodConfig<callable>
     */
    public function method(string $name): MethodConfig
    {
        /** @var MethodConfig<callable> */
        return new MethodConfig(
            builder: $this->builder,
            autowiring: $this->autowiring,
            function: $this->class->getMethod($name),
            object: $this,
            declaredAt: Location::caller(),
        );
    }

    protected function createFactory(): Factory
    {
        $factory = $this->createBaseFactory();

        foreach ($this->factoryDecorators as $factoryDecorator) {
            $factory = $factoryDecorator($factory);
        }

        if ($this->lazy) {
            $factory = new LazyObjectFactory(
                class: $this->class,
                factory: $factory,
            );
        }

        return $factory;
    }

    /**
     * @return Factory<T>
     */
    private function createBaseFactory(): Factory
    {
        $arguments = ArgumentsFactory::from($this->arguments);

        if ($this->factory === null) {
            return new NewFactory(
                class: $this->class->name,
                arguments: $arguments,
            );
        }

        if ($this->factory instanceof Ref) {
            return new RefCallableFactory(
                ref: $this->factory,
                arguments: $arguments,
            );
        }

        return new CallableFactory(
            callable: $this->factory,
            arguments: $arguments,
        );
    }
}
