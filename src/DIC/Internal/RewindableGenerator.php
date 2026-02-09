<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

/**
 * @template T
 * @implements \IteratorAggregate<T>
 */
final readonly class RewindableGenerator implements \IteratorAggregate
{
    /**
     * @param \Closure(): \Generator<T> $generator
     */
    public function __construct(
        private \Closure $generator,
    ) {}

    public function getIterator(): \Traversable
    {
        yield from ($this->generator)();
    }
}
