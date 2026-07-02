<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\BuildError;
use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Arguments\ClosureArguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\Autoconfiguration;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\ArgumentsFactory;
use Thesis\Dic\Internal\Factory\LazyObjectFactory;
use Thesis\Dic\Internal\Factory\NewFactory;
use Thesis\Dic\Internal\Factory\RefFactory;
use Thesis\Dic\Internal\Signature;
use Thesis\Dic\Internal\Signature\ReflectionFunctionSignature;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use function Thesis\Formatter\formatReflectedClass;

/**
 * @api
 *
 * @template-covariant T of object
 * @extends Config<T>
 */
final class ObjectConfig extends Config
{
    /**
     * @var \ReflectionClass<covariant T>
     */
    final public readonly \ReflectionClass $reflection;

    /**
     * @internal
     *
     * @param \ReflectionClass<covariant T> $reflection
     * @param ?Ref<callable(): T> $factory
     */
    public function __construct(
        Builder $builder,
        private readonly Autoconfiguration $autoconfiguration,
        Autowiring $autowiring,
        \ReflectionClass $reflection,
        private readonly ?Ref $factory,
        Location $declaredAt,
    ) {
        $this->reflection = $reflection;

        $this->arguments = new Arguments(
            signature: match ($factory) {
                null => match ($reflection->isInstantiable()) {
                    true => Signature::ofConstructor($reflection),
                    false => throw BuildError::classNotInstantiable($reflection),
                },
                default => $factory->signature ?? throw BuildError::factoryNotCallable($factory),
            },
            autowiring: $autowiring,
            closureArguments: ClosureArguments::empty(),
        );

        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            label: formatReflectedClass($reflection),
            declaredAt: $declaredAt,
            defaultLifetimeStrategy: LifetimeStrategy::Singleton,
        );

        $autoconfiguration->schedule($this);
    }

    /**
     * @var ?ReflectionFunctionSignature<\ReflectionMethod>
     */
    final protected ?Signature $signature {
        get => Signature::ofClass($this->reflection);
    }

    final protected ?\ReflectionMethod $reflectionFunction {
        get => $this->signature?->reflection;
    }

    final protected \ReflectionClass $reflectionClass {
        get => $this->reflection;
    }

    /**
     * Opts this service out of all onObject() autoconfiguration listeners.
     */
    public function doNotAutoconfigure(): static
    {
        $this->autoconfiguration->unschedule($this);

        return $this;
    }

    /**
     * @var array<string, FunctionConfig<callable-array>>
     */
    private array $methods = [];

    /**
     * Exposes $name as a callable service; chain closure() on the result to adapt it to a typed \Closure.
     *
     * @return FunctionConfig<callable-array>
     */
    public function method(string $name): FunctionConfig
    {
        if (isset($this->methods[$name])) {
            return $this->methods[$name];
        }

        /** @var ValueConfig<callable> */
        $value = new ValueConfig(
            builder: $this->builder,
            autowiring: $this->autowiring,
            value: [$this, $name],
            declaredAt: Location::caller(),
        );

        /** @var FunctionConfig<callable-array> */
        return $this->methods[$name] = new FunctionConfig(
            builder: $this->builder,
            autoconfiguration: $this->autoconfiguration,
            autowiring: $this->autowiring,
            value: $value,
            declaredAt: Location::caller(),
        );
    }

    /**
     * One shared instance for the life of the container (the default).
     */
    public function singleton(): static
    {
        $this->builder->setLifetimeStrategy($this, LifetimeStrategy::Singleton);

        return $this;
    }

    /**
     * Adaptive lifetime: scoped if any transitive dependency is scoped, singleton otherwise.
     */
    public function canBeScoped(): static
    {
        $this->builder->setLifetimeStrategy($this, LifetimeStrategy::CanBeScoped);

        return $this;
    }

    /**
     * One instance per scope.
     */
    public function scoped(): static
    {
        $this->builder->setLifetimeStrategy($this, LifetimeStrategy::Scoped);

        return $this;
    }

    /**
     * @var Arguments<never>
     */
    private readonly Arguments $arguments;

    /**
     * Disables autowiring for all parameters; every dependency must be set explicitly with arg() / args().
     */
    public function doNotAutowire(): static
    {
        $this->arguments->doNotAutowire();

        return $this;
    }

    /**
     * Sets a single constructor / factory parameter by position or name.
     */
    public function arg(int|string $positionOrName, mixed $value): static
    {
        $this->arguments->arg($positionOrName, $value);

        return $this;
    }

    /**
     * Passes a list to the variadic constructor / factory parameter.
     *
     * @param iterable<array-key, mixed>|Ref<iterable<array-key, mixed>> $variadic
     */
    public function variadic(iterable|Ref $variadic): static
    {
        $this->arguments->variadic($variadic);

        return $this;
    }

    /**
     * Sets multiple constructor / factory parameters at once.
     *
     * @param array<mixed> $values
     */
    public function args(array $values): static
    {
        $this->arguments->args($values);

        return $this;
    }

    private bool $lazy = false;

    /**
     * Defers instantiation until first use, returning a lazy proxy in the meantime.
     */
    public function lazy(): static
    {
        if (!$this->reflection->isInstantiable()) {
            throw BuildError::lazyClassNotInstantiable($this->reflection);
        }

        $this->lazy = true;

        return $this;
    }

    /**
     * Cancels lazy(); the object is instantiated eagerly when the scope is built.
     */
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
     * Calls $method on the constructed object; use for setters and other side-effecting post-construction steps.
     *
     * @param array<mixed> $args
     * @param iterable<array-key, mixed>|Ref<iterable<array-key, mixed>> $variadic
     */
    public function call(string $method, array $args = [], iterable|Ref $variadic = []): static
    {
        $arguments = $this->createMethodArguments($method, $args, $variadic);

        /** @phpstan-ignore assign.propertyType */
        $this->factoryDecorators[] = static fn(Factory $factory) => new Factory\PostCallFactory(
            factory: $factory,
            method: $method,
            arguments: ArgumentsFactory::from($arguments),
        );

        return $this;
    }

    /**
     * Calls $method and replaces the instance with its return value; use for wither-style (immutable) builders.
     *
     * @param array<mixed> $args
     * @param iterable<array-key, mixed>|Ref<iterable<array-key, mixed>> $variadic
     */
    public function chain(string $method, array $args = [], iterable|Ref $variadic = []): static
    {
        $arguments = $this->createMethodArguments($method, $args, $variadic);

        /** @phpstan-ignore assign.propertyType */
        $this->factoryDecorators[] = static fn(Factory $factory) => new Factory\PostChainFactory(
            factory: $factory,
            method: $method,
            arguments: ArgumentsFactory::from($arguments),
        );

        return $this;
    }

    /**
     * @param array<mixed> $args
     * @param iterable<array-key, mixed>|Ref<iterable<array-key, mixed>> $variadic
     */
    private function createMethodArguments(string $name, array $args, iterable|Ref $variadic): Arguments
    {
        $reflection = $this->reflection->getMethod($name);

        if (!$reflection->isPublic()) {
            throw BuildError::calledMethodNotPublic($reflection);
        }

        $arguments = new Arguments(
            signature: Signature::ofMethod($reflection),
            autowiring: $this->autowiring,
            closureArguments: ClosureArguments::empty(),
        );
        $arguments->args($args);
        $arguments->variadic($variadic);

        return $arguments;
    }

    protected function createFactory(): Factory
    {
        $arguments = ArgumentsFactory::from($this->arguments);

        $factory = match ($this->factory) {
            null => new NewFactory(
                class: $this->reflection->name,
                arguments: $arguments,
            ),
            default => new RefFactory(
                ref: $this->factory,
                arguments: $arguments,
            ),
        };

        foreach ($this->factoryDecorators as $factoryDecorator) {
            $factory = $factoryDecorator($factory);
        }

        if ($this->lazy) {
            $factory = new LazyObjectFactory(
                /**
                 * @todo think about it...
                 * @phpstan-ignore argument.type
                 */
                class: $this->reflection,
                factory: $factory,
            );
        }

        return $factory;
    }
}
