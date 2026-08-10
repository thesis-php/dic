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
final class Tags implements TaggedRefs
{
    public function __construct()
    {
        $this->requestedInstances = new \SplObjectStorage();
    }

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
        if ($this->resolved) {
            throw BuildError::cannotAddResolvedTag($ref, $tag);
        }

        if (array_any($this->requestedClasses, static fn(bool $v, string $class) => $tag instanceof $class)
            || isset($this->requestedInstances[$tag])
        ) {
            throw BuildError::cannotAddAlreadyRequestedTag($ref, $tag);
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
        if ($this->resolved) {
            throw BuildError::cannotListenResolvedTags();
        }

        $this->listeners[] = $listener;
    }

    /**
     * @var array<class-string, true>
     */
    private array $requestedClasses = [];

    /**
     * @var \SplObjectStorage<Tag<*>, true>
     */
    private \SplObjectStorage $requestedInstances;

    public function find(Tag|string $tag): array
    {
        if (\is_string($tag)) {
            $this->requestedClasses[$tag] = true;

            /** @phpstan-ignore return.type */
            return array_values(
                array_filter(
                    $this->taggedRefs,
                    static fn(TaggedRef $tr) => $tr->tag instanceof $tag,
                ),
            );
        }

        $this->requestedInstances[$tag] = true;

        /** @phpstan-ignore return.type */
        return array_values(
            array_filter(
                $this->taggedRefs,
                static fn(TaggedRef $tr) => $tr->tag === $tag,
            ),
        );
    }

    private bool $resolved = false;

    public function resolve(): void
    {
        for ($i = 0; $i < \count($this->listeners); ++$i) {
            $this->listeners[$i]($this);
        }

        $this->listeners = [];
        $this->resolved = true;
    }
}
