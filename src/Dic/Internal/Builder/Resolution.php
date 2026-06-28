<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Thesis\Dic\Internal\Dependency;

/**
 * The outcome of resolving a service: the strategy it was resolved under, the concrete lifetime it settled on,
 * and — when it is a transparent carrier ({@see LifetimeStrategy::Inferred}) that ended up holding a scoped
 * service — the edges from it down to that scoped service, so a singleton depending on it can name the real culprit.
 *
 * @internal
 */
final class Resolution
{
    public static function singleton(LifetimeStrategy $lifetimeStrategy): self
    {
        return new self($lifetimeStrategy, null);
    }

    /**
     * @param list<Dependency> $scopedPath
     */
    public static function scoped(
        LifetimeStrategy $lifetimeStrategy,
        array $scopedPath,
    ): self {
        return new self($lifetimeStrategy, $scopedPath);
    }

    public bool $isSingleton {
        get => $this->scopedPath === null;
    }

    /**
     * @param ?list<Dependency> $scopedPath
     */
    private function __construct(
        public readonly LifetimeStrategy $strategy,
        public readonly ?array $scopedPath,
    ) {}
}
