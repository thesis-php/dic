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
use Thesis\Dic\Location;
use Thesis\Dic\Ref;

/**
 * @api
 *
 * @template-covariant T of object
 * @extends ObjectConfig<T>
 */
final class ObjectFactoryConfig extends ObjectConfig
{
    /**
     * @internal
     *
     * @param \ReflectionClass<covariant T> $reflection
     * @param ?Config<callable(): T> $factory
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        \ReflectionClass $reflection,
        private readonly ?Config $factory,
        Location $declaredAt,
    ) {
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
            reflection: $reflection,
            declaredAt: $declaredAt,
        );
    }

    public function doNotAutoconfigure(): static
    {
        $this->isAutoconfigurable = false;

        return $this;
    }

    /**
     * @var Arguments<never>
     */
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
        if (!$this->reflection->isInstantiable()) {
            throw BuildError::lazyClassNotInstantiable($this->reflection);
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
