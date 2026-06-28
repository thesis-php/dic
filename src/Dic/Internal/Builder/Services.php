<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Thesis\Dic\BuildError;
use Thesis\Dic\Configuration\Config;
use Thesis\Dic\Internal\Container\Factories;
use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\ShouldNotHappen;
use Thesis\Dic\Ref;

/**
 * @internal
 */
final class Services
{
    private bool $resolving = false;

    /**
     * @var \SplObjectStorage<Ref<mixed>, (\Closure(): Factory<mixed>)|true|Resolution>
     */
    private \SplObjectStorage $resolution;

    public function __construct(
        private readonly Factories $singletonFactories,
        private readonly Factories $scopedFactories,
    ) {
        $this->resolution = new \SplObjectStorage();
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param \Closure(): Factory<T> $createFactory
     */
    public function register(Ref $ref, \Closure $createFactory): void
    {
        if ($this->resolving) {
            throw new ShouldNotHappen("Cannot register {$ref}: service resolution has already started");
        }

        if ($this->resolution->offsetExists($ref)) {
            throw new ShouldNotHappen("{$ref} is already registered");
        }

        $this->resolution[$ref] = $createFactory;
    }

    public function resolve(): void
    {
        $this->resolving = true;

        foreach ($this->resolution as $ref) {
            if ($this->resolution[$ref] instanceof \Closure) {
                $this->resolveService($ref);
            }
        }
    }

    /**
     * @param Ref<mixed> $ref
     */
    private function resolveService(Ref $ref): Resolution
    {
        $resolution = $this->resolution[$ref]
            ?? throw new ShouldNotHappen("Cannot resolve {$ref}: it is not registered");

        if ($resolution instanceof Resolution) {
            return $resolution;
        }

        if ($resolution === true) {
            throw new Cycle($ref);
        }

        $this->resolution[$ref] = true;

        try {
            $factory = $resolution();
        } catch (BuildError $error) {
            throw BuildError::invalidServiceFactory($ref, $error);
        }

        $dependencies = $this->resolveDependencies($ref, $factory->dependencies());

        $resolution = $this->strategyOf($ref)->resolve($ref, $dependencies);

        // The strategy may decide before consuming every dependency; resolve the rest so the whole graph is built.
        while ($dependencies->valid()) {
            $dependencies->next();
        }

        if ($resolution->isSingleton) {
            $this->singletonFactories->register($ref, $factory);
        } else {
            $this->scopedFactories->register($ref, $factory);
        }

        $this->resolution->offsetSet($ref, $resolution);

        return $resolution;
    }

    /**
     * @param Ref<mixed> $ref
     * @param iterable<Dependency> $dependencies
     * @return \Generator<Dependency, Resolution>
     */
    private function resolveDependencies(Ref $ref, iterable $dependencies): \Generator
    {
        foreach ($dependencies as $dependency) {
            try {
                yield $dependency => $this->resolveService($dependency->ref);
            } catch (Cycle $cycle) {
                $cycle->prependDependency($dependency);

                if ($cycle->anchor === $ref) {
                    throw BuildError::circularDependency($ref, $cycle->dependencies);
                }

                throw $cycle;
            }
        }
    }

    /**
     * @param Ref<mixed> $ref
     */
    private function strategyOf(Ref $ref): LifetimeStrategy
    {
        if (!$ref instanceof Config) {
            return LifetimeStrategy::Singleton;
        }

        /**
         * @var \Closure(Config<mixed>): LifetimeStrategy
         * @phpstan-ignore varTag.type
         */
        static $get = \Closure::bind(
            closure: static fn(Config $config) => $config->lifetimeStrategy,
            newThis: null,
            newScope: Config::class,
        );

        return $get($ref);
    }
}
