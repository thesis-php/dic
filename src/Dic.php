<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\Dic\Configuration\Autoconfig;
use Thesis\Dic\Configuration\ClosureConfig;
use Thesis\Dic\Configuration\ObjectConfig;
use Thesis\Dic\Configuration\ScopedConfig;
use Thesis\Dic\Configuration\TaggedListConfig;
use Thesis\Dic\Configuration\ValueConfig;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\NonCopyable;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;
use Thesis\Dic\TaggedRef;
use Thesis\Dic\TaggedRefs;
use Typhoon\Type\ClosureT;

/**
 * @api
 */
final readonly class Dic
{
    use NonCopyable;

    /**
     * The recommended entry point. Runs $function with the service the module
     * returns, then disposes the scope and the container (even on throw).
     *
     * @template T
     * @template R
     * @param callable(self): Ref<T> $module
     * @param callable(T): R $function
     * @return R
     */
    public static function run(callable $module, callable $function): mixed
    {
        $builder = new Builder();

        $ref = $module(new self($builder));

        $container = $builder->build();

        $scope = $container->startScope();
        $error = null;

        try {
            return $function($scope->get($ref));
        } catch (\Throwable $error) {
            throw $error;
        } finally {
            $scope->dispose($error);
            $container->dispose($error);
        }
    }

    /**
     * Returns the service the module returns without disposing anything.
     * Meant for tests and debugging modules; otherwise prefer {@see self::run()}.
     *
     * @template T
     * @param callable(self): Ref<T> $module
     * @return T
     */
    public static function assemble(callable $module): mixed
    {
        $builder = new Builder();

        $ref = $module(new self($builder));

        return $builder
            ->build()
            ->startScope()
            ->get($ref);
    }

    private Autowiring $autowiring;

    private function __construct(
        private Builder $builder,
    ) {
        $this->autowiring = new Autowiring();
    }

    /**
     * @template T
     * @param callable(self): T $module
     * @return T
     */
    public function require(callable $module): mixed
    {
        return $module(new self($this->builder));
    }

    /**
     * @template T
     * @param T|Ref<T> $value
     * @return ValueConfig<T>
     */
    public function value(mixed $value): ValueConfig
    {
        /** @var ValueConfig<T> */
        return new ValueConfig(
            builder: $this->builder,
            autowiring: $this->autowiring,
            value: $value,
            declaredAt: Location::caller(),
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param null|Ref<callable(): T>|callable(): T $factory
     * @return ObjectConfig<T>
     */
    public function object(string $class, null|Ref|callable $factory = null): ObjectConfig
    {
        return new ObjectConfig(
            builder: $this->builder,
            autowiring: $this->autowiring,
            class: new \ReflectionClass($class),
            factory: $factory,
            declaredAt: Location::caller(),
        );
    }

    /**
     * @template T of \Closure
     * @param ClosureT<T> $type
     * @param callable|array{Ref<class-string|object>, string}|Ref<callable> $function
     * @return ClosureConfig<T>
     */
    public function closure(ClosureT $type, callable|array|Ref $function): ClosureConfig
    {
        if (!$function instanceof Ref) {
            $function = new ValueConfig(
                builder: $this->builder,
                autowiring: $this->autowiring,
                value: $function,
                declaredAt: Location::caller(),
            );
        }

        /** @var Ref<callable> $function */
        return new ClosureConfig(
            builder: $this->builder,
            autowiring: $this->autowiring,
            type: $type,
            function: $function,
            declaredAt: Location::caller(),
        );
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @return ScopedConfig<T>
     */
    public function scoped(Ref $ref): ScopedConfig
    {
        return new ScopedConfig(
            builder: $this->builder,
            autowiring: $this->autowiring,
            ref: $ref,
            declaredAt: Location::caller(),
        );
    }

    /**
     * @template T
     * @template TTag of Tag<T>
     * @param class-string<TTag>|TTag $tag
     * @param ?callable(TaggedRef<T, TTag>, TaggedRef<T, TTag>): int $sort
     * @return TaggedListConfig<T, TTag>
     */
    public function taggedList(string|Tag $tag, ?callable $sort = null): TaggedListConfig
    {
        return new TaggedListConfig(
            builder: $this->builder,
            autowiring: $this->autowiring,
            tag: $tag,
            sort: $sort,
            declaredAt: Location::caller(),
        );
    }

    /**
     * @param callable(TaggedRefs): void $listener
     */
    public function onTagResolution(callable $listener): void
    {
        $this->builder->onTagResolution($listener);
    }

    /**
     * @param callable(Autoconfig<*>): void $configurator
     */
    public function autoconfigure(callable $configurator): void
    {
        $this->builder->addAutoconfigurator($configurator);
    }
}
