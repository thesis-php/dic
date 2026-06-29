<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\ScopedFactory;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Thesis\Dic\Scoped;

/**
 * @api
 *
 * @template-covariant T
 * @extends Config<Scoped<T>>
 */
final class ScopedConfig extends Config
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
            label: "Scoped<{$ref->label}>",
            declaredAt: $declaredAt,
            defaultLifetimeStrategy: LifetimeStrategy::Detached,
        );
    }

    protected null $signature { get => null; }

    protected null $reflectionFunction { get => null; }

    protected \ReflectionClass $reflectionClass {
        get => new \ReflectionClass(Scoped::class);
    }

    protected function createFactory(): Factory
    {
        return new ScopedFactory($this->ref);
    }
}
