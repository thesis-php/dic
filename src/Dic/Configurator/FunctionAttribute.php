<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

/**
 * @api
 */
interface FunctionAttribute
{
    /**
     * @param FunctionConfigurator<mixed> $configurator
     */
    public function configure(FunctionConfigurator $configurator): void;
}
