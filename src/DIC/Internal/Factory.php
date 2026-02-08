<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\FactoryConfigurator;
use Thesis\DIC\Tag;

/**
 * @internal
 *
 * @template T
 * @extends Resolvable<T, Factory\Data<T>>
 * @implements FactoryConfigurator<T>
 */
final class Factory extends Resolvable implements FactoryConfigurator
{
    /**
     * @template TObject of object
     * @param class-string<TObject> $class
     * @return self<TObject>
     */
    public static function object(string $class): self
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new \LogicException();
        }

        return new self(
            factory: static fn(mixed ...$args) => new $class(...$args),
            parameters: $reflection->getConstructor()?->getParameters() ?? [],
        );
    }

    /**
     * @template TValue
     * @param callable(never, never, never, never, never, never): TValue $factory
     * @return self<TValue>
     */
    public static function factory(callable $factory): self
    {
        $factory = $factory(...);

        return new self(
            factory: $factory, // @phpstan-ignore argument.type
            parameters: new \ReflectionFunction($factory)->getParameters(),
        );
    }

    /**
     * @param \Closure(mixed...): T $factory
     * @param list<\ReflectionParameter> $parameters
     */
    private function __construct(
        \Closure $factory,
        array $parameters,
    ) {
        parent::__construct(new Factory\Data(
            factory: $factory,
            args: new Args($parameters),
        ));
    }

    public function bindAs(string $class): static
    {
        // todo check class
        $this->data->bindings[] = $class;

        return $this;
    }

    public function arg(int|string $param, mixed $arg): static
    {
        $this->data->args->setOne($param, $arg);

        return $this;
    }

    public function args(array $args): static
    {
        $this->data->args->set($args);

        return $this;
    }

    public function doNotAutowireArgs(): static
    {
        $this->data->args->doNotAutowire();

        return $this;
    }

    public function tag(Tag $tag): static
    {
        $this->data->tags[] = $tag;

        return $this;
    }

    public function transient(): static
    {
        $this->data->transient = true;

        return $this;
    }

    public function register(Autowiring $autowiring, Tags $tags): void
    {
        $autowiring->register($this, $this->data->bindings);
        $tags->register($this, $this->data->tags);
    }

    protected function toFactory(Autowiring $autowiring, Tags $tags): \Closure
    {
        $factory = $this->data->factory;
        $args = $this->data->args->resolve($autowiring);
        $valueFactory = static fn() => $factory(...$args);

        return $this->data->transient ? $valueFactory : new Singleton($valueFactory)(...);
    }
}
