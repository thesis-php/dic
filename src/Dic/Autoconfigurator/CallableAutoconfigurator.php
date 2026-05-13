<?php

declare(strict_types=1);

namespace Thesis\Dic\Autoconfigurator;

use Thesis\Dic\Configurator\CallableConfigurator;

/**
 * @api
 */
interface CallableAutoconfigurator
{
    public function supportsCallable(\ReflectionFunction|\ReflectionMethod $reflection): bool;

    public function autoconfigureCallable(CallableConfigurator $configurator): void;
}
