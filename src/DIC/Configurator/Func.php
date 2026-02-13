<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Configurator;
use Thesis\DIC\Internal\AutowirableFunction;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Lifetime;
use Thesis\DIC\Location;
use Thesis\DIC\Reference;
use Typhoon\Type;
use const Thesis\DIC\scoped;
use const Thesis\DIC\singleton;
use const Thesis\DIC\transient;

/**
 * @api
 *
 * @template T
 * @extends Configurator<\Closure(mixed...): T>
 */
final class Func extends Configurator
{
    /**
     * @internal
     *
     * @template R
     * @param callable(): R $function
     * @return self<R>
     */
    public static function function(
        callable $function,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
        Autowiring $autowiring,
    ): self {
        $configurator = new self(
            function: AutowirableFunction::callable($function),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            tagger: $tagger,
        );

        $subscriber->onBeforeAssemble(
            static function (ServiceRegistrar $registrar) use ($configurator): void {
                $registrar->register(
                    reference: $configurator,
                    factory: $configurator->function->autowire($configurator->autowiring)->apply(...),
                    lifetime: $configurator->lifetime,
                );
            },
        );

        return $configurator;
    }

    /**
     * @internal
     *
     * @param Reference<object> $object
     * @param non-empty-string $name
     * @return self<mixed>
     */
    public static function method(
        Reference $object,
        string $name,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
        Autowiring $autowiring,
    ): self {
        $type = self::referenceType($object) ?? throw new \LogicException();

        if (!$type instanceof Type\NamedObjectT) {
            throw new \LogicException("{$object} is not a named object reference");
        }

        try {
            $reflection = new \ReflectionMethod($type->class, $name);
        } catch (\ReflectionException) {
            throw new \LogicException("{$object} does not have method `%s`");
        }

        if (!$reflection->isPublic()) {
            throw new \LogicException("Method `{$name}` on {$object} is not public");
        }

        $configurator = new self(
            function: AutowirableFunction::containerAwareProxy(
                /** @phpstan-ignore method.dynamicName */
                function: static fn(Container $container, mixed ...$args): mixed => $container->get($object)->{$name}(...$args),
                reflection: $reflection,
            ),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            tagger: $tagger,
        );

        $subscriber->onBeforeAssemble(
            static function (ServiceRegistrar $registrar) use ($configurator): void {
                $registrar->register(
                    reference: $configurator,
                    factory: $configurator->function->autowire($configurator->autowiring)->apply(...),
                    lifetime: $configurator->lifetime,
                );
            },
        );

        return $configurator;
    }

    /**
     * @param AutowirableFunction<T> $function
     */
    protected function __construct(
        private AutowirableFunction $function,
        Location $declaredAt,
        Autowiring $autowiring,
        Tagger $tagger,
    ) {
        parent::__construct(
            nativeType: Type\objectT(\Closure::class),
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
        $this->function = $this->function->withArguments($args);

        return $this;
    }

    /**
     * @param non-negative-int|non-empty-string $param
     */
    public function arg(int|string $param, mixed $arg): static
    {
        $this->function = $this->function->withArgument($param, $arg);

        return $this;
    }
}
