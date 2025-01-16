<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Recipe;

/**
 * @internal
 * @implements Recipe<mixed>
 */
enum DefaultArgument implements Recipe
{
    case Value;
}
