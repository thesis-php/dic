<?php

declare(strict_types=1);

namespace Thesis\Fixture;

use function Amp\delay;

/**
 * Suspends the fiber while being created, as async I/O would.
 */
final readonly class Suspending
{
    public static function create(): self
    {
        delay(0);

        return new self();
    }

    public function __construct()
    {
        delay(0);
    }
}
