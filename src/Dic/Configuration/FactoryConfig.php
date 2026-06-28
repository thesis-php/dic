<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Typhoon\Type\Parameter;

/**
 * @api
 *
 * @template-covariant T
 * @template TClosureParameter of Parameter
 * @extends Config<T>
 * @implements LifetimeConfig<T>
 *
 * @phpstan-sealed ClosureConfig|ObjectConfig
 */
abstract class FactoryConfig extends Config implements LifetimeConfig
{
    /**
     * @param Arguments<TClosureParameter> $arguments
     */
    protected function __construct(
        Builder $builder,
        Autowiring $autowiring,
        Location $declaredAt,
        protected readonly Arguments $arguments,
    ) {
        parent::__construct($builder, $autowiring, $declaredAt);
    }

    /**
     * @see Builder\Services::lifetimeStrategyOf()
     * @phpstan-ignore property.uninitialized
     */
    protected private(set) LifetimeStrategy $lifetimeStrategy {
        get => $this->lifetimeStrategy ??= LifetimeStrategy::Singleton;
        set {
            if ($this->isAutoconfiguring) {
                $this->lifetimeStrategy ??= $value;
            } else {
                $this->lifetimeStrategy = $value;
            }
        }
    }

    final public function singleton(): static
    {
        $this->lifetimeStrategy = LifetimeStrategy::Singleton;

        return $this;
    }

    final public function canBeScoped(): static
    {
        $this->lifetimeStrategy = LifetimeStrategy::CanBeScoped;

        return $this;
    }

    final public function scoped(): static
    {
        $this->lifetimeStrategy = LifetimeStrategy::Scoped;

        return $this;
    }

    final public function doNotAutowire(): static
    {
        $this->arguments->doNotAutowire();

        return $this;
    }

    final public function arg(int|string $positionOrName, mixed $value): static
    {
        $this->arguments->arg($positionOrName, $value);

        return $this;
    }

    /**
     * @param iterable<array-key, mixed>|Ref<iterable<array-key, mixed>>|TClosureParameter $variadic
     */
    final public function variadic(iterable|Ref|Parameter $variadic): static
    {
        $this->arguments->variadic($variadic);

        return $this;
    }

    /**
     * @param array<mixed> $values
     */
    final public function args(array $values): static
    {
        $this->arguments->args($values);

        return $this;
    }
}
