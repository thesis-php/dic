<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @template-covariant T
 */
interface Ref
{
    /**
     * @return non-empty-string
     */
    public function __toString(): string;
}
