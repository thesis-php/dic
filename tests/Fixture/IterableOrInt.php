<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final readonly class IterableOrInt
{
    /**
     * @param iterable<mixed>|int $value
     */
    public function __construct(
        public iterable|int $value,
    ) {}
}
