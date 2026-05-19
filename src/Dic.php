<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\Dic\Configurator\MethodConfigurator;
use Thesis\Dic\Configurator\ObjectConfigurator;
use Thesis\Dic\Configurator\ScopedConfigurator;
use Thesis\Dic\Configurator\SignatureConfigurator;
use Thesis\Dic\Configurator\TaggedListConfigurator;
use Thesis\Dic\Configurator\ValueConfigurator;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Lifetime;
use Thesis\Dic\Internal\NonCopyable;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;
use Thesis\Dic\TaggedRef;
use Thesis\Dic\Tags;
use Typhoon\Type\ClosureT;

/**
 * @api
 */
final readonly class Dic
{
    use NonCopyable;

    /**
     * @template T
     * @template R
     * @param callable(self): Ref<T> $module
     * @param callable(T): R $function
     * @return R
     */
    public static function run(callable $module, callable $function): mixed
    {
        /** @var \ReflectionProperty */
        static $lifetimeProperty = new \ReflectionProperty(Ref::class, 'lifetime');

        $containerBuilder = new ContainerBuilder();

        $ref = $module(new self($containerBuilder));

        $root = $containerBuilder->build();

        if ($lifetimeProperty->getValue($ref) === Lifetime::Scoped) {
            $scope = $root->startScope();
            $value = $scope->get($ref);
        } else {
            $scope = null;
            $value = $root->get($ref);
        }

        try {
            $result = $function($value);
        } catch (\Throwable $error) {
            $scope?->dispose($error);
            $root->dispose($error);

            throw $error;
        }

        $scope?->dispose(null);
        $root->dispose(null);

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
     * @template T of object
     * @param class-string<T> $class
     * @param null|Ref<callable(): T>|callable(): T $factory
     * @return ObjectConfigurator<T>
     */
    public function object(string $class, null|Ref|callable $factory = null): ObjectConfigurator
    {
        return new ObjectConfigurator(
            class: $class,
            factory: $factory,
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    /**
     * @param Ref<object> $object
     */
    public function method(Ref $object, string $name): MethodConfigurator
    {
        return new MethodConfigurator(
            object: $object,
            name: $name,
            declaredAt: Location::caller(),
            autowiring: $this->autowiring,
            containerBuilder: $this->containerBuilder,
        );
    }

    /**
     * @template T of \Closure
     * @param ClosureT<T> $signature
     * @param callable|Ref<callable> $implementation
     * @return SignatureConfigurator<T>
     */
    public function signature(ClosureT $signature, callable|Ref $implementation): SignatureConfigurator
    {
        $declaredAt = Location::caller();

        if (!$implementation instanceof Ref) {
            $implementation = new ValueConfigurator(
                value: $implementation,
                declaredAt: $declaredAt,
                autowiring: $this->autowiring,
                containerBuilder: $this->containerBuilder,
            );
        }

        return new SignatureConfigurator(
            signature: $signature,
            implementation: $implementation,
            declaredAt: $declaredAt,
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
            ref: $ref,
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
     * @return TaggedListConfigurator<T, TTag>
     */
    public function taggedList(string|Tag $tag, ?callable $sort = null): TaggedListConfigurator
    {
        return new TaggedListConfigurator(
            tag: $tag,
            sort: $sort,
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
