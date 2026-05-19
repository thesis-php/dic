<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Internal\ArgumentAttribute;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
final readonly class DoNotAutowire implements ArgumentAttribute {}

/**
 * @api
 */
const doNotAutowire = new DoNotAutowire();
