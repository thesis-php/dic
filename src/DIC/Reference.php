<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @template-covariant T
 */
interface Reference
{
    public Location $declaredAt { get; }

    /**
     * @return non-empty-string
     */
    public function __toString(): string;
}
