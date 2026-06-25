<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Ref;

/**
 * Internal control-flow marker carrying a dependency cycle as it is unwound back
 * to the node where it closes (the anchor).
 *
 * @internal
 */
final class Cycle extends \LogicException
{
    /**
     * @var list<Dependency> cycle edges, from the anchor back to itself
     */
    public private(set) array $dependencies = [];

    /**
     * @param Ref<mixed> $anchor node where the cycle closes
     */
    public function __construct(
        public readonly Ref $anchor,
    ) {
        parent::__construct();
    }

    public function prependDependency(Dependency $dependency): void
    {
        array_unshift($this->dependencies, $dependency);
    }
}
