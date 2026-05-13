<?php

declare(strict_types=1);

namespace Thesis\Dic\Autoconfigurator;

use Thesis\Dic\Configurator\ObjectConfigurator;

/**
 * @api
 */
interface ObjectAutoconfigurator
{
    /**
     * @param \ReflectionClass<object> $reflection
     */
    public function supportsObject(\ReflectionClass $reflection): bool;

    public function autoconfigureObject(ObjectConfigurator $configurator): void;
}
