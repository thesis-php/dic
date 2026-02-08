<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Value;

use Thesis\DIC\Internal\Resolvable\Data as DataI;
use Thesis\DIC\Tag;

/**
 * @internal
 *
 * @template T
 */
final class Data implements DataI
{
    /**
     * @param T $value
     * @param list<class-string> $bindings
     * @param list<Tag<T>> $tags
     */
    public function __construct(
        public readonly mixed $value,
        public array $bindings = [],
        public array $tags = [],
    ) {}
}
