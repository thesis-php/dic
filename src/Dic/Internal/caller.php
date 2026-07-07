<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Location;

/**
 * @internal
 */
function caller(): Location
{
    /** @var non-falsy-string */
    static $root = \dirname(__DIR__, 2) . \DIRECTORY_SEPARATOR;

    foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10) as $frame) {
        $file = $frame['file'] ?? null;

        if ($file === null) {
            continue;
        }

        if (str_starts_with($file, $root)) {
            continue;
        }

        if (preg_match('/^(.+)\((\d+)\) : eval\(\)\'d code$/', $file, $matches) === 1) {
            $file = $matches[1];
            $line = (int) $matches[2];
        } else {
            $line = $frame['line'] ?? 0;
        }

        \assert($file !== '', 'debug_backtrace() should almost never return an empty file name');
        \assert($line >= 1, 'debug_backtrace() should almost never return a non-positive line number');

        return new Location($file, $line);
    }

    return throw new ShouldNotHappen('debug_backtrace() yielded no caller frame outside the library within 10 frames');
}
