<?php

declare(strict_types=1);

namespace Thesis\Fixture;

/**
 * A link that takes the previous handler as an optional `?parent`.
 */
final class NextInChain extends Base
{
    public function __construct(
        public readonly ?parent $next = null,
    ) {}
}
