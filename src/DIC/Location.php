<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @api
 */
final readonly class Location
{
    /**
     * @param non-positive-int $offset
     */
    public static function fromBacktrace(int $offset = 0): self
    {
        $offset = abs($offset);

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $offset + 1);

        $trace = $backtrace[$offset] ?? throw new \LogicException('Invalid backtrace offset');

        $file = $trace['file'] ?? '';
        $line = $trace['line'] ?? 0;

        if ($file === '') {
            throw new \LogicException('No file in backtrace');
        }

        if ($line < 1) {
            throw new \LogicException('No line in backtrace');
        }

        return new self($file, $line);
    }

    /**
     * @param non-empty-string $file
     * @param positive-int $line
     */
    public function __construct(
        public string $file,
        public int $line,
    ) {}

    public function __toString(): string
    {
        return $this->file . ':' . $this->line;
    }
}
