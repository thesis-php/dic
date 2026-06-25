<?php

declare(strict_types=1);

namespace Thesis\Fixture;

/**
 * Mutable service used to observe instance identity across singleton/scoped lifetimes.
 */
final class Counter
{
    public int $value = 0;
}
