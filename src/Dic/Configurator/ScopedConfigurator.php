<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Configurator;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Closure;
use Thesis\Dic\Lifetime;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Thesis\Dic\Scoped;

/**
 * @api
 *
 * @template T
 * @extends Configurator<Scoped<T>>
 */
final class ScopedConfigurator extends Configurator
{
    public Lifetime $lifetime { get => Lifetime::Singleton; }

    /**
     * @internal
     *
     * @param Ref<T> $target
     */
    public function __construct(
        private readonly Ref $target,
        Location $declaredAt,
        protected readonly Autowiring $autowiring,
        protected readonly ContainerBuilder $containerBuilder,
    ) {
        parent::__construct(
            label: "scoped {$target}",
            declaredAt: $declaredAt,
        );
    }

    protected function createFactory(): Factory
    {
        $ref = $this->target;

        if ($this->target->lifetime !== Lifetime::Scoped) {
            throw new \LogicException(\sprintf(
                'Cannot wrap in Scoped a %s service %s',
                strtolower($ref->lifetime->name),
                $ref,
            ));
        }

        return new Closure(static fn(Container $c) => new Scoped($ref, $c));
    }
}
