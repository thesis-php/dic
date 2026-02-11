<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Psr\Container\ContainerInterface;
use Thesis\DIC\Tag;
use Thesis\DIC\TaggedValue;

/**
 * @internal
 *
 * @template TValue
 * @template TTag of Tag<TValue>
 * @template TKey of array-key = non-negative-int
 * @implements \IteratorAggregate<TKey, TValue>
 * @implements \ArrayAccess<TKey, TValue>
 */
final class TaggedContainer implements \IteratorAggregate, \ArrayAccess, \Countable, ContainerInterface
{
    /**
     * @var \Closure(): array<TKey, TValue>|array<TKey, TValue>
     */
    private \Closure|array $values;

    /**
     * @var array<TKey, TValue>
     */
    private array $resolved {
        get => $this->values instanceof \Closure
            ? $this->values = ($this->values)()
            : $this->values;
    }

    /**
     * @param class-string<TTag>|TTag $tag
     * @param ?callable(TaggedValue<TValue, TTag>): (-1|0|1) $sort
     * @param ?callable(TaggedValue<TValue, TTag>): TKey $key
     * @param ?callable(TaggedValue<TValue, TTag>): bool $filter
     */
    public function __construct(
        TaggedValues $taggedValues,
        string|Tag $tag,
        ?callable $sort = null,
        ?callable $key = null,
        ?callable $filter = null,
    ) {
        $this->values = static function () use ($taggedValues, $tag, $sort, $filter, $key): array {
            $taggedValues = array_filter(
                $taggedValues->list,
                \is_string($tag)
                    ? static fn(TaggedValue $tv): bool => $tv->tag instanceof $tag
                    : static fn(TaggedValue $tv): bool => $tv->tag === $tag,
            );

            if ($filter !== null) {
                $taggedValues = array_filter($taggedValues, $filter); // @phpstan-ignore argument.type
            }

            if ($sort !== null) {
                usort($taggedValues, $sort); // @phpstan-ignore argument.type
            }

            if ($key === null) {
                return array_column($taggedValues, 'value');
            }

            return array_combine(
                array_map($key, $taggedValues), // @phpstan-ignore argument.type
                array_column($taggedValues, 'value'),
            );
        };
    }

    public function getIterator(): \Traversable
    {
        yield from $this->resolved;
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->resolved);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (\array_key_exists($offset, $this->resolved)) {
            return $this->resolved[$offset];
        }

        throw new \OutOfBoundsException();
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException();
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException();
    }

    public function count(): int
    {
        return \count($this->resolved);
    }

    public function has(string $id): bool
    {
        return \array_key_exists($id, $this->resolved);
    }

    public function get(string $id): mixed
    {
        if (\array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        throw new \OutOfBoundsException();
    }
}
