<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Composer\Autoload\ClassLoader;

/**
 * @api
 */
final class Location
{
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
