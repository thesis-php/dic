<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\DIC\Configurator\Factory;
use Thesis\DIC\Configurator\Func;
use Thesis\DIC\Configurator\Tagged;
use Thesis\DIC\Configurator\Value;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Binding;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Location;
use Thesis\DIC\Ref;
use Thesis\DIC\Scoped;
use Thesis\DIC\Tag;
use Thesis\DIC\Tags;
use Typhoon\Type;

/**
 * @api
 *
 * @phpstan-type Args = array<non-negative-int|non-empty-string, mixed>
 */
final readonly class DIC
{
    /**
     * @template T
     * @param callable(self): Ref<T> $app
     * @return T
     */
    public static function install(callable $app): mixed
    {
        return Container::assemble(
            static fn(Subscriber $subscriber, Tagger $tagger) => $app(new self(
                subscriber: $subscriber,
                tagger: $tagger,
            )),
        );
    }

    private Autowiring $autowiring;

    private function __construct(
        private Subscriber $subscriber,
        private Tagger $tagger,
        private Autowiring $parentAutowiring = new Autowiring(),
    ) {
        $this->autowiring = new Autowiring();
    }

    public function inheritAutowiring(): void
    {
        $this->autowiring->inheritAutowiringFrom($this->parentAutowiring);
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param Type<contravariant T> $type
     */
    public function bind(Ref $ref, Type $type, string|\Stringable|\UnitEnum $qualifier = ''): void
    {
        $this->autowiring->addBinding(new Binding($ref, $type, $qualifier));
    }

    /**
     * @template T
     * @param callable(self): T $module
     * @return T
     */
    public function require(callable $module): mixed
    {
        return $module(new self(
            subscriber: $this->subscriber,
            tagger: $this->tagger,
            parentAutowiring: $this->autowiring,
        ));
    }

    /**
     * @template T
     * @param T $value
     * @return Value<T>
     */
    public function value(mixed $value): Value
    {
        return Value::value(
            value: $value,
            declaredAt: Location::fromBacktrace(-1),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
            autowiring: $this->autowiring,
        );
    }

    /**
     * @template T
     * @param callable(): T $factory
     * @return Factory<T>
     */
    public function factory(callable $factory): Factory
    {
        return Factory::factory(
            factory: $factory,
            declaredAt: Location::fromBacktrace(-1),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
            autowiring: $this->autowiring,
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return Factory<T>
     */
    public function object(string $class): Factory
    {
        return Factory::object(
            class: $class,
            declaredAt: Location::fromBacktrace(-1),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
            autowiring: $this->autowiring,
        );
    }

    /**
     * @template T
     * @param Ref<T> $value
     * @return Value<Scoped<T>>
     */
    public function scoped(Ref $value): Value
    {
        return Value::scoped(
            value: $value,
            declaredAt: Location::fromBacktrace(-1),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
            autowiring: $this->autowiring,
        );
    }

    /**
     * @param Ref<object> $object
     * @param non-empty-string $name
     * @return Func<mixed>
     */
    public function method(Ref $object, string $name): Func
    {
        return Func::method(
            object: $object,
            name: $name,
            declaredAt: Location::fromBacktrace(-1),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
            autowiring: $this->autowiring,
        );
    }

    /**
     * @template T
     * @param class-string<Tag<T>>|Tag<T> $tag
     * @return Tagged<list<T>>
     */
    public function taggedList(string|Tag $tag): Tagged
    {
        return Tagged::list(
            tag: $tag,
            declaredAt: Location::fromBacktrace(-1),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
            autowiring: $this->autowiring,
        );
    }

    /**
     * @param callable(Tags): void $listener
     */
    public function onResolveTags(callable $listener): void
    {
        $this->subscriber->onResolveTags($listener);
    }
}
