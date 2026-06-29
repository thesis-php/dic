<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\ProviderFactory;
use Thesis\Dic\Internal\Signature;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;

/**
 * @api
 *
 * @template-covariant T
 * @extends Config<\Closure(): T>
 */
final class ProviderConfig extends Config
{
    /**
     * @internal
     *
     * @param Ref<T> $ref
     */
    public function __construct(
        Builder $builder,
        Autowiring $autowiring,
        private readonly Ref $ref,
        Location $declaredAt,
    ) {
        parent::__construct(
            builder: $builder,
            autowiring: $autowiring,
            label: "provider({$ref})",
            declaredAt: $declaredAt,
            defaultLifetimeStrategy: LifetimeStrategy::Inferred,
        );
    }

    protected Signature $signature {
        get => Signature::ofCallable(static fn() => null);
    }

    protected \ReflectionFunction $reflectionFunction {
        get => new \ReflectionFunction(static fn() => null);
    }

    protected \ReflectionClass $reflectionClass {
        get => new \ReflectionClass(\Closure::class);
    }

    protected function createFactory(): Factory
    {
        return new ProviderFactory($this->ref);
    }
}
