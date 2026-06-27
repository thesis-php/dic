<?php

declare(strict_types=1);

namespace Thesis\Fixture;

interface Cache
{
    public function get(string $key): ?string;
}
