<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\Dic\Configurator\DisposeConfigurator;
use Thesis\Dic\Configurator\FactoryConfigurator;
use Thesis\Dic\Configurator\FunctionConfigurator;
use Thesis\Dic\Configurator\ObjectConfigurator;
use Thesis\Dic\Configurator\ScopedConfigurator;
use Thesis\Dic\Configurator\TaggedList;
use Thesis\Dic\Configurator\ValueConfigurator;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\NonCopyable;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;
use Thesis\Dic\TaggedRef;
use Thesis\Dic\Tags;

/**
 * @api
 */
final readonly class Dic
{
    use NonCopyable;

    /**
     * Assembles the container and resolves the ref returned by $module.
     *
     * @template T
     * @param callable(self): Ref<T> $module
     * @return T
     */
    public static function assemble(callable $module): mixed
    {
        $containerBuilder = new ContainerBuilder();

        $ref = $module(new self($containerBuilder));

        return $containerBuilder->build()->get($ref);
    }

    /**
     * @template T
     * @template R
     * @param callable(self): Ref<T> $module
     * @param callable(T): R $function
     * @return R
     */
    public static function run(callable $module, callable $function): mixed
    {
        $containerBuilder = new ContainerBuilder();

        $ref = $module(new self($containerBuilder));

        $container = $containerBuilder->build();

        $value = $container->get($ref);

        try {
            $result = $function($value);
        } catch (\Throwable $error) {
            $container->dispose($error);

            throw $error;
        }

        $container->dispose();

        return $result;
    }

    private Autowiring $autowiring;

    private function __construct(
        private ContainerBuilder $containerBuilder,
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
        return $module(new self($this->containerBuilder));
    }

    /**
     * @template T
     * @param T|Ref<T> $value
     * @return ValueConfigurator<T>
     */
    public function value(mixed $value): ValueConfigurator
    {
        /** @var ValueConfigurator<T> */
        return new ValueConfigurator(
            value: $value,
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    /**
     * @template T
     * @param callable(): T $function
     * @return FactoryConfigurator<T>
     */
    public function factory(callable $function): FactoryConfigurator
    {
        return new FactoryConfigurator(
            function: $function(...),
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ObjectConfigurator<T>
     */
    public function object(string $class): ObjectConfigurator
    {
        return new ObjectConfigurator(
            class: $class,
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    /**
     * @return FunctionConfigurator<\Closure>
     * @phpstan-ignore missingType.callable, missingType.callable
     */
    public function function(callable $function): FunctionConfigurator
    {
        /** @var FunctionConfigurator<\Closure> */
        return new FunctionConfigurator(
            function: $function(...),
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @return ScopedConfigurator<T>
     */
    public function scoped(Ref $ref): ScopedConfigurator
    {
        return new ScopedConfigurator(
            target: $ref,
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    /**
     * @template T
     * @template TTag of Tag<T>
     * @param class-string<TTag>|TTag $tag
     * @param ?callable(TaggedRef<T, TTag>, TaggedRef<T, TTag>): int $sort
     * @return TaggedList<T, TTag>
     */
    public function taggedList(string|Tag $tag, ?callable $sort = null): TaggedList
    {
        return new TaggedList(
            tag: $tag,
            sort: $sort,
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    public function dispose(): DisposeConfigurator
    {
        return new DisposeConfigurator(
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    /**
     * @param callable(Tags): void $handler
     */
    public function onResolveTags(callable $handler): void
    {
        $this->containerBuilder->onResolveTags($handler);
    }
}
