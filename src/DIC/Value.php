<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @template T
 * @implements Service<T>
 */
final readonly class Value implements Service
{
    /**
     * @param T $value
     */
    public function __construct(
        public mixed $value,
    ) {}
}
