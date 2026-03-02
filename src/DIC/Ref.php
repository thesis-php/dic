<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @api This interface must not be implemented in userland.
 *
 * @template-covariant T
 */
interface Ref
{
    /**
     * @return non-empty-string
     */
    public function __toString(): string;
}
