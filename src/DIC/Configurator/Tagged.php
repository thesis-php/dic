<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Configurator;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Lifetime;
use Thesis\DIC\Location;
use Thesis\DIC\Reference;
use Thesis\DIC\Tag;
use Thesis\DIC\Tags;
use Typhoon\Type;
use const Thesis\DIC\scoped;
use const Thesis\DIC\singleton;
use const Thesis\DIC\transient;

/**
 * @api
 *
 * @template T
 * @extends Configurator<T>
 */
final class Tagged extends Configurator
{
    /**
     * @internal
     *
     * @template V
     * @param class-string<Tag<V>>|Tag<V> $tag
     * @return self<list<V>>
     */
    public static function list(
        string|Tag $tag,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
        Autowiring $autowiring,
    ): self {
        /** @var self<list<V>> */
        $configurator = new self(
            nativeType: Type\arrayT,
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            tagger: $tagger,
        );

        /** @var list<Reference<V>> */
        $references = [];

        $subscriber->onResolveTags(
            static function (Tags $tags) use ($tag, &$references): void {
                $references = array_column($tags->taggedBy($tag), 'reference');
            },
        );

        $subscriber->onBeforeAssemble(
            static function (ServiceRegistrar $registrar) use (&$references, $configurator): void {
                $registrar->register(
                    reference: $configurator,
                    factory: static fn(Container $container) => array_map($container->get(...), $references),
                    lifetime: $configurator->lifetime,
                );
            },
        );

        return $configurator;
    }

    private Lifetime $lifetime = singleton;

    public function singleton(): static
    {
        $this->lifetime = singleton;

        return $this;
    }

    public function scoped(): static
    {
        $this->lifetime = scoped;

        return $this;
    }

    public function transient(): static
    {
        $this->lifetime = transient;

        return $this;
    }
}
