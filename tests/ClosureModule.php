<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\Dic\Module;

/**
 * @template-covariant T
 * @implements Module<T>
 */
final readonly class ClosureModule implements Module
{
    /**
     * @param \Closure(Dic): T $module
     */
    public function __construct(
        private \Closure $module,
    ) {}

    public function configure(Dic $dic): mixed
    {
        return ($this->module)($dic);
    }
}
