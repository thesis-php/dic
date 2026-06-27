<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final class ApcuCache implements Cache
{
    public function get(string $key): string
    {
        return 'apcu';
    }
}
