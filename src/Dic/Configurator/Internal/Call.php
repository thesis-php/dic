<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

use Thesis\Dic\Internal\Arguments;

/**
 * @internal
 */
final readonly class Call
{
    /**
     * @param non-empty-string $method
     */
    public function __construct(
        public string $method,
        public Arguments $arguments,
        public bool $chain = false,
    ) {}
}
