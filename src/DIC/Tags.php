<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @api
 */
interface Tags
{
    /**
     * @template T
     * @template TTag of Tag<T>
     * @param class-string<TTag>|TTag $tag
     * @return list<TaggedRef<T, TTag>>
     */
    public function tagged(Tag|string $tag): array;
}
