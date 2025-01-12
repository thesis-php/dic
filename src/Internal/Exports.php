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
     * @param array<non-empty-string, true> $moduleIdMap
     */
    private function __construct(
        private array $moduleIdMap,
    ) {}

    public function has(ModuleId $id): bool
    {
        return isset($this->moduleIdMap[$id->toString()]);
    }

    public function with(ModuleId $id): self
    {
        $moduleIdMap = $this->moduleIdMap;
        $moduleIdMap[$id->toString()] = true;

        return new self($moduleIdMap);
    }
}
