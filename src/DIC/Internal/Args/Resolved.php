<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Args;

use Thesis\DIC\Service;

/**
 * @internal
 *
 * @implements \IteratorAggregate<int, mixed>
 */
final readonly class Resolved implements \IteratorAggregate
{
    /**
     * @param list<mixed> $values
     */
    public function __construct(
        private array $values,
    ) {}

    public function getIterator(): \Traversable
    {
        foreach ($this->values as $value) {
            yield self::unwrap($value);
        }
    }

    private static function unwrap(mixed $value): mixed
    {
        if ($value instanceof Service) {
            return $value->value;
        }

        if (\is_array($value)) {
            return array_map(self::unwrap(...), $value);
        }

        return $value;
    }
}
