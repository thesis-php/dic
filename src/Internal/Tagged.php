<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\ModuleId;
use Thesis\DI\Tag;

/**
 * @internal
 */
final readonly class Tagged
{
    public static function create(): self
    {
        return new self([]);
    }

    /**
     * @param array<class-string<Tag>, non-empty-list<array{ModuleId, Tag}>> $data
     */
    private function __construct(
        private array $data,
    ) {}

    /**
     * @template T
     * @template TTag of Tag<T>
     * @param class-string<TTag> $tag
     * @return list<array{ModuleId<*, T>, TTag}>
     */
    public function get(string $tag): array
    {
        /** @var list<array{ModuleId<*, T>, TTag}> */
        return $this->data[$tag] ?? [];
    }

    /**
     * @template T of TTagValue
     * @template TTagValue
     * @param ModuleId<*, T> $id
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
