<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\ModuleId;
use Thesis\DI\Tag;

/**
 * @internal
 */
final readonly class Tags
{
    /**
     * @param array<class-string<Tag>, non-empty-list<array{ModuleId, Tag}>> $data
     */
    public function __construct(
        private array $data = [],
    ) {}

    /**
     * @template TValue
     * @template TTag of Tag<TValue>
     * @param class-string<TTag> $tag
     * @return list<array{ModuleId<*, TValue>, TTag}>
     */
    public function get(string $tag): array
    {
        /** @var list<array{ModuleId<*, TValue>, TTag}> */
        return $this->data[$tag] ?? [];
    }

    /**
     * @template TValue of TTagValue
     * @template TTagValue
     * @param ModuleId<*, TValue> $id
     * @param list<Tag<TTagValue>> $tags
     */
    public function with(ModuleId $id, array $tags): self
    {
        if ($tags === []) {
            return $this;
        }

        $allTags = $this->data;

        foreach ($tags as $tag) {
            $allTags[$tag::class][] = [$id, $tag];
        }

        return new self($allTags);
    }
}
