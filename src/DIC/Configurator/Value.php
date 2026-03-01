<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Location;
use Thesis\DIC\Ref;
use function Typhoon\Formatter\format;

/**
 * @api
 *
 * @template T
 * @implements Ref<T>
 */
final readonly class Value implements Ref
{
    use HasDescription;

    /**
     * @use HasTags<T>
     */
    use HasTags;

    /**
     * @internal
     *
     * @param T $value
     */
    public function __construct(
        mixed $value,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
    ) {
        $this->tagger = $tagger;
        $this->description = \sprintf('[%s at %s]', format($value), $declaredAt);

        $subscriber->onBeforeAssemble(
            function (ServiceRegistrar $registrar) use ($value): void {
                $registrar->register(
                    ref: $this,
                    factory: static fn() => $value,
                );
            },
        );
    }
}
