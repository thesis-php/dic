<?php

declare(strict_types=1);

namespace Thesis\Dic\Autoconfigurator;

use Thesis\Dic\Configurator\CallableConfigurator;

/**
 * @api
 */
interface CallableAttribute
{
    public function configure(CallableConfigurator $configurator): void;
}
