<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\ModuleId;

/**
 * @internal
 * @psalm-internal Thesis\DI
 */
final readonly class Exports
{
    public static function create(): self
    {
        return new self([]);
    }

    /**
     * @param array<non-empty-string, true> $exports
     */
    private function __construct(
        private array $exports,
    ) {}

    public function has(ModuleId $id): bool
    {
        return isset($this->exports[$id->toString()]);
    }

    public function with(ModuleId $id): self
    {
        $exports = $this->exports;
        $exports[$id->toString()] = true;

        return new self($exports);
    }
}
