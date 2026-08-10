<?php

declare(strict_types=1);

namespace Thesis\Dic;

/**
 * @api
 */
interface TaggedRefs
{
    /**
     * @template T
     * @template TTag of Tag<T>
     * @param class-string<TTag>|TTag $tag
     * @return list<TaggedRef<T, TTag>>
     */
    public function find(Tag|string $tag): array;
}
