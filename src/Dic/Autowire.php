<?php

declare(strict_types=1);

namespace Thesis\Dic;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
final readonly class Autowire
{
    public function __construct(
        public string|\Stringable|\UnitEnum $qualifier = '',
    ) {}
}

/**
 * @api
 */
const autowire = new Autowire();

/**
 * @api
 */
function autowire(string|\Stringable|\UnitEnum $qualifier = ''): Autowire
{
    return new Autowire($qualifier);
}
