<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Autowiring;

use Typhoon\Type;
use Typhoon\Type\Visitor;

/**
 * @internal
 *
 * @extends Visitor\Fallback<bool>
 */
abstract class BindingMatcher extends Visitor\Fallback
{
    protected function fallback(Type $type): bool
    {
        return false;
    }
}
