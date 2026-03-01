<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @api
 *
 * @template-covariant T
 * @template-covariant TTag of Tag<T>
 */
final readonly class TaggedRef
{
    /**
     * @param Ref<T> $ref
     * @param TTag $tag
     */
    public function __construct(
        public Ref $ref,
        public Tag $tag,
    ) {}
}
