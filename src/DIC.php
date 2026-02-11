<?php

declare(strict_types=1);

namespace Thesis;

use Psr\Container\ContainerInterface;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\ModuleMetadata;
use Thesis\DIC\Internal\TaggedContainer;
use Thesis\DIC\Internal\TaggedValues;
use Thesis\DIC\Register;
use Thesis\DIC\Tag;
use Thesis\DIC\TaggedValue;

/**
 * @api
 *
 * @phpstan-type Arguments = array<non-negative-int|non-empty-string, mixed>
 */
final readonly class DIC
{
    /**
     * @template T
     * @param callable(): T $app
     * @param Arguments $arguments
     * @return T
     */
    public static function install(callable $app, array $arguments = []): mixed
    {
        return new self(
            autowiring: new Autowiring(),
            taggedValues: new TaggedValues(),
        )->require($app, $arguments);
    }

    private function __construct(
        private Autowiring $autowiring,
        private TaggedValues $taggedValues,
    ) {}

    /**
     * @template T
     * @param callable(): T $module
     * @param Arguments $arguments
     * @return T
     */
    public function require(callable $module, array $arguments = []): mixed
    {
        $metadata = new ModuleMetadata($module);

        $arguments = $this->autowiring->resolveModuleArguments(
            module: $metadata->reflection,
            dic: new self(
                autowiring: $metadata->inheritAutowiring ? clone $this->autowiring : new Autowiring(),
                taggedValues: $this->taggedValues,
            ),
            arguments: $arguments,
        );

        return $module(...$arguments);
    }

    /**
     * @template T
     * @param T $value
     * @return Register<T>
     */
    public function register(mixed $value): Register
    {
        $this->taggedValues->registerFromAttributes($value);

        return new Register($value, $this->autowiring, $this->taggedValues);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param Arguments $arguments
     * @return T
     */
    public function new(string $class, array $arguments = []): object
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new \LogicException('Not instantiable');
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            if ($arguments !== []) {
                throw new \LogicException('No constructor');
            }
        } else {
            $arguments = $this->autowiring->resolveArguments($constructor, $arguments);
        }

        return $reflection->newLazyProxy(static fn() => new $class(...$arguments));
    }

    /**
     * @template T of object
     * @param callable(): T $factory
     * @return T
     */
    public function objectFrom(callable $factory): object
    {
        $factory = $factory(...);
        $factoryReflection = new \ReflectionFunction($factory);

        $returnType = $factoryReflection->getReturnType();

        if (!$returnType instanceof \ReflectionNamedType) {
            throw new \LogicException();
        }

        $class = $returnType->getName();

        if (!class_exists($class)) {
            throw new \LogicException();
        }

        /** @var class-string<T> $class */
        $classReflection = new \ReflectionClass($class);

        if (!$classReflection->isInstantiable()) {
            throw new \LogicException('Not instantiable');
        }

        $arguments = $this->autowiring->resolveArguments($factoryReflection);

        return $classReflection->newLazyProxy(static fn() => $factory(...$arguments));
    }

    /**
     * @template T
     * @param callable(): T $function
     * @param Arguments $arguments
     * @return \Closure(): T
     */
    public function apply(callable $function, array $arguments = []): \Closure
    {
        $arguments = $this->autowiring->resolveArguments(
            function: new \ReflectionFunction($function(...)),
            arguments: $arguments,
        );

        return static fn() => $function(...$arguments);
    }

    /**
     * @template TValue
     * @template TTag of Tag<TValue>
     * @template TKey of array-key = non-negative-int
     * @param class-string<TTag>|TTag $tag
     * @param ?callable(TaggedValue<TValue, TTag>): (-1|0|1) $sort
     * @param ?callable(TaggedValue<TValue, TTag>): TKey $key
     * @param ?callable(TaggedValue<TValue, TTag>): bool $filter
     * @return \Traversable<TKey, TValue>&\ArrayAccess<TKey, TValue>&\Countable&ContainerInterface
     */
    public function tagged(
        string|Tag $tag,
        ?callable $sort = null,
        ?callable $key = null,
        ?callable $filter = null,
    ): \Traversable {
        return new TaggedContainer($this->taggedValues, $tag, $sort, $key, $filter);
    }
}
