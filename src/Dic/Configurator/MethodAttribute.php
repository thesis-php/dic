<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

/**
 * @api
 */
interface MethodAttribute
{
    /**
     * @param MethodConfigurator<mixed> $configurator
     */
    public function configure(MethodConfigurator $configurator): void;
}
