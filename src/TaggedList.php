<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Location;

/**
 * @api
 * @template T
 * @template TTag of Tag<T>
 */
final readonly class TaggedList
{
    public Location $location;

    /**
     * @param class-string<TTag> $tag
     * @param ?callable(T, TTag): non-negative-int $priority
     */
    public function __construct(
        public string $tag,
        public mixed $priority = null,
        ?Location $location = null,
    ) {
        $this->location = $location ?? Location::caller();
    }
}

/**
 * @api
 * @template T
 * @template TTag of Tag<T>
 * @param class-string<TTag> $tag
 * @param ?callable(T, TTag): non-negative-int $priority
 * @return TaggedList<T, TTag>
 */
function taggedList(string $tag, ?callable $priority = null): TaggedList
{
    return new TaggedList($tag, $priority, Location::caller());
}
