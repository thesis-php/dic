<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @template-covariant T
 */
interface Service
{
    /**
     * @var T
     */
    public mixed $value { get; } // @phpstan-ignore generics.variance
}
