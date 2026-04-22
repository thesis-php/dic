<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Container;

use Thesis\DIC\Internal\Container;
use Thesis\DIC\Lifetime;
use Thesis\DIC\Ref;

/**
 * @internal
 */
final class ServiceRegistrar
{
    public private(set) Singletons $singletons;

    public private(set) Scopeds $scopeds;

    public private(set) Transients $transients;

    public function __construct()
    {
        $this->singletons = Singletons::new();
        $this->scopeds = Scopeds::new();
        $this->transients = Transients::new();
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param callable(Container): T $factory
     */
    public function register(Ref $ref, callable $factory, Lifetime $lifetime = Lifetime::Singleton): void
    {
        match ($lifetime) {
            Lifetime::Singleton => $this->singletons = $this->singletons->with($ref, $factory),
            Lifetime::Scoped => $this->scopeds = $this->scopeds->with($ref, $factory),
            Lifetime::Transient => $this->transients = $this->transients->with($ref, $factory),
        };
    }
}
