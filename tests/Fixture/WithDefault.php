<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final readonly class WithDefault
{
    public function __construct(
        public int $number = 42,
    ) {}
}
