<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @api
 *
 * @template TValue
 * @template TTag of Tag<TValue>
 */
final readonly class TaggedValue
{
    /**
     * @param TValue $value
     * @param TTag $tag
     */
    public function __construct(
        public mixed $value,
        public Tag $tag,
    ) {}
}
