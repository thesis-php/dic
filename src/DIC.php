<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\DIC\Configurator\Factory;
use Thesis\DIC\Configurator\Func;
use Thesis\DIC\Configurator\Obj;
use Thesis\DIC\Configurator\Scope;
use Thesis\DIC\Configurator\TaggedList;
use Thesis\DIC\Configurator\Value;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Binding;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Location;
use Thesis\DIC\Ref;
use Thesis\DIC\Tag;
use Thesis\DIC\TaggedRef;
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
        return new Value(
            value: $value,
            declaredAt: Location::caller(),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
        );
    }

    /**
     * @template T
     * @param callable(): T $factory
     * @return Factory<T>
     */
    public function factory(callable $factory): Factory
    {
        return new Factory(
            factory: $factory,
            declaredAt: Location::caller(),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
            autowiring: $this->autowiring,
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return Obj<T>
     */
    public function object(string $class): Obj
    {
        return new Obj(
            class: $class,
            declaredAt: Location::caller(),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
            autowiring: $this->autowiring,
        );
    }

    /**
     * @template T
     * @param callable(): T $function
     * @return Func<T>
     */
    public function function(callable $function): Func
    {
        return new Func(
            function: $function,
            declaredAt: Location::caller(),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
            autowiring: $this->autowiring,
        );
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @return Scope<T>
     */
    public function scope(Ref $ref): Scope
    {
        return new Scope(
            ref: $ref,
            declaredAt: Location::caller(),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
        );
    }

    /**
     * @template T
     * @template TTag of Tag<T>
     * @param class-string<TTag>|TTag $tag
     * @param ?callable(TaggedRef<T, TTag>, TaggedRef<T, TTag>): (-1|0|1) $sort
     * @return TaggedList<T, TTag>
     */
    public function taggedList(string|Tag $tag, ?callable $sort = null): TaggedList
    {
        return new TaggedList(
            tag: $tag,
            sort: $sort,
            declaredAt: Location::caller(),
            subscriber: $this->subscriber,
            tagger: $this->tagger,
        );
    }

    /**
     * @template T
     * @param Type<contravariant T> $type
     * @param T|Ref<T> $value
     */
    public function bind(Type $type, mixed $value): void
    {
        $this->bindQualifier($type, '', $value);
    }

    /**
     * @template T
     * @param Type<contravariant T> $type
     * @param T|Ref<T> $value
     */
    public function bindQualifier(Type $type, string|\Stringable|\UnitEnum $qualifier, mixed $value): void
    {
        $this->autowiring->addBinding(new Binding($type, $qualifier, $value));
    }

    /**
     * @template T
     * @param T|Ref<T> $value
     * @param Tag<T> $tag
     */
    public function tag(mixed $value, Tag $tag): void
    {
        if (!$value instanceof Ref) {
            $value = $this->value($value);
        }

        /** @phpstan-ignore argument.type */
        $this->tagger->tag($value, $tag);
    }

    /**
     * @param callable(Tags): void $listener
     */
    public function onResolveTags(callable $listener): void
    {
        $this->subscriber->onResolveTags($listener);
    }
}
