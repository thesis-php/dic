<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final class RuGreeter implements Greeter
{
    public function greet(): string
    {
        return 'привет';
    }
}
