<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\BuildError;
use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Arguments\ClosureArguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
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
 * @extends FactoryConfig<T, never>
 */
final class ObjectConfig extends FactoryConfig
{
    /**
     * @internal
     *
     * @param \ReflectionClass<covariant T> $class
     * @param ?Config<callable(): T> $factory
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        public readonly \ReflectionClass $class,
        private readonly ?Config $factory,
        Location $declaredAt,
    ) {
        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            declaredAt: $declaredAt,
            arguments: new Arguments(
                signature: match ($factory) {
                    null => match ($class->isInstantiable()) {
                        true => Signature::ofConstructor($class),
                        false => throw BuildError::classNotInstantiable($class),
                    },
                    default => $factory->signature ?? throw BuildError::factoryNotCallable($factory),
                },
                autowiring: $autowiring,
                closureArguments: ClosureArguments::empty(),
            ),
        );
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

    /** @phpstan-ignore property.phpDocType */
    public ?\ReflectionMethod $function {
        get => $this->signature?->reflection;
    }

    private bool $lazy = false;

    public function lazy(): static
    {
        if (!$this->class->isInstantiable()) {
            throw BuildError::lazyClassNotInstantiable($this->class);
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
        $reflection = $this->class->getMethod($name);

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

    /**
     * @return MethodConfig<callable-array>
     */
    public function method(string $name): MethodConfig
    {
        /** @var MethodConfig<callable-array> */
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
        $arguments = ArgumentsFactory::from($this->arguments);

        $factory = match ($this->factory) {
            null => new NewFactory(
                class: $this->class->name,
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
                class: $this->class,
                factory: $factory,
            );
        }

        return $factory;
    }
}
