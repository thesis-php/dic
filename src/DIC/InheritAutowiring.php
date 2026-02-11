<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
final readonly class InheritAutowiring {}
