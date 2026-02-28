<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Container;

use Thesis\DIC\Internal\Container;
use Thesis\DIC\Lifetime;
use Thesis\DIC\Ref;
use const Thesis\DIC\scoped;
use const Thesis\DIC\singleton;
use const Thesis\DIC\transient;

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
    public function register(Ref $ref, callable $factory, Lifetime $lifetime = singleton): void
    {
        match ($lifetime) {
            singleton => $this->singletons = $this->singletons->with($ref, $factory),
            scoped => $this->scopeds = $this->scopeds->with($ref, $factory),
            transient => $this->transients = $this->transients->with($ref, $factory),
        };
    }
}
