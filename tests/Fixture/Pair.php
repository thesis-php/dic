<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final readonly class Pair
{
    public function __construct(
        public Holder $first,
        public Holder $second,
    ) {}
}
