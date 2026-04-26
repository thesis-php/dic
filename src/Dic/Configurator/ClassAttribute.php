<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

/**
 * @api
 */
interface ClassAttribute
{
    /**
     * @param ObjectConfigurator<object> $configurator
     */
    public function configure(ObjectConfigurator $configurator): void;
}
