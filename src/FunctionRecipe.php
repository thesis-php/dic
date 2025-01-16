<?php

declare(strict_types=1);

namespace Thesis\DI;

/**
 * @api
 * @template-covariant TValue
 * @extends Recipe<TValue>
 */
interface FunctionRecipe extends Recipe
{
    public function args(mixed ...$args): static;

    public function doNotAutowire(): static;
}
