<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Internal\ArgumentAttribute;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
final readonly class Autowire implements ArgumentAttribute
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
