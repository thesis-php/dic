<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Internal\AutowireableFactory\Arguments;
use Thesis\DIC\Internal\AutowireableFactory\Func as Factory;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Location;
use Thesis\DIC\Ref;
use function Thesis\Formatter\formatFunction;
use const Thesis\DIC\scoped;

/**
 * @api
 *
 * @template-covariant T
 * @implements Ref<\Closure(mixed...): T>
 */
final class Func implements Ref
{
    use HasArgs;
    use HasDescription;
    use HasLifetime;

    /**
     * @use HasTags<\Closure(mixed...): T>
     */
    use HasTags;

    /**
     * @internal
     *
     * @param callable(): T $function
     */
    public function __construct(
        callable $function,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
        Autowiring $autowiring,
    ) {
        $function = $function(...);

        $this->lifetime = scoped;
        $this->arguments = Arguments::fromFunction($function);
        $this->tagger = $tagger;
        $this->description = \sprintf('[%s at %s]', formatFunction($function), $declaredAt);

        $subscriber->onBeforeAssemble(
            function (ServiceRegistrar $registrar) use ($autowiring, $function): void {
                $arguments = $this->arguments->autowire($autowiring);

                $registrar->register(
                    ref: $this,
                    factory: $arguments->isEmpty ? static fn() => $function : new Factory($function, $arguments),
                    lifetime: $this->lifetime,
                );
            },
        );
    }
}
