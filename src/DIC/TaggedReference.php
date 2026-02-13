<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @api
 *
 * @template T
 * @template TTag of Tag<T>
 */
final readonly class TaggedReference
{
    /**
     * @param Reference<T> $reference
     * @param TTag $tag
     */
    public function __construct(
        public Reference $reference,
        public Tag $tag,
    ) {}
}
