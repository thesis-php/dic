<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Internal\Container;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Location;
use Thesis\DIC\Ref;
use Thesis\DIC\Scoped;

/**
 * @api
 *
 * @template-covariant T
 * @implements Ref<Scoped<T>>
 */
final readonly class ScopedOf implements Ref
{
    use HasDescription;

    /**
     * @use HasTags<Scoped<T>>
     */
    use HasTags;

    /**
     * @internal
     *
     * @param Ref<T> $ref
     */
    public function __construct(
        Ref $ref,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
    ) {
        $this->tagger = $tagger;
        $this->description = "[scoped {$ref} at {$declaredAt}]";

        $subscriber->onBeforeAssemble(
            function (ServiceRegistrar $registrar) use ($ref): void {
                $registrar->register(
                    ref: $this,
                    factory: static fn(Container $container) => new Scoped($ref, $container),
                );
            },
        );
    }
}
