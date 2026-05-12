<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

/**
 * @api
 *
 * @template T of object
 */
interface ClassAttribute
{
    /**
     * @param ObjectConfigurator<T> $configurator
     */
    public function configure(ObjectConfigurator $configurator): void;
}
