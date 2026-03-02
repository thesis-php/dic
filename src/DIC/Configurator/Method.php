<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Internal\AutowireableFactory as Factory;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Location;
use Thesis\DIC\Ref;
use function Typhoon\Formatter\formatReflectedFunction;
use const Thesis\DIC\scoped;

/**
 * @api
 *
 * @template-covariant T
 * @implements Ref<\Closure(mixed...): T>
 */
final class Method implements Ref
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
     * @param Ref<object> $object
     */
    public function __construct(
        \ReflectionMethod $reflection,
        Ref $object,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
        Autowiring $autowiring,
    ) {
        $this->lifetime = scoped;
        $this->arguments = Factory\Arguments::fromParameters($reflection->getParameters());
        $this->tagger = $tagger;
        $this->description = \sprintf('[%s at %s]', formatReflectedFunction($reflection), $declaredAt);

        $subscriber->onBeforeAssemble(
            function (ServiceRegistrar $registrar) use ($autowiring, $object, $reflection): void {
                $arguments = $this->arguments->autowire($autowiring);

                $name = $reflection->name;

                $registrar->register(
                    ref: $this,
                    factory: $arguments->isEmpty
                        /** @phpstan-ignore method.dynamicName */
                        ? static fn(Container $container) => $container->get($object)->{$name}(...)
                        : new Factory\Method($reflection, $object, $arguments),
                    lifetime: $this->lifetime,
                );
            },
        );
    }
}
