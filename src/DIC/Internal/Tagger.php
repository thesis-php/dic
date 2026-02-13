<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Reference;
use Thesis\DIC\Tag;
use Thesis\DIC\TaggedReference;
use Thesis\DIC\Tags;

/**
 * @internal
 */
final class Tagger implements Tags
{
    /**
     * @var list<TaggedReference<*, *>>
     */
    private array $taggedReferences = [];

    private bool $closed = false;

    /**
     * @template T
     * @param Reference<T> $reference
     * @param Tag<T> $tag
     */
    public function tag(Reference $reference, Tag $tag): void
    {
        if ($this->closed) {
            throw new \LogicException('Cannot add tags anymore');
        }

        $this->taggedReferences[] = new TaggedReference($reference, $tag);
    }

    public function taggedBy(Tag|string $tag): array
    {
        /** @phpstan-ignore return.type */
        return array_values(
            array_filter(
                $this->taggedReferences,
                \is_string($tag)
                    ? static fn(TaggedReference $tr) => $tr->tag instanceof $tag
                    : static fn(TaggedReference $tr) => $tr->tag === $tag,
            ),
        );
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
