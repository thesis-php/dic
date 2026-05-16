<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Value;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use function Thesis\Formatter\format;

/**
 * @api
 *
 * @template T
 * @extends Ref<T>
 */
final class ValueConfigurator extends Ref
{
    /** @use Internal\Bind<T> */
    use Internal\Bind;

    /** @use Internal\Tag<T> */
    use Internal\Tag;

    /** @use Internal\Disposer<T> */
    use Internal\Disposer;

    /**
     * @internal
     */
    public function __construct(
        private readonly mixed $value,
        Location $declaredAt,
        Autowiring $autowiring,
        ContainerBuilder $containerBuilder,
    ) {
        parent::__construct(
            label: format($value),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            containerBuilder: $containerBuilder,
        );
    }

    protected function createFactory(): Factory
    {
        /** @var Factory<T> */
        return Value::from($this->value);
    }
}
