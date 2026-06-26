<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Error;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
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
 * @template T of mixed
 * @extends Config<T>
 */
final class MethodConfig extends Config
{
    /**
     * @internal
     *
     * @param Ref<object> $object
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        public readonly \ReflectionMethod $function,
        private readonly Ref $object,
        Location $declaredAt,
    ) {
        if (!$function->isPublic()) {
            throw Error::factoryMethodNotPublic($function);
        }

        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            declaredAt: $declaredAt,
        );
    }

    protected function defaultLabel(): string
    {
        return formatReflectedFunction($this->function);
    }

    protected Signature $signature {
        get => Signature::ofMethod($this->function);
    }

    public null $class {
        get => null;
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
        return ValueFactory::from([$this->object, $this->function->name]);
    }
}
