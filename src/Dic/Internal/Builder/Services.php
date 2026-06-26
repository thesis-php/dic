<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Thesis\Dic\Configuration\ScopedConfig;
use Thesis\Dic\Error\CircularDependency;
use Thesis\Dic\Error\InvalidConfigurationError;
use Thesis\Dic\Error\SingletonDependsOnScoped;
use Thesis\Dic\Internal\Container\Factories;
use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Lifetime;
use Thesis\Dic\Internal\ShouldNotHappen;
use Thesis\Dic\Ref;

/**
 * @internal
 *
 * @phpstan-type ResolvedLifetime = Lifetime::Singleton|Lifetime::Scoped
 */
final class Services
{
    private bool $resolving = false;

    /**
     * @var \SplObjectStorage<Ref<mixed>, \Closure(): Factory<mixed>|true|ResolvedLifetime>
     */
    private \SplObjectStorage $resolution;

    /**
     * @var \WeakMap<Ref<mixed>, Lifetime>
     */
    private \WeakMap $lifetimes;

    public function __construct(
        private readonly Factories $singletonFactories,
        private readonly Factories $scopedFactories,
    ) {
        $this->resolution = new \SplObjectStorage();
        $this->lifetimes = new \WeakMap();
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

    /**
     * @param Ref<mixed> $ref
     */
    public function setDefaultLifetime(Ref $ref, Lifetime $lifetime): void
    {
        if ($this->resolving) {
            throw new ShouldNotHappen("Cannot change the lifetime of {$ref}: service resolution has already started");
        }

        $this->lifetimes[$ref] ??= $lifetime;
    }

    /**
     * @param Ref<mixed> $ref
     */
    public function setLifetime(Ref $ref, Lifetime $lifetime): void
    {
        if ($this->resolving) {
            throw new ShouldNotHappen("Cannot change the lifetime of {$ref}: service resolution has already started");
        }

        $this->lifetimes[$ref] = $lifetime;
    }

    public function resolve(): void
    {
        $this->resolving = true;

        foreach ($this->resolution as $ref) {
            if ($this->resolution[$ref] instanceof \Closure) {
                $this->resolveRef($ref);
            }
        }
    }

    /**
     * @param Ref<mixed> $ref
     * @return ResolvedLifetime
     */
    private function resolveRef(Ref $ref): Lifetime
    {
        $resolution = $this->resolution[$ref]
            ?? throw new ShouldNotHappen("Cannot resolve {$ref}: it is not registered");

        if ($resolution instanceof Lifetime) {
            return $resolution;
        }

        if ($resolution === true) {
            throw new Cycle($ref);
        }

        $this->resolution[$ref] = true;

        try {
            $factory = $resolution();
        } catch (ShouldNotHappen $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new InvalidConfigurationError($ref, $exception);
        }

        $resolvedLifetime = $this->resolveRefLifetime($ref, $factory->dependencies());

        match ($resolvedLifetime) {
            Lifetime::Singleton => $this->singletonFactories->register($ref, $factory),
            Lifetime::Scoped => $this->scopedFactories->register($ref, $factory),
        };

        $this->resolution->offsetSet($ref, $resolvedLifetime);

        return $resolvedLifetime;
    }

    /**
     * @param Ref<mixed> $ref
     * @param iterable<Dependency> $dependencies
     * @return ResolvedLifetime
     */
    public function resolveRefLifetime(Ref $ref, iterable $dependencies): Lifetime
    {
        $lifetime = $this->getLifetime($ref);

        $shouldBeScoped = false;
        $invalidSingletonDependencies = [];

        foreach ($dependencies as $dependency) {
            $dependencyResolvedLifetime = $this->resolveDependencyLifetime($ref, $dependency);

            if ($lifetime === Lifetime::CanBeScoped) {
                if ($dependencyResolvedLifetime === Lifetime::Scoped) {
                    $shouldBeScoped = true;
                }

                continue;
            }

            if ($lifetime === Lifetime::Singleton && !$ref instanceof ScopedConfig) {
                $dependencyLifetime = $this->getLifetime($dependency->ref);

                if ($dependencyLifetime !== Lifetime::Singleton) {
                    $invalidSingletonDependencies[] = [$dependency, $dependencyLifetime];
                }
            }
        }

        if ($invalidSingletonDependencies !== []) {
            throw new SingletonDependsOnScoped($ref, $invalidSingletonDependencies);
        }

        return match ($lifetime) {
            Lifetime::Singleton => Lifetime::Singleton,
            Lifetime::CanBeScoped => $shouldBeScoped ? Lifetime::Scoped : Lifetime::Singleton,
            Lifetime::Scoped => Lifetime::Scoped,
        };
    }

    /**
     * @param Ref<mixed> $ref
     */
    private function getLifetime(Ref $ref): Lifetime
    {
        return $this->lifetimes[$ref] ?? Lifetime::Singleton;
    }

    /**
     * @param Ref<mixed> $ref
     * @return ResolvedLifetime
     */
    private function resolveDependencyLifetime(Ref $ref, Dependency $dependency): Lifetime
    {
        try {
            return $this->resolveRef($dependency->ref);
        } catch (Cycle $cycle) {
            $cycle->prependDependency($dependency);

            if ($cycle->anchor === $ref) {
                throw new CircularDependency($ref, $cycle->dependencies);
            }

            throw $cycle;
        }
    }
}
