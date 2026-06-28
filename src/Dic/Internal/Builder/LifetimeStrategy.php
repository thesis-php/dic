<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Thesis\Dic\BuildError;
use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Ref;

/**
 * How a service decides whether it resolves to a singleton or a scoped instance from its dependencies, and how it
 * behaves when a singleton depends on it. Each case owns its own rule, so the resolution logic lives here rather
 * than in the driver.
 *
 * `Singleton`, `CanBeScoped` and `Scoped` are user-selectable. `Inferred` and `Detached` are assigned internally:
 * `Inferred` to transparent carriers that take on the lifetime of the refs they hold (value/taggedList/method),
 * `Detached` to a `Scoped<T>` handle, which resolves its service lazily inside a scope.
 *
 * @internal
 */
enum LifetimeStrategy
{
    case Singleton;
    case CanBeScoped;
    case Scoped;
    case Inferred;
    case Detached;

    /**
     * @param Ref<mixed> $ref the service being resolved
     * @param \Generator<Dependency, Resolution> $dependencies each dependency edge paired with its own resolution
     */
    public function resolve(Ref $ref, \Generator $dependencies): Resolution
    {
        // A strategy reads only as far as it needs to decide; the driver drains the rest.
        return match ($this) {
            self::Singleton => $this->resolveSingleton($ref, $dependencies),
            self::CanBeScoped, self::Inferred => $this->resolveCarrying($dependencies),
            self::Scoped => Resolution::scoped($this, []),
            self::Detached => Resolution::singleton($this),
        };
    }

    /**
     * A singleton may not depend on a non-singleton: fail on the first offending dependency, naming the real
     * culprit through any transparent carrier.
     *
     * @param Ref<mixed> $ref
     * @param \Generator<Dependency, Resolution> $dependencies
     */
    private function resolveSingleton(Ref $ref, \Generator $dependencies): Resolution
    {
        foreach ($dependencies as $dependency => $resolution) {
            // Declared scoped/canBeScoped fail eagerly even when they currently resolve to a singleton, so a
            // scoped dependency appearing later cannot break this far away. A transparent carrier is a problem
            // only when it actually ended up holding a scoped service, and then we descend to name it. Detached
            // handles are singleton-safe.
            $chain = match ($resolution->strategy) {
                self::Scoped, self::CanBeScoped => [$dependency],
                self::Inferred => $resolution->scopedPath === null ? null : [$dependency, ...$resolution->scopedPath],
                self::Singleton, self::Detached => null,
            };

            if ($chain === null) {
                continue;
            }

            throw BuildError::singletonDependsOnScoped(
                singleton: $ref,
                chain: $chain,
                lifetime: $resolution->strategy === self::CanBeScoped ? self::CanBeScoped : self::Scoped,
            );
        }

        return Resolution::singleton($this);
    }

    /**
     * Take on the lifetime of the dependencies: scoped as soon as one of them is — stop there, recording the
     * path to that first scoped service so a singleton further up can point at it.
     *
     * @param \Generator<Dependency, Resolution> $dependencies
     */
    private function resolveCarrying(\Generator $dependencies): Resolution
    {
        foreach ($dependencies as $dependency => $resolution) {
            if ($resolution->scopedPath !== null) {
                return Resolution::scoped($this, [
                    $dependency,
                    ...$resolution->scopedPath,
                ]);
            }
        }

        return Resolution::singleton($this);
    }
}
