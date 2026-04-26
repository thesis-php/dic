<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Configurator;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Value;
use Thesis\Dic\Lifetime;
use Thesis\Dic\Location;
use function Thesis\Formatter\format;

/**
 * @api
 *
 * @template T
 * @extends Configurator<T>
 */
final class ValueConfigurator extends Configurator
{
    public Lifetime $lifetime { get => Lifetime::Singleton; }

    /**
     * @internal
     *
     * @param T $value
     */
    public function __construct(
        private readonly mixed $value,
        Location $declaredAt,
        protected readonly Autowiring $autowiring,
        protected readonly ContainerBuilder $containerBuilder,
    ) {
        parent::__construct(
            label: format($value),
            declaredAt: $declaredAt,
        );
    }

    protected function createFactory(): Factory
    {
        return Value::from($this->value);
    }
}
