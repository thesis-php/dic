<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

/**
 * @api
 *
 * @template T
 */
interface FunctionAttribute
{
    /**
     * @param FunctionConfigurator<T> $configurator
     */
    public function configure(FunctionConfigurator $configurator): void;
}
