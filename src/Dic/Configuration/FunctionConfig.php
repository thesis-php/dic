<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\BuildError;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\ValueFactory;
use Thesis\Dic\Internal\Signature;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Typhoon\Type\ClosureT;
use function Thesis\Formatter\formatReflectedFunction;

/**
 * @api
 *
 * @template-covariant T
 * @extends Config<T>
 */
final class FunctionConfig extends Config
{
    public readonly \ReflectionFunction|\ReflectionMethod $reflection;

    /**
     * @internal
     *
     * @param Ref<callable> $value
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        private readonly Ref $value,
        Location $declaredAt,
    ) {
        $reflection = $value->reflectionFunction ?? throw BuildError::notCallable($value);

        if ($reflection instanceof \ReflectionMethod && !$reflection->isPublic()) {
            throw BuildError::factoryMethodNotPublic($reflection);
        }

        $this->reflection = $reflection;

        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            declaredAt: $declaredAt,
        );
    }

    protected LifetimeStrategy $lifetimeStrategy {
        get => LifetimeStrategy::Inferred;
    }

    protected function defaultLabel(): string
    {
        return formatReflectedFunction($this->reflection);
    }

    protected ?Signature $signature {
        get => $this->value->signature;
    }

    protected null|\ReflectionFunction|\ReflectionMethod $reflectionFunction {
        get => $this->reflection;
    }

    protected ?\ReflectionClass $reflectionClass {
        get => $this->value->reflectionClass;
    }

    /**
     * @see Builder\Autoconfiguration::autoconfigure()
     */
    protected bool $isAutoconfigurable = true;

    public function doNotAutoconfigure(): static
    {
        $this->isAutoconfigurable = false;

        return $this;
    }

    /**
     * @template C of \Closure
     * @param ClosureT<C> $type
     * @return ClosureConfig<C>
     */
    public function closure(ClosureT $type): ClosureConfig
    {
        return new ClosureConfig(
            builder: $this->builder,
            autowiring: $this->autowiring,
            type: $type,
            function: $this,
            declaredAt: Location::caller(),
        );
    }

    protected function createFactory(): Factory
    {
        return ValueFactory::from($this->value);
    }
}
