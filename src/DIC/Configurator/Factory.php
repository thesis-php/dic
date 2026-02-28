<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Configurator;
use Thesis\DIC\Internal\AutowirableFunction;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Lifetime;
use Thesis\DIC\Location;
use Typhoon\Type;
use function Thesis\DIC\Internal\Type\fromReflectedType;
use const Thesis\DIC\scoped;
use const Thesis\DIC\singleton;
use const Thesis\DIC\transient;

/**
 * @api
 *
 * @template T
 * @extends Configurator<T>
 */
final class Factory extends Configurator
{
    /**
     * @internal
     *
     * @template R
     * @param callable(): R $factory
     * @return self<R>
     */
    public static function factory(
        callable $factory,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
        Autowiring $autowiring,
    ): self {
        $reflection = new \ReflectionFunction($factory(...));

        /** @var ?Type<contravariant R> */
        $nativeType = fromReflectedType(
            type: $reflection->getReturnType(),
            self: $reflection->getClosureScopeClass()?->name,
            static: $reflection->getClosureCalledClass()?->name,
        );

        $configurator = new self(
            nativeType: $nativeType,
            factory: AutowirableFunction::callable($factory),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            tagger: $tagger,
        );

        $subscriber->onBeforeAssemble(
            static function (ServiceRegistrar $registrar) use ($configurator): void {
                $factory = $configurator->factory->autowire($configurator->autowiring);

                if ($configurator->lifetime === singleton) {
                    $factory->check();
                }

                $registrar->register(
                    ref: $configurator,
                    factory: $factory,
                    lifetime: $configurator->lifetime,
                );
            },
        );

        return $configurator;
    }

    /**
     * @internal
     *
     * @template O of object
     * @param class-string<O> $class
     * @return self<O>
     */
    public static function object(
        string $class,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
        Autowiring $autowiring,
    ): self {
        $configurator = new self(
            nativeType: Type\objectT($class),
            factory: AutowirableFunction::constructor($class),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            tagger: $tagger,
        );

        $subscriber->onBeforeAssemble(
            static function (ServiceRegistrar $registrar) use ($configurator): void {
                $factory = $configurator->factory->autowire($configurator->autowiring);

                if ($configurator->lifetime === singleton) {
                    $factory->check();
                }

                $registrar->register(
                    ref: $configurator,
                    factory: $factory,
                    lifetime: $configurator->lifetime,
                );
            },
        );

        return $configurator;
    }

    /**
     * @param ?Type<contravariant T> $nativeType
     * @param AutowirableFunction<T> $factory
     */
    protected function __construct(
        ?Type $nativeType,
        private AutowirableFunction $factory,
        Location $declaredAt,
        Autowiring $autowiring,
        Tagger $tagger,
    ) {
        parent::__construct(
            nativeType: $nativeType,
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            tagger: $tagger,
        );
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

    /**
     * @param array<non-negative-int|non-empty-string, mixed> $args
     */
    public function args(array $args): static
    {
        $this->factory = $this->factory->withArguments($args);

        return $this;
    }

    /**
     * @param non-negative-int|non-empty-string $param
     */
    public function arg(int|string $param, mixed $arg): static
    {
        $this->factory = $this->factory->withArgument($param, $arg);

        return $this;
    }
}
