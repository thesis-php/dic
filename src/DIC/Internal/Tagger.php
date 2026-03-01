<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Ref;
use Thesis\DIC\Tag;
use Thesis\DIC\TaggedRef;
use Thesis\DIC\Tags;

/**
 * @internal
 */
final class Tagger implements Tags
{
    /**
     * @var list<TaggedRef<*, *>>
     */
    private array $taggedRefs = [];

    private bool $closed = false;

    /**
     * @template T
     * @param Ref<T> $ref
     * @param Tag<T> $tag
     */
    public function tag(Ref $ref, Tag $tag): void
    {
        if ($this->closed) {
            throw new \LogicException('Cannot add tags anymore');
        }

        $this->taggedRefs[] = new TaggedRef($ref, $tag);
    }

    public function tagged(Tag|string $tag): array
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

    public function close(): void
    {
        $this->closed = true;
    }
}
