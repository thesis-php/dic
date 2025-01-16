<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Recipe;
use Thesis\DI\Tag;

/**
 * @internal
 * @template TValue
 * @template TTag of Tag<TValue>
 * @implements Recipe<list<TValue>>
 */
final readonly class TaggedList implements Recipe
{
    public Location $location;

    /**
     * @param class-string<TTag> $tag
     */
    public function __construct(
        public string $tag,
        ?Location $location = null,
    ) {
        $this->location = $location ?? Location::caller();
    }
}
