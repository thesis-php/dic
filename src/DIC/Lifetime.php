<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @api
 */
enum Lifetime
{
    case Singleton;
    case Scoped;
    case Transient;
}
