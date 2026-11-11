<?php

declare(strict_types=1);

namespace Thesis\Fixture;

use function Amp\async;

/**
 * Runs $function in two fibers, starting both before awaiting either,
 * so the second call runs while the first one is suspended.
 *
 * @template T
 * @param \Closure(): T $function
 * @return array{T, T}
 */
function concurrently(\Closure $function): array
{
    $first = async($function);
    $second = async($function);

    return [$first->await(), $second->await()];
}
