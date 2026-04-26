<?php

declare(strict_types=1);

namespace Thesis\Dic;

/**
 * @api This interface must not be implemented in userland.
 *
 * @template-covariant T
 */
interface Ref
{
    public Lifetime $lifetime { get; }

    /**
     * @return non-empty-string
     */
    public function __toString(): string;
}
