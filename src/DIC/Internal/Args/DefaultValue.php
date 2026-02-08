<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Args;

use Thesis\DIC\Service;

/**
 * @internal
 *
 * @implements Service<mixed>
 */
final class DefaultValue implements Service
{
    public mixed $value {
        get => $this->parameter->getDefaultValue();
    }

    public function __construct(
        private readonly \ReflectionParameter $parameter,
    ) {}
}
