<?php

declare(strict_types=1);

namespace Thesis\Dic\Error;

use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Ref;

/**
 * @api
 */
final class CircularDependency extends ConfigurationError
{
    /**
     * @param Ref<mixed> $anchor node where the cycle closes
     * @param list<Dependency> $dependencies cycle edges from the anchor back to itself
     */
    public function __construct(Ref $anchor, array $dependencies)
    {
        $lastStep = array_key_last($dependencies);

        $lines = ["  {$anchor}"];

        foreach ($dependencies as $step => $dependency) {
            $indent = str_repeat(' ', 2 + 3 * $step);
            $edge = $dependency->path === '' ? '' : "{$dependency->path} → ";
            $marker = $step === $lastStep ? '  ← cycle' : '';
            $lines[] = "{$indent}└─ {$edge}{$dependency->ref}{$marker}";
        }

        parent::__construct(\sprintf(
            <<<'TEXT'
                Circular dependency detected:

                %s

                Break the cycle by removing one of the dependencies above.
                TEXT,
            implode("\n", $lines),
        ));
    }
}
