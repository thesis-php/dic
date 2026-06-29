<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\LifetimeStrategy;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;

/**
 * @api
 *
 * @template-covariant T of object = object
 */
final class ObjectAutoconfig
{
    /**
     * @var Ref<T>
     */
    public Ref $ref {
        get => $this->config;
    }

    /**
     * @var \ReflectionClass<covariant T>
     */
    public \ReflectionClass $reflection {
        get => $this->config->reflection;
    }

    public readonly Attributes $attributes;

    /**
     * @var \Generator<int, MethodAutoconfig>
     */
    public \Generator $methods {
        get {
            foreach ($this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                yield new MethodAutoconfig(
                    object: $this->config,
                    reflection: $method,
                );
            }
        }
    }

    /**
     * @internal
     *
     * @param ObjectConfig<T> $config
     */
    public function __construct(
        private readonly Builder $builder,
        private readonly ObjectConfig $config,
    ) {
        $this->attributes = new Attributes($config->reflection);
    }

    /**
     * @template X of object
     * @param class-string<X> $class
     * @phpstan-assert-if-true self<X> $this
     */
    public function is(string $class): bool
    {
        return match (true) {
            class_exists($class) => $this->reflection->isSubclassOf($class),
            interface_exists($class) => $this->reflection->implementsInterface($class),
            default => false,
        };
    }

    /**
     * @phpstan-assert-if-true self<callable-object> $this
     */
    public function isInvokable(): bool
    {
        return $this->reflection->hasMethod('__invoke');
    }

    public function defaultScoped(): static
    {
        $this->builder->setDefaultLifetimeStrategy($this->config, LifetimeStrategy::Scoped);

        return $this;
    }

    public function defaultCanBeScoped(): static
    {
        $this->builder->setDefaultLifetimeStrategy($this->config, LifetimeStrategy::CanBeScoped);

        return $this;
    }

    /**
     * @param Tag<T> $tag
     */
    public function tag(Tag $tag): static
    {
        $this->config->tag($tag);

        return $this;
    }

    /**
     * @param callable(T, ?\Throwable): void $disposer
     */
    public function disposer(callable $disposer): static
    {
        $this->config->disposer($disposer);

        return $this;
    }
}
