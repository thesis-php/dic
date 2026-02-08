<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Tags;

use Thesis\DIC\Service;
use Thesis\DIC\Tag;

/**
 * @internal
 *
 * @template T
 */
final readonly class Pair
{
    /**
     * @param Service<T> $service
     * @param Tag<T> $tag
     */
    public function __construct(
        public Service $service,
        public Tag $tag,
    ) {}
}
