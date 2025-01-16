<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Recipe;

/**
 * @internal
 * @template TValue
 * @implements Recipe<TValue>
 */
final readonly class Value implements Recipe
{
    /**
     * @param TValue $value
     */
    public function __construct(
        public mixed $value,
    ) {}
}
