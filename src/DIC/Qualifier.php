<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class Qualifier
{
    /**
     * @param non-empty-string|\UnitEnum $qualifier
     */
    public function __construct(
        public string|\UnitEnum $qualifier,
    ) {}
}
