<?php

declare(strict_types=1);

namespace Thesis\Fixture;

/**
 * A link whose nullable `?parent` has no default: it must be autowired or passed explicitly.
 */
final class RequiredNextInChain extends Base
{
    public function __construct(
        public readonly ?parent $next,
    ) {}
}
