<?php

declare(strict_types=1);

namespace Thesis\Dic;

/**
 * @api
 */
enum Lifetime
{
    /**
     * A single instance is created and reused for the entire lifetime of the container.
     */
    case Singleton;

    /**
     * A new instance is created for each {@see Scoped::run()} call and shared within that scope.
     */
    case Scoped;
}
