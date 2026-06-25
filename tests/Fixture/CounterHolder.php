<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final readonly class CounterHolder
{
    public function __construct(
        public Counter $counter,
    ) {}
}
