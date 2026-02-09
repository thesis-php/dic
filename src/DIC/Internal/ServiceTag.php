<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Tag;

/**
 * @template T
 */
final readonly class ServiceTag
{
    /**
     * @param T $service
     * @param Tag<T> $tag
     */
    public function __construct(
        public mixed $service,
        public Tag $tag,
    ) {}
}
