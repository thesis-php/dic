<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Arguments\ClosureArguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\ClosureFactory;
use Thesis\Dic\Internal\ShouldNotHappen;
use Thesis\Dic\Internal\Signature;
use Thesis\Dic\Internal\Signature\ClosureSignature;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Typhoon\Type\ClosureT;
use Typhoon\Type\Parameter;
use function Typhoon\Type\stringify;

/**
 * @api
 *
 * @template-covariant T of \Closure
 * @extends Config<T>
 */
final class ClosureConfig extends Config
{
    /**
     * @internal
     *
     * @param ClosureT<T> $type
     * @param Ref<callable> $function
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        private readonly ClosureT $type,
        private readonly Ref $function,
        Location $declaredAt,
    ) {
        $this->arguments = new Arguments(
            signature: $function->signature ?? throw new ShouldNotHappen('Ref does not reference a function'),
            autowiring: $autowiring,
            closureArguments: ClosureArguments::fromType($type),
        );

        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            label: stringify($type),
            declaredAt: $declaredAt,
            defaultLifetimeStrategy: LifetimeStrategy::Singleton,
        );
    }

    protected ClosureSignature $signature {
        get => Signature::ofClosure($this->type);
    }

    protected \ReflectionFunction $reflectionFunction {
        get => $this->signature->reflection;
    }

    protected \ReflectionClass $reflectionClass {
        get => new \ReflectionClass(\Closure::class);
    }

    public function singleton(): static
    {
        $this->builder->setLifetimeStrategy($this, LifetimeStrategy::Singleton);

        return $this;
    }

    public function canBeScoped(): static
    {
        $this->builder->setLifetimeStrategy($this, LifetimeStrategy::CanBeScoped);

        return $this;
    }

    public function scoped(): static
    {
        $this->builder->setLifetimeStrategy($this, LifetimeStrategy::Scoped);

        return $this;
    }

    /**
     * @var Arguments<Parameter>
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
     * @param iterable<array-key, mixed>|Ref<iterable<array-key, mixed>>|Parameter $variadic
     */
    public function variadic(iterable|Ref|Parameter $variadic): static
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

    protected function createFactory(): Factory
    {
        /** @var ClosureFactory<T> */
        return ClosureFactory::from(
            type: $this->type,
            function: $this->function,
            arguments: $this->arguments,
        );
    }
}
