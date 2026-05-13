<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Autoconfigurator\CallableAttribute;
use Thesis\Dic\Autoconfigurator\CallableAutoconfigurator;
use Thesis\Dic\Autoconfigurator\ObjectAttribute;
use Thesis\Dic\Autoconfigurator\ObjectAutoconfigurator;
use Thesis\Dic\Configurator\CallableConfigurator;
use Thesis\Dic\Configurator\ObjectConfigurator;

/**
 * @internal
 */
final readonly class AttributeAutoconfigurator implements CallableAutoconfigurator, ObjectAutoconfigurator
{
    public function supportsCallable(\ReflectionMethod|\ReflectionFunction $reflection): bool
    {
        return $reflection->getAttributes(CallableAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) !== [];
    }

    public function autoconfigureCallable(CallableConfigurator $configurator): void
    {
        foreach ($configurator->reflection->getAttributes(CallableAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->configure($configurator);
        }
    }

    public function supportsObject(\ReflectionClass $reflection): bool
    {
        return $reflection->getAttributes(ObjectAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) !== [];
    }

    public function autoconfigureObject(ObjectConfigurator $configurator): void
    {
        foreach ($configurator->reflection->getAttributes(ObjectAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->configure($configurator);
        }
    }
}
