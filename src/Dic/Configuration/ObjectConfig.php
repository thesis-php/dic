<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Internal\Signature;
use Thesis\Dic\Internal\Signature\ReflectionFunctionSignature;
use Thesis\Dic\Location;
use function Thesis\Formatter\formatReflectedClass;

/**
 * @api
 *
 * @template-covariant T of object
 * @extends Config<T>
 *
 * @phpstan-sealed ObjectFactoryConfig
 */
abstract class ObjectConfig extends Config
{
    /**
     * @var \ReflectionClass<covariant T>
     */
    final public readonly \ReflectionClass $reflection;

    /**
     * @internal
     *
     * @param \ReflectionClass<covariant T> $reflection
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        \ReflectionClass $reflection,
        Location $declaredAt,
    ) {
        $this->reflection = $reflection;

        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            declaredAt: $declaredAt,
        );
    }

    final protected function defaultLabel(): string
    {
        return formatReflectedClass($this->reflection);
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

    /**
     * @see Builder\Autoconfiguration::autoconfigure()
     */
    final protected bool $isAutoconfigurable = true;

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

    /**
     * @return FunctionConfig<callable-array>
     */
    final public function method(string $name): FunctionConfig
    {
        /** @var ValueConfig<callable> */
        $value = new ValueConfig(
            builder: $this->builder,
            autowiring: $this->autowiring,
            value: [$this, $name],
            declaredAt: Location::caller(),
        );

        /** @var FunctionConfig<callable-array> */
        return new FunctionConfig(
            builder: $this->builder,
            autowiring: $this->autowiring,
            value: $value,
            declaredAt: Location::caller(),
        );
    }
}
