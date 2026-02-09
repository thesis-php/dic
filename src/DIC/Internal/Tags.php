<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

/**
 * @internal
 *
 * @implements \IteratorAggregate<int, ServiceTag<*>>
 */
final class Tags implements \IteratorAggregate
{
    /**
     * @var list<ServiceTag<*>>
     */
    private array $tagged = [];

    /**
     * @param ServiceTag<*> $tagged
     */
    public function add(ServiceTag $tagged): void
    {
        $this->tagged[] = $tagged;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->tagged;
    }
}
