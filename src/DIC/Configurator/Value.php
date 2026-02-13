<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Configurator;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Location;
use Thesis\DIC\Reference;
use Thesis\DIC\Scoped;
use Typhoon\Type;
use function Thesis\DIC\Internal\Type\nativeTypeOf;

/**
 * @api
 *
 * @template T
 * @extends Configurator<T>
 */
final class Value extends Configurator
{
    /**
     * @internal
     *
     * @template V
     * @param V $value
     * @return self<V>
     */
    public static function value(
        mixed $value,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
        Autowiring $autowiring,
    ): self {
        $configurator = new self(
            nativeType: nativeTypeOf($value),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            tagger: $tagger,
        );

        $subscriber->onBeforeAssemble(
            static function (ServiceRegistrar $registrar) use ($value, $configurator): void {
                $registrar->register(
                    reference: $configurator,
                    factory: static fn() => $value,
                );
            },
        );

        return $configurator;
    }

    /**
     * @internal
     *
     * @template V
     * @param Reference<V> $value
     * @return self<Scoped<V>>
     */
    public static function scoped(
        Reference $value,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
        Autowiring $autowiring,
    ): self {
        /** @var self<Scoped<V>> */
        $configurator = new self(
            nativeType: Type\objectT(Scoped::class),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            tagger: $tagger,
        );

        $subscriber->onBeforeAssemble(
            static function (ServiceRegistrar $registrar) use ($value, $configurator): void {
                $registrar->register(
                    reference: $configurator,
                    factory: static fn(Container $container) => new Scoped($value, $container),
                );
            },
        );

        return $configurator;
    }
}
