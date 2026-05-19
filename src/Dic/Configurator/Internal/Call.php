<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

/**
 * @internal
 */
final readonly class Call
{
    public function __construct(
        public string $method,
        public Arguments $arguments,
        public bool $chain = false,
    ) {}
}
