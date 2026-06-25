<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final readonly class Consumer
{
    public function __construct(
        public Greeter $greeter,
    ) {}
}
