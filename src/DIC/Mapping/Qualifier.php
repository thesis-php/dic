<?php

declare(strict_types=1);

namespace Thesis\DIC\Mapping;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class Qualifier
{
    public function __construct(
        public string|\Stringable|\UnitEnum $value,
    ) {}
}
