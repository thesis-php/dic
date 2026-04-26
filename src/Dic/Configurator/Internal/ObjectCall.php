<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\NonCopyable;

/**
 * @internal
 */
final readonly class ObjectCall
{
    use NonCopyable;

    /**
     * @param non-empty-string $method
     */
    public function __construct(
        public string $method,
        public Arguments $arguments,
        public bool $chain = false,
    ) {}
}
