<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\Dic\Configuration\Autoconfig;
use Thesis\Dic\Configuration\ClosureConfig;
use Thesis\Dic\Configuration\ObjectConfig;
use Thesis\Dic\Configuration\ScopedConfig;
use Thesis\Dic\Configuration\TaggedListConfig;
use Thesis\Dic\Configuration\ValueConfig;
use Thesis\Dic\DisposalFailed;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Factory\ValueFactory;
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
     * @param callable(self): (Ref<T>|mixed) $module
     * @param callable(T): R $main
     * @return R
     */
    public static function run(callable $module, callable $main): mixed
    {
        $builder = new Builder();

        $result = $module(new self($builder));
        $factory = ValueFactory::from($result);

        $container = $builder->build();

        // a scope is used to resolve a service of any lifetime: singleton or scoped
        $scope = $container->startScope();
        $error = null;

        try {
            $value = $factory->create($scope);

            return $main($value);
        } catch (\Throwable $error) {
            throw $error;
        } finally {
            // Always dispose both the scope and the container, even if a
            // disposer fails; surface any disposer errors without masking $error.
            $disposalErrors = [
                ...$scope->dispose($error),
                ...$container->dispose($error),
            ];

            if ($disposalErrors !== []) {
                throw new DisposalFailed($disposalErrors, $error);
            }
        }
    }

    /**
     * Returns the service the module returns without disposing anything.
     * Meant for tests and debugging modules; otherwise prefer {@see self::run()}.
     *
     * @template T
     * @param callable(self): mixed $module
     * @return ($module is (callable(self): Ref<T>) ? T : mixed)
     */
    public static function assemble(callable $module): mixed
    {
        $builder = new Builder();

        $result = $module(new self($builder));

        // a scope is used to resolve a service of any lifetime: singleton or scoped
        $scope = $builder->build()->startScope();

        return ValueFactory::from($result)->create($scope);
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
     * @param null|callable(): T|array{Ref<class-string|object>, string}|Ref<callable(): T> $factory
     * @return ObjectConfig<T>
     */
    public function object(string $class, null|callable|array|Ref $factory = null): ObjectConfig
    {
        if ($factory !== null && !$factory instanceof Ref) {
            /** @var ValueConfig<callable(): T> */
            $factory = new ValueConfig(
                builder: $this->builder,
                autowiring: $this->autowiring,
                value: $factory,
                declaredAt: Location::caller(),
            );
        }

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
            /** @var ValueConfig<callable> */
            $function = new ValueConfig(
                builder: $this->builder,
                autowiring: $this->autowiring,
                value: $function,
                declaredAt: Location::caller(),
            );
        }

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
