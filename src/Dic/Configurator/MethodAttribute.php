<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

/**
 * @api
 *
 * @template T
 */
interface MethodAttribute
{
    /**
     * @param MethodConfigurator<T> $configurator
     */
    public function configure(MethodConfigurator $configurator): void;
}
