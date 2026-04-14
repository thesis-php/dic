<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Internal\AutowireableFactory as Factory;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Location;
use Thesis\DIC\Ref;
use function Thesis\Formatter\formatReflectedType;
use const Thesis\DIC\singleton;

/**
 * @api
 *
 * @template-covariant T
 * @implements Ref<T>
 */
final class Call implements Ref
{
    use HasArgs;
    use HasDescription;
    use HasLifetime;

    /**
     * @use HasTags<T>
     */
    use HasTags;

    /**
     * @internal
     *
     * @param callable(): T $factory
     */
    public function __construct(
        callable $factory,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
        Autowiring $autowiring,
    ) {
        $factory = $factory(...);

        $this->tagger = $tagger;
        $this->arguments = Factory\Arguments::fromFunction($factory);
        $type = new \ReflectionFunction($factory)->getReturnType();
        $this->description = \sprintf('[%s at %s]', $type === null ? 'mixed' : formatReflectedType($type), $declaredAt);

        $subscriber->onBeforeAssemble(
            function (ServiceRegistrar $registrar) use ($factory, $autowiring): void {
                $arguments = $this->arguments->autowire($autowiring);

                if ($this->lifetime === singleton) {
                    $arguments->ensureResolvable();
                }

                $registrar->register(
                    ref: $this,
                    factory: $arguments->isEmpty ? $factory : new Factory\Call($factory, $arguments),
                    lifetime: $this->lifetime,
                );
            },
        );
    }
}
