<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Closure;
use Thesis\Dic\Location;

/**
 * @api
 *
 * @extends LifetimeConfigurator<\Closure(?\Throwable): void>
 */
final class DisposerConfigurator extends LifetimeConfigurator
{
    /**
     * @internal
     */
    public function __construct(
        Location $declaredAt,
        protected readonly Autowiring $autowiring,
        protected readonly ContainerBuilder $containerBuilder,
    ) {
        parent::__construct(
            label: 'disposer',
            declaredAt: $declaredAt,
        );
    }

    protected function createFactory(): Factory
    {
        return new Closure(static fn(Container $c) => $c->dispose(...));
    }
}
