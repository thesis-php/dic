<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @api
 */
final readonly class Location
{
    /**
     * @param non-negative-int $index
     */
    public static function caller(int $index = 0): self
    {
        return self::fromTrace($index + 2);
    }

    /**
     * @param non-negative-int $index
     */
    public static function fromTrace(int $index = 0): self
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $index + 1);

        $trace = $backtrace[$index] ?? throw new \LogicException('Invalid trace index');

        $file = $trace['file'] ?? '';
        $line = $trace['line'] ?? 0;

        if (preg_match('/^(.+)\((\d+)\) : eval\(\)\'d code$/', $file, $matches) === 1) {
            $file = $matches[1];
            $line = (int) $matches[2];
        }

        if ($file === '') {
            throw new \LogicException(\sprintf('No `file` in trace #%d: %s', $index, json_encode($trace))); // @codeCoverageIgnore
        }

        if ($line < 1) {
            throw new \LogicException(\sprintf('No `line` in trace #%d: %s', $index, json_encode($trace))); // @codeCoverageIgnore
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
