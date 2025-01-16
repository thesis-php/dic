<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

/**
 * @internal
 */
final readonly class Location
{
    public static function current(): self
    {
        return self::fromTrace(1);
    }

    public static function caller(): self
    {
        return self::fromTrace(2);
    }

    /**
     * @param non-negative-int $offset
     */
    public static function fromTrace(int $offset): self
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $offset + 1)[$offset] ?? null;
        \assert($trace !== null);
        \assert(isset($trace['file']) && $trace['file'] !== '');
        \assert(isset($trace['line']) && $trace['line'] > 0);

        return new self($trace['file'], $trace['line']);
    }

    /**
     * @param non-empty-string $file
     * @param positive-int $line
     */
    public function __construct(
        public string $file,
        public int $line,
    ) {}
}
