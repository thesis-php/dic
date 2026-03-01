<?php

declare(strict_types=1);

namespace Thesis\DIC\Mapping;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class Transient {}
