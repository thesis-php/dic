<?php

declare(strict_types=1);

namespace Thesis\Dic\Autoconfigurator;

use Thesis\Dic\Configurator\MethodConfigurator;

/**
 * @api
 */
interface MethodAttribute
{
    public function configure(MethodConfigurator $configurator): void;
}
