<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\RewindableGenerator;
use Thesis\DIC\Internal\ServiceTag;
use Thesis\DIC\Internal\Tags;
use Thesis\DIC\Tag;

/**
 * @phpstan-type Arguments = array<non-negative-int|non-empty-string, mixed>
 */
final readonly class DIC
{
    /**
     * @template T
     * @param callable(never, never, never, never, never, never): T $app
     * @param Arguments $arguments
     * @return T
     */
    public static function install(callable $app, array $arguments = []): mixed
    {
        return new self()->require($app, $arguments);
    }

    private function __construct(
        private Autowiring $autowiring = new Autowiring(),
        private Tags $tags = new Tags(),
    ) {}

    /**
     * @template T
     * @param callable(never, never, never, never, never, never): T $component
     * @param Arguments $arguments
     * @return T
     */
    public function require(callable $component, array $arguments = []): mixed
    {
        $autowiring = clone $this->autowiring;
        $autowiring->qualifyObject(new self(tags: $this->tags));

        $arguments = $autowiring->resolveArguments(
            function: new \ReflectionFunction($component(...)),
            arguments: $arguments,
        );

        return $component(...$arguments); // @phpstan-ignore argument.type
    }

    /**
     * @param ?class-string $class
     */
    public function bindObject(object $object, ?string $class = null, string|\UnitEnum $qualifier = ''): void
    {
        $this->autowiring->qualifyObject($object, $class, $qualifier);
    }

    /**
     * @param non-empty-string|\UnitEnum $qualifier
     */
    public function bind(mixed $value, string|\UnitEnum $qualifier): void
    {
        $this->autowiring->qualify($value, $qualifier);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param Arguments $arguments
     * @param list<Tag<T>> $tags
     * @return T
     */
    public function object(string $class, array $arguments = [], array $tags = []): object
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

        $object = $reflection->newLazyProxy(static fn() => new $class(...$arguments));

        $this->tag($object, ...$tags);

        return $object;
    }

    /**
     * @template T
     * @param callable(never, never, never, never, never, never): T $function
     * @param Arguments $arguments
     * @return \Closure(): T
     */
    public function apply(callable $function, array $arguments = []): \Closure
    {
        $arguments = $this->autowiring->resolveArguments(
            function: new \ReflectionFunction($function(...)),
            arguments: $arguments,
        );

        return static fn() => $function(...$arguments); // @phpstan-ignore argument.type
    }

    /**
     * @template T
     * @param callable(never, never, never, never, never, never): T $function
     * @param Arguments $arguments
     * @return T
     */
    public function call(callable $function, array $arguments = []): mixed
    {
        return $this->apply($function, $arguments)();
    }

    /**
     * @no-named-arguments
     * @template T
     * @param T $service
     * @param Tag<T> ...$tags
     */
    public function tag(mixed $service, Tag ...$tags): void
    {
        foreach ($tags as $tag) {
            $this->tags->add(new ServiceTag($service, $tag));
        }
    }

    /**
     * @template T
     * @param class-string<T>|Tag<T> $tag
     * @return iterable<T>
     */
    public function taggedIterator(string|Tag $tag): iterable
    {
        $tags = $this->tags;

        if (\is_string($tag)) {
            return new RewindableGenerator(static function () use ($tags, $tag): \Generator {
                foreach ($tags as $serviceTag) {
                    if ($serviceTag->tag instanceof $tag) {
                        yield $serviceTag->service;
                    }
                }
            });
        }

        return new RewindableGenerator(static function () use ($tags, $tag): \Generator {
            foreach ($tags as $serviceTag) {
                if ($serviceTag->tag === $tag) {
                    yield $serviceTag->service;
                }
            }
        });
    }
}
