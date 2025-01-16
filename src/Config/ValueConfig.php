<?php

declare(strict_types=1);

namespace Thesis\DI\Config;

use Thesis\DI\Module;
use Thesis\DI\ModuleConfig;
use Thesis\DI\Tag;

/**
 * @api This class must not be implemented in userland.
 * @template TReqs of Module
 * @template TValue
 * @extends ModuleConfig<TReqs>
 */
interface ValueConfig extends ModuleConfig
{
    /**
     * @no-named-arguments
     * @param Tag<TValue> ...$tags
     */
    public function tags(Tag ...$tags): static;
}
