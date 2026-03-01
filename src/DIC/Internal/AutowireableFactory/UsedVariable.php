<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

/**
 * @internal
 */
final readonly class UsedVariable
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public string $name,
        public bool $byReference = false,
    ) {}
}
