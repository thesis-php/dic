<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Composer\Autoload\ClassLoader;

/**
 * @api
 */
final class Location
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

        $trace = $backtrace[$index] ?? throw new \OutOfRangeException('Invalid trace index');

        $file = $trace['file'] ?? '';
        $line = $trace['line'] ?? 0;

        if (preg_match('/^(.+)\((\d+)\) : eval\(\)\'d code$/', $file, $matches) === 1) {
            $file = $matches[1];
            $line = (int) $matches[2];
        }

        \assert($file !== '', 'debug_backtrace() should almost never return an empty file name');
        \assert($line >= 1, 'debug_backtrace() should almost never return a non-positive line number');

        return new self($file, $line);
    }

    public string $shortFile {
        get {
            /** @var ?non-falsy-string */
            static $prefix = match ($vendorDir = array_key_first(ClassLoader::getRegisteredLoaders())) {
                null => null,
                default => \dirname($vendorDir) . \DIRECTORY_SEPARATOR,
            };

            if ($prefix !== null && str_starts_with($this->file, $prefix)) {
                return substr($this->file, \strlen($prefix));
            }

            return $this->file;
        }
    }

    /**
     * @param non-empty-string $file
     * @param positive-int $line
     */
    public function __construct(
        public readonly string $file,
        public readonly int $line,
    ) {}

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->shortFile . ':' . $this->line;
    }
}
