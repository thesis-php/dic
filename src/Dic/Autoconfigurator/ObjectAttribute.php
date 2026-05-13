<?php

declare(strict_types=1);

namespace Thesis\Dic\Autoconfigurator;

use Thesis\Dic\Configurator\ObjectConfigurator;

/**
 * @api
 */
interface ObjectAttribute
{
    public function configure(ObjectConfigurator $configurator): void;
}
