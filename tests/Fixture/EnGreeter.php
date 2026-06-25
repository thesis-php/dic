<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final class EnGreeter implements Greeter
{
    public function greet(): string
    {
        return 'hello';
    }
}
