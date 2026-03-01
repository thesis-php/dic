<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Internal\Container;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Location;
use Thesis\DIC\Ref;
use Thesis\DIC\Scope as Wrapper;

/**
 * @api
 *
 * @template-covariant T
 * @implements Ref<Wrapper<T>>
 */
final readonly class Scope implements Ref
{
    use HasDescription;

    /**
     * @use HasTags<Wrapper<T>>
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
                    factory: static fn(Container $container) => new Wrapper($ref, $container),
                );
            },
        );
    }
}
