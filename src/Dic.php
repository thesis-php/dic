<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\Dic\Configuration\FunctionAutoconfig;
use Thesis\Dic\Configuration\FunctionConfig;
use Thesis\Dic\Configuration\ObjectAutoconfig;
use Thesis\Dic\Configuration\ObjectConfig;
use Thesis\Dic\Configuration\ProviderConfig;
use Thesis\Dic\Configuration\ScopedConfig;
use Thesis\Dic\Configuration\TaggedListConfig;
use Thesis\Dic\Configuration\ValueConfig;
use Thesis\Dic\DisposalFailed;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\Autoconfiguration;
use Thesis\Dic\Internal\Factory\ValueFactory;
use Thesis\Dic\Internal\NonCopyable;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;
use Thesis\Dic\TaggedRef;
use Thesis\Dic\TaggedRefs;
use function Thesis\Dic\Internal\caller;

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
     * @param Module<T|Ref<T>> $module
     * @param callable(T): R $main
     * @return R
     */
    public static function run(Module $module, callable $main): mixed
    {
        $builder = new Builder();

        $result = new self($builder)->import($module);

        $container = $builder->build();

        // a scope is used to resolve a service of any lifetime: singleton or scoped
        $scope = $container->startScope();
        $error = null;

        try {
            $value = ValueFactory::from($result)->create($scope);

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
     * @param Module<T|Ref<T>> $module
     * @return T
     */
    public static function build(Module $module): mixed
    {
        $builder = new Builder();

        $result = new self($builder)->import($module);

        // a scope is used to resolve a service of any lifetime: singleton or scoped
        $scope = $builder->build()->startScope();

        return ValueFactory::from($result)->create($scope);
    }

    private Autoconfiguration $autoconfiguration;

    private Autowiring $autowiring;

    private function __construct(
        private Builder $builder,
    ) {
        $this->autoconfiguration = new Autoconfiguration($builder);
        $this->autowiring = new Autowiring();
    }

    /**
     * Runs $module in a fresh isolated Dic scope and returns whatever it returns.
     *
     * @template T
     * @param Module<T> $module
     * @return T
     */
    public function import(Module $module): mixed
    {
        $dic = new self($this->builder);
        $result = $module->configure($dic);
        $dic->autoconfiguration->start();

        return $result;
    }

    /**
     * Calls $configurator on this Dic and returns whatever it returns; its bindings stay visible in the current scope.
     *
     * @template T
     * @param callable(self): T $configurator
     * @return T
     */
    public function apply(callable $configurator): mixed
    {
        return $configurator($this);
    }

    /**
     * Declares $value as a service.
     *
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
            declaredAt: caller(),
        );
    }

    /**
     * Declares a callable as a service; chain {@see FunctionConfig::closure()} to expose it as a typed \Closure.
     *
     * @param callable|array{Ref<object>, string}|Ref<callable> $function
     * @return FunctionConfig<callable>
     */
    public function function(callable|array|Ref $function): FunctionConfig
    {
        $declaredAt = caller();

        if (!$function instanceof Ref) {
            /** @var ValueConfig<callable> */
            $function = new ValueConfig(
                builder: $this->builder,
                autowiring: $this->autowiring,
                value: $function,
                declaredAt: $declaredAt,
            );
        }

        /** @var FunctionConfig<callable> */
        return new FunctionConfig(
            builder: $this->builder,
            autoconfiguration: $this->autoconfiguration,
            autowiring: $this->autowiring,
            value: $function,
            declaredAt: $declaredAt,
        );
    }

    /**
     * Declares $class as an object service; constructor parameters are autowired unless a $factory is provided.
     *
     * @template T of object
     * @param class-string<T> $class
     * @param null|callable(): T|array{Ref<class-string|object>, string}|Ref<callable(): T> $factory
     * @return ObjectConfig<T>
     */
    public function object(string $class, null|callable|array|Ref $factory = null): ObjectConfig
    {
        $declaredAt = caller();

        if (!($factory === null || $factory instanceof Ref)) {
            /** @var ValueConfig<callable(): T> */
            $factory = new ValueConfig(
                builder: $this->builder,
                autowiring: $this->autowiring,
                value: $factory,
                declaredAt: $declaredAt,
            );
        }

        return new ObjectConfig(
            builder: $this->builder,
            autoconfiguration: $this->autoconfiguration,
            autowiring: $this->autowiring,
            reflection: new \ReflectionClass($class),
            factory: $factory,
            declaredAt: $declaredAt,
        );
    }

    /**
     * Declares a Scoped<T> handle that opens a fresh scope, resolves $ref inside it, and disposes on exit.
     *
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
            declaredAt: caller(),
        );
    }

    /**
     * Declares a lazy \Closure(): T that resolves $value on each call without opening a scope.
     *
     * @template T
     * @param T|Ref<T> $value
     * @return ProviderConfig<T>
     */
    public function provider(mixed $value): ProviderConfig
    {
        $declaredAt = caller();

        if (!$value instanceof Ref) {
            $value = new ValueConfig(
                builder: $this->builder,
                autowiring: $this->autowiring,
                value: $value,
                declaredAt: $declaredAt,
            );
        }

        /** @var Ref<T> $value */
        return new ProviderConfig(
            builder: $this->builder,
            autowiring: $this->autowiring,
            ref: $value,
            declaredAt: $declaredAt,
        );
    }

    /**
     * Declares a list of every service tagged with $tag, resolved once after all modules have run.
     *
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
            declaredAt: caller(),
        );
    }

    /**
     * Registers a listener called for every object() service declared on this Dic, before the container is built.
     *
     * @param callable(ObjectAutoconfig<object>): void $listener
     */
    public function onObject(callable $listener): void
    {
        $this->autoconfiguration->onObject($listener);
    }

    /**
     * Registers a listener called for every function() / method() service declared on this Dic, before the container
     * is built.
     *
     * @param callable(FunctionAutoconfig): void $listener
     */
    public function onFunction(callable $listener): void
    {
        $this->autoconfiguration->onFunction($listener);
    }

    /**
     * Registers a listener called once after all modules run, receiving the complete set of tagged services.
     *
     * @param callable(TaggedRefs): void $listener
     */
    public function onTagResolution(callable $listener): void
    {
        $this->builder->onTagResolution($listener);
    }
}
