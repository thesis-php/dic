<?php

declare(strict_types=1);

namespace Thesis\Dic;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
final readonly class DoNotAutowire {}

/**
 * @api
 */
const doNotAutowire = new DoNotAutowire();
