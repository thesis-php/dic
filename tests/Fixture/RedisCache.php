<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final class RedisCache implements Cache
{
    public function get(string $key): string
    {
        return 'redis';
    }
}
