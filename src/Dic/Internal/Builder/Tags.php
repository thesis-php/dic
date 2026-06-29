<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Thesis\Dic\BuildError;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;
use Thesis\Dic\TaggedRef;
use Thesis\Dic\TaggedRefs;

/**
 * @internal
 */
final class Tags
{
    private bool $resolving = false;

    /**
     * @var list<TaggedRef<*, *>>
     */
    private array $taggedRefs = [];

    /**
     * @template T
     * @param Ref<T> $ref
     * @param Tag<T> $tag
     */
    public function add(Ref $ref, Tag $tag): void
    {
        if ($this->resolving) {
            throw BuildError::taggedDuringResolution($ref, $tag);
        }

        $this->taggedRefs[] = new TaggedRef($ref, $tag);
    }

    /**
     * @var list<callable(TaggedRefs): void>
     */
    private array $listeners = [];

    /**
     * @param callable(TaggedRefs): void $listener
     */
    public function onResolution(callable $listener): void
    {
        $this->listeners[] = $listener;
    }

    public function resolve(): void
    {
        $this->resolving = true;

        $taggedRefs = new TaggedRefs($this->taggedRefs);

        $this->taggedRefs = [];

        for ($i = 0; $i < \count($this->listeners); ++$i) {
            $this->listeners[$i]($taggedRefs);
        }

        $this->listeners = [];
    }
}
