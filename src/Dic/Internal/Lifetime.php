<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

/**
 * @internal
 */
enum Lifetime
{
    case Singleton;
    case CanBeScoped;
    case Scoped;
}
