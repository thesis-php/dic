<?php

declare(strict_types=1);

namespace Thesis\DI;

/**
 * @api
 * @template-covariant TReqs of Module
 */
interface Module
{
    /**
     * @template TConfigurator of ModuleConfigurator<TReqs, static>
     * @param TConfigurator $module
     * @return TConfigurator
     */
    public function configureModule(ModuleConfigurator $module): ModuleConfigurator;
}
