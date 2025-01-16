<?php

declare(strict_types=1);

namespace Thesis\DI;

/**
 * @api
 * @template TReqs of Module = never
 */
interface Module
{
    /**
     * @param ModuleConfig<TReqs> $config
     * @return ModuleConfig<TReqs>
     */
    public function configureModule(ModuleConfig $config): ModuleConfig;
}
