<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Internal\Tags\Pair;
use Thesis\DIC\Service;
use Thesis\DIC\Tag;

/**
 * @internal
 *
 * @implements \IteratorAggregate<int, Pair<*>>
 */
final class Tags implements \IteratorAggregate
{
    /**
     * @var list<Pair<*>>
     */
    private array $serviceTags = [];

    /**
     * @template T
     * @param Service<T> $service
     * @param list<Tag<T>> $tags
     */
    public function register(Service $service, array $tags): void
    {
        foreach ($tags as $tag) {
            $this->serviceTags[] = new Pair($service, $tag);
        }
    }

    public function getIterator(): \Traversable
    {
        yield from $this->serviceTags;
    }
}
