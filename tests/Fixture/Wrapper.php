<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final readonly class Wrapper
{
    public function __construct(
        public mixed $value,
    ) {}
}
