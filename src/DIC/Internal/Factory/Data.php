<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Factory;

use Thesis\DIC\Internal\Args;
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
     * @param \Closure(mixed...): T $factory
     * @param list<class-string> $bindings
     * @param list<Tag<T>> $tags
     */
    public function __construct(
        public readonly \Closure $factory,
        public readonly Args $args,
        public array $bindings = [],
        public array $tags = [],
        public bool $transient = false,
    ) {}
}
