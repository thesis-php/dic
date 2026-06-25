<?php

declare(strict_types=1);

namespace Thesis\Dic;

/**
 * @api
 */
final readonly class TaggedRefs
{
    /**
     * @internal
     *
     * @param list<TaggedRef<*, *>> $taggedRefs
     */
    public function __construct(
        private array $taggedRefs,
    ) {}

    /**
     * @template T
     * @template TTag of Tag<T>
     * @param class-string<TTag>|TTag $tag
     * @return list<TaggedRef<T, TTag>>
     */
    public function find(Tag|string $tag): array
    {
        /** @phpstan-ignore return.type */
        return array_values(
            array_filter(
                $this->taggedRefs,
                \is_string($tag)
                    ? static fn(TaggedRef $tr) => $tr->tag instanceof $tag
                    : static fn(TaggedRef $tr) => $tr->tag === $tag,
            ),
        );
    }
}
