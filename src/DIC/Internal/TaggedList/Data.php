<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\TaggedList;

use Thesis\DIC\Internal\Resolvable\Data as DataI;
use Thesis\DIC\Tag;

/**
 * @internal
 *
 * @template T
 */
final readonly class Data implements DataI
{
    /**
     * @param class-string<Tag<T>>|Tag<T> $tag
     */
    public function __construct(
        public string|Tag $tag,
    ) {}
}
