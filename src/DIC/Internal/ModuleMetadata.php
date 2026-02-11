<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\InheritAutowiring;

/**
 * @internal
 *
 * @template T
 */
final class ModuleMetadata
{
    public readonly \ReflectionFunction $reflection;

    public bool $inheritAutowiring {
        get => $this->reflection->getAttributes(InheritAutowiring::class) !== [];
    }

    /**
     * @param callable(): T $module
     */
    public function __construct(callable $module)
    {
        $this->reflection = new \ReflectionFunction($module(...));
    }
}
