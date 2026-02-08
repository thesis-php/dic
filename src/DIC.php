<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\DIC\FactoryConfigurator;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Factory;
use Thesis\DIC\Internal\Resolvable;
use Thesis\DIC\Internal\TaggedList;
use Thesis\DIC\Internal\Tags;
use Thesis\DIC\Service;
use Thesis\DIC\Tag;
use Thesis\DIC\ValueConfigurator;

final readonly class DIC
{
    /**
     * @template T
     * @param callable(self): T $app
     * @return T
     */
    public static function setup(callable $app): mixed
    {
        /** @var \SplObjectStorage<Resolvable<*, *>, Autowiring> */
        $resolvables = new \SplObjectStorage();
        $dic = new self($resolvables);

        $result = $app($dic);

        $tags = new Tags();

        foreach ($resolvables as $resolvable) {
            $resolvable->register($resolvables->getInfo(), $tags);
        }

        foreach ($resolvables as $resolvable) {
            $resolvable->resolve($resolvables->getInfo(), $tags);
        }

        return $result;
    }

    private Autowiring $autowiring;

    /**
     * @param \SplObjectStorage<Resolvable<*, *>, Autowiring> $resolvables
     */
    private function __construct(
        private \SplObjectStorage $resolvables,
    ) {
        $this->autowiring = new Autowiring();
    }

    /**
     * @template T
     * @param callable(self): T $component
     * @return T
     */
    public function require(callable $component): mixed
    {
        return $component(new self($this->resolvables));
    }

    /**
     * @template TAs of object
     * @template T of TAs
     * @param class-string<TAs> $class
     * @param T|Service<T> $service
     */
    public function bind(string $class, object $service): void
    {
        $this->autowiring->register($service, [$class]);
    }

    /**
     * @template T
     * @param T $value
     * @return ValueConfigurator<T>
     */
    public function value(mixed $value): ValueConfigurator
    {
        return $this->register(new DIC\Internal\Value($value));
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return FactoryConfigurator<T>
     */
    public function object(string $class): FactoryConfigurator
    {
        return $this->register(Factory::object($class));
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return FactoryConfigurator<T>
     */
    public function bindObject(string $class): FactoryConfigurator
    {
        return $this->object($class)->bindAs($class);
    }

    /**
     * @template T
     * @param callable(never, never, never, never, never, never): T $factory
     * @return FactoryConfigurator<T>
     */
    public function factory(callable $factory): FactoryConfigurator
    {
        return $this->register(Factory::factory($factory));
    }

    /**
     * @template T
     * @param class-string<Tag<T>>|Tag<T> $tag
     * @return Service<list<T>>
     */
    public function taggedList(string|Tag $tag): Service
    {
        return $this->register(new TaggedList($tag));
    }

    /**
     * @template T of Resolvable<*, *>
     * @param T $definition
     * @return T
     */
    private function register(Resolvable $definition): Resolvable
    {
        $this->resolvables[$definition] = $this->autowiring;

        return $definition;
    }
}
